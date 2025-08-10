<?php
// get_sync_result.php
require 'config.php';

$BLINK_SCHEDULE_OFFSET = 10;

$data = json_decode(file_get_contents("php://input"), true);

$synch_id = $data['synch_id'] ?? null;
$client_id = $data['client_id'] ?? null;
//$synch_id = 111;
//$client_id = "c_si68ku17";

if (!isset($client_id) || !isset($synch_id)) {
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$pdo = pdo_connect();
$ret = [];

$stmt = $pdo->prepare(
    "SELECT *
    FROM sync_offsets 
    WHERE synch_id = ?"
);
$stmt->execute([$synch_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) == 0) { // First arriving client
    
    // 1. Get all timestamps for this synchId
    $stmt = $pdo->prepare(
        "SELECT client_id, ts_usec 
        FROM sync_timestamps 
        WHERE synch_id = ?
        ORDER BY client_id ASC");
    $stmt->execute([$synch_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Pick the first client as reference
    $reference = $rows[0]['ts_usec'];
    $referenceClientId = $rows[0]['client_id'];

    // 3. Schedule blink start ~x seconds into the future
    $blink_start_usec = microtime(true) + $BLINK_SCHEDULE_OFFSET;

    // 4. Calculate offsets
    foreach ($rows as $row) {

        $offset = $row['ts_usec'] - $reference;

        $stmt2 = $pdo->prepare(
            "INSERT INTO sync_offsets (synch_id, client_id, offset_usec, ref_client_id, blink_start_usec) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt2->execute([
            $synch_id, 
            $row['client_id'], 
            $offset, 
            $referenceClientId, 
            $blink_start_usec]);

        if ($client_id == $row["client_id"]) {
            $ret = [
                "offset_usec" => $offset,
                "ref_client_id" => $referenceClientId
            ];
        }
    }

    $ret["blink_start_usec"] = $blink_start_usec;
    $ret["blink_start_usec_formatted"] = formatMicrotime($blink_start_usec);
    $ret["success"] = true;

} else { // Other not-first arriving clients

    $curr = null;
    foreach ($rows as $row) {
        if ($client_id == $row["client_id"]) {
            $curr = $row;
        }
    }
    $ret = [
        "success" => true,
        "offset_usec" => $curr["offset_usec"],
        "ref_client_id" => $curr["ref_client_id"],
        "blink_start_usec" => $curr["blink_start_usec"],
        "blink_start_usec_formatted" => formatMicrotime($curr["blink_start_usec"])
    ];
}

function formatMicrotime($microtimeFloat) {
    // Separate seconds and fractional part
    $seconds = floor($microtimeFloat);
    $fraction = $microtimeFloat - $seconds;

    // Format date/time part
    $timeStr = date('H:i:s', $seconds);

    // Get 4 digits from fractional seconds (microseconds)
    $fractionStr = substr(sprintf('%.6f', $fraction), 2, 4);

    return "$timeStr.$fractionStr";
}

echo json_encode($ret);