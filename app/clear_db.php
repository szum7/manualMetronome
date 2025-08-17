<?php
require 'config.php';

$pdo = pdo_connect();
$ret = [];

$stmt = $pdo->prepare("DELETE FROM users");
$stmt->execute();

echo json_encode(['success' => true]);