<?php
// get_sync_result.php
require 'config.php';

$clientId = $_GET['clientId'] ?? '';
$synchId  = $_GET['synchId'] ?? '';

if (!$clientId || !$synchId) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

// LONG POLLING: wait until blinkStart is available or 20 seconds passes
$startTime = time();
$timeout   = 20; // seconds

while (true) {
    $stmt = $pdo->prepare("SELECT offset, blinkStart FROM sync_results WHERE clientId = ? AND synchId = ?");
    $stmt->execute([$clientId, $synchId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['blinkStart'])) {
        echo json_encode([
            "offset"     => (float)$row['offset'],
            "blinkStart" => (float)$row['blinkStart']
        ]);
        exit;
    }

    // Break if timeout reached
    if ((time() - $startTime) >= $timeout) {
        echo json_encode(["offset" => null, "blinkStart" => null]);
        exit;
    }

    usleep(200000); // sleep 0.2s to avoid high CPU usage
}