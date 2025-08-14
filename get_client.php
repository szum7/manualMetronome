<?php
require 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$sync_id = $data['sync_id'] ?? null;
$client_id = $data['client_id'] ?? null;
//$sync_id = 78567;
//$client_id = "c_si68ku17";

if (!isset($client_id) || !isset($sync_id)) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$pdo = pdo_connect();
$ret = [];

$stmt = $pdo->prepare(
    "SELECT *
    FROM clients 
    WHERE sync_id = ?
    AND client_id = ?"
);
$stmt->execute([$sync_id, $client_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) > 0) {

    $ret = [
        "success" => true,
        "found" => true,
        "sync_id" => $sync_id,
        "client_id" => $client_id,
        "is_ref" => $row[0]["is_ref"],
        "offset_usec" => $row[0]["offset_usec"]
    ];

} else {    

    $ret = [
        "success" => true,
        "found" => false
    ];
}

echo json_encode($ret);