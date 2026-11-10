<?php
// =============================================================
// edit.php : ADMIN - change the date and/or mechanic of ONE appointment
// Opened from admin.php as  edit.php?id=5
// The same rules as booking apply to the NEW date/mechanic:
//   - the client must not already have another appointment that day
//   - the new mechanic must have a free place that day
// =============================================================
require 'db.php';

$today  = date('Y-m-d');
$id     = (int) ($_GET['id'] ?? 0);
$errors = [];
$message = '';

// ---------- 1. Load the appointment we are editing ----------
$stmt = $conn->prepare(
    "SELECT a.*, m.name AS mechanic_name
     FROM appointments a JOIN mechanics m ON m.id = a.mechanic_id
     WHERE a.id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$appt = $stmt->get_result()->fetch_assoc();

if (!$appt) {
    die('Appointment not found. <a href="admin.php">Back to the list</a>');
}

// Values shown in the form (start with the current ones)
$newDate     = $appt['appointment_date'];
$newMechanic = (string) $appt['mechanic_id'];

// ---------- 2. The admin pressed "Save changes" ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newDate     = trim($_POST['appointment_date'] ?? '');
    $newMechanic = trim($_POST['mechanic_id'] ?? '');
    $mechanicId  = (int) $newMechanic;

    // Validate the two fields
    if (!isValidDate($newDate)) {
        $errors['appointment_date'] = 'Choose a valid date.';
    } elseif ($newDate < $today) {
        $errors['appointment_date'] = 'The date cannot be in the past.';
    }
    if (getMechanicName($conn, $mechanicId) === null) {
        $errors['mechanic_id'] = 'Please select a mechanic from the list.';
    }

    if (empty($errors)) {
        // $id is passed as "exclude" so this appointment does not count against itself
        if (clientHasBooking($conn, $appt['phone'], $appt['car_license'], $newDate, $id)) {
            $message = 'This client already has another appointment on ' . $newDate . '.';

        } elseif (countMechanicBookings($conn, $mechanicId, $newDate, $id) >= MAX_CARS_PER_DAY) {
            $message = 'That mechanic is fully booked on ' . $newDate . '. Choose another mechanic.';

        } else {
            $stmt = $conn->prepare(
                "UPDATE appointments SET appointment_date = ?, mechanic_id = ? WHERE id = ?"
            );
            $stmt->bind_param('sii', $newDate, $mechanicId, $id);
            $stmt->execute();

            // Go back to the list and show a "updated" message
            header('Location: admin.php?updated=' . $id);
            exit;
        }
    }
}

// ---------- 3. Mechanic list with free places for the chosen date ----------
$listDate  = isValidDate($newDate) ? $newDate : $today;
$mechanics = getMechanicsWithSlots($conn, $listDate, $id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Appointment #<?= $id ?> | AutoCare Workshop</title>
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
    <h1>Edit Appointment #<?= $id ?></h1>
    <p class="lead"><a href="admin.php">&larr; Back to the appointment list</a></p>

    <?php if ($message !== ''): ?>
        <div class="alert alert-error"><?= e($message) ?></div>
    <?php endif; ?>

    <!-- Client details: shown only, the admin cannot change them -->
    <div class="card details">
        <p><strong>Client:</strong> <?= e($appt['client_name']) ?></p>
        <p><strong>Phone:</strong> <?= e($appt['phone']) ?></p>
        <p><strong>Car registration no.:</strong> <?= e($appt['car_license']) ?></p>
        <p><strong>Current booking:</strong> <?= e($appt['appointment_date']) ?>
            with <?= e($appt['mechanic_name']) ?></p>
    </div>

    <form id="appointmentForm" class="card" method="post" action="edit.php?id=<?= $id ?>" novalidate>
        <div class="grid">
            <div class="field">
                <label for="appointment_date">New appointment date</label>
                <input type="date" id="appointment_date" name="appointment_date" min="<?= $today ?>"
                       value="<?= e($newDate) ?>" required>
                <small class="error" id="err-appointment_date"><?= e($errors['appointment_date'] ?? '') ?></small>
            </div>

            <div class="field">
                <label for="mechanic_id">New mechanic</label>
                <!-- data-exclude tells script.js to ignore THIS appointment when counting -->
                <select id="mechanic_id" name="mechanic_id" data-exclude="<?= $id ?>" required>
                    <option value="">-- Select a mechanic --</option>
                    <?php foreach ($mechanics as $m): ?>
                        <option value="<?= $m['id'] ?>"
                            <?= $m['free'] <= 0 ? 'disabled' : '' ?>
                            <?= (string) $m['id'] === $newMechanic ? 'selected' : '' ?>>
                            <?= e($m['name']) ?> &mdash;
                            <?= $m['free'] > 0 ? $m['free'] . ' of ' . MAX_CARS_PER_DAY . ' places free' : 'FULL' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="hint" id="slotInfo">Free places shown for <?= e($listDate) ?>.</small>
                <small class="error" id="err-mechanic_id"><?= e($errors['mechanic_id'] ?? '') ?></small>
            </div>
        </div>

        <button type="submit" class="btn">Save changes</button>
        <a href="admin.php" class="btn btn-secondary">Cancel</a>
    </form>
</main>

<footer class="site-footer">
    <div class="container">CSE 391 &middot; Assignment 3 &middot; Car Workshop Appointment System</div>
</footer>

<script>
    const MAX_CARS = <?= MAX_CARS_PER_DAY ?>;
</script>
<script src="js/script.js"></script>
</body>
</html>
