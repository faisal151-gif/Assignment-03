<?php
// USER PANEL - the client books an appointment here
require 'db.php';

$today   = date('Y-m-d');
$errors  = [];  
$message = '';   
$msgType = '';   

// Values to put back in the form (so the client doesn't retype everything)
$old = [
    'name' => '', 'address' => '', 'phone' => '', 'car_license' => '',
    'car_engine' => '', 'appointment_date' => '', 'mechanic_id' => '',
];

// submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    
    $old['name']             = trim($_POST['name'] ?? '');
    $old['address']          = trim($_POST['address'] ?? '');
    $old['phone']            = trim($_POST['phone'] ?? '');
    $old['car_license']      = strtoupper(trim($_POST['car_license'] ?? ''));
    $old['car_engine']       = trim($_POST['car_engine'] ?? '');
    $old['appointment_date'] = trim($_POST['appointment_date'] ?? '');
    $old['mechanic_id']      = trim($_POST['mechanic_id'] ?? '');

    // Validate
    if (!preg_match('/^[A-Za-z .\-]{2,100}$/', $old['name'])) {
        $errors['name'] = 'Enter your name (letters and spaces only).';
    }
    if ($old['address'] === '') {
        $errors['address'] = 'Enter your address.';
    }
    if (!preg_match('/^[0-9]{11}$/', $old['phone'])) {
        $errors['phone'] = 'Phone must be exactly 11 digits, e.g. 01712345678.';
    }
    if (!preg_match('/^[A-Z0-9 \-]{2,50}$/', $old['car_license'])) {
        $errors['car_license'] = 'Enter the car license number (letters, numbers, - and spaces).';
    }
    if (!preg_match('/^[0-9]{1,30}$/', $old['car_engine'])) {
        $errors['car_engine'] = 'Engine number must contain numbers only.';
    }
    if (!isValidDate($old['appointment_date'])) {
        $errors['appointment_date'] = 'Choose a valid date.';
    } elseif ($old['appointment_date'] < $today) {
        $errors['appointment_date'] = 'The date cannot be in the past.';
    }
    $mechanicId   = (int) $old['mechanic_id'];
    $mechanicName = getMechanicName($conn, $mechanicId);   
    if ($mechanicName === null) {
        $errors['mechanic_id'] = 'Please select a mechanic from the list.';
    }

    // Business rules
    if (empty($errors)) {
        $date = $old['appointment_date'];

        if (clientHasBooking($conn, $old['phone'], $old['car_license'], $date)) {
            // Rule A: one appointment per client per day
            $msgType = 'error';
            $message = 'You have already taken an appointment on ' . $date
                     . '. Only one appointment per day is allowed.';

        } elseif (countMechanicBookings($conn, $mechanicId, $date) >= MAX_CARS_PER_DAY) {
            // Rule B: a mechanic can have at most 4 cars per day
            $msgType = 'error';
            $message = 'Sorry, this mechanic is fully booked on ' . $date
                     . '. Please choose another mechanic or another date.';

        } else {
            // save it
            $stmt = $conn->prepare(
                "INSERT INTO appointments
                   (client_name, address, phone, car_license, car_engine, appointment_date, mechanic_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                'ssssssi',
                $old['name'], $old['address'], $old['phone'], $old['car_license'],
                $old['car_engine'], $date, $mechanicId
            );
            $stmt->execute();

            $msgType = 'success';
            $message = 'Appointment confirmed! ' . $old['name'] . ', your car (' . $old['car_license']
                     . ') is booked with ' . $mechanicName . ' on ' . $date
                     . '. Appointment no: #' . $stmt->insert_id . '.';

            // Empty the form
            foreach ($old as $key => $value) {
                $old[$key] = '';
            }
        }
    }
}

//mechanic list
$listDate  = isValidDate($old['appointment_date']) ? $old['appointment_date'] : $today;
$mechanics = getMechanicsWithSlots($conn, $listDate);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book an Appointment | AutoCare Workshop</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="index.php">AutoCare <span>Workshop</span></a>
        <nav>
            <a href="index.php" class="active">Book Appointment</a>
            <a href="admin.php">Admin Panel</a>
            <a href="help.html">Help</a>
        </nav>
    </div>
</header>

<main class="container">
    <h1>Book an Appointment</h1>
    <p class="lead">Choose the mechanic you want. Each mechanic can take at most
        <?= MAX_CARS_PER_DAY ?> cars per day. Need help? See the <a href="help.html">Help page</a>.</p>

    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= $msgType ?>"><?= e($message) ?></div>
    <?php endif; ?>

    <form id="appointmentForm" class="card" method="post" action="index.php" novalidate>

        <div class="grid">
            <div class="field">
                <label for="name">Full name</label>
                <input type="text" id="name" name="name" maxlength="100"
                       value="<?= e($old['name']) ?>" placeholder="e.g. Faisal Rahman" required>
                <small class="hint">Letters and spaces only.</small>
                <small class="error" id="err-name"><?= e($errors['name'] ?? '') ?></small>
            </div>

            <div class="field">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" maxlength="11" inputmode="numeric"
                       value="<?= e($old['phone']) ?>" placeholder="01712345678" required>
                <small class="hint">11 digits, numbers only.</small>
                <small class="error" id="err-phone"><?= e($errors['phone'] ?? '') ?></small>
            </div>

            <div class="field full">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="2" maxlength="255"
                          placeholder="House, road, area, city" required><?= e($old['address']) ?></textarea>
                <small class="error" id="err-address"><?= e($errors['address'] ?? '') ?></small>
            </div>

            <div class="field">
                <label for="car_license">Car license number</label>
                <input type="text" id="car_license" name="car_license" maxlength="50"
                       value="<?= e($old['car_license']) ?>" placeholder="DHAKA-METRO-GA-12-3456" required>
                <small class="hint">The number written on the number plate.</small>
                <small class="error" id="err-car_license"><?= e($errors['car_license'] ?? '') ?></small>
            </div>

            <div class="field">
                <label for="car_engine">Car engine number</label>
                <input type="text" id="car_engine" name="car_engine" maxlength="30" inputmode="numeric"
                       value="<?= e($old['car_engine']) ?>" placeholder="e.g. 4567891230" required>
                <small class="hint">Numbers only (see your car papers).</small>
                <small class="error" id="err-car_engine"><?= e($errors['car_engine'] ?? '') ?></small>
            </div>

            <div class="field">
                <label for="appointment_date">Appointment date</label>
                <input type="date" id="appointment_date" name="appointment_date" min="<?= $today ?>"
                       value="<?= e($old['appointment_date']) ?>" required>
                <small class="hint">Pick a date first to see free places.</small>
                <small class="error" id="err-appointment_date"><?= e($errors['appointment_date'] ?? '') ?></small>
            </div>

            <div class="field">
                <label for="mechanic_id">Mechanic</label>
                <select id="mechanic_id" name="mechanic_id" required>
                    <option value="">-- Select a mechanic --</option>
                    <?php foreach ($mechanics as $m): ?>
                        <option value="<?= $m['id'] ?>"
                            <?= $m['free'] <= 0 ? 'disabled' : '' ?>
                            <?= (string) $m['id'] === $old['mechanic_id'] ? 'selected' : '' ?>>
                            <?= e($m['name']) ?> &mdash;
                            <?= $m['free'] > 0 ? $m['free'] . ' of ' . MAX_CARS_PER_DAY . ' places free' : 'FULL' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="hint" id="slotInfo">Free places shown for <?= e($listDate) ?>.</small>
                <small class="error" id="err-mechanic_id"><?= e($errors['mechanic_id'] ?? '') ?></small>
            </div>
        </div>

        <button type="submit" class="btn">Book Appointment</button>
    </form>
</main>

<footer class="site-footer">
    <div class="container">CSE 391 &middot; Assignment 3 &middot; Car Workshop Appointment System</div>
</footer>

<script>
    // Tell script.js the max places so it can write "3 of 4 places free"
    const MAX_CARS = <?= MAX_CARS_PER_DAY ?>;
</script>
<script src="js/script.js"></script>
</body>
</html>
