<?php
// ============================================================
//  Currency rates proxy for convert.php (T10).
//  Public, read-only, GET-only endpoint — no login/CSRF needed
//  (it never touches the caller's own data, just a shared cache).
//
//  Fetches THB-based rates from https://api.frankfurter.app (free,
//  no key) server-side and caches the result in the existing
//  `settings` key/value table for one day, so the frontend never
//  hits frankfurter.app directly (avoids CORS + external rate limits)
//  and repeated page loads don't hammer the external API.
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_out(['ok' => false, 'error' => 'method'], 405);

const RATES_SETTINGS_KEY = 'convert_currency_rates_thb';
const RATES_TTL_SECONDS  = 86400; // 1 day

/** Read the cached rates blob (decoded), or null if none/corrupt. */
function rates_cache_get(): ?array {
    try {
        $st = db()->prepare('SELECT v FROM settings WHERE k = ? LIMIT 1');
        $st->execute([RATES_SETTINGS_KEY]);
        $v = $st->fetchColumn();
    } catch (Throwable $e) {
        return null;
    }
    if ($v === false || $v === null || $v === '') return null;
    $data = json_decode($v, true);
    return is_array($data) ? $data : null;
}

/** Upsert the cache row (key/value `settings` table — no new table needed). */
function rates_cache_set(array $data): void {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    try {
        db()->prepare(
            'INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)'
        )->execute([RATES_SETTINGS_KEY, $json]);
    } catch (Throwable $e) {
        // best-effort — if the write fails we still return the freshly-fetched
        // rates to the caller, just without persisting the cache this time.
    }
}

/** Server-side fetch of live THB rates. Returns null on any failure. */
function rates_fetch_live(): ?array {
    $url = 'https://api.frankfurter.app/latest?from=THB';
    $raw = false;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true, // frankfurter.app currently 301s to frankfurter.dev
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $raw = curl_exec($ch);
        if (curl_errno($ch)) $raw = false;
        curl_close($ch);
    }
    if ($raw === false) {
        // fallback for environments without curl
        $ctx = @stream_context_create(['http' => ['timeout' => 6, 'ignore_errors' => true, 'follow_location' => 1]]);
        $raw = @file_get_contents($url, false, $ctx);
    }
    if ($raw === false || $raw === null) return null;

    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || empty($decoded['rates']) || !is_array($decoded['rates'])) return null;

    return [
        'base'       => $decoded['base'] ?? 'THB',
        'date'       => $decoded['date'] ?? date('Y-m-d'),
        'rates'      => $decoded['rates'],
        'fetched_at' => time(),
    ];
}

$cached = rates_cache_get();
$now    = time();

// 1) fresh cache (< 1 day old) — serve straight from `settings`, no external call at all.
if ($cached && !empty($cached['fetched_at']) && ($now - (int)$cached['fetched_at']) < RATES_TTL_SECONDS) {
    json_out([
        'ok'         => true,
        'base'       => $cached['base'],
        'date'       => $cached['date'],
        'rates'      => $cached['rates'],
        'cached'     => true,
        'stale'      => false,
        'fetched_at' => date('Y-m-d H:i:s', (int)$cached['fetched_at']),
    ]);
}

// 2) no fresh cache — try a live fetch.
$fresh = rates_fetch_live();
if ($fresh) {
    rates_cache_set($fresh);
    json_out([
        'ok'         => true,
        'base'       => $fresh['base'],
        'date'       => $fresh['date'],
        'rates'      => $fresh['rates'],
        'cached'     => false,
        'stale'      => false,
        'fetched_at' => date('Y-m-d H:i:s', $fresh['fetched_at']),
    ]);
}

// 3) live fetch failed — prefer serving a stale cache over failing outright.
if ($cached) {
    json_out([
        'ok'         => true,
        'base'       => $cached['base'],
        'date'       => $cached['date'],
        'rates'      => $cached['rates'],
        'cached'     => true,
        'stale'      => true,
        'fetched_at' => date('Y-m-d H:i:s', (int)$cached['fetched_at']),
    ]);
}

// 4) no cache at all and the live fetch failed — graceful error for the frontend.
json_out(['ok' => false, 'error' => 'ไม่สามารถดึงอัตราแลกเปลี่ยนได้ในขณะนี้ กรุณาลองใหม่ภายหลัง'], 502);
