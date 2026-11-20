<?php
session_start();

require_once __DIR__ . '/config/db_connect.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

// Only accept booking form submissions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Invalid request method.');
}

// Check that the customer is logged in
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    http_response_code(401);
    exit('Please log in before making a booking.');
}

// Verify CSRF security token
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Invalid security token. Please refresh the page.');
}

// Retrieve submitted booking details
$petId = filter_var(
    $_POST['pet_id'] ?? null,
    FILTER_VALIDATE_INT
);

$serviceId = filter_var(
    $_POST['service_id'] ?? null,
    FILTER_VALIDATE_INT
);

$groomer = $_POST['groomer_name'] ?? '';
$date = $_POST['booking_date'] ?? '';
$time = $_POST['booking_time'] ?? '';

// Validate required fields
if (
    !$petId || !$serviceId ||
    !is_string($groomer) ||
    !is_string($date) ||
    !is_string($time)
) {
    http_response_code(400);
    exit('Please complete all booking fields.');
}

// Verify selected groomer
$validGroomers = [
    'Sarah Jenkins',
    'David Chen',
    'Emma Watson'
];

if (!in_array($groomer, $validGroomers, true)) {
    http_response_code(400);
    exit('Invalid groomer selection.');
}

// Validate date and time
$timezone = new DateTimeZone('Asia/Kuala_Lumpur');

$appointment = DateTimeImmutable::createFromFormat(
    '!Y-m-d H:i',
    $date . ' ' . $time,
    $timezone
);

if (
    !$appointment ||
    $appointment->format('Y-m-d H:i') !==
        $date . ' ' . $time
) {
    http_response_code(400);
    exit('Invalid appointment date or time.');
}

$now = new DateTimeImmutable('now', $timezone);

if ($appointment <= $now) {
    http_response_code(400);
    exit('Please choose a future appointment.');
}

// Check operating hours
$day = (int) $appointment->format('N');

$isWeekend = $day >= 6;

$opening = $isWeekend ? '10:00' : '09:00';
$closing = $isWeekend ? '18:00' : '19:00';

if ($time < $opening || $time > $closing) {
    http_response_code(400);
    exit('Selected time is outside operating hours.');
}

// Verify that the pet belongs to the logged-in user
$petStmt = $pdo->prepare("
    SELECT PetID, AnimalType
    FROM pets
    WHERE PetID = ? AND UserID = ?
");

$petStmt->execute([$petId, $userId]);
$pet = $petStmt->fetch();

if (!$pet) {
    http_response_code(403);
    exit('Invalid pet selection.');
}

// Retrieve service information from MySQL
$serviceStmt = $pdo->prepare("
    SELECT ServiceID, AnimalType, BasePrice
    FROM services
    WHERE ServiceID = ? AND ActiveStatus = 1
");

$serviceStmt->execute([$serviceId]);
$service = $serviceStmt->fetch();

if (!$service) {
    http_response_code(400);
    exit('Selected service is unavailable.');
}

// Check that the service matches the animal type
if ($pet['AnimalType'] !== $service['AnimalType']) {
    http_response_code(400);
    exit('This service does not match your pet type.');
}

// Price must come from MySQL, not the customer's form
$totalPrice = $service['BasePrice'];

// Save the booking
try {
    $insert = $pdo->prepare("
        INSERT INTO appointments
        (
            PetID,
            ServiceID,
            GroomerName,
            BookingDate,
            BookingTime,
            TotalPrice,
            BookingStatus
        )
        VALUES (?, ?, ?, ?, ?, ?, 'Pending')
    ");

    $insert->execute([
        $petId,
        $serviceId,
        $groomer,
        $date,
        $time,
        $totalPrice
    ]);

    $bookingId = $pdo->lastInsertId();

    echo 'Booking submitted successfully!';
    echo '<p>Booking ID: ' . (int) $bookingId . '</p>';
    echo '<p>Status: Pending</p>';

} catch (PDOException $e) {

    // MySQL error 1062 means a UNIQUE constraint was violated
    $mysqlError = (int) ($e->errorInfo[1] ?? 0);

    if (
        $e->getCode() === '23000' &&
        $mysqlError === 1062
    ) {
        http_response_code(409);

        exit(
            '<h2>Appointment Unavailable</h2>' .
            '<p>This groomer is already booked at ' .
            'the selected date and time.</p>' .
            '<p>Please choose another slot.</p>' .
            '<a href="booking.php">Back to Booking</a>'
        );
    }

    // Log unexpected database errors privately
    error_log($e->getMessage());

    http_response_code(500);
    exit('Unable to save your booking.');
}
?>