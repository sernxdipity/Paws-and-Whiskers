<?php
session_start();

require_once __DIR__ . '/config/db_connect.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Invalid request method.');
}

// Verify logged-in user
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    http_response_code(401);
    exit('Please log in first.');
}

// Verify administrator permissions
$stmt = $pdo->prepare("
    SELECT UserID
    FROM users
    WHERE UserID = ? AND Role = 'Admin'
");

$stmt->execute([$userId]);

if (!$stmt->fetch()) {
    http_response_code(403);
    exit('Access denied. Administrators only.');
}

// Verify CSRF token
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    !isset($_SESSION['admin_csrf_token']) ||
    !hash_equals($_SESSION['admin_csrf_token'], $token)
) {
    http_response_code(403);
    exit('Invalid security token.');
}

// Validate submitted information
$appointmentId = filter_input(
    INPUT_POST,
    'appointment_id',
    FILTER_VALIDATE_INT
);

$newStatus = $_POST['new_status'] ?? '';

if (
    !$appointmentId ||
    !is_string($newStatus)
) {
    http_response_code(400);
    exit('Invalid booking information.');
}

// Retrieve current status
$stmt = $pdo->prepare("
    SELECT BookingStatus
    FROM appointments
    WHERE AppointmentID = ?
");

$stmt->execute([$appointmentId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    http_response_code(404);
    exit('Appointment not found.');
}

$currentStatus = $appointment['BookingStatus'];

// Define allowed status changes
$allowedTransitions = [
    'Pending' => ['Confirmed', 'Cancelled'],
    'Confirmed' => ['Completed', 'Cancelled']
];

$allowed = $allowedTransitions[$currentStatus] ?? [];

if (!in_array($newStatus, $allowed, true)) {
    http_response_code(400);
    exit('This status change is not permitted.');
}

// Update the appointment
$update = $pdo->prepare("
    UPDATE appointments
    SET BookingStatus = ?
    WHERE AppointmentID = ?
      AND BookingStatus = ?
");

$update->execute([
    $newStatus,
    $appointmentId,
    $currentStatus
]);

if ($update->rowCount() !== 1) {
    http_response_code(409);
    exit('Booking status changed. Please reload and try again.');
}

// Return to the dashboard
header('Location: admin.php?updated=1');
exit;
?>