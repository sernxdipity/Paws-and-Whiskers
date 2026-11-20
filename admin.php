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
                <?php foreach ($appointments as $a): ?>
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

                <?php if (empty($appointments)): ?>
                    <tr>
                        <td colspan="10">
                            No appointments found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</body>
</html>