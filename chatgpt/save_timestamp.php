<?php
// save_timestamp.php
require 'config.php';

// CONFIGURABLE: how many clients are needed before offsets are computed
define('MIN_CLIENTS', 2);

$clientId = $_POST['clientId'] ?? '';
$timestamp = $_POST['timestamp'] ?? '';
$synchId = $_POST['synchId'] ?? '';

if (!$clientId || !$timestamp || !$synchId) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

// Store timestamp
$stmt = $pdo->prepare("INSERT INTO sync_timestamps (clientId, timestamp, synchId) VALUES (?, ?, ?)");
$stmt->execute([$clientId, $timestamp, $synchId]);

// Check how many distinct clients have submitted for this synchId
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT clientId) AS cnt FROM sync_timestamps WHERE synchId = ?");
$stmt->execute([$synchId]);
$count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

// If enough clients, compute offsets
if ($count >= MIN_CLIENTS) {
    // 1. Get all timestamps for this synchId
    $stmt = $pdo->prepare("SELECT clientId, timestamp FROM sync_timestamps WHERE synchId = ?");
    $stmt->execute([$synchId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Pick the first client as reference
    $reference = $rows[0]['timestamp'];
    $referenceClientId = $rows[0]['clientId'];

    // 3. Calculate offsets
    foreach ($rows as $row) {
        $offset = $row['timestamp'] - $reference;
        $stmt2 = $pdo->prepare("REPLACE INTO sync_results (synchId, clientId, offset) VALUES (?, ?, ?)");
        $stmt2->execute([$synchId, $row['clientId'], $offset]);
    }

    // 4. Schedule blink start ~5 seconds into the future
    $blinkStart = microtime(true) + 5;
    $stmt = $pdo->prepare("UPDATE sync_results SET blinkStart = ? WHERE synchId = ?");
    $stmt->execute([$blinkStart, $synchId]);

    echo json_encode([
        "status" => "timestamp_saved_and_offsets_computed",
        "blinkStart" => $blinkStart,
        "referenceClientId" => $referenceClientId
    ]);
} else {
    echo json_encode(["status" => "timestamp_saved", "clients_submitted" => $count]);
}
