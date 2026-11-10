<?php

// Database login details

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'car_workshop';

// Rules
const MAX_CARS_PER_DAY = 4;          
date_default_timezone_set('Asia/Dhaka');

// Connect

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $ex) {
    die('Could not connect to the database. Please check the settings in db.php.');
}

// Helper functionse

function e($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function isValidDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}


function getMechanicsWithSlots($conn, $date, $excludeId = 0)
{
    $sql = "SELECT m.id, m.name, COUNT(a.id) AS booked
            FROM mechanics m
            LEFT JOIN appointments a
                   ON a.mechanic_id = m.id
                  AND a.appointment_date = ?
                  AND a.id <> ?
            GROUP BY m.id, m.name
            ORDER BY m.id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $date, $excludeId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $mechanics = [];
    foreach ($rows as $row) {
        $mechanics[] = [
            'id'   => (int) $row['id'],
            'name' => $row['name'],
            'free' => MAX_CARS_PER_DAY - (int) $row['booked'],
        ];
    }
    return $mechanics;
}

// How many cars does this mechanic already have on $date?
function countMechanicBookings($conn, $mechanicId, $date, $excludeId = 0)
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM appointments
         WHERE mechanic_id = ? AND appointment_date = ? AND id <> ?"
    );
    $stmt->bind_param('isi', $mechanicId, $date, $excludeId);
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_row()[0];
}


// A client is recognised by phone number OR car license number.
function clientHasBooking($conn, $phone, $carLicense, $date, $excludeId = 0)
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM appointments
         WHERE appointment_date = ? AND (phone = ? OR car_license = ?) AND id <> ?"
    );
    $stmt->bind_param('sssi', $date, $phone, $carLicense, $excludeId);
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_row()[0] > 0;
}

function getMechanicName($conn, $mechanicId)
{
    $stmt = $conn->prepare("SELECT name FROM mechanics WHERE id = ?");
    $stmt->bind_param('i', $mechanicId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? $row['name'] : null;
}
