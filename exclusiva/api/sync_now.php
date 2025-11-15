<?php
require_once __DIR__ . '/../lib/sync.php';
header('Content-Type: application/json; charset=utf-8');

$started = false;
if (!is_running()) {
    $started = start_background_sync();
}

echo json_encode([
    'status' => true,
    'started' => $started,
    'is_running' => is_running(),
    'last_import_at' => last_import_at()
], JSON_UNESCAPED_UNICODE);
