<?php
require_once 'config.php';

header('Content-Type: application/json');
echo json_encode([
    'base_url' => BASE_URL,
    'local_url' => LOCAL_URL ?? BASE_URL
]);
?>
