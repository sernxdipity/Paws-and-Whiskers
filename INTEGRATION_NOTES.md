# Paws & Whiskers — Integration Notes

## Database
Database name: paws_whiskers

Required tables:
- users
- pets
- services
- appointments

Import database/paws_whiskers.sql before testing.

## Database Connection
Copy config/db_connect.example.php
to config/db_connect.php.

Enter your own MySQL credentials.
Do not upload database passwords to GitHub.

## Authentication (Member 1)
Required session variable:

$_SESSION['user_id']

Set this after successful login.

Use session_regenerate_id(true) after authentication.

User roles:
- Customer
- Admin

## Booking System (Member 2)
Files:
- booking.php
- booking_process.php

Required POST fields:
- pet_id
- service_id
- groomer_name
- booking_date
- booking_time
- csrf_token

Bookings must initially have Pending status.

## Admin System (Member 2)
Files:
- admin.php
- admin_update.php

Only users with the Admin database role
may access these features.

## Integration Testing
1. Register a customer account.
2. Log in as the customer.
3. Register a pet.
4. Open booking.php.
5. Select the registered pet and a matching service.
6. Submit a future appointment.
7. Verify the appointment in MySQL.
8. Log in as an administrator.
9. Update the appointment status.
10. Verify the updated status in customer history.