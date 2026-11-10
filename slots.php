<?php
// =============================================================
// slots.php : tiny "API" used by JavaScript
// The browser calls   slots.php?date=2026-11-20
// and gets back JSON like:
//   [{"id":1,"name":"Rahim Uddin","free":3}, ...]
// This is how the page asks the server for fresh free places
// every time the client picks a new date (no page reload).
// =============================================================
require 'db.php';

header('Content-Type: application/json');

$date      = $_GET['date'] ?? '';
$excludeId = (int) ($_GET['exclude'] ?? 0);   // used only by edit.php

if (!isValidDate($date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date']);
    exit;
}

echo json_encode(getMechanicsWithSlots($conn, $date, $excludeId));
