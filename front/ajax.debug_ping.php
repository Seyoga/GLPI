<?php
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'message' => 'ping', 'ts' => time()], JSON_UNESCAPED_UNICODE);
