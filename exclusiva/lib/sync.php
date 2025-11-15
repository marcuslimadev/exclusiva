<?php
require_once __DIR__ . '/db.php';

function last_import_at()
{
    $stmt = pdo()->query('SELECT last_import_at FROM import_status WHERE id=1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['last_import_at'] : null;
}

function is_running()
{
    $stmt = pdo()->query('SELECT is_running FROM import_status WHERE id=1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row && intval($row['is_running']) === 1;
}

function should_sync()
{
    $last = last_import_at();
    if (!$last) return true;
    $diff = time() - strtotime($last);
    return $diff >= 4 * 3600;
}

function start_background_sync()
{
    if (is_running()) return false;
    $php = PHP_BINARY;
    $worker = escapeshellarg(PROJECT_ROOT . DIRECTORY_SEPARATOR . 'sync_worker.php');
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $cmd = 'start /B "" ' . escapeshellarg($php) . ' ' . $worker;
        @pclose(@popen($cmd, 'r'));
    } else {
        $cmd = 'nohup ' . escapeshellarg($php) . ' ' . $worker . ' > /dev/null 2>&1 &';
        @exec($cmd);
    }
    return true;
}
