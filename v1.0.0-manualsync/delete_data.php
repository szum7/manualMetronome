<?php
require 'config.php';

//$data = json_decode(file_get_contents("php://input"), true);

$pdo = pdo_connect();
$ret = [];

$stmt = $pdo->prepare("DELETE FROM metronome");
$stmt->execute();
$stmt = $pdo->prepare("DELETE FROM sync_offsets");
$stmt->execute();
$stmt = $pdo->prepare("DELETE FROM sync_timestamps");
$stmt->execute();

echo json_encode(['success' => true]);