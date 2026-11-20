-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 20, 2026 at 07:58 AM
-- Server version: 8.0.45
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `paws_whiskers`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `AppointmentID` int UNSIGNED NOT NULL,
  `PetID` int UNSIGNED NOT NULL,
  `ServiceID` int UNSIGNED NOT NULL,
  `GroomerName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `PetSize` enum('Small','Medium','Large') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CoatCondition` enum('Healthy','Mild','Severe') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `SizeSurcharge` decimal(10,2) NOT NULL DEFAULT '0.00',
  `CoatSurcharge` decimal(10,2) NOT NULL DEFAULT '0.00',
  `BookingDate` date NOT NULL,
  `BookingTime` time NOT NULL,
  `TotalPrice` decimal(10,2) NOT NULL,
  `BookingStatus` enum('Pending','Confirmed','Completed','Cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `DateCreated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ReservedGroomer` varchar(100) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`BookingStatus` in (_utf8mb4'Pending',_utf8mb4'Confirmed',_utf8mb4'Completed')) then `GroomerName` else NULL end)) STORED
) ;

-- --------------------------------------------------------

--
-- Table structure for table `appointment_addons`
--

CREATE TABLE `appointment_addons` (
  `AppointmentID` int UNSIGNED NOT NULL,
  `AddonID` int UNSIGNED NOT NULL,
  `ChargedPrice` decimal(10,2) NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `PetID` int UNSIGNED NOT NULL,
  `UserID` int UNSIGNED NOT NULL,
  `PetName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `AnimalType` enum('Dog','Cat','Rabbit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `Breed` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Age` int UNSIGNED NOT NULL,
  `MedicalNotes` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `ServiceID` int UNSIGNED NOT NULL,
  `ServiceName` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ServiceTier` enum('Bronze','Silver','Gold') COLLATE utf8mb4_unicode_ci NOT NULL,
  `FullDescription` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `BasePrice` decimal(10,2) NOT NULL,
  `ActiveStatus` tinyint(1) NOT NULL DEFAULT '1',
  `AnimalType` enum('Dog','Cat','Rabbit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ImagePath` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `IsPackage` tinyint(1) NOT NULL DEFAULT '0'
) ;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`ServiceID`, `ServiceName`, `ServiceTier`, `FullDescription`, `BasePrice`, `ActiveStatus`, `AnimalType`, `ImagePath`, `IsPackage`) VALUES
(1, 'Signature Dog Spa', 'Gold', 'A complete cleansing, conditioning and coat care treatment for a fresh finish.', 85.00, 1, 'Dog', 'images/srv-dog-spa.jpg', 0),
(2, 'Puppy First Groom', 'Silver', 'A gentle introduction to grooming designed to help puppies feel comfortable.', 65.00, 1, 'Dog', 'images/srv-dog-puppy.jpg', 0),
(3, 'Dog De-Shedding', 'Gold', 'Specialised coat treatment that helps remove loose fur and reduce shedding.', 95.00, 1, 'Dog', 'images/srv-dog-shed.jpg', 0),
(4, 'Dog Nail & Paw Care', 'Bronze', 'Professional nail trimming with gentle paw cleaning and moisturising.', 35.00, 1, 'Dog', 'images/srv-dog-paw.jpg', 0),
(5, 'Gentle Cat Retreat', 'Gold', 'A calm grooming experience designed especially for sensitive felines.', 75.00, 1, 'Cat', 'images/srv-cat-spa.jpg', 0),
(6, 'Cat Coat & Brush', 'Silver', 'Gentle brushing to reduce tangles and keep fur soft and healthy.', 55.00, 1, 'Cat', 'images/srv-cat-brush.jpg', 0),
(7, 'Cat Hygiene Groom', 'Silver', 'A hygienic session focused on maintaining cleanliness and everyday comfort.', 65.00, 1, 'Cat', 'images/srv-cat-hygiene.jpg', 0),
(8, 'Cat Nail Care', 'Bronze', 'Safe and careful nail trimming performed in a pet-friendly environment.', 30.00, 1, 'Cat', 'images/srv-cat-nail.jpg', 0),
(9, 'Bunny Care Session', 'Silver', 'Gentle coat care, hygiene maintenance and nail trimming for your rabbit.', 55.00, 1, 'Rabbit', 'images/srv-rabbit-care.jpg', 0),
(10, 'Rabbit Coat Groom', 'Silver', 'Careful brushing to remove loose fur and maintain a clean coat.', 45.00, 1, 'Rabbit', 'images/srv-rabbit-groom.jpg', 0),
(11, 'Rabbit Nail Trim', 'Bronze', 'Gentle nail trimming designed specifically for the safety of rabbits.', 25.00, 1, 'Rabbit', 'images/srv-rabbit-nail.jpg', 0),
(12, 'Fluffy Treatment', 'Gold', 'Specialised coat maintenance for fluffy rabbits prone to matting.', 60.00, 1, 'Rabbit', 'images/srv-rabbit-fluff.jpg', 0),
(13, 'Gold Grooming Package - Dog', 'Gold', 'Gold grooming package with treatments adapted for dogs.', 85.00, 1, 'Dog', 'images/srv-dog-spa.jpg', 1),
(14, 'Silver Grooming Package - Dog', 'Silver', 'Silver grooming package with treatments adapted for dogs.', 55.00, 1, 'Dog', 'images/srv-dog-spa.jpg', 1),
(15, 'Bronze Grooming Package - Dog', 'Bronze', 'Bronze grooming package with treatments adapted for dogs.', 35.00, 1, 'Dog', 'images/srv-dog-spa.jpg', 1),
(16, 'Gold Grooming Package - Cat', 'Gold', 'Gold grooming package with treatments adapted for cats.', 85.00, 1, 'Cat', 'images/srv-cat-spa.jpg', 1),
(17, 'Silver Grooming Package - Cat', 'Silver', 'Silver grooming package with treatments adapted for cats.', 55.00, 1, 'Cat', 'images/srv-cat-spa.jpg', 1),
(18, 'Bronze Grooming Package - Cat', 'Bronze', 'Bronze grooming package with treatments adapted for cats.', 35.00, 1, 'Cat', 'images/srv-cat-spa.jpg', 1),
(19, 'Gold Grooming Package - Rabbit', 'Gold', 'Gold grooming package with treatments adapted for rabbits.', 85.00, 1, 'Rabbit', 'images/srv-rabbit-care.jpg', 1),
(20, 'Silver Grooming Package - Rabbit', 'Silver', 'Silver grooming package with treatments adapted for rabbits.', 55.00, 1, 'Rabbit', 'images/srv-rabbit-care.jpg', 1),
(21, 'Bronze Grooming Package - Rabbit', 'Bronze', 'Bronze grooming package with treatments adapted for rabbits.', 35.00, 1, 'Rabbit', 'images/srv-rabbit-care.jpg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `spa_addons`
--

CREATE TABLE `spa_addons` (
  `AddonID` int UNSIGNED NOT NULL,
  `AddonName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `AddonPrice` decimal(10,2) NOT NULL,
  `ActiveStatus` tinyint(1) NOT NULL DEFAULT '1'
) ;

--
-- Dumping data for table `spa_addons`
--

INSERT INTO `spa_addons` (`AddonID`, `AddonName`, `AddonPrice`, `ActiveStatus`) VALUES
(1, 'Nail Trimming Only', 15.00, 1),
(2, 'Ear Cleaning Only', 10.00, 1),
(3, 'Flea & Tick Treatment', 15.00, 1),
(4, 'De-matting', 25.00, 1),
(5, 'Blueberry Facial Scrub', 15.00, 1),
(6, 'Soothing Mud Bath', 20.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int UNSIGNED NOT NULL,
  `FullName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `HashedPassword` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `PhoneNumber` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Role` enum('Customer','Admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Customer',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`AppointmentID`),
  ADD UNIQUE KEY `uq_groomer_booking` (`ReservedGroomer`,`BookingDate`,`BookingTime`),
  ADD KEY `fk_appointments_pet` (`PetID`),
  ADD KEY `fk_appointments_service` (`ServiceID`);

--
-- Indexes for table `appointment_addons`
--
ALTER TABLE `appointment_addons`
  ADD PRIMARY KEY (`AppointmentID`,`AddonID`),
  ADD KEY `fk_appt_addons_addon` (`AddonID`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`PetID`),
  ADD KEY `fk_pets_user` (`UserID`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`ServiceID`);

--
-- Indexes for table `spa_addons`
--
ALTER TABLE `spa_addons`
  ADD PRIMARY KEY (`AddonID`),
  ADD UNIQUE KEY `AddonName` (`AddonName`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `AppointmentID` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `PetID` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `ServiceID` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spa_addons`
--
ALTER TABLE `spa_addons`
  MODIFY `AddonID` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_pet` FOREIGN KEY (`PetID`) REFERENCES `pets` (`PetID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointments_service` FOREIGN KEY (`ServiceID`) REFERENCES `services` (`ServiceID`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `appointment_addons`
--
ALTER TABLE `appointment_addons`
  ADD CONSTRAINT `fk_appt_addons_addon` FOREIGN KEY (`AddonID`) REFERENCES `spa_addons` (`AddonID`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_appt_addons_appointment` FOREIGN KEY (`AppointmentID`) REFERENCES `appointments` (`AppointmentID`) ON DELETE CASCADE;

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `fk_pets_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
