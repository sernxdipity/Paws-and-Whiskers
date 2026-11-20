<?php
session_start();

require_once __DIR__ . '/config/db_connect.php';

// Only logged-in customers can view their bookings
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    http_response_code(401);
    exit('Please log in to view your booking.');
}

// Validate booking ID from the URL
$bookingId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$bookingId || $bookingId <= 0) {
    http_response_code(400);
    exit('Invalid booking reference.');
}

// Retrieve the booking belonging to this customer
$stmt = $pdo->prepare("
    SELECT
        a.AppointmentID,
        a.BookingDate,
        a.BookingTime,
        a.TotalPrice,
        a.BookingStatus,
        a.GroomerName,
        a.PetSize,
        a.CoatCondition,
        a.SizeSurcharge,
        a.CoatSurcharge,
        p.PetName,
        p.AnimalType,
        s.ServiceName
    FROM appointments a
    INNER JOIN pets p
        ON a.PetID = p.PetID
    INNER JOIN services s
        ON a.ServiceID = s.ServiceID
    WHERE a.AppointmentID = ?
      AND p.UserID = ?
    LIMIT 1
");

$stmt->execute([$bookingId, $userId]);
$booking = $stmt->fetch();

if (!$booking) {
    http_response_code(404);
    exit('Booking not found.');
}

 // Retrieve selected spa add-ons
$addonStmt = $pdo->prepare("
    SELECT
        sa.AddonName,
        aa.ChargedPrice
    FROM appointment_addons aa
    INNER JOIN spa_addons sa
        ON aa.AddonID = sa.AddonID
    WHERE aa.AppointmentID = ?
    ORDER BY sa.AddonName
");

$addonStmt->execute([$bookingId]);
$selectedAddons = $addonStmt->fetchAll();

// Calculate original grooming service portion
$addonTotalCents = 0;

foreach ($selectedAddons as $addon) {
    $addonTotalCents += (int) round(
        (float)$addon['ChargedPrice'] * 100
    );
}

$totalCents = (int) round(
    (float)$booking['TotalPrice'] * 100
);

// Retrieve the stored surcharges
$sizeSurchargeCents = (int) round(
    (float)$booking['SizeSurcharge'] * 100
);

$coatSurchargeCents = (int) round(
    (float)$booking['CoatSurcharge'] * 100
);

// Calculate the original grooming service price
$serviceTotalCents =
    $totalCents
    - $addonTotalCents
    - $sizeSurchargeCents
    - $coatSurchargeCents;

// Escape database values before displaying
function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$date = date(
    'd F Y',
    strtotime($booking['BookingDate'])
);

$time = date(
    'g:i A',
    strtotime($booking['BookingTime'])
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Booking Received | Paws & Whiskers</title>

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Poppins:wght@400;500;600;700&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="css/global.css">

    <style>
        body {
            background: #fffaf5;
        }

        .confirmation-wrapper {
            max-width: 720px;
            margin: 70px auto;
            padding: 0 20px;
        }

        .confirmation-card {
            background: white;
            padding: 45px;
            border-radius: 25px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        }

        .success-icon {
            width: 75px;
            height: 75px;
            background: #e7f2e8;
            border-radius: 50%;
            color: #527c57;
            font-size: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .confirmation-card h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 38px;
            margin-bottom: 12px;
        }

        .confirmation-desc {
            color: #777;
            margin-bottom: 20px;
        }

        .booking-status {
            display: inline-block;
            background: #fff3d8;
            color: #9a691b;
            padding: 8px 18px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 25px;
        }

        .receipt-details {
            background: #f9f7f3;
            padding: 25px;
            border-radius: 15px;
            text-align: left;
            margin-bottom: 25px;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 15px;
            margin-bottom: 15px;
        }

        .receipt-row span {
            color: #777;
        }

        .receipt-row strong {
            text-align: right;
        }

        .receipt-total {
            border-top: 1px solid #ddd;
            padding-top: 18px;
            margin-top: 15px;
            font-size: 20px;
            color: #527c57;
        }

        .home-button {
            display: inline-block;
            background: #789b75;
            color: white;
            padding: 13px 25px;
            border-radius: 25px;
            font-weight: 600;
        }

        .home-button:hover {
            background: #5f815c;
        }

        @media (max-width: 600px) {
            .confirmation-card {
                padding: 25px 18px;
            }

            .confirmation-card h1 {
                font-size: 30px;
            }
        }
    </style>
</head>

<body>

<header>
    <nav class="navbar">
        <a href="index.html" class="logo">
            🐾Paws & Whiskers
        </a>

        <button class="menu-toggle"
                id="menuToggle"
                aria-label="Toggle navigation">
            ☰
        </button>

        <div class="nav-links" id="navLinks">
            <a href="index.html">Home</a>
            <a href="service.html">Services</a>
            <a href="packages.php">Packages</a>
            <a href="groomers.html">Our Groomers</a>
            <a href="contact.html">Contact</a>
            <a href="booking.php" class="book-btn">
                Book Appointment
            </a>
        </div>
    </nav>
</header>

<main class="confirmation-wrapper">
    <div class="confirmation-card">

        <div class="success-icon">✓</div>

        <h1>Booking Received!</h1>

        <p class="confirmation-desc">
            Your grooming appointment request
            has been submitted successfully.
        </p>

        <span class="booking-status">
            <?= e($booking['BookingStatus']) ?>
        </span>

        <div class="receipt-details">

            <div class="receipt-row">
                <span>Booking ID</span>
                <strong>
                    #<?= (int)$booking['AppointmentID'] ?>
                </strong>
            </div>

            <div class="receipt-row">
                <span>Pet</span>
                <strong>
                    <?= e($booking['PetName']) ?>
                    (<?= e($booking['AnimalType']) ?>)
                </strong>
            </div>

            <div class="receipt-row">
                <span>Grooming Service</span>
                <strong>
                    <?= e($booking['ServiceName']) ?>
                </strong>
            </div>

            <div class="receipt-row">
                <span>Groomer</span>
                <strong>
                    <?= e($booking['GroomerName']) ?>
                </strong>
            </div>

            <div class="receipt-row">
                <span>Date</span>
                <strong><?= e($date) ?></strong>
            </div>

            <div class="receipt-row">
                <span>Time</span>
                <strong><?= e($time) ?></strong>
            </div>

            <div class="receipt-row">
                <span>Grooming Service Price</span>
                <strong>
                    RM <?= number_format(
                        $serviceTotalCents / 100,
                        2
                    ) ?>
                </strong>
            </div>

            <?php if (!empty($booking['PetSize'])): ?>
                <div class="receipt-row">
                    <span>
                        Pet Size (<?= e($booking['PetSize']) ?>)
                    </span>

                    <strong>
                        + RM <?= number_format(
                            $sizeSurchargeCents / 100,
                            2
                        ) ?>
                    </strong>
                </div>
            <?php endif; ?>

            <?php if (!empty($booking['CoatCondition'])): ?>
                <div class="receipt-row">
                    <span>
                        Coat Condition
                        (<?= e($booking['CoatCondition']) ?>)
                    </span>

                    <strong>
                        + RM <?= number_format(
                            $coatSurchargeCents / 100,
                            2
                        ) ?>
                    </strong>
                </div>
            <?php endif; ?>

            <?php foreach ($selectedAddons as $addon): ?>

                <div class="receipt-row">
                    <span>
                        <?= e($addon['AddonName']) ?>
                    </span>

                    <strong>
                        + RM <?= number_format(
                            (float)$addon['ChargedPrice'],
                            2
                        ) ?>
                    </strong>
                </div>

            <?php endforeach; ?>

            <div class="receipt-row receipt-total">
                <span>Total Price</span>
                <strong>
                    RM <?= number_format(
                        (float)$booking['TotalPrice'],
                        2
                    ) ?>
                </strong>
            </div>

        </div>

        <p class="confirmation-desc">
            Your appointment is subject to
            confirmation by our grooming team.
        </p>

        <a href="index.html" class="home-button">
            Return to Homepage
        </a>

    </div>
</main>

<script src="js/navigation.js"></script>

</body>
</html>