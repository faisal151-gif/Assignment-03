<?php

// admin.php : ADMIN PANEL
require 'db.php';

$sql = "SELECT a.id, a.client_name, a.phone, a.car_license, a.appointment_date,
               m.name AS mechanic_name
        FROM appointments a
        JOIN mechanics m ON m.id = a.mechanic_id
        ORDER BY a.appointment_date, a.id";
$appointments = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// edit.php sends us back here with ?updated=ID after a successful change
$updatedId = (int) ($_GET['updated'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | AutoCare Workshop</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="index.php">AutoCare <span>Workshop</span></a>
        <nav>
            <a href="index.php">Book Appointment</a>
            <a href="admin.php" class="active">Admin Panel</a>
            <a href="help.html">Help</a>
        </nav>
    </div>
</header>

<main class="container">
    <h1>Admin Panel &ndash; Appointment List</h1>
    <p class="lead">Total appointments: <strong><?= count($appointments) ?></strong>.
        Click <em>Edit</em> to change the date or the mechanic of an appointment.</p>

    <?php if ($updatedId > 0): ?>
        <div class="alert alert-success">Appointment #<?= $updatedId ?> was updated.</div>
    <?php endif; ?>

    <div class="card table-wrap">
        <?php if (count($appointments) === 0): ?>
            <p>No appointments yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client name</th>
                        <th>Phone</th>
                        <th>Car registration no.</th>
                        <th>Appointment date</th>
                        <th>Mechanic</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $a): ?>
                        <tr<?= $a['id'] == $updatedId ? ' class="highlight"' : '' ?>>
                            <td><?= $a['id'] ?></td>
                            <td><?= e($a['client_name']) ?></td>
                            <td><?= e($a['phone']) ?></td>
                            <td><?= e($a['car_license']) ?></td>
                            <td><?= e($a['appointment_date']) ?></td>
                            <td><?= e($a['mechanic_name']) ?></td>
                            <td><a class="btn btn-small" href="edit.php?id=<?= $a['id'] ?>">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<footer class="site-footer">
    <div class="container">CSE 391 &middot; Assignment 3 &middot; Car Workshop Appointment System</div>
</footer>
</body>
</html>
