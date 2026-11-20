<?php
session_start();

if (empty($_SESSION['user_id']) ||
    (int) $_SESSION['user_id'] <= 0) {

    http_response_code(401);

    exit(
        'Please log in before booking an appointment.'
    );
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/config/db_connect.php';

// Load active grooming services
$stmt = $pdo->prepare("
    SELECT ServiceID, ServiceName, ServiceTier,
           BasePrice, AnimalType
    FROM services
    WHERE ActiveStatus = 1
    ORDER BY AnimalType, ServiceName
");

$stmt->execute();
$services = $stmt->fetchAll();

// Get the logged-in customer's ID
$userId = (int) ($_SESSION['user_id'] ?? 0);

// Load only pets belonging to this customer
$pets = [];

if ($userId > 0) {
    $petStmt = $pdo->prepare("
        SELECT PetID, PetName, AnimalType
        FROM pets
        WHERE UserID = ?
        ORDER BY PetName
    ");

    $petStmt->execute([$userId]);
    $pets = $petStmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment | Paws & Whiskers</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/global.css">
    <style>
        .booking-header { text-align: center; padding: 80px 30px 40px; }
        .section-label { color: #789b75; font-size: 13px; font-weight: bold; letter-spacing: 2px; margin-bottom: 12px; }
        .booking-header h1 { font-size: 46px; margin-bottom: 15px; }

        .booking-wrapper { max-width: 800px; margin: 0 auto 100px; position: relative; padding: 0 20px; }
        .form-container { background: white; padding: 50px; border-radius: 25px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); transition: opacity 0.4s ease; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 5px; }
        .form-group.full-width { grid-column: 1 / -1; }

        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; font-size: 14px; }
        .form-group input:not([type="checkbox"]), .form-group select { width: 100%; padding: 14px; border: 1px solid #ddd; border-radius: 12px; font-family: inherit; font-size: 14px; outline: none; transition: 0.3s; }
        .form-group input:not([type="checkbox"]):focus, .form-group select:focus { border-color: #789b75; box-shadow: 0 0 0 3px rgba(120, 155, 117, 0.12); }
        .form-group input.invalid, .form-group select.invalid { border-color: #ff4d4d; background: #fff5f5; }

        /* Add-ons Grid Styling */
        .addons-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; padding: 20px; border: 1px solid #ddd; border-radius: 12px; background: #f9fdf9; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #555; cursor: pointer; margin: 0; }
        .checkbox-group input[type="checkbox"] { width: auto; transform: scale(1.3); accent-color: #789b75; cursor: pointer; }

        .error-msg { color: #ff4d4d; font-size: 12px; display: none; margin-top: 6px; font-weight: 500; }

        .submit-btn { grid-column: 1 / -1; padding: 16px; background: #789b75; color: white; border: none; border-radius: 25px; font-weight: bold; font-size: 16px; cursor: pointer; margin-top: 15px; transition: 0.3s; }
        .submit-btn:hover { background: #5f815c; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(120,155,117,0.3); }

        #receiptContainer { display: none; background: white; padding: 50px; border-radius: 25px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); text-align: center; animation: slideUp 0.5s ease forwards; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        /* CSS Checkmark instead of emoji */
        .success-icon { width: 80px; height: 80px; background: #e4eee2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; position: relative; }
        .success-icon::after { content: ""; width: 20px; height: 35px; border-bottom: 5px solid #789b75; border-right: 5px solid #789b75; transform: rotate(45deg); margin-bottom: 10px; }

        .receipt-header { font-family: "DM Serif Display", serif; font-size: 32px; color: #333; margin-bottom: 10px; }
        .receipt-desc { color: #666; margin-bottom: 30px; }
        .receipt-card { background: #f4efe8; border-radius: 15px; padding: 30px; text-align: left; border: 2px dashed #ddd5c9; }
        .receipt-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .receipt-row:last-child { border-bottom: none; }
        .receipt-row span.label { color: #666; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; flex-shrink: 0; margin-right: 15px; }
        .receipt-row span.val { color: #333; font-weight: 600; font-size: 16px; text-align: right; }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .addons-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <header>
        <nav class="navbar">
            <a href="index.html" class="logo">🐾Paws & Whiskers</a>
            <button class="menu-toggle" id="menuToggle">☰</button>
            <div class="nav-links" id="navLinks">
                <a href="index.html">Home</a>
                <a href="service.html">Services</a>
                <a href="packages.html">Packages</a>
                <a href="groomers.html">Our Groomers</a>
                <a href="contact.html">Contact</a>
                <a href="booking.php" class="book-btn">Book Appointment</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="booking-header">
            <p class="section-label">RESERVATION</p>
            <h1>Schedule a Session</h1>
        </section>

        <section class="booking-wrapper">
            <div class="form-container" id="formContainer">

                <form
                    id="bookingForm"
                    class="form-grid"
                    action="booking_process.php"
                    method="POST"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <div class="form-group full-width">
                        <label>Owner Name</label>
                        <input type="text" id="ownerName" placeholder="Your full name" required>
                    </div>

                    <div class="form-group full-width">
                        <label>Contact Phone (Malaysian format)</label>
                        <input
                            type="tel"
                            id="contactPhone"
                            placeholder="012-3456789"
                            required
                            pattern="^01\d-?\d{7,8}$"
                            title="Please enter a valid Malaysian number (e.g., 012-3456789)"
                            oninput="this.value = this.value.replace(/[^0-9-]/g, '')"
                        >
                        <div class="error-msg" id="phoneError">Invalid format. Must be a Malaysian number.</div>
                    </div>

                    <div class="form-group">
                        <label for="petName">Select Your Pet</label>

                        <select id="petName" name="pet_id" required>
                            <option value="">Choose your pet...</option>

                            <?php foreach ($pets as $pet): ?>
                                <option
                                    value="<?= (int)$pet['PetID'] ?>"
                                    data-type="<?= htmlspecialchars(
                                        $pet['AnimalType'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >
                                    <?= htmlspecialchars(
                                        $pet['PetName'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?php if ($userId === 0): ?>
                            <small>Please log in to select a pet.</small>
                        <?php elseif (empty($pets)): ?>
                            <small>
                                No registered pets found.
                                Please register a pet first.
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="petType">Pet Type</label>

                        <input
                            type="text"
                            id="petType"
                            placeholder="Automatically selected"
                            readonly
                        >
                    </div>

                    <div class="form-group full-width">

                        <label>Choose Grooming Service</label>
                        <select
                            id="selectedPackage"
                            name="service_id"
                            required
                        >
                            <option value="">Choose a service...</option>

                            <?php foreach ($services as $service): ?>
                                <option value="<?= (int)$service['ServiceID'] ?>">
                                    <?= htmlspecialchars(
                                        $service['ServiceName'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                    (<?= htmlspecialchars($service['AnimalType']) ?>)
                                    - RM <?= number_format(
                                        (float)$service['BasePrice'],
                                        2
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #777; font-size: 12px; margin-top: 6px; display: block; font-style: italic;">
                            *Please note: The prices shown are base estimates. The final price may vary slightly upon arrival depending on your pet's exact size, breed, and coat condition.
                        </small>
                    </div>

                    <div class="form-group full-width">
                        <label>Specific Services & Spa Add-ons</label>
                        <div class="addons-grid">
                            <!-- Basic standalone services added -->
                            <label class="checkbox-group"><input type="checkbox" name="addon" value="Nail Trimming Only (+RM15)"> Nail Trimming Only (+RM15)</label>
                            <label class="checkbox-group"><input type="checkbox" name="addon" value="Ear Cleaning Only (+RM10)"> Ear Cleaning Only (+RM10)</label>
                            <label class="checkbox-group"><input type="checkbox" name="addon" value="Flea & Tick Treatment (+RM15)"> Flea & Tick Treatment (+RM15)</label>
                            <label class="checkbox-group"><input type="checkbox" name="addon" value="De-matting (+RM25)"> De-matting (+RM25)</label>
                            <label class="checkbox-group"><input type="checkbox" name="addon" value="Blueberry Facial (+RM15)"> Blueberry Facial (+RM15)</label>
                            <label class="checkbox-group"><input type="checkbox" name="addon" value="Soothing Mud Bath (+RM20)"> Soothing Mud Bath (+RM20)</label>
                        </div>
                        <div class="error-msg" id="servicesError">Please select at least one specific service since you did not choose a base package.</div>
                    </div>

                    <div class="form-group full-width">
                        <label for="groomerName">Preferred Groomer</label>

                        <select
                            id="groomerName"
                            name="groomer_name"
                            required
                        >
                            <option value="">Choose a groomer...</option>

                            <option value="Sarah Jenkins">
                                Sarah Jenkins
                            </option>

                            <option value="David Chen">
                                David Chen
                            </option>

                            <option value="Emma Watson">
                                Emma Watson
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Appointment Date</label>
                        <input
                            type="date"
                            id="appDate"
                            name="booking_date"
                            required
                        >
                        <div class="error-msg" id="dateError">Date must be scheduled in the future.</div>
                    </div>

                    <div class="form-group">
                        <label>Time Slot</label>
                        <input type="time" id="appTime" name="booking_time" required>
                        <div class="error-msg" id="timeError">Please select a valid time within our operating hours.</div>
                        <small style="color: #666; font-size: 12px; margin-top: 4px; display: block;">
                            Mon-Fri: 9:00 AM - 7:00 PM | Sat-Sun: 10:00 AM - 6:00 PM
                        </small>
                    </div>

                    <button type="submit" class="submit-btn">Confirm Reservation</button>
                </form>
            </div>

        </section>
    </main>

    <footer class="footer">
        <div class="footer-bottom">
            <p>&copy; 2026 Paws & Whiskers Pet Care & Grooming Centre. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/navigation.js"></script>
    <script>
    const bookingForm = document.getElementById('bookingForm');
    const dateInput = document.getElementById('appDate');
    const timeInput = document.getElementById('appTime');

    const petSelect = document.getElementById('petName');
    const petTypeInput = document.getElementById('petType');
    const serviceSelect = document.getElementById('selectedPackage');

    // Automatically display selected pet type
    petSelect.addEventListener('change', function() {
        const selected = petSelect.selectedOptions[0];
        petTypeInput.value = selected?.dataset.type || '';
    });

    // Set minimum date using local time
    const now = new Date();

    const localToday = [
        now.getFullYear(),
        String(now.getMonth() + 1).padStart(2, '0'),
        String(now.getDate()).padStart(2, '0')
    ].join('-');

    dateInput.min = localToday;

    // Temporarily disable add-ons until pricing is integrated
    document.querySelectorAll('input[name="addon"]').forEach(box => {
        box.checked = false;
        box.disabled = true;
    });

    // Validate booking before submitting
    bookingForm.addEventListener('submit', function(event) {
        const date = dateInput.value;
        const time = timeInput.value;

        const selectedPet = petSelect.selectedOptions[0];
        const selectedService = serviceSelect.selectedOptions[0];

        const petType = selectedPet?.dataset.type;
        const serviceType = selectedService?.dataset.type;

        if (!date || !time) {
            event.preventDefault();
            alert('Please select an appointment date and time.');
            return;
        }

        const appointment = new Date(`${date}T${time}:00`);

        if (appointment <= new Date()) {
            event.preventDefault();
            alert('Please choose a future appointment.');
            return;
        }

        const day = appointment.getDay();
        const weekend = day === 0 || day === 6;

        const opening = weekend ? '10:00' : '09:00';
        const closing = weekend ? '18:00' : '19:00';

        if (time < opening || time > closing) {
            event.preventDefault();
            alert('Please select a time within operating hours.');
            return;
        }

        if (!petType || !serviceType || petType !== serviceType) {
            event.preventDefault();
            alert('Please choose a grooming service matching your pet type.');
            return;
        }
    });
    </script>

</body>
</html>