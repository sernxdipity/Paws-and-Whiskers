<?php
session_start();
if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/config/db_connect.php';

// Check whether the user is logged in
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    http_response_code(401);
    exit('Please log in to access this page.');
}

// Verify administrator role using the database
$adminStmt = $pdo->prepare("
    SELECT UserID
    FROM users
    WHERE UserID = ? AND Role = 'Admin'
");

$adminStmt->execute([$userId]);

if (!$adminStmt->fetch()) {
    http_response_code(403);
    exit('Access denied. Administrators only.');
}

// Retrieve all customer appointments
$stmt = $pdo->prepare("
    SELECT
        a.AppointmentID,
        u.FullName,
        p.PetName,
        p.AnimalType,
        s.ServiceName,
        a.GroomerName,
        a.BookingDate,
        a.BookingTime,
        a.TotalPrice,
        a.BookingStatus
    FROM appointments a
    INNER JOIN pets p
        ON a.PetID = p.PetID
    INNER JOIN users u
        ON p.UserID = u.UserID
    INNER JOIN services s
        ON a.ServiceID = s.ServiceID
    ORDER BY a.BookingDate DESC,
             a.BookingTime DESC
");

$stmt->execute();
$appointments = $stmt->fetchAll();
$stats = [
    'Total' => count($appointments),
    'Pending' => 0,
    'Confirmed' => 0,
    'Completed' => 0,
    'Cancelled' => 0
];

foreach ($appointments as $appointment) {
    $status = $appointment['BookingStatus'];

    if (isset($stats[$status])) {
        $stats[$status]++;
    }
}

 // Define valid booking statuses
$allowedStatuses = [
    'All',
    'Pending',
    'Confirmed',
    'Completed',
    'Cancelled'
];

// Get the selected filter from the URL
$selectedStatus = $_GET['status'] ?? 'All';

// Validate filter selection
if (
    !is_string($selectedStatus) ||
    !in_array($selectedStatus, $allowedStatuses, true)
) {
    $selectedStatus = 'All';
}

// Filter the appointments
if ($selectedStatus === 'All') {
    $visibleAppointments = $appointments;
} else {
    $visibleAppointments = array_values(
        array_filter(
            $appointments,
            function ($appointment) use ($selectedStatus) {
                return $appointment['BookingStatus'] === $selectedStatus;
            }
        )
    );
}

// Get search keyword from URL
$searchQuery = $_GET['q'] ?? '';

if (!is_string($searchQuery)) {
    $searchQuery = '';
}

$searchQuery = trim($searchQuery);
$searchQuery = substr($searchQuery, 0, 100);

// Search within the status-filtered appointments
if ($searchQuery !== '') {
    $visibleAppointments = array_values(
        array_filter(
            $visibleAppointments,
            function ($appointment) use ($searchQuery) {

                $bookingId =
                    (string)$appointment['AppointmentID'];

                $customerName =
                    (string)$appointment['FullName'];

                $petName =
                    (string)$appointment['PetName'];

                return
                    stripos($bookingId, $searchQuery) !== false ||
                    stripos($customerName, $searchQuery) !== false ||
                    stripos($petName, $searchQuery) !== false;
            }
        )
    );
}

function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Paws & Whiskers</title>

    <link rel="stylesheet" href="css/global.css">

    <style>
        body {
            background: #f8f7f2;
            font-family: Arial, sans-serif;
        }

        .admin-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
        }

        .admin-container h1 {
            color: #456b4f;
            margin-bottom: 10px;
        }

        .table-wrapper {
            overflow-x: auto;
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #789b75;
            color: white;
        }

        tr:hover {
            background: #f3f7f1;
        }

        .status {
            font-weight: bold;
        }

        .Pending { color: #b7791f; }
        .Confirmed { color: #2563eb; }
        .Completed { color: #15803d; }
        .Cancelled { color: #dc2626; }

        .status-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-form select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: white;
        }

        .status-form button {
            padding: 8px 12px;
            background: #789b75;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .status-form button:hover {
            background: #456b4f;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border-left: 5px solid #789b75;
        }

        .stat-card h3 {
            color: #666;
            font-size: 15px;
            margin-bottom: 12px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #456b4f;
            margin: 0;
        }

        @media (max-width: 900px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .filter-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin: 25px 0;
            flex-wrap: wrap;
        }

        .filter-form {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .filter-form label {
            font-weight: 600;
            color: #456b4f;
        }

        .filter-form select {
            padding: 11px 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: white;
            font-family: inherit;
        }

        .filter-form button {
            background: #789b75;
            color: white;
            padding: 11px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .filter-form button:hover {
            background: #456b4f;
        }

        .filter-count {
            color: #777;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            .filter-section,
            .filter-form {
                align-items: stretch;
                flex-direction: column;
            }

            .filter-form select,
            .filter-form button {
                width: 100%;
            }
        }

        .search-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .search-group label {
            font-weight: 600;
            color: #456b4f;
        }

        .search-group input {
            width: 260px;
            max-width: 100%;
            padding: 11px 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: white;
            font-family: inherit;
            font-size: 14px;
        }

        .search-group input:focus {
            outline: none;
            border-color: #789b75;
        }

        @media (max-width: 600px) {
            .search-group,
            .search-group input {
                width: 100%;
            }
        }

        /* Admin dashboard responsive layout */

        .admin-container {
            width: 100%;
            box-sizing: border-box;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            box-sizing: border-box;
        }

        .table-wrapper table {
            min-width: 1100px;
        }

        .table-wrapper th,
        .table-wrapper td {
            vertical-align: middle;
            white-space: nowrap;
        }

        .table-wrapper td:nth-child(2),
        .table-wrapper td:nth-child(3),
        .table-wrapper td:nth-child(4) {
            white-space: normal;
            min-width: 130px;
        }

        /* Keep status controls aligned */

        .status-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-form select,
        .status-form button {
            font-family: inherit;
            font-size: 13px;
        }

        /* Mobile scrolling instruction */

        .table-scroll-hint {
            display: none;
            color: #777;
            font-size: 13px;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .admin-container {
                margin: 20px auto;
                padding: 15px;
            }

            .admin-container h1 {
                font-size: 28px;
            }

            .table-wrapper {
                padding: 10px;
            }

            .table-scroll-hint {
                display: block;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .stat-card {
                padding: 18px;
            }

            .stat-number {
                font-size: 26px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }

            .search-group input {
                width: 100%;
                box-sizing: border-box;
            }
}
    </style>
</head>

<body>
    <main class="admin-container">

        <h1>Admin Dashboard</h1>
        <?php if (($_GET['updated'] ?? '') === '1'): ?>
            <p style="color: green; font-weight: bold;">
                Booking status updated successfully!
            </p>
        <?php endif; ?>
        <p>Manage Paws & Whiskers appointments</p>
        <div class="stats-grid">

            <?php foreach ([
                'Total',
                'Pending',
                'Confirmed',
                'Completed'
            ] as $status): ?>

                <div class="stat-card">
                    <h3><?= e($status) ?> Bookings</h3>

                    <p class="stat-number">
                        <?= (int)$stats[$status] ?>
                    </p>
                </div>

            <?php endforeach; ?>

        </div>

        <div class="filter-section">

            <form method="GET" action="admin.php"
                class="filter-form">

                <label for="statusFilter">
                    Filter by Booking Status
                </label>

                <select
                    id="statusFilter"
                    name="status"
                >
                    <?php foreach ($allowedStatuses as $status): ?>
                        <option
                            value="<?= e($status) ?>"
                            <?= $selectedStatus === $status
                                ? 'selected'
                                : '' ?>
                        >
                            <?= $status === 'All'
                                ? 'All Bookings'
                                : e($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="search-group">
                    <label for="bookingSearch">
                        Search Appointments
                    </label>

                    <input
                        type="search"
                        id="bookingSearch"
                        name="q"
                        placeholder="Customer, pet or booking ID"
                        value="<?= e($searchQuery) ?>"
                        maxlength="100"
                    >
                </div>

                <button type="submit">
                    Apply Filter
                </button>
            </form>

            <p class="filter-count">
                Showing <?= count($visibleAppointments) ?>
                appointment(s)
            </p>

        </div>

            <p class="table-scroll-hint">
                Swipe left or right to view all appointment details.
            </p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Customer</th>
                        <th>Pet</th>
                        <th>Service</th>
                        <th>Groomer</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($visibleAppointments as $a): ?>
                    <tr>
                        <td><?= (int)$a['AppointmentID'] ?></td>
                        <td><?= e($a['FullName']) ?></td>
                        <td>
                            <?= e($a['PetName']) ?>
                            (<?= e($a['AnimalType']) ?>)
                        </td>
                        <td><?= e($a['ServiceName']) ?></td>
                        <td><?= e($a['GroomerName']) ?></td>
                        <td><?= e($a['BookingDate']) ?></td>
                        <td><?= e($a['BookingTime']) ?></td>
                        <td>
                            RM <?= number_format(
                                (float)$a['TotalPrice'], 2
                            ) ?>
                        </td>
                        <td class="status <?= e($a['BookingStatus']) ?>">
                            <?= e($a['BookingStatus']) ?>
                        </td>

                        <td>
                            <?php
                            $status = $a['BookingStatus'];

                            $options = match ($status) {
                                'Pending' => ['Confirmed', 'Cancelled'],
                                'Confirmed' => ['Completed', 'Cancelled'],
                                default => []
                            };
                            ?>

                            <?php if (!empty($options)): ?>

                                <form
                                    action="admin_update.php"
                                    method="POST"
                                    class="status-form"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION['admin_csrf_token']) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="appointment_id"
                                        value="<?= (int)$a['AppointmentID'] ?>"
                                    >

                                    <select name="new_status" required>
                                        <option value="">Select...</option>

                                        <?php foreach ($options as $option): ?>
                                            <option value="<?= e($option) ?>">
                                                <?= e($option) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button type="submit">
                                        Update
                                    </button>
                                </form>

                            <?php else: ?>
                                <span>No actions</span>
                            <?php endif; ?>
                        </td>

                    </tr>
                <?php endforeach; ?>

                <?php if (empty($visibleAppointments)): ?>
                    <tr>
                        <td colspan="10">
                            No appointments match your search or filter.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</body>
</html>