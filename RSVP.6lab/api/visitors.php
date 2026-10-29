<?php
/**
 * Great books — server-side live visitor counter (powers the homepage line
 * "Now on site: N", i.e. how many clients are working on the server right now).
 *
 * Each open browser tab (js/live.js) pings this script with its unique session
 * id every ~15 seconds. One record per visitor tab => the reply reflects the
 * real-time load of the server.
 *
 * Storage backends:
 *   1) APCu user cache (preferred; shared across PHP-FPM workers);
 *   2) flat files under api/data/ (fallback, works with plain "php -S").
 *
 * Response (JSON): {"now":N,"today":N,"total":N}
 *   now   — ids with a heartbeat younger than $staleSec (real-time clients);
 *   today — unique visitors since midnight (server date);
 *   total — unique visitors since the counter was installed.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

$staleSec = 45;    // a client counts as "now on site" if it pinged within 45 s
$keepDays = 400;   // prune daily files older than this

$id    = isset($_GET['id']) ? preg_replace('/[^A-Za-z0-9_-]/', '', (string) $_GET['id']) : '';
$bye   = !empty($_GET['bye']);
$today = date('Y-m-d');

if ($id === '' || strlen($id) > 64) {
    echo json_encode(['now' => 0, 'today' => 0, 'total' => 0]);
    exit;
}

/* ------------------------------------------------------------------ */
/* Backend 1: APCu — fast, no disk writes                              */
/* ------------------------------------------------------------------ */
$apcuReady = function_exists('apc_add') && function_exists('apc_store')
          && function_exists('apc_fetch') && function_exists('apc_delete')
          && filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN)
          && (PHP_SAPI !== 'cli' || filter_var(ini_get('apc.enable_cli'), FILTER_VALIDATE_BOOLEAN));

if ($apcuReady) {
    if ($bye) {
        /* tab closed: remove this client from the "now" pool immediately */
        apc_delete('gb_now_' . $id);
        echo json_encode(['ok' => 1]);
        exit;
    }
    /* heartbeat: value = timestamp, TTL outlives the stale window a bit */
    $fresh = apc_add('gb_now_' . $id, time(), $staleSec + 30);
    if (!$fresh) {
        apc_store('gb_now_' . $id, time(), $staleSec + 30);   // refresh heartbeat
    }
    /* "today": one marker per visitor id, expires just after midnight+slack */
    apc_store('gb_today_' . $id, 1, 90000);

    /* "total": count only when this id is seen for the very first time */
    if ($fresh) {
        $seen = apc_fetch('gb_total_ids', $sHit);
        $seen = is_array($sHit) ? $sHit : [];
        $seen[$id] = 1;
        if (count($seen) > 50000) { $seen = array_slice($seen, -50000, null, true); }
        apc_store('gb_total_ids', $seen, 0);
        apc_store('gb_total_cnt', count($seen), 0);
    }

    /* now: iterate live keys and keep only fresh heartbeats */
    $nowCount = 0;
    $it = new APCUIterator('#^gb_now_#', APCU_ITER_KEY_ONLY, 512);
    foreach ($it as $key) {
        $ts = apc_fetch((string) $key, $hit);
        if ($hit && is_int($ts) && (time() - $ts) < $staleSec) $nowCount++;
    }
    $todayCnt = iterator_count(new APCUIterator('#^gb_today_#', APCU_ITER_KEY_ONLY, 512));
    $totalCnt = (int) apc_fetch('gb_total_cnt', $tHit);
    if (!$tHit) $totalCnt = $todayCnt;

    echo json_encode(['now' => $nowCount, 'today' => $todayCnt, 'total' => $totalCnt]);
    exit;
}

/* ------------------------------------------------------------------ */
/* Backend 2: flat files (no APCu: e.g. dev server or minimal PHP)     */
/* ------------------------------------------------------------------ */
$dir = __DIR__ . '/data';
if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
    echo json_encode(['now' => 1, 'today' => 1, 'total' => 1]);
    exit;
}

$fp = @fopen($dir . '/visitors.lock', 'c');
if ($fp && flock($fp, LOCK_EX | LOCK_NB)) {

    /* per-day file: {visitorId: lastHeartbeatTimestamp} */
    $file = $dir . '/day-' . $today . '.json';
    $day  = [];
    if (is_readable($file)) {
        $decoded = json_decode((string) @file_get_contents($file), true);
        if (is_array($decoded)) $day = $decoded;
    }

    if ($bye) {
        /* tab closed: drop this client from the "now" pool right away,
           but keep it in today/total registries (it really visited).     */
        unset($day[$id]);
        @file_put_contents($file, json_encode($day), LOCK_EX);
        $nowCount = 0;
        foreach ($day as $ts) {
            if (time() - (int) $ts < $staleSec) $nowCount++;
        }
        flock($fp, LOCK_UN);
        fclose($fp);
        echo json_encode(['ok' => 1, 'now' => $nowCount]);
        exit;
    }

    $day[$id] = time();
    @file_put_contents($file, json_encode($day), LOCK_EX);

    /* unique-ever registry: one line per id (gives "total") */
    $reg    = $dir . '/seen.log';
    $known  = [];
    if (is_readable($reg)) {
        $lines  = array_filter(array_map('trim', (array) @file($reg, FILE_IGNORE_NEW_LINES)));
        $known  = array_flip($lines);
    }
    if (!isset($known[$id])) {
        @file_put_contents($reg, $id . "\n", FILE_APPEND | LOCK_EX);
        $known[$id] = 1;
    }

    /* prune old daily files */
    foreach ((array) glob($dir . '/day-*.json') as $f) {
        if (preg_match('/day-(\d{4}-\d{2}-\d{2})\.json$/', $f, $m)
            && (strtotime($today) - strtotime($m[1])) > $keepDays * 86400) {
            @unlink($f);
        }
    }

    $nowCount = 0;
    foreach ($day as $ts) {
        if (time() - (int) $ts < $staleSec) $nowCount++;
    }

    flock($fp, LOCK_UN);
    fclose($fp);

    echo json_encode([
        'now'   => $nowCount,
        'today' => count($day),
        'total' => count($known),
    ]);
    exit;
}

/* could not take the lock (another worker is writing): best effort */
echo json_encode(['now' => 1, 'today' => 1, 'total' => 1]);
