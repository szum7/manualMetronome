<?php
require 'config.php';

$pdo = pdo_connect();
$ret = [];

$stmt = $pdo->prepare("DELETE FROM clients");
$stmt->execute();

echo json_encode(['success' => true]);