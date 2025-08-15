<?php
require 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$sync_id = $data['sync_id'] ?? null;
$client_id = $data['client_id'] ?? null;
$offset_usec = $data['offset_usec'] ?? null;
// $sync_id = 78567;
// $client_id = "c_si68ku17";
// $offset_usec = 34535;

if (!isset($client_id) || !isset($sync_id) || !isset($offset_usec)) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$pdo = pdo_connect();
$ret = [];

$stmt = $pdo->prepare(
    "DELETE FROM clients WHERE sync_id = ? AND client_id = ?"
);
$stmt->execute([$sync_id, $client_id]);

$stmt = $pdo->prepare(
    "INSERT INTO clients (sync_id, client_id, is_ref, offset_usec) VALUES (?, ?, ?, ?)"
);
$stmt->execute([$sync_id, $client_id, false, $offset_usec]);

$ret = [
    "success" => true
];

echo json_encode($ret);