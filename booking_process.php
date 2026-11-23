<?php
session_start();

require_once __DIR__ . '/config/db_connect.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

function returnToBookingWithError($message) {

    // Save the error message
    $_SESSION['booking_error'] = $message;

    // Remember the submitted booking fields
    $fields = [
        'pet_id',
        'service_id',
        'groomer_name',
        'booking_date',
        'booking_time',
        'pet_size',
        'coat_condition'
    ];

    $oldBooking = [];

    foreach ($fields as $field) {
        $value = $_POST[$field] ?? '';

        $oldBooking[$field] = is_string($value)
            ? substr($value, 0, 100)
            : '';
    }

    // Preserve selected spa add-ons
    $addons = $_POST['addon_ids'] ?? [];

    $oldBooking['addon_ids'] = [];

    if (is_array($addons)) {
        foreach (array_slice($addons, 0, 20) as $id) {
            if (is_string($id) && ctype_digit($id)) {
                $oldBooking['addon_ids'][] = $id;
            }
        }
    }

    $_SESSION['booking_old'] = $oldBooking;

    // Preserve the selected grooming service
    $serviceId = filter_var(
        $oldBooking['service_id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $returnUrl = 'booking.php';

    if ($serviceId) {
        $returnUrl .= '?service_id=' . $serviceId;
    }

    header('Location: ' . $returnUrl, true, 303);
    exit;
}

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
$petSize = $_POST['pet_size'] ?? '';
$coatCondition = $_POST['coat_condition'] ?? '';

// Pet size surcharges (in cents)
$sizePrices = [
    'Small' => 0,
    'Medium' => 1500,
    'Large' => 3000
];

// Coat condition surcharges (in cents)
$coatPrices = [
    'Healthy' => 0,
    'Mild' => 1000,
    'Severe' => 2500
];

// Validate pet size
if (
    !is_string($petSize) ||
    !array_key_exists($petSize, $sizePrices)
) {
    returnToBookingWithError('Invalid pet size selection.');
}

// Validate coat condition
if (
    !is_string($coatCondition) ||
    !array_key_exists($coatCondition, $coatPrices)
) {
    returnToBookingWithError('Invalid coat condition selection.');
}

// Calculate surcharges
$sizeCents = $sizePrices[$petSize];
$coatCents = $coatPrices[$coatCondition];

// Format amounts for MySQL
$sizeSurcharge = number_format(
    $sizeCents / 100, 2, '.', ''
);

$coatSurcharge = number_format(
    $coatCents / 100, 2, '.', ''
);

// Validate required fields
if (
    !$petId || !$serviceId ||
    !is_string($groomer) ||
    !is_string($date) ||
    !is_string($time)
) {
    returnToBookingWithError(
        'Please complete all booking fields.'
    );
}

// Verify selected groomer
$validGroomers = [
    'Sarah Jenkins',
    'David Chen',
    'Emma Watson'
];

if (!in_array($groomer, $validGroomers, true)) {
    returnToBookingWithError('Invalid groomer selection.');
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
    returnToBookingWithError('Invalid appointment date or time.');
}

$now = new DateTimeImmutable('now', $timezone);

if ($appointment <= $now) {
    returnToBookingWithError('Please choose a future appointment.');
}

// Check operating hours
$day = (int) $appointment->format('N');

$isWeekend = $day >= 6;

$opening = $isWeekend ? '10:00' : '09:00';
$closing = $isWeekend ? '18:00' : '19:00';

if ($time < $opening || $time > $closing) {
    returnToBookingWithError('Selected time is outside operating hours.');
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
    returnToBookingWithError('Selected service is unavailable.');
}

// Check that the service matches the animal type
if ($pet['AnimalType'] !== $service['AnimalType']) {
    returnToBookingWithError('This service does not match your pet type.');
}

// Price must come from MySQL, not the customer's form
 // Get selected spa add-ons
$rawAddonIds = $_POST['addon_ids'] ?? [];

if (!is_array($rawAddonIds) || count($rawAddonIds) > 20) {
    http_response_code(400);
    exit('Invalid spa add-on selection.');
}

$addonIds = [];

// Validate every selected add-on ID
foreach ($rawAddonIds as $rawId) {
    if (!is_string($rawId)) {
        http_response_code(400);
        exit('Invalid spa add-on ID.');
    }

    $addonId = filter_var(
        $rawId,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($addonId === false) {
        http_response_code(400);
        exit('Invalid spa add-on ID.');
    }

    $addonIds[] = $addonId;
}

// Prevent duplicate add-on selections
if (count($addonIds) !== count(array_unique($addonIds))) {
    http_response_code(400);
    exit('Duplicate spa add-ons are not allowed.');
}

// Retrieve selected add-ons from MySQL
$addonRows = [];

if (!empty($addonIds)) {
    $placeholders = implode(
        ',',
        array_fill(0, count($addonIds), '?')
    );

    $addonStmt = $pdo->prepare("
        SELECT AddonID, AddonName, AddonPrice
        FROM spa_addons
        WHERE ActiveStatus = 1
          AND AddonID IN ($placeholders)
    ");

    $addonStmt->execute($addonIds);
    $addonRows = $addonStmt->fetchAll();

    // Ensure all selected add-ons exist and are active
    if (count($addonRows) !== count($addonIds)) {
        returnToBookingWithError('One or more selected add-ons are unavailable.');
    }
}

// Calculate total using database prices
// Work in cents to avoid rounding differences
// 1. Main grooming service price
$totalCents = (int) round(
    (float)$service['BasePrice'] * 100
);

// 2. Pet size surcharge
$totalCents += $sizeCents;

// 3. Coat condition surcharge
$totalCents += $coatCents;

// 4. Spa add-on prices
foreach ($addonRows as $addon) {
    $totalCents += (int) round(
        (float)$addon['AddonPrice'] * 100
    );
}

// 5. Final total
$totalPrice = number_format(
    $totalCents / 100,
    2,
    '.',
    ''
);

// Save the booking
try {
    // Start database transaction
    $pdo->beginTransaction();

    // 1. Save the main appointment
    $insert = $pdo->prepare("
        INSERT INTO appointments
        (
            PetID,
            ServiceID,
            GroomerName,
            PetSize,
            CoatCondition,
            SizeSurcharge,
            CoatSurcharge,
            BookingDate,
            BookingTime,
            TotalPrice,
            BookingStatus
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
    ");

    $insert->execute([
        $petId,
        $serviceId,
        $groomer,
        $petSize,
        $coatCondition,
        $sizeSurcharge,
        $coatSurcharge,
        $date,
        $time,
        $totalPrice
    ]);

    // Get the newly created appointment ID
    $bookingId = (int) $pdo->lastInsertId();

    // 2. Save selected spa add-ons
    if (!empty($addonRows)) {

        $addonInsert = $pdo->prepare("
            INSERT INTO appointment_addons
            (
                AppointmentID,
                AddonID,
                ChargedPrice
            )
            VALUES (?, ?, ?)
        ");

        foreach ($addonRows as $addon) {
            $addonInsert->execute([
                $bookingId,
                $addon['AddonID'],
                $addon['AddonPrice']
            ]);
        }
    }

    // 3. Save all changes permanently
    $pdo->commit();

    // 4. Redirect to booking confirmation
    header(
        'Location: booking_success.php?id=' . $bookingId,
        true,
        303
    );
    exit;

} catch (PDOException $e) {

    // Undo incomplete database changes
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Check for duplicate groomer bookings
    $mysqlError = (int) ($e->errorInfo[1] ?? 0);
    $errorDetail = (string) ($e->errorInfo[2] ?? '');

    if (
        $e->getCode() === '23000' &&
        $mysqlError === 1062 &&
        str_contains($errorDetail, 'uq_groomer_booking')
    ) {
        returnToBookingWithError(
            'This groomer is already booked at the selected ' .
            'date and time. Please choose another slot.'
        );
    }

    // Record unexpected errors privately
    error_log($e->getMessage());

    returnToBookingWithError(
        'We could not save your appointment. ' .
        'Please try again.'
    );
}
?>