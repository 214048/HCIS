-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 19, 2025 at 12:41 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hcis_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_date` datetime NOT NULL,
  `slot_time` time DEFAULT NULL,
  `type` enum('consultation','follow-up','emergency') NOT NULL DEFAULT 'consultation',
  `reason` text DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `appointment_date`, `slot_time`, `type`, `reason`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(5, 3, 6, '2025-05-09 09:30:00', '09:30:00', 'consultation', 'الم في الضرس', 'completed', '', '2025-05-06 01:41:21', '2025-05-19 19:53:58'),
(8, 3, 7, '2025-05-09 09:30:00', '09:30:00', 'consultation', 'الم في الناب', 'cancelled', '', '2025-05-09 14:59:27', '2025-05-19 19:56:52'),
(17, 3, 6, '2025-05-19 09:30:00', '09:30:00', 'consultation', 'الم في الضرس', 'completed', '', '2025-05-18 19:41:40', '2025-06-10 17:46:02'),
(18, 3, 7, '2025-05-23 09:00:00', '09:00:00', 'consultation', 'تقويم', 'cancelled', '', '2025-05-18 19:42:14', '2025-06-10 17:45:59'),
(19, 4, 6, '2025-05-20 09:00:00', '09:00:00', 'consultation', 'الم في العين و الرغبة في عمل كشف نضارة', 'pending', '', '2025-05-19 21:17:46', '2025-05-19 21:17:46'),
(20, 3, 6, '2025-05-23 09:30:00', '09:30:00', 'consultation', 'pain', 'cancelled', '', '2025-05-21 14:38:38', '2025-06-18 21:25:48'),
(21, 3, 6, '2025-06-13 09:00:00', '09:00:00', 'consultation', '22', 'completed', '22', '2025-06-10 18:19:09', '2025-06-16 18:38:03'),
(22, 4, 6, '2025-06-17 09:30:00', '09:30:00', 'consultation', 'fghh', 'completed', '', '2025-06-16 18:08:26', '2025-06-16 18:38:53'),
(23, 4, 6, '2025-06-17 09:30:00', '09:30:00', 'consultation', 'jkl', 'cancelled', '', '2025-06-16 18:39:09', '2025-06-16 18:39:17'),
(24, 3, 6, '2025-06-16 09:00:00', '09:00:00', 'consultation', 'ؤرلاىة', 'pending', '', '2025-06-16 18:50:16', '2025-06-16 18:50:16'),
(25, 3, 6, '2025-06-16 11:00:00', '11:00:00', 'consultation', 'قب', 'pending', '', '2025-06-16 18:51:01', '2025-06-16 18:51:01');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedule`
--

CREATE TABLE `doctor_schedule` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_schedule`
--

INSERT INTO `doctor_schedule` (`id`, `doctor_id`, `day_of_week`, `start_time`, `end_time`, `created_at`) VALUES
(18, 3, 'Monday', '09:00:00', '15:00:00', '2025-05-19 19:31:35'),
(19, 3, 'Friday', '09:00:00', '15:00:00', '2025-05-19 19:31:35'),
(22, 4, 'Tuesday', '09:00:00', '15:00:00', '2025-05-19 19:35:15'),
(23, 4, 'Thursday', '09:00:00', '15:00:00', '2025-05-19 19:35:15');

-- --------------------------------------------------------

--
-- Table structure for table `lab_tests`
--

CREATE TABLE `lab_tests` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `test_type` varchar(100) NOT NULL,
  `urgency` enum('routine','urgent') NOT NULL DEFAULT 'routine',
  `status` enum('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `results` text DEFAULT NULL,
  `requested_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_date` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `result_pdf` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_tests`
--

INSERT INTO `lab_tests` (`id`, `patient_id`, `doctor_id`, `test_type`, `urgency`, `status`, `results`, `requested_date`, `completed_date`, `notes`, `created_at`, `result_pdf`) VALUES
(7, 6, 3, 'Urine Test', 'routine', 'completed', '{\"result_type\":\"normal\",\"details\":\"\\\\zsa\",\"recommendations\":\"\"}', '2025-05-14 17:06:34', '2025-05-15 20:58:58', 'fgh', '2025-05-14 17:06:34', 'uploads/results/result_68265592b40a42.32602107.pdf'),
(8, 6, 3, 'Urine Test', 'routine', 'completed', '{\"result_type\":\"normal\",\"details\":\"33\",\"recommendations\":\"\"}', '2025-05-14 17:44:37', '2025-06-10 19:26:32', '', '2025-05-14 17:44:37', 'uploads/results/result_684886e8d847f5.08896685.pdf'),
(9, 6, 3, 'X-Ray', 'routine', 'completed', '{\"result_type\":\"abnormal\",\"details\":\"abnormal\",\"recommendations\":\"\"}', '2025-05-16 19:17:52', '2025-06-16 17:51:15', 'right arm', '2025-05-16 19:17:52', 'uploads/results/result_685059935b78d8.42630147.pdf'),
(10, 6, 3, 'Urine Test', 'routine', 'completed', '{\"result_type\":\"normal\",\"details\":\"good\\r\\n\",\"recommendations\":\"\"}', '2025-06-10 19:25:45', '2025-06-10 19:27:20', '', '2025-06-10 19:25:45', 'uploads/results/result_684887182e7198.98226578.pdf'),
(11, 6, 3, 'MRI', 'routine', 'completed', '{\"result_type\":\"critical\",\"details\":\"criticl\",\"recommendations\":\"\"}', '2025-06-16 18:36:37', '2025-06-16 18:56:00', '', '2025-06-16 18:36:37', 'uploads/results/result_685068c07f69a4.74231742.pdf'),
(12, 6, 3, 'Urine Test', 'routine', 'completed', '{\"result_type\":\"critical\",\"details\":\"criticallllllllll\",\"recommendations\":\"\"}', '2025-06-18 21:42:28', '2025-06-18 21:43:14', '', '2025-06-18 21:42:28', 'uploads/results/result_685332f29821e5.44857240.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `medical_history`
--

CREATE TABLE `medical_history` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `condition_name` varchar(100) NOT NULL,
  `diagnosis_date` date NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','resolved','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_history`
--

INSERT INTO `medical_history` (`id`, `patient_id`, `condition_name`, `diagnosis_date`, `doctor_id`, `notes`, `status`, `created_at`) VALUES
(1, 4, 'Hypertension', '2024-01-15', 1, 'Patient shows elevated blood pressure. Prescribed medication and lifestyle changes.', 'active', '2025-05-06 00:57:16'),
(2, 4, 'Type 2 Diabetes', '2024-02-01', 2, 'Regular monitoring of blood sugar levels required.', 'active', '2025-05-06 00:57:16');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `drug_ingredient` varchar(255) NOT NULL,
  `brand_name` varchar(255) NOT NULL,
  `drug_class` varchar(255) NOT NULL,
  `dosage_form` varchar(100) NOT NULL,
  `strength` varchar(100) NOT NULL,
  `drug_category` varchar(255) NOT NULL,
  `used_for_what` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `drug_ingredient`, `brand_name`, `drug_class`, `dosage_form`, `strength`, `drug_category`, `used_for_what`, `price`, `quantity`, `created_at`, `updated_at`) VALUES
(2, 'Atorvastatin', 'Disprin', 'NSAID', 'Injection', '500 mg', 'Respiratory', 'Bacterial Infections', 0.00, 25, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(3, 'Ciprofloxacin', 'Lipitor', 'Statin', 'Capsule', '1000 mg', 'Gastrointestinal', 'Fever', 0.00, 245, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(4, 'Omeprazole', 'Zestril', 'Statin', 'Suspension', '500 mg', 'Gastrointestinal', 'Heartburn', 0.00, 423, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(5, 'Omeprazole', 'Glucophage', 'Analgesic', 'Inhaler', '250 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 261, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(6, 'Lisinopril', 'Losec', 'Fluoroquinolone', 'Capsule', '500 mg', 'Pain Relief', 'Hypertension', 0.00, 53, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(7, 'Aspirin', 'Cipro', 'Antiplatelet', 'Suspension', '1000 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 184, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(8, 'Aspirin', 'Cipro', 'Proton Pump Inhibitor', 'Suspension', '1000 mg', 'Cardiovascular', 'Hypertension', 0.00, 451, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(9, 'Lisinopril', 'Zestril', 'Proton Pump Inhibitor', 'Inhaler', '1000 mg', 'Gastrointestinal', 'Type 2 Diabetes', 0.00, 115, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(10, 'Ibuprofen', 'Cipro', 'Antidiabetic', 'Capsule', '5 mg', 'Respiratory', 'Hypertension', 0.00, 449, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(11, 'Paracetamol', 'Lipitor', 'Antiplatelet', 'Tablet', '200 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 445, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(12, 'Atorvastatin', 'Ventolin', 'Antibiotic', 'Capsule', '1000 mg', 'Gastrointestinal', 'Type 2 Diabetes', 0.00, 154, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(13, 'Atorvastatin', 'Panadol', 'Bronchodilator', 'Suspension', '200 mg', 'Cardiovascular', 'Type 2 Diabetes', 0.00, 228, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(14, 'Salbutamol', 'Augmentin', 'Fluoroquinolone', 'Capsule', '10 mg', 'Gastrointestinal', 'Fever', 0.00, 242, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(15, 'Metformin', 'Augmentin', 'Analgesic', 'Injection', '250 mg', 'Gastrointestinal', 'Bacterial Infections', 0.00, 122, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(16, 'Metformin', 'Ventolin', 'Analgesic', 'Suspension', '500 mg', 'Diabetes Care', 'Hypertension', 0.00, 381, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(17, 'Lisinopril', 'Lipitor', 'Analgesic', 'Suspension', '10 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 493, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(18, 'Atorvastatin', 'Lipitor', 'Antiplatelet', 'Tablet', '250 mg', 'Respiratory', 'Hypertension', 0.00, 282, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(19, 'Ciprofloxacin', 'Disprin', 'NSAID', 'Tablet', '1000 mg', 'Cardiovascular', 'Fever', 0.00, 77, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(20, 'Atorvastatin', 'Lipitor', 'Antibiotic', 'Capsule', '500 mg', 'Pain Relief', 'Asthma', 0.00, 190, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(21, 'Ibuprofen', 'Losec', 'ACE Inhibitor', 'Tablet', '5 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 388, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(22, 'Lisinopril', 'Losec', 'Analgesic', 'Inhaler', '500 mg', 'Pain Relief', 'Hypertension', 0.00, 292, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(23, 'Ciprofloxacin', 'Augmentin', 'NSAID', 'Inhaler', '500 mg', 'Respiratory', 'Infection Control', 0.00, 435, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(24, 'Omeprazole', 'Lipitor', 'Bronchodilator', 'Suspension', '5 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 192, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(25, 'Amoxicillin', 'Cipro', 'Antibiotic', 'Suspension', '10 mg', 'Respiratory', 'Blood Clots', 0.00, 408, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(26, 'Amoxicillin', 'Cipro', 'Bronchodilator', 'Injection', '500 mg', 'Cardiovascular', 'Blood Clots', 0.00, 293, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(27, 'Paracetamol', 'Panadol', 'Proton Pump Inhibitor', 'Suspension', '1000 mg', 'Diabetes Care', 'Blood Clots', 0.00, 351, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(28, 'Lisinopril', 'Zestril', 'Antibiotic', 'Inhaler', '500 mg', 'Gastrointestinal', 'Heartburn', 0.00, 218, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(29, 'Ciprofloxacin', 'Panadol', 'NSAID', 'Syrup', '500 mg', 'Cardiovascular', 'Heartburn', 0.00, 105, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(30, 'Metformin', 'Advil', 'Proton Pump Inhibitor', 'Tablet', '250 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 294, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(31, 'Metformin', 'Losec', 'ACE Inhibitor', 'Injection', '20 mg', 'Pain Relief', 'Fever', 0.00, 251, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(32, 'Atorvastatin', 'Cipro', 'Bronchodilator', 'Injection', '10 mg', 'Respiratory', 'High Cholesterol', 0.00, 89, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(33, 'Omeprazole', 'Cipro', 'Antiplatelet', 'Injection', '20 mg', 'Gastrointestinal', 'Hypertension', 0.00, 438, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(34, 'Omeprazole', 'Augmentin', 'ACE Inhibitor', 'Injection', '5 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 82, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(35, 'Salbutamol', 'Glucophage', 'Analgesic', 'Capsule', '200 mg', 'Pain Relief', 'Infection Control', 0.00, 459, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(36, 'Salbutamol', 'Disprin', 'Antidiabetic', 'Injection', '100 mg', 'Diabetes Care', 'Blood Clots', 0.00, 443, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(37, 'Ibuprofen', 'Ventolin', 'Statin', 'Suspension', '1000 mg', 'Pain Relief', 'Blood Clots', 0.00, 239, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(38, 'Amoxicillin', 'Ventolin', 'Proton Pump Inhibitor', 'Capsule', '20 mg', 'Anti-Infective', 'Pain Relief', 0.00, 239, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(39, 'Atorvastatin', 'Glucophage', 'Antidiabetic', 'Suspension', '500 mg', 'Pain Relief', 'Infection Control', 0.00, 257, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(40, 'Paracetamol', 'Panadol', 'Analgesic', 'Suspension', '200 mg', 'Gastrointestinal', 'Pain Relief', 0.00, 152, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(41, 'Lisinopril', 'Lipitor', 'NSAID', 'Syrup', '100 mg', 'Diabetes Care', 'Type 2 Diabetes', 0.00, 485, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(42, 'Aspirin', 'Glucophage', 'NSAID', 'Injection', '5 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 190, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(43, 'Ciprofloxacin', 'Panadol', 'Antiplatelet', 'Tablet', '5 mg', 'Respiratory', 'Bacterial Infections', 0.00, 370, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(44, 'Ibuprofen', 'Panadol', 'Statin', 'Injection', '10 mg', 'Respiratory', 'Pain Relief', 0.00, 259, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(45, 'Aspirin', 'Panadol', 'Proton Pump Inhibitor', 'Syrup', '100 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 123, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(46, 'Lisinopril', 'Losec', 'Fluoroquinolone', 'Tablet', '1000 mg', 'Diabetes Care', 'Hypertension', 0.00, 127, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(47, 'Aspirin', 'Losec', 'Proton Pump Inhibitor', 'Inhaler', '250 mg', 'Cardiovascular', 'Hypertension', 0.00, 458, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(48, 'Salbutamol', 'Losec', 'Analgesic', 'Tablet', '250 mg', 'Diabetes Care', 'Hypertension', 0.00, 404, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(49, 'Salbutamol', 'Losec', 'Statin', 'Syrup', '250 mg', 'Anti-Infective', 'Blood Clots', 0.00, 148, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(50, 'Aspirin', 'Cipro', 'Antibiotic', 'Suspension', '200 mg', 'Cardiovascular', 'Infection Control', 0.00, 401, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(51, 'Omeprazole', 'Disprin', 'Proton Pump Inhibitor', 'Tablet', '5 mg', 'Pain Relief', 'Hypertension', 0.00, 68, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(52, 'Amoxicillin', 'Augmentin', 'Analgesic', 'Tablet', '5 mg', 'Pain Relief', 'Infection Control', 0.00, 175, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(53, 'Ciprofloxacin', 'Advil', 'NSAID', 'Suspension', '10 mg', 'Diabetes Care', 'Heartburn', 0.00, 486, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(54, 'Paracetamol', 'Disprin', 'Antidiabetic', 'Injection', '250 mg', 'Diabetes Care', 'Blood Clots', 0.00, 89, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(55, 'Paracetamol', 'Zestril', 'Antibiotic', 'Tablet', '100 mg', 'Cardiovascular', 'Blood Clots', 0.00, 36, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(56, 'Omeprazole', 'Losec', 'Statin', 'Injection', '200 mg', 'Diabetes Care', 'Bacterial Infections', 0.00, 165, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(57, 'Amoxicillin', 'Lipitor', 'Proton Pump Inhibitor', 'Inhaler', '200 mg', 'Diabetes Care', 'Blood Clots', 0.00, 204, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(58, 'Atorvastatin', 'Panadol', 'NSAID', 'Suspension', '200 mg', 'Anti-Infective', 'Asthma', 0.00, 434, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(59, 'Ciprofloxacin', 'Cipro', 'Bronchodilator', 'Inhaler', '100 mg', 'Gastrointestinal', 'Bacterial Infections', 0.00, 499, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(60, 'Paracetamol', 'Glucophage', 'Antibiotic', 'Syrup', '250 mg', 'Respiratory', 'High Cholesterol', 0.00, 322, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(61, 'Lisinopril', 'Disprin', 'Proton Pump Inhibitor', 'Capsule', '20 mg', 'Respiratory', 'High Cholesterol', 0.00, 84, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(62, 'Metformin', 'Advil', 'ACE Inhibitor', 'Suspension', '200 mg', 'Pain Relief', 'Heartburn', 0.00, 226, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(63, 'Omeprazole', 'Zestril', 'NSAID', 'Inhaler', '10 mg', 'Anti-Infective', 'Hypertension', 0.00, 356, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(64, 'Aspirin', 'Losec', 'NSAID', 'Inhaler', '20 mg', 'Gastrointestinal', 'Pain Relief', 0.00, 339, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(65, 'Omeprazole', 'Advil', 'NSAID', 'Suspension', '500 mg', 'Cardiovascular', 'Asthma', 0.00, 47, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(66, 'Amoxicillin', 'Advil', 'ACE Inhibitor', 'Syrup', '1000 mg', 'Gastrointestinal', 'Heartburn', 0.00, 168, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(67, 'Metformin', 'Zestril', 'Proton Pump Inhibitor', 'Syrup', '200 mg', 'Diabetes Care', 'Type 2 Diabetes', 0.00, 239, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(68, 'Paracetamol', 'Zestril', 'Analgesic', 'Suspension', '200 mg', 'Respiratory', 'Fever', 0.00, 341, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(69, 'Amoxicillin', 'Disprin', 'Bronchodilator', 'Capsule', '100 mg', 'Anti-Infective', 'Blood Clots', 0.00, 376, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(70, 'Atorvastatin', 'Panadol', 'Antidiabetic', 'Syrup', '20 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 50, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(71, 'Metformin', 'Advil', 'Antiplatelet', 'Capsule', '200 mg', 'Cardiovascular', 'Bacterial Infections', 0.00, 301, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(72, 'Metformin', 'Augmentin', 'Statin', 'Capsule', '20 mg', 'Respiratory', 'High Cholesterol', 0.00, 191, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(73, 'Atorvastatin', 'Ventolin', 'Statin', 'Inhaler', '500 mg', 'Anti-Infective', 'Asthma', 0.00, 217, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(74, 'Paracetamol', 'Zestril', 'NSAID', 'Capsule', '250 mg', 'Respiratory', 'Type 2 Diabetes', 0.00, 169, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(75, 'Metformin', 'Ventolin', 'NSAID', 'Syrup', '10 mg', 'Cardiovascular', 'Infection Control', 0.00, 332, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(76, 'Lisinopril', 'Panadol', 'Statin', 'Injection', '500 mg', 'Pain Relief', 'Blood Clots', 0.00, 498, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(77, 'Paracetamol', 'Ventolin', 'Fluoroquinolone', 'Inhaler', '250 mg', 'Diabetes Care', 'Infection Control', 0.00, 453, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(78, 'Ciprofloxacin', 'Losec', 'ACE Inhibitor', 'Inhaler', '200 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 262, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(79, 'Ciprofloxacin', 'Zestril', 'ACE Inhibitor', 'Inhaler', '20 mg', 'Pain Relief', 'Pain Relief', 0.00, 304, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(80, 'Lisinopril', 'Panadol', 'Proton Pump Inhibitor', 'Suspension', '5 mg', 'Gastrointestinal', 'Heartburn', 0.00, 497, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(81, 'Amoxicillin', 'Zestril', 'Fluoroquinolone', 'Injection', '10 mg', 'Anti-Infective', 'Bacterial Infections', 0.00, 250, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(82, 'Ciprofloxacin', 'Cipro', 'Antiplatelet', 'Inhaler', '1000 mg', 'Anti-Infective', 'Bacterial Infections', 0.00, 412, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(83, 'Ibuprofen', 'Losec', 'Statin', 'Capsule', '100 mg', 'Diabetes Care', 'Hypertension', 0.00, 160, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(84, 'Metformin', 'Glucophage', 'Proton Pump Inhibitor', 'Suspension', '1000 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 152, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(85, 'Omeprazole', 'Cipro', 'Proton Pump Inhibitor', 'Tablet', '250 mg', 'Pain Relief', 'Blood Clots', 0.00, 72, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(86, 'Lisinopril', 'Disprin', 'Statin', 'Inhaler', '5 mg', 'Diabetes Care', 'Infection Control', 0.00, 334, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(87, 'Metformin', 'Cipro', 'Antiplatelet', 'Tablet', '100 mg', 'Anti-Infective', 'Asthma', 0.00, 192, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(88, 'Paracetamol', 'Glucophage', 'Analgesic', 'Capsule', '100 mg', 'Pain Relief', 'Asthma', 0.00, 391, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(89, 'Atorvastatin', 'Zestril', 'Analgesic', 'Capsule', '200 mg', 'Pain Relief', 'Infection Control', 0.00, 67, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(90, 'Atorvastatin', 'Losec', 'ACE Inhibitor', 'Syrup', '20 mg', 'Pain Relief', 'Blood Clots', 0.00, 65, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(91, 'Omeprazole', 'Glucophage', 'NSAID', 'Injection', '250 mg', 'Respiratory', 'Infection Control', 0.00, 171, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(92, 'Ciprofloxacin', 'Disprin', 'Statin', 'Tablet', '100 mg', 'Pain Relief', 'Heartburn', 0.00, 164, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(93, 'Aspirin', 'Glucophage', 'Fluoroquinolone', 'Capsule', '250 mg', 'Cardiovascular', 'Asthma', 0.00, 49, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(94, 'Atorvastatin', 'Ventolin', 'NSAID', 'Syrup', '500 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 317, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(95, 'Omeprazole', 'Augmentin', 'Analgesic', 'Syrup', '100 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 382, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(96, 'Salbutamol', 'Ventolin', 'Statin', 'Tablet', '500 mg', 'Respiratory', 'Heartburn', 0.00, 484, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(97, 'Atorvastatin', 'Augmentin', 'Proton Pump Inhibitor', 'Syrup', '1000 mg', 'Gastrointestinal', 'Fever', 0.00, 218, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(98, 'Metformin', 'Glucophage', 'Antiplatelet', 'Syrup', '20 mg', 'Respiratory', 'Infection Control', 0.00, 253, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(99, 'Ciprofloxacin', 'Disprin', 'Statin', 'Capsule', '250 mg', 'Respiratory', 'Fever', 0.00, 231, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(100, 'Aspirin', 'Lipitor', 'Statin', 'Inhaler', '100 mg', 'Pain Relief', 'Blood Clots', 0.00, 400, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(101, 'Atorvastatin', 'Zestril', 'Fluoroquinolone', 'Injection', '1000 mg', 'Pain Relief', 'Infection Control', 0.00, 172, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(102, 'Metformin', 'Losec', 'Statin', 'Injection', '200 mg', 'Cardiovascular', 'Blood Clots', 0.00, 298, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(103, 'Ciprofloxacin', 'Glucophage', 'Analgesic', 'Suspension', '1000 mg', 'Pain Relief', 'Blood Clots', 0.00, 71, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(104, 'Paracetamol', 'Lipitor', 'Antidiabetic', 'Injection', '20 mg', 'Anti-Infective', 'Type 2 Diabetes', 0.00, 216, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(105, 'Amoxicillin', 'Augmentin', 'NSAID', 'Injection', '500 mg', 'Anti-Infective', 'High Cholesterol', 0.00, 232, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(106, 'Salbutamol', 'Lipitor', 'Antidiabetic', 'Inhaler', '20 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 300, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(107, 'Paracetamol', 'Panadol', 'Analgesic', 'Inhaler', '100 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 284, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(108, 'Paracetamol', 'Losec', 'NSAID', 'Injection', '1000 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 18, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(109, 'Amoxicillin', 'Glucophage', 'Antiplatelet', 'Suspension', '200 mg', 'Gastrointestinal', 'Infection Control', 0.00, 177, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(110, 'Ibuprofen', 'Augmentin', 'Proton Pump Inhibitor', 'Syrup', '500 mg', 'Pain Relief', 'Heartburn', 0.00, 471, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(111, 'Salbutamol', 'Disprin', 'Antibiotic', 'Inhaler', '1000 mg', 'Anti-Infective', 'Type 2 Diabetes', 0.00, 32, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(112, 'Ciprofloxacin', 'Zestril', 'Analgesic', 'Suspension', '100 mg', 'Gastrointestinal', 'Hypertension', 0.00, 61, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(113, 'Salbutamol', 'Lipitor', 'Antibiotic', 'Tablet', '200 mg', 'Cardiovascular', 'Fever', 0.00, 293, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(114, 'Paracetamol', 'Ventolin', 'ACE Inhibitor', 'Capsule', '5 mg', 'Anti-Infective', 'Bacterial Infections', 0.00, 283, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(115, 'Amoxicillin', 'Panadol', 'Proton Pump Inhibitor', 'Syrup', '500 mg', 'Respiratory', 'High Cholesterol', 0.00, 23, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(116, 'Lisinopril', 'Augmentin', 'Antibiotic', 'Capsule', '500 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 119, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(117, 'Omeprazole', 'Cipro', 'Antibiotic', 'Suspension', '1000 mg', 'Diabetes Care', 'Heartburn', 0.00, 185, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(118, 'Ibuprofen', 'Ventolin', 'Proton Pump Inhibitor', 'Inhaler', '200 mg', 'Diabetes Care', 'Fever', 0.00, 272, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(119, 'Omeprazole', 'Losec', 'Antiplatelet', 'Suspension', '10 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 455, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(120, 'Metformin', 'Panadol', 'Bronchodilator', 'Syrup', '250 mg', 'Respiratory', 'Infection Control', 0.00, 466, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(121, 'Metformin', 'Losec', 'Antiplatelet', 'Inhaler', '20 mg', 'Cardiovascular', 'Asthma', 0.00, 360, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(122, 'Lisinopril', 'Losec', 'ACE Inhibitor', 'Injection', '10 mg', 'Diabetes Care', 'Type 2 Diabetes', 0.00, 353, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(123, 'Paracetamol', 'Cipro', 'Antibiotic', 'Capsule', '20 mg', 'Cardiovascular', 'Bacterial Infections', 0.00, 115, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(124, 'Ibuprofen', 'Losec', 'Analgesic', 'Injection', '500 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 35, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(125, 'Lisinopril', 'Advil', 'Proton Pump Inhibitor', 'Injection', '20 mg', 'Pain Relief', 'Asthma', 0.00, 470, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(126, 'Amoxicillin', 'Losec', 'NSAID', 'Inhaler', '5 mg', 'Anti-Infective', 'Infection Control', 0.00, 242, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(127, 'Atorvastatin', 'Glucophage', 'NSAID', 'Capsule', '500 mg', 'Diabetes Care', 'Asthma', 0.00, 463, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(128, 'Lisinopril', 'Advil', 'Proton Pump Inhibitor', 'Syrup', '200 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 344, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(129, 'Ciprofloxacin', 'Glucophage', 'Antidiabetic', 'Inhaler', '5 mg', 'Diabetes Care', 'Heartburn', 0.00, 318, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(130, 'Ciprofloxacin', 'Panadol', 'Bronchodilator', 'Inhaler', '20 mg', 'Pain Relief', 'Fever', 0.00, 37, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(131, 'Omeprazole', 'Zestril', 'ACE Inhibitor', 'Tablet', '1000 mg', 'Diabetes Care', 'Pain Relief', 0.00, 353, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(132, 'Paracetamol', 'Glucophage', 'Antiplatelet', 'Suspension', '500 mg', 'Cardiovascular', 'Fever', 0.00, 327, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(133, 'Aspirin', 'Advil', 'ACE Inhibitor', 'Injection', '100 mg', 'Cardiovascular', 'Pain Relief', 0.00, 52, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(134, 'Aspirin', 'Disprin', 'NSAID', 'Syrup', '100 mg', 'Respiratory', 'Fever', 0.00, 346, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(135, 'Amoxicillin', 'Zestril', 'Proton Pump Inhibitor', 'Tablet', '100 mg', 'Respiratory', 'Asthma', 0.00, 12, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(136, 'Omeprazole', 'Zestril', 'ACE Inhibitor', 'Capsule', '250 mg', 'Respiratory', 'Heartburn', 0.00, 104, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(137, 'Ibuprofen', 'Lipitor', 'Analgesic', 'Inhaler', '20 mg', 'Respiratory', 'Pain Relief', 0.00, 430, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(138, 'Metformin', 'Augmentin', 'NSAID', 'Tablet', '10 mg', 'Anti-Infective', 'Blood Clots', 0.00, 153, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(139, 'Amoxicillin', 'Advil', 'Antidiabetic', 'Inhaler', '20 mg', 'Pain Relief', 'High Cholesterol', 0.00, 496, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(140, 'Omeprazole', 'Zestril', 'Proton Pump Inhibitor', 'Injection', '10 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 402, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(141, 'Lisinopril', 'Disprin', 'Statin', 'Capsule', '250 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 233, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(142, 'Amoxicillin', 'Disprin', 'Analgesic', 'Inhaler', '500 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 40, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(143, 'Ciprofloxacin', 'Zestril', 'Antiplatelet', 'Syrup', '250 mg', 'Gastrointestinal', 'Asthma', 0.00, 206, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(144, 'Paracetamol', 'Ventolin', 'Analgesic', 'Tablet', '5 mg', 'Diabetes Care', 'Infection Control', 0.00, 17, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(145, 'Omeprazole', 'Zestril', 'ACE Inhibitor', 'Injection', '1000 mg', 'Anti-Infective', 'Heartburn', 0.00, 39, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(146, 'Salbutamol', 'Augmentin', 'NSAID', 'Capsule', '1000 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 443, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(147, 'Lisinopril', 'Losec', 'Bronchodilator', 'Tablet', '1000 mg', 'Respiratory', 'Blood Clots', 0.00, 55, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(148, 'Atorvastatin', 'Cipro', 'ACE Inhibitor', 'Syrup', '20 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 462, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(149, 'Ibuprofen', 'Glucophage', 'NSAID', 'Syrup', '100 mg', 'Anti-Infective', 'Hypertension', 0.00, 469, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(150, 'Salbutamol', 'Losec', 'Fluoroquinolone', 'Injection', '250 mg', 'Gastrointestinal', 'Fever', 0.00, 21, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(151, 'Omeprazole', 'Augmentin', 'Analgesic', 'Capsule', '100 mg', 'Pain Relief', 'Hypertension', 0.00, 312, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(152, 'Metformin', 'Advil', 'Antibiotic', 'Inhaler', '200 mg', 'Pain Relief', 'High Cholesterol', 0.00, 58, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(153, 'Amoxicillin', 'Zestril', 'Statin', 'Inhaler', '20 mg', 'Gastrointestinal', 'Infection Control', 0.00, 15, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(154, 'Aspirin', 'Lipitor', 'Antibiotic', 'Injection', '500 mg', 'Respiratory', 'Infection Control', 0.00, 300, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(155, 'Amoxicillin', 'Lipitor', 'Antibiotic', 'Capsule', '500 mg', 'Cardiovascular', 'Bacterial Infections', 0.00, 491, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(156, 'Ciprofloxacin', 'Panadol', 'Antiplatelet', 'Injection', '20 mg', 'Respiratory', 'Pain Relief', 0.00, 121, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(157, 'Atorvastatin', 'Lipitor', 'Proton Pump Inhibitor', 'Tablet', '250 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 332, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(158, 'Lisinopril', 'Advil', 'Antiplatelet', 'Inhaler', '5 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 128, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(159, 'Lisinopril', 'Cipro', 'NSAID', 'Tablet', '10 mg', 'Respiratory', 'Infection Control', 0.00, 38, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(160, 'Metformin', 'Zestril', 'Proton Pump Inhibitor', 'Suspension', '100 mg', 'Respiratory', 'Fever', 0.00, 24, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(161, 'Paracetamol', 'Ventolin', 'Bronchodilator', 'Injection', '5 mg', 'Diabetes Care', 'Infection Control', 0.00, 71, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(162, 'Lisinopril', 'Disprin', 'Proton Pump Inhibitor', 'Syrup', '10 mg', 'Cardiovascular', 'Infection Control', 0.00, 287, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(163, 'Salbutamol', 'Disprin', 'Analgesic', 'Injection', '5 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 397, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(164, 'Paracetamol', 'Panadol', 'Statin', 'Tablet', '200 mg', 'Cardiovascular', 'Heartburn', 0.00, 34, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(165, 'Metformin', 'Disprin', 'Antibiotic', 'Capsule', '5 mg', 'Anti-Infective', 'Heartburn', 0.00, 399, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(166, 'Salbutamol', 'Augmentin', 'NSAID', 'Suspension', '200 mg', 'Diabetes Care', 'Blood Clots', 0.00, 26, '0000-00-00 00:00:00', '2025-06-16 19:09:26'),
(167, 'Atorvastatin', 'Disprin', 'NSAID', 'Inhaler', '1000 mg', 'Pain Relief', 'Blood Clots', 0.00, 195, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(168, 'Metformin', 'Lipitor', 'Antibiotic', 'Capsule', '200 mg', 'Cardiovascular', 'Asthma', 0.00, 350, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(169, 'Ciprofloxacin', 'Advil', 'Antidiabetic', 'Tablet', '200 mg', 'Cardiovascular', 'Pain Relief', 0.00, 84, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(170, 'Amoxicillin', 'Glucophage', 'Statin', 'Tablet', '10 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 393, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(171, 'Aspirin', 'Losec', 'NSAID', 'Capsule', '200 mg', 'Gastrointestinal', 'Fever', 0.00, 209, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(172, 'Aspirin', 'Advil', 'Statin', 'Inhaler', '500 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 137, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(173, 'Aspirin', 'Lipitor', 'Antidiabetic', 'Injection', '5 mg', 'Cardiovascular', 'Infection Control', 0.00, 367, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(174, 'Omeprazole', 'Zestril', 'Analgesic', 'Syrup', '500 mg', 'Respiratory', 'Infection Control', 0.00, 175, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(175, 'Salbutamol', 'Ventolin', 'Proton Pump Inhibitor', 'Injection', '100 mg', 'Gastrointestinal', 'Heartburn', 0.00, 494, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(176, 'Ibuprofen', 'Advil', 'NSAID', 'Syrup', '250 mg', 'Diabetes Care', 'Bacterial Infections', 0.00, 21, '0000-00-00 00:00:00', '2025-06-16 19:04:58'),
(177, 'Omeprazole', 'Augmentin', 'Statin', 'Injection', '100 mg', 'Respiratory', 'High Cholesterol', 0.00, 329, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(178, 'Ibuprofen', 'Panadol', 'Fluoroquinolone', 'Inhaler', '20 mg', 'Diabetes Care', 'Heartburn', 0.00, 494, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(179, 'Paracetamol', 'Augmentin', 'Antiplatelet', 'Inhaler', '100 mg', 'Anti-Infective', 'Heartburn', 0.00, 468, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(180, 'Lisinopril', 'Advil', 'Bronchodilator', 'Syrup', '200 mg', 'Anti-Infective', 'Heartburn', 0.00, 270, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(181, 'Atorvastatin', 'Augmentin', 'Bronchodilator', 'Inhaler', '1000 mg', 'Anti-Infective', 'Type 2 Diabetes', 0.00, 404, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(182, 'Ibuprofen', 'Panadol', 'Proton Pump Inhibitor', 'Injection', '200 mg', 'Cardiovascular', 'Fever', 0.00, 436, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(183, 'Ciprofloxacin', 'Ventolin', 'NSAID', 'Capsule', '1000 mg', 'Anti-Infective', 'Heartburn', 0.00, 35, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(184, 'Atorvastatin', 'Panadol', 'Fluoroquinolone', 'Suspension', '250 mg', 'Pain Relief', 'Blood Clots', 0.00, 298, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(185, 'Atorvastatin', 'Glucophage', 'Fluoroquinolone', 'Tablet', '20 mg', 'Gastrointestinal', 'Hypertension', 0.00, 32, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(186, 'Amoxicillin', 'Advil', 'Analgesic', 'Syrup', '20 mg', 'Diabetes Care', 'Fever', 0.00, 322, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(187, 'Amoxicillin', 'Lipitor', 'Antidiabetic', 'Syrup', '100 mg', 'Anti-Infective', 'Asthma', 0.00, 445, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(188, 'Metformin', 'Disprin', 'ACE Inhibitor', 'Suspension', '200 mg', 'Cardiovascular', 'Hypertension', 0.00, 390, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(189, 'Amoxicillin', 'Cipro', 'ACE Inhibitor', 'Injection', '500 mg', 'Pain Relief', 'Heartburn', 0.00, 202, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(190, 'Aspirin', 'Panadol', 'Analgesic', 'Suspension', '20 mg', 'Anti-Infective', 'Pain Relief', 0.00, 308, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(191, 'Amoxicillin', 'Augmentin', 'Fluoroquinolone', 'Suspension', '200 mg', 'Respiratory', 'Hypertension', 0.00, 64, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(192, 'Lisinopril', 'Advil', 'Antiplatelet', 'Suspension', '5 mg', 'Cardiovascular', 'Hypertension', 0.00, 290, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(193, 'Ciprofloxacin', 'Zestril', 'Fluoroquinolone', 'Inhaler', '250 mg', 'Respiratory', 'Pain Relief', 0.00, 310, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(194, 'Salbutamol', 'Losec', 'Analgesic', 'Injection', '10 mg', 'Anti-Infective', 'High Cholesterol', 0.00, 289, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(195, 'Paracetamol', 'Ventolin', 'Antibiotic', 'Injection', '1000 mg', 'Cardiovascular', 'Bacterial Infections', 0.00, 74, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(196, 'Atorvastatin', 'Advil', 'Fluoroquinolone', 'Suspension', '10 mg', 'Anti-Infective', 'High Cholesterol', 0.00, 70, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(197, 'Paracetamol', 'Augmentin', 'Fluoroquinolone', 'Syrup', '250 mg', 'Pain Relief', 'Asthma', 0.00, 209, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(198, 'Lisinopril', 'Panadol', 'NSAID', 'Suspension', '10 mg', 'Pain Relief', 'High Cholesterol', 0.00, 55, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(199, 'Aspirin', 'Glucophage', 'Antidiabetic', 'Injection', '100 mg', 'Pain Relief', 'Heartburn', 0.00, 314, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(200, 'Aspirin', 'Advil', 'Bronchodilator', 'Injection', '200 mg', 'Diabetes Care', 'Blood Clots', 0.00, 475, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(201, 'Omeprazole', 'Ventolin', 'Antiplatelet', 'Capsule', '250 mg', 'Anti-Infective', 'Heartburn', 0.00, 429, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(202, 'Atorvastatin', 'Glucophage', 'Antidiabetic', 'Capsule', '250 mg', 'Pain Relief', 'Pain Relief', 0.00, 238, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(203, 'Omeprazole', 'Zestril', 'NSAID', 'Suspension', '20 mg', 'Pain Relief', 'Fever', 0.00, 488, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(204, 'Lisinopril', 'Advil', 'Analgesic', 'Tablet', '1000 mg', 'Cardiovascular', 'Hypertension', 0.00, 434, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(205, 'Amoxicillin', 'Zestril', 'Antidiabetic', 'Suspension', '100 mg', 'Diabetes Care', 'Pain Relief', 0.00, 95, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(206, 'Lisinopril', 'Panadol', 'Proton Pump Inhibitor', 'Syrup', '10 mg', 'Anti-Infective', 'Type 2 Diabetes', 0.00, 274, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(207, 'Salbutamol', 'Lipitor', 'Analgesic', 'Injection', '500 mg', 'Diabetes Care', 'Bacterial Infections', 0.00, 437, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(208, 'Salbutamol', 'Disprin', 'Proton Pump Inhibitor', 'Suspension', '1000 mg', 'Diabetes Care', 'Blood Clots', 0.00, 320, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(209, 'Paracetamol', 'Advil', 'Antiplatelet', 'Inhaler', '500 mg', 'Diabetes Care', 'Fever', 0.00, 478, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(210, 'Metformin', 'Losec', 'Bronchodilator', 'Capsule', '200 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 134, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(211, 'Aspirin', 'Advil', 'Analgesic', 'Inhaler', '1000 mg', 'Cardiovascular', 'Infection Control', 0.00, 85, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(212, 'Ibuprofen', 'Glucophage', 'Antibiotic', 'Syrup', '200 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 384, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(213, 'Ciprofloxacin', 'Cipro', 'Antidiabetic', 'Tablet', '500 mg', 'Anti-Infective', 'Blood Clots', 0.00, 296, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(214, 'Aspirin', 'Augmentin', 'Fluoroquinolone', 'Injection', '200 mg', 'Respiratory', 'Pain Relief', 0.00, 262, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(215, 'Amoxicillin', 'Lipitor', 'NSAID', 'Suspension', '250 mg', 'Gastrointestinal', 'Pain Relief', 0.00, 57, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(216, 'Ciprofloxacin', 'Ventolin', 'NSAID', 'Injection', '100 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 115, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(217, 'Salbutamol', 'Glucophage', 'Antidiabetic', 'Capsule', '100 mg', 'Cardiovascular', 'Type 2 Diabetes', 0.00, 331, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(218, 'Aspirin', 'Glucophage', 'Proton Pump Inhibitor', 'Suspension', '500 mg', 'Respiratory', 'Type 2 Diabetes', 0.00, 417, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(219, 'Paracetamol', 'Zestril', 'NSAID', 'Syrup', '200 mg', 'Pain Relief', 'Infection Control', 0.00, 368, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(220, 'Salbutamol', 'Glucophage', 'ACE Inhibitor', 'Inhaler', '500 mg', 'Anti-Infective', 'Infection Control', 0.00, 226, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(221, 'Ciprofloxacin', 'Zestril', 'Antidiabetic', 'Capsule', '20 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 415, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(222, 'Salbutamol', 'Losec', 'Analgesic', 'Syrup', '1000 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 353, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(223, 'Salbutamol', 'Losec', 'Bronchodilator', 'Injection', '20 mg', 'Anti-Infective', 'Blood Clots', 0.00, 354, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(224, 'Salbutamol', 'Cipro', 'Fluoroquinolone', 'Suspension', '250 mg', 'Anti-Infective', 'Infection Control', 0.00, 58, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(225, 'Salbutamol', 'Augmentin', 'ACE Inhibitor', 'Injection', '1000 mg', 'Respiratory', 'Fever', 0.00, 424, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(226, 'Salbutamol', 'Losec', 'ACE Inhibitor', 'Injection', '100 mg', 'Respiratory', 'Heartburn', 0.00, 477, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(227, 'Salbutamol', 'Losec', 'Antibiotic', 'Injection', '250 mg', 'Gastrointestinal', 'Infection Control', 0.00, 201, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(228, 'Lisinopril', 'Panadol', 'Fluoroquinolone', 'Syrup', '250 mg', 'Diabetes Care', 'Fever', 0.00, 47, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(229, 'Ciprofloxacin', 'Cipro', 'Statin', 'Inhaler', '250 mg', 'Pain Relief', 'Blood Clots', 0.00, 211, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(230, 'Ibuprofen', 'Disprin', 'Fluoroquinolone', 'Syrup', '10 mg', 'Pain Relief', 'Hypertension', 0.00, 230, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(231, 'Atorvastatin', 'Glucophage', 'Analgesic', 'Capsule', '100 mg', 'Respiratory', 'Heartburn', 0.00, 49, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(232, 'Aspirin', 'Augmentin', 'Antiplatelet', 'Suspension', '500 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 165, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(233, 'Salbutamol', 'Advil', 'Statin', 'Injection', '10 mg', 'Anti-Infective', 'Hypertension', 0.00, 159, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(234, 'Salbutamol', 'Zestril', 'NSAID', 'Capsule', '250 mg', 'Respiratory', 'Infection Control', 0.00, 41, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(235, 'Aspirin', 'Glucophage', 'ACE Inhibitor', 'Capsule', '100 mg', 'Gastrointestinal', 'Fever', 0.00, 345, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(236, 'Metformin', 'Zestril', 'Antidiabetic', 'Suspension', '20 mg', 'Diabetes Care', 'Fever', 0.00, 336, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(237, 'Paracetamol', 'Augmentin', 'Analgesic', 'Inhaler', '20 mg', 'Anti-Infective', 'Pain Relief', 0.00, 140, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(238, 'Ibuprofen', 'Glucophage', 'ACE Inhibitor', 'Injection', '100 mg', 'Diabetes Care', 'Blood Clots', 0.00, 46, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(239, 'Ciprofloxacin', 'Lipitor', 'Antibiotic', 'Suspension', '250 mg', 'Cardiovascular', 'Infection Control', 0.00, 220, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(240, 'Omeprazole', 'Panadol', 'NSAID', 'Syrup', '200 mg', 'Respiratory', 'Pain Relief', 0.00, 258, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(241, 'Omeprazole', 'Losec', 'Proton Pump Inhibitor', 'Injection', '500 mg', 'Cardiovascular', 'Type 2 Diabetes', 0.00, 345, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(242, 'Metformin', 'Augmentin', 'Antiplatelet', 'Syrup', '200 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 254, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(243, 'Ibuprofen', 'Ventolin', 'Statin', 'Suspension', '20 mg', 'Anti-Infective', 'Type 2 Diabetes', 0.00, 327, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(244, 'Salbutamol', 'Lipitor', 'ACE Inhibitor', 'Inhaler', '1000 mg', 'Gastrointestinal', 'Heartburn', 0.00, 263, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(245, 'Atorvastatin', 'Advil', 'Antibiotic', 'Syrup', '500 mg', 'Cardiovascular', 'Pain Relief', 0.00, 259, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(246, 'Omeprazole', 'Advil', 'Analgesic', 'Syrup', '250 mg', 'Gastrointestinal', 'Pain Relief', 0.00, 400, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(247, 'Aspirin', 'Glucophage', 'Proton Pump Inhibitor', 'Tablet', '200 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 371, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(248, 'Aspirin', 'Zestril', 'Proton Pump Inhibitor', 'Suspension', '1000 mg', 'Pain Relief', 'High Cholesterol', 0.00, 262, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(249, 'Ibuprofen', 'Losec', 'Fluoroquinolone', 'Capsule', '200 mg', 'Respiratory', 'Type 2 Diabetes', 0.00, 424, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(250, 'Paracetamol', 'Cipro', 'Antibiotic', 'Tablet', '5 mg', 'Diabetes Care', 'Hypertension', 0.00, 306, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(251, 'Atorvastatin', 'Zestril', 'Antibiotic', 'Syrup', '10 mg', 'Diabetes Care', 'Pain Relief', 0.00, 42, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(252, 'Paracetamol', 'Zestril', 'Antibiotic', 'Tablet', '200 mg', 'Anti-Infective', 'Heartburn', 0.00, 430, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(253, 'Amoxicillin', 'Zestril', 'ACE Inhibitor', 'Injection', '200 mg', 'Respiratory', 'Pain Relief', 0.00, 295, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(254, 'Omeprazole', 'Augmentin', 'Statin', 'Suspension', '5 mg', 'Diabetes Care', 'Bacterial Infections', 0.00, 193, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(255, 'Amoxicillin', 'Panadol', 'Fluoroquinolone', 'Capsule', '20 mg', 'Pain Relief', 'Pain Relief', 0.00, 247, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(256, 'Ciprofloxacin', 'Lipitor', 'Proton Pump Inhibitor', 'Injection', '5 mg', 'Anti-Infective', 'Blood Clots', 0.00, 126, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(257, 'Ciprofloxacin', 'Disprin', 'Antibiotic', 'Syrup', '10 mg', 'Cardiovascular', 'Heartburn', 0.00, 11, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(258, 'Lisinopril', 'Panadol', 'Proton Pump Inhibitor', 'Inhaler', '20 mg', 'Diabetes Care', 'Asthma', 0.00, 349, '0000-00-00 00:00:00', '2025-06-16 19:00:58'),
(259, 'Salbutamol', 'Lipitor', 'ACE Inhibitor', 'Capsule', '500 mg', 'Respiratory', 'Infection Control', 0.00, 304, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(260, 'Ibuprofen', 'Glucophage', 'Statin', 'Tablet', '200 mg', 'Cardiovascular', 'Blood Clots', 0.00, 123, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(261, 'Aspirin', 'Lipitor', 'Antibiotic', 'Tablet', '5 mg', 'Respiratory', 'Infection Control', 0.00, 213, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(262, 'Amoxicillin', 'Losec', 'Antidiabetic', 'Suspension', '10 mg', 'Diabetes Care', 'Asthma', 0.00, 18, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(263, 'Metformin', 'Glucophage', 'Antibiotic', 'Syrup', '200 mg', 'Anti-Infective', 'Hypertension', 0.00, 17, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(265, 'Ciprofloxacin', 'Glucophage', 'Analgesic', 'Capsule', '20 mg', 'Gastrointestinal', 'Pain Relief', 0.00, 156, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(266, 'Aspirin', 'Zestril', 'Analgesic', 'Syrup', '100 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 117, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(267, 'Paracetamol', 'Advil', 'Antidiabetic', 'Injection', '20 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 289, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(268, 'Salbutamol', 'Panadol', 'Antiplatelet', 'Inhaler', '1000 mg', 'Respiratory', 'Hypertension', 0.00, 82, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(269, 'Omeprazole', 'Disprin', 'Antiplatelet', 'Injection', '250 mg', 'Pain Relief', 'High Cholesterol', 0.00, 377, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(270, 'Paracetamol', 'Glucophage', 'Fluoroquinolone', 'Suspension', '250 mg', 'Gastrointestinal', 'Heartburn', 0.00, 31, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(271, 'Paracetamol', 'Disprin', 'Analgesic', 'Capsule', '500 mg', 'Pain Relief', 'Infection Control', 0.00, 127, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(272, 'Omeprazole', 'Ventolin', 'Proton Pump Inhibitor', 'Syrup', '250 mg', 'Respiratory', 'Heartburn', 0.00, 12, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(273, 'Atorvastatin', 'Disprin', 'Antibiotic', 'Syrup', '250 mg', 'Respiratory', 'Asthma', 0.00, 252, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(274, 'Lisinopril', 'Augmentin', 'Antibiotic', 'Suspension', '250 mg', 'Gastrointestinal', 'Bacterial Infections', 0.00, 388, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(275, 'Ciprofloxacin', 'Panadol', 'Bronchodilator', 'Tablet', '100 mg', 'Diabetes Care', 'Bacterial Infections', 0.00, 23, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(276, 'Amoxicillin', 'Advil', 'NSAID', 'Inhaler', '20 mg', 'Cardiovascular', 'Hypertension', 0.00, 497, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(277, 'Paracetamol', 'Zestril', 'NSAID', 'Injection', '20 mg', 'Pain Relief', 'Fever', 0.00, 235, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(278, 'Aspirin', 'Ventolin', 'Fluoroquinolone', 'Syrup', '10 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 372, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(279, 'Omeprazole', 'Disprin', 'Antibiotic', 'Capsule', '200 mg', 'Cardiovascular', 'Blood Clots', 0.00, 339, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(280, 'Atorvastatin', 'Augmentin', 'Proton Pump Inhibitor', 'Inhaler', '200 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 214, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(281, 'Atorvastatin', 'Advil', 'Fluoroquinolone', 'Suspension', '20 mg', 'Respiratory', 'Asthma', 0.00, 29, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(282, 'Omeprazole', 'Zestril', 'Antibiotic', 'Syrup', '5 mg', 'Gastrointestinal', 'Fever', 0.00, 204, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(283, 'Omeprazole', 'Ventolin', 'Fluoroquinolone', 'Suspension', '500 mg', 'Gastrointestinal', 'Asthma', 0.00, 112, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(284, 'Paracetamol', 'Advil', 'ACE Inhibitor', 'Capsule', '20 mg', 'Diabetes Care', 'Hypertension', 0.00, 440, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(285, 'Metformin', 'Lipitor', 'Fluoroquinolone', 'Capsule', '500 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 30, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(286, 'Salbutamol', 'Panadol', 'Antiplatelet', 'Tablet', '5 mg', 'Pain Relief', 'Blood Clots', 0.00, 296, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(287, 'Ibuprofen', 'Cipro', 'Antiplatelet', 'Syrup', '1000 mg', 'Respiratory', 'Blood Clots', 0.00, 433, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(288, 'Ciprofloxacin', 'Disprin', 'Proton Pump Inhibitor', 'Injection', '100 mg', 'Cardiovascular', 'Fever', 0.00, 401, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(289, 'Ibuprofen', 'Ventolin', 'ACE Inhibitor', 'Tablet', '5 mg', 'Cardiovascular', 'Heartburn', 0.00, 492, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(290, 'Aspirin', 'Cipro', 'Fluoroquinolone', 'Capsule', '5 mg', 'Anti-Infective', 'High Cholesterol', 0.00, 223, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(291, 'Aspirin', 'Disprin', 'Antiplatelet', 'Tablet', '500 mg', 'Pain Relief', 'Infection Control', 0.00, 56, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(292, 'drug_ingredient', 'brand_name', 'drug_class', 'dosage_form', 'strength', 'drug_category', 'used_for_what', 0.00, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(293, 'Metformin', 'Zestril', 'Antidiabetic', 'Syrup', '20 mg', 'Diabetes Care', 'Hypertension', 0.00, 270, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(294, 'Atorvastatin', 'Disprin', 'NSAID', 'Injection', '500 mg', 'Respiratory', 'Bacterial Infections', 0.00, 25, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(295, 'Ciprofloxacin', 'Lipitor', 'Statin', 'Capsule', '1000 mg', 'Gastrointestinal', 'Fever', 0.00, 245, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(296, 'Omeprazole', 'Zestril', 'Statin', 'Suspension', '500 mg', 'Gastrointestinal', 'Heartburn', 0.00, 423, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(297, 'Omeprazole', 'Glucophage', 'Analgesic', 'Inhaler', '250 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 261, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(298, 'Lisinopril', 'Losec', 'Fluoroquinolone', 'Capsule', '500 mg', 'Pain Relief', 'Hypertension', 0.00, 53, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(299, 'Aspirin', 'Cipro', 'Antiplatelet', 'Suspension', '1000 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 184, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(300, 'Aspirin', 'Cipro', 'Proton Pump Inhibitor', 'Suspension', '1000 mg', 'Cardiovascular', 'Hypertension', 0.00, 451, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(301, 'Lisinopril', 'Zestril', 'Proton Pump Inhibitor', 'Inhaler', '1000 mg', 'Gastrointestinal', 'Type 2 Diabetes', 0.00, 115, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(302, 'Ibuprofen', 'Cipro', 'Antidiabetic', 'Capsule', '5 mg', 'Respiratory', 'Hypertension', 0.00, 449, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(303, 'Paracetamol', 'Lipitor', 'Antiplatelet', 'Tablet', '200 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 445, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(304, 'Atorvastatin', 'Ventolin', 'Antibiotic', 'Capsule', '1000 mg', 'Gastrointestinal', 'Type 2 Diabetes', 0.00, 154, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(305, 'Atorvastatin', 'Panadol', 'Bronchodilator', 'Suspension', '200 mg', 'Cardiovascular', 'Type 2 Diabetes', 0.00, 228, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(306, 'Salbutamol', 'Augmentin', 'Fluoroquinolone', 'Capsule', '10 mg', 'Gastrointestinal', 'Fever', 0.00, 242, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(307, 'Metformin', 'Augmentin', 'Analgesic', 'Injection', '250 mg', 'Gastrointestinal', 'Bacterial Infections', 0.00, 122, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(308, 'Metformin', 'Ventolin', 'Analgesic', 'Suspension', '500 mg', 'Diabetes Care', 'Hypertension', 0.00, 381, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(309, 'Lisinopril', 'Lipitor', 'Analgesic', 'Suspension', '10 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 493, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(310, 'Atorvastatin', 'Lipitor', 'Antiplatelet', 'Tablet', '250 mg', 'Respiratory', 'Hypertension', 0.00, 282, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(311, 'Ciprofloxacin', 'Disprin', 'NSAID', 'Tablet', '1000 mg', 'Cardiovascular', 'Fever', 0.00, 77, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(312, 'Atorvastatin', 'Lipitor', 'Antibiotic', 'Capsule', '500 mg', 'Pain Relief', 'Asthma', 0.00, 190, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(313, 'Ibuprofen', 'Losec', 'ACE Inhibitor', 'Tablet', '5 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 388, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(314, 'Lisinopril', 'Losec', 'Analgesic', 'Inhaler', '500 mg', 'Pain Relief', 'Hypertension', 0.00, 292, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(315, 'Ciprofloxacin', 'Augmentin', 'NSAID', 'Inhaler', '500 mg', 'Respiratory', 'Infection Control', 0.00, 435, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(316, 'Omeprazole', 'Lipitor', 'Bronchodilator', 'Suspension', '5 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 192, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(317, 'Amoxicillin', 'Cipro', 'Antibiotic', 'Suspension', '10 mg', 'Respiratory', 'Blood Clots', 0.00, 408, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(318, 'Amoxicillin', 'Cipro', 'Bronchodilator', 'Injection', '500 mg', 'Cardiovascular', 'Blood Clots', 0.00, 293, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(319, 'Paracetamol', 'Panadol', 'Proton Pump Inhibitor', 'Suspension', '1000 mg', 'Diabetes Care', 'Blood Clots', 0.00, 351, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `medicines` (`id`, `drug_ingredient`, `brand_name`, `drug_class`, `dosage_form`, `strength`, `drug_category`, `used_for_what`, `price`, `quantity`, `created_at`, `updated_at`) VALUES
(320, 'Lisinopril', 'Zestril', 'Antibiotic', 'Inhaler', '500 mg', 'Gastrointestinal', 'Heartburn', 0.00, 218, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(321, 'Ciprofloxacin', 'Panadol', 'NSAID', 'Syrup', '500 mg', 'Cardiovascular', 'Heartburn', 0.00, 105, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(322, 'Metformin', 'Advil', 'Proton Pump Inhibitor', 'Tablet', '250 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 294, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(323, 'Metformin', 'Losec', 'ACE Inhibitor', 'Injection', '20 mg', 'Pain Relief', 'Fever', 0.00, 251, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(324, 'Atorvastatin', 'Cipro', 'Bronchodilator', 'Injection', '10 mg', 'Respiratory', 'High Cholesterol', 0.00, 89, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(325, 'Omeprazole', 'Cipro', 'Antiplatelet', 'Injection', '20 mg', 'Gastrointestinal', 'Hypertension', 0.00, 438, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(326, 'Omeprazole', 'Augmentin', 'ACE Inhibitor', 'Injection', '5 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 82, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(327, 'Salbutamol', 'Glucophage', 'Analgesic', 'Capsule', '200 mg', 'Pain Relief', 'Infection Control', 0.00, 459, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(328, 'Salbutamol', 'Disprin', 'Antidiabetic', 'Injection', '100 mg', 'Diabetes Care', 'Blood Clots', 0.00, 443, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(329, 'Ibuprofen', 'Ventolin', 'Statin', 'Suspension', '1000 mg', 'Pain Relief', 'Blood Clots', 0.00, 239, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(330, 'Amoxicillin', 'Ventolin', 'Proton Pump Inhibitor', 'Capsule', '20 mg', 'Anti-Infective', 'Pain Relief', 0.00, 239, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(331, 'Atorvastatin', 'Glucophage', 'Antidiabetic', 'Suspension', '500 mg', 'Pain Relief', 'Infection Control', 0.00, 257, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(332, 'Paracetamol', 'Panadol', 'Analgesic', 'Suspension', '200 mg', 'Gastrointestinal', 'Pain Relief', 0.00, 152, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(333, 'Lisinopril', 'Lipitor', 'NSAID', 'Syrup', '100 mg', 'Diabetes Care', 'Type 2 Diabetes', 0.00, 485, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(334, 'Aspirin', 'Glucophage', 'NSAID', 'Injection', '5 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 190, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(335, 'Ciprofloxacin', 'Panadol', 'Antiplatelet', 'Tablet', '5 mg', 'Respiratory', 'Bacterial Infections', 0.00, 370, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(336, 'Ibuprofen', 'Panadol', 'Statin', 'Injection', '10 mg', 'Respiratory', 'Pain Relief', 0.00, 259, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(337, 'Aspirin', 'Panadol', 'Proton Pump Inhibitor', 'Syrup', '100 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 123, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(338, 'Lisinopril', 'Losec', 'Fluoroquinolone', 'Tablet', '1000 mg', 'Diabetes Care', 'Hypertension', 0.00, 127, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(339, 'Aspirin', 'Losec', 'Proton Pump Inhibitor', 'Inhaler', '250 mg', 'Cardiovascular', 'Hypertension', 0.00, 458, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(340, 'Salbutamol', 'Losec', 'Analgesic', 'Tablet', '250 mg', 'Diabetes Care', 'Hypertension', 0.00, 404, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(341, 'Salbutamol', 'Losec', 'Statin', 'Syrup', '250 mg', 'Anti-Infective', 'Blood Clots', 0.00, 148, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(342, 'Aspirin', 'Cipro', 'Antibiotic', 'Suspension', '200 mg', 'Cardiovascular', 'Infection Control', 0.00, 401, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(343, 'Omeprazole', 'Disprin', 'Proton Pump Inhibitor', 'Tablet', '5 mg', 'Pain Relief', 'Hypertension', 0.00, 68, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(344, 'Amoxicillin', 'Augmentin', 'Analgesic', 'Tablet', '5 mg', 'Pain Relief', 'Infection Control', 0.00, 175, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(345, 'Ciprofloxacin', 'Advil', 'NSAID', 'Suspension', '10 mg', 'Diabetes Care', 'Heartburn', 0.00, 486, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(346, 'Paracetamol', 'Disprin', 'Antidiabetic', 'Injection', '250 mg', 'Diabetes Care', 'Blood Clots', 0.00, 89, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(347, 'Paracetamol', 'Zestril', 'Antibiotic', 'Tablet', '100 mg', 'Cardiovascular', 'Blood Clots', 0.00, 36, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(348, 'Omeprazole', 'Losec', 'Statin', 'Injection', '200 mg', 'Diabetes Care', 'Bacterial Infections', 0.00, 165, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(349, 'Amoxicillin', 'Lipitor', 'Proton Pump Inhibitor', 'Inhaler', '200 mg', 'Diabetes Care', 'Blood Clots', 0.00, 204, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(350, 'Atorvastatin', 'Panadol', 'NSAID', 'Suspension', '200 mg', 'Anti-Infective', 'Asthma', 0.00, 434, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(351, 'Ciprofloxacin', 'Cipro', 'Bronchodilator', 'Inhaler', '100 mg', 'Gastrointestinal', 'Bacterial Infections', 0.00, 499, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(352, 'Paracetamol', 'Glucophage', 'Antibiotic', 'Syrup', '250 mg', 'Respiratory', 'High Cholesterol', 0.00, 322, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(353, 'Lisinopril', 'Disprin', 'Proton Pump Inhibitor', 'Capsule', '20 mg', 'Respiratory', 'High Cholesterol', 0.00, 84, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(354, 'Metformin', 'Advil', 'ACE Inhibitor', 'Suspension', '200 mg', 'Pain Relief', 'Heartburn', 0.00, 226, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(355, 'Omeprazole', 'Zestril', 'NSAID', 'Inhaler', '10 mg', 'Anti-Infective', 'Hypertension', 0.00, 356, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(356, 'Aspirin', 'Losec', 'NSAID', 'Inhaler', '20 mg', 'Gastrointestinal', 'Pain Relief', 0.00, 339, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(357, 'Omeprazole', 'Advil', 'NSAID', 'Suspension', '500 mg', 'Cardiovascular', 'Asthma', 0.00, 47, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(358, 'Amoxicillin', 'Advil', 'ACE Inhibitor', 'Syrup', '1000 mg', 'Gastrointestinal', 'Heartburn', 0.00, 168, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(359, 'Metformin', 'Zestril', 'Proton Pump Inhibitor', 'Syrup', '200 mg', 'Diabetes Care', 'Type 2 Diabetes', 0.00, 239, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(360, 'Paracetamol', 'Zestril', 'Analgesic', 'Suspension', '200 mg', 'Respiratory', 'Fever', 0.00, 341, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(361, 'Amoxicillin', 'Disprin', 'Bronchodilator', 'Capsule', '100 mg', 'Anti-Infective', 'Blood Clots', 0.00, 376, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(362, 'Atorvastatin', 'Panadol', 'Antidiabetic', 'Syrup', '20 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 50, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(363, 'Metformin', 'Advil', 'Antiplatelet', 'Capsule', '200 mg', 'Cardiovascular', 'Bacterial Infections', 0.00, 301, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(364, 'Metformin', 'Augmentin', 'Statin', 'Capsule', '20 mg', 'Respiratory', 'High Cholesterol', 0.00, 191, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(365, 'Atorvastatin', 'Ventolin', 'Statin', 'Inhaler', '500 mg', 'Anti-Infective', 'Asthma', 0.00, 217, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(366, 'Paracetamol', 'Zestril', 'NSAID', 'Capsule', '250 mg', 'Respiratory', 'Type 2 Diabetes', 0.00, 169, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(367, 'Metformin', 'Ventolin', 'NSAID', 'Syrup', '10 mg', 'Cardiovascular', 'Infection Control', 0.00, 332, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(368, 'Lisinopril', 'Panadol', 'Statin', 'Injection', '500 mg', 'Pain Relief', 'Blood Clots', 0.00, 498, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(369, 'Paracetamol', 'Ventolin', 'Fluoroquinolone', 'Inhaler', '250 mg', 'Diabetes Care', 'Infection Control', 0.00, 453, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(370, 'Ciprofloxacin', 'Losec', 'ACE Inhibitor', 'Inhaler', '200 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 262, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(371, 'Ciprofloxacin', 'Zestril', 'ACE Inhibitor', 'Inhaler', '20 mg', 'Pain Relief', 'Pain Relief', 0.00, 304, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(372, 'Lisinopril', 'Panadol', 'Proton Pump Inhibitor', 'Suspension', '5 mg', 'Gastrointestinal', 'Heartburn', 0.00, 497, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(373, 'Amoxicillin', 'Zestril', 'Fluoroquinolone', 'Injection', '10 mg', 'Anti-Infective', 'Bacterial Infections', 0.00, 250, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(374, 'Ciprofloxacin', 'Cipro', 'Antiplatelet', 'Inhaler', '1000 mg', 'Anti-Infective', 'Bacterial Infections', 0.00, 412, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(375, 'Ibuprofen', 'Losec', 'Statin', 'Capsule', '100 mg', 'Diabetes Care', 'Hypertension', 0.00, 160, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(376, 'Metformin', 'Glucophage', 'Proton Pump Inhibitor', 'Suspension', '1000 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 152, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(377, 'Omeprazole', 'Cipro', 'Proton Pump Inhibitor', 'Tablet', '250 mg', 'Pain Relief', 'Blood Clots', 0.00, 72, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(378, 'Lisinopril', 'Disprin', 'Statin', 'Inhaler', '5 mg', 'Diabetes Care', 'Infection Control', 0.00, 334, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(379, 'Metformin', 'Cipro', 'Antiplatelet', 'Tablet', '100 mg', 'Anti-Infective', 'Asthma', 0.00, 192, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(380, 'Paracetamol', 'Glucophage', 'Analgesic', 'Capsule', '100 mg', 'Pain Relief', 'Asthma', 0.00, 391, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(381, 'Atorvastatin', 'Zestril', 'Analgesic', 'Capsule', '200 mg', 'Pain Relief', 'Infection Control', 0.00, 67, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(382, 'Atorvastatin', 'Losec', 'ACE Inhibitor', 'Syrup', '20 mg', 'Pain Relief', 'Blood Clots', 0.00, 65, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(383, 'Omeprazole', 'Glucophage', 'NSAID', 'Injection', '250 mg', 'Respiratory', 'Infection Control', 0.00, 171, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(384, 'Ciprofloxacin', 'Disprin', 'Statin', 'Tablet', '100 mg', 'Pain Relief', 'Heartburn', 0.00, 164, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(385, 'Aspirin', 'Glucophage', 'Fluoroquinolone', 'Capsule', '250 mg', 'Cardiovascular', 'Asthma', 0.00, 49, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(386, 'Atorvastatin', 'Ventolin', 'NSAID', 'Syrup', '500 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 317, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(387, 'Omeprazole', 'Augmentin', 'Analgesic', 'Syrup', '100 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 382, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(388, 'Salbutamol', 'Ventolin', 'Statin', 'Tablet', '500 mg', 'Respiratory', 'Heartburn', 0.00, 484, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(389, 'Atorvastatin', 'Augmentin', 'Proton Pump Inhibitor', 'Syrup', '1000 mg', 'Gastrointestinal', 'Fever', 0.00, 218, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(390, 'Metformin', 'Glucophage', 'Antiplatelet', 'Syrup', '20 mg', 'Respiratory', 'Infection Control', 0.00, 253, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(391, 'Ciprofloxacin', 'Disprin', 'Statin', 'Capsule', '250 mg', 'Respiratory', 'Fever', 0.00, 231, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(392, 'Aspirin', 'Lipitor', 'Statin', 'Inhaler', '100 mg', 'Pain Relief', 'Blood Clots', 0.00, 400, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(393, 'Atorvastatin', 'Zestril', 'Fluoroquinolone', 'Injection', '1000 mg', 'Pain Relief', 'Infection Control', 0.00, 172, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(394, 'Metformin', 'Losec', 'Statin', 'Injection', '200 mg', 'Cardiovascular', 'Blood Clots', 0.00, 298, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(395, 'Ciprofloxacin', 'Glucophage', 'Analgesic', 'Suspension', '1000 mg', 'Pain Relief', 'Blood Clots', 0.00, 71, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(396, 'Paracetamol', 'Lipitor', 'Antidiabetic', 'Injection', '20 mg', 'Anti-Infective', 'Type 2 Diabetes', 0.00, 216, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(397, 'Amoxicillin', 'Augmentin', 'NSAID', 'Injection', '500 mg', 'Anti-Infective', 'High Cholesterol', 0.00, 232, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(398, 'Salbutamol', 'Lipitor', 'Antidiabetic', 'Inhaler', '20 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 300, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(399, 'Paracetamol', 'Panadol', 'Analgesic', 'Inhaler', '100 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 284, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(400, 'Paracetamol', 'Losec', 'NSAID', 'Injection', '1000 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 18, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(401, 'Amoxicillin', 'Glucophage', 'Antiplatelet', 'Suspension', '200 mg', 'Gastrointestinal', 'Infection Control', 0.00, 177, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(402, 'Ibuprofen', 'Augmentin', 'Proton Pump Inhibitor', 'Syrup', '500 mg', 'Pain Relief', 'Heartburn', 0.00, 471, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(403, 'Salbutamol', 'Disprin', 'Antibiotic', 'Inhaler', '1000 mg', 'Anti-Infective', 'Type 2 Diabetes', 0.00, 32, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(404, 'Ciprofloxacin', 'Zestril', 'Analgesic', 'Suspension', '100 mg', 'Gastrointestinal', 'Hypertension', 0.00, 61, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(405, 'Salbutamol', 'Lipitor', 'Antibiotic', 'Tablet', '200 mg', 'Cardiovascular', 'Fever', 0.00, 293, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(406, 'Paracetamol', 'Ventolin', 'ACE Inhibitor', 'Capsule', '5 mg', 'Anti-Infective', 'Bacterial Infections', 0.00, 283, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(407, 'Amoxicillin', 'Panadol', 'Proton Pump Inhibitor', 'Syrup', '500 mg', 'Respiratory', 'High Cholesterol', 0.00, 23, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(408, 'Lisinopril', 'Augmentin', 'Antibiotic', 'Capsule', '500 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 119, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(409, 'Omeprazole', 'Cipro', 'Antibiotic', 'Suspension', '1000 mg', 'Diabetes Care', 'Heartburn', 0.00, 185, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(410, 'Ibuprofen', 'Ventolin', 'Proton Pump Inhibitor', 'Inhaler', '200 mg', 'Diabetes Care', 'Fever', 0.00, 272, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(411, 'Omeprazole', 'Losec', 'Antiplatelet', 'Suspension', '10 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 455, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(412, 'Metformin', 'Panadol', 'Bronchodilator', 'Syrup', '250 mg', 'Respiratory', 'Infection Control', 0.00, 466, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(413, 'Metformin', 'Losec', 'Antiplatelet', 'Inhaler', '20 mg', 'Cardiovascular', 'Asthma', 0.00, 360, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(414, 'Lisinopril', 'Losec', 'ACE Inhibitor', 'Injection', '10 mg', 'Diabetes Care', 'Type 2 Diabetes', 0.00, 353, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(415, 'Paracetamol', 'Cipro', 'Antibiotic', 'Capsule', '20 mg', 'Cardiovascular', 'Bacterial Infections', 0.00, 115, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(416, 'Ibuprofen', 'Losec', 'Analgesic', 'Injection', '500 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 35, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(417, 'Lisinopril', 'Advil', 'Proton Pump Inhibitor', 'Injection', '20 mg', 'Pain Relief', 'Asthma', 0.00, 470, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(418, 'Amoxicillin', 'Losec', 'NSAID', 'Inhaler', '5 mg', 'Anti-Infective', 'Infection Control', 0.00, 242, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(419, 'Atorvastatin', 'Glucophage', 'NSAID', 'Capsule', '500 mg', 'Diabetes Care', 'Asthma', 0.00, 463, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(420, 'Lisinopril', 'Advil', 'Proton Pump Inhibitor', 'Syrup', '200 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 344, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(421, 'Ciprofloxacin', 'Glucophage', 'Antidiabetic', 'Inhaler', '5 mg', 'Diabetes Care', 'Heartburn', 0.00, 318, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(422, 'Ciprofloxacin', 'Panadol', 'Bronchodilator', 'Inhaler', '20 mg', 'Pain Relief', 'Fever', 0.00, 37, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(423, 'Omeprazole', 'Zestril', 'ACE Inhibitor', 'Tablet', '1000 mg', 'Diabetes Care', 'Pain Relief', 0.00, 353, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(424, 'Paracetamol', 'Glucophage', 'Antiplatelet', 'Suspension', '500 mg', 'Cardiovascular', 'Fever', 0.00, 327, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(425, 'Aspirin', 'Advil', 'ACE Inhibitor', 'Injection', '100 mg', 'Cardiovascular', 'Pain Relief', 0.00, 52, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(426, 'Aspirin', 'Disprin', 'NSAID', 'Syrup', '100 mg', 'Respiratory', 'Fever', 0.00, 346, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(427, 'Amoxicillin', 'Zestril', 'Proton Pump Inhibitor', 'Tablet', '100 mg', 'Respiratory', 'Asthma', 0.00, 12, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(428, 'Omeprazole', 'Zestril', 'ACE Inhibitor', 'Capsule', '250 mg', 'Respiratory', 'Heartburn', 0.00, 104, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(429, 'Ibuprofen', 'Lipitor', 'Analgesic', 'Inhaler', '20 mg', 'Respiratory', 'Pain Relief', 0.00, 430, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(430, 'Metformin', 'Augmentin', 'NSAID', 'Tablet', '10 mg', 'Anti-Infective', 'Blood Clots', 0.00, 153, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(431, 'Amoxicillin', 'Advil', 'Antidiabetic', 'Inhaler', '20 mg', 'Pain Relief', 'High Cholesterol', 0.00, 496, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(432, 'Omeprazole', 'Zestril', 'Proton Pump Inhibitor', 'Injection', '10 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 402, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(433, 'Lisinopril', 'Disprin', 'Statin', 'Capsule', '250 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 233, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(434, 'Amoxicillin', 'Disprin', 'Analgesic', 'Inhaler', '500 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 40, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(435, 'Ciprofloxacin', 'Zestril', 'Antiplatelet', 'Syrup', '250 mg', 'Gastrointestinal', 'Asthma', 0.00, 206, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(436, 'Paracetamol', 'Ventolin', 'Analgesic', 'Tablet', '5 mg', 'Diabetes Care', 'Infection Control', 0.00, 17, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(437, 'Omeprazole', 'Zestril', 'ACE Inhibitor', 'Injection', '1000 mg', 'Anti-Infective', 'Heartburn', 0.00, 39, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(438, 'Salbutamol', 'Augmentin', 'NSAID', 'Capsule', '1000 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 443, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(439, 'Lisinopril', 'Losec', 'Bronchodilator', 'Tablet', '1000 mg', 'Respiratory', 'Blood Clots', 0.00, 55, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(440, 'Atorvastatin', 'Cipro', 'ACE Inhibitor', 'Syrup', '20 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 462, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(441, 'Ibuprofen', 'Glucophage', 'NSAID', 'Syrup', '100 mg', 'Anti-Infective', 'Hypertension', 0.00, 469, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(442, 'Salbutamol', 'Losec', 'Fluoroquinolone', 'Injection', '250 mg', 'Gastrointestinal', 'Fever', 0.00, 21, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(443, 'Omeprazole', 'Augmentin', 'Analgesic', 'Capsule', '100 mg', 'Pain Relief', 'Hypertension', 0.00, 312, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(444, 'Metformin', 'Advil', 'Antibiotic', 'Inhaler', '200 mg', 'Pain Relief', 'High Cholesterol', 0.00, 57, '0000-00-00 00:00:00', '2025-06-16 19:00:21'),
(445, 'Amoxicillin', 'Zestril', 'Statin', 'Inhaler', '20 mg', 'Gastrointestinal', 'Infection Control', 0.00, 15, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(446, 'Aspirin', 'Lipitor', 'Antibiotic', 'Injection', '500 mg', 'Respiratory', 'Infection Control', 0.00, 300, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(447, 'Amoxicillin', 'Lipitor', 'Antibiotic', 'Capsule', '500 mg', 'Cardiovascular', 'Bacterial Infections', 0.00, 491, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(448, 'Ciprofloxacin', 'Panadol', 'Antiplatelet', 'Injection', '20 mg', 'Respiratory', 'Pain Relief', 0.00, 121, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(449, 'Atorvastatin', 'Lipitor', 'Proton Pump Inhibitor', 'Tablet', '250 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 332, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(450, 'Lisinopril', 'Advil', 'Antiplatelet', 'Inhaler', '5 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 128, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(451, 'Lisinopril', 'Cipro', 'NSAID', 'Tablet', '10 mg', 'Respiratory', 'Infection Control', 0.00, 38, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(452, 'Metformin', 'Zestril', 'Proton Pump Inhibitor', 'Suspension', '100 mg', 'Respiratory', 'Fever', 0.00, 24, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(453, 'Paracetamol', 'Ventolin', 'Bronchodilator', 'Injection', '5 mg', 'Diabetes Care', 'Infection Control', 0.00, 71, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(454, 'Lisinopril', 'Disprin', 'Proton Pump Inhibitor', 'Syrup', '10 mg', 'Cardiovascular', 'Infection Control', 0.00, 287, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(455, 'Salbutamol', 'Disprin', 'Analgesic', 'Injection', '5 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 397, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(456, 'Paracetamol', 'Panadol', 'Statin', 'Tablet', '200 mg', 'Cardiovascular', 'Heartburn', 0.00, 34, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(457, 'Metformin', 'Disprin', 'Antibiotic', 'Capsule', '5 mg', 'Anti-Infective', 'Heartburn', 0.00, 399, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(458, 'Salbutamol', 'Augmentin', 'NSAID', 'Suspension', '200 mg', 'Diabetes Care', 'Blood Clots', 0.00, 16, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(459, 'Atorvastatin', 'Disprin', 'NSAID', 'Inhaler', '1000 mg', 'Pain Relief', 'Blood Clots', 0.00, 195, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(460, 'Metformin', 'Lipitor', 'Antibiotic', 'Capsule', '200 mg', 'Cardiovascular', 'Asthma', 0.00, 350, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(461, 'Ciprofloxacin', 'Advil', 'Antidiabetic', 'Tablet', '200 mg', 'Cardiovascular', 'Pain Relief', 0.00, 84, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(462, 'Amoxicillin', 'Glucophage', 'Statin', 'Tablet', '10 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 393, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(463, 'Aspirin', 'Losec', 'NSAID', 'Capsule', '200 mg', 'Gastrointestinal', 'Fever', 0.00, 209, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(464, 'Aspirin', 'Advil', 'Statin', 'Inhaler', '500 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 137, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(465, 'Aspirin', 'Lipitor', 'Antidiabetic', 'Injection', '5 mg', 'Cardiovascular', 'Infection Control', 0.00, 367, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(466, 'Omeprazole', 'Zestril', 'Analgesic', 'Syrup', '500 mg', 'Respiratory', 'Infection Control', 0.00, 175, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(467, 'Salbutamol', 'Ventolin', 'Proton Pump Inhibitor', 'Injection', '100 mg', 'Gastrointestinal', 'Heartburn', 0.00, 494, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(468, 'Ibuprofen', 'Advil', 'NSAID', 'Syrup', '250 mg', 'Diabetes Care', 'Bacterial Infections', 0.00, 41, '0000-00-00 00:00:00', '2025-06-16 19:05:21'),
(469, 'Omeprazole', 'Augmentin', 'Statin', 'Injection', '100 mg', 'Respiratory', 'High Cholesterol', 0.00, 329, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(470, 'Ibuprofen', 'Panadol', 'Fluoroquinolone', 'Inhaler', '20 mg', 'Diabetes Care', 'Heartburn', 0.00, 494, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(471, 'Paracetamol', 'Augmentin', 'Antiplatelet', 'Inhaler', '100 mg', 'Anti-Infective', 'Heartburn', 0.00, 468, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(472, 'Lisinopril', 'Advil', 'Bronchodilator', 'Syrup', '200 mg', 'Anti-Infective', 'Heartburn', 0.00, 270, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(473, 'Atorvastatin', 'Augmentin', 'Bronchodilator', 'Inhaler', '1000 mg', 'Anti-Infective', 'Type 2 Diabetes', 0.00, 404, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(474, 'Ibuprofen', 'Panadol', 'Proton Pump Inhibitor', 'Injection', '200 mg', 'Cardiovascular', 'Fever', 0.00, 436, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(475, 'Ciprofloxacin', 'Ventolin', 'NSAID', 'Capsule', '1000 mg', 'Anti-Infective', 'Heartburn', 0.00, 35, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(476, 'Atorvastatin', 'Panadol', 'Fluoroquinolone', 'Suspension', '250 mg', 'Pain Relief', 'Blood Clots', 0.00, 298, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(477, 'Atorvastatin', 'Glucophage', 'Fluoroquinolone', 'Tablet', '20 mg', 'Gastrointestinal', 'Hypertension', 0.00, 32, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(478, 'Amoxicillin', 'Advil', 'Analgesic', 'Syrup', '20 mg', 'Diabetes Care', 'Fever', 0.00, 322, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(479, 'Amoxicillin', 'Lipitor', 'Antidiabetic', 'Syrup', '100 mg', 'Anti-Infective', 'Asthma', 0.00, 445, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(480, 'Metformin', 'Disprin', 'ACE Inhibitor', 'Suspension', '200 mg', 'Cardiovascular', 'Hypertension', 0.00, 390, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(481, 'Amoxicillin', 'Cipro', 'ACE Inhibitor', 'Injection', '500 mg', 'Pain Relief', 'Heartburn', 0.00, 202, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(482, 'Aspirin', 'Panadol', 'Analgesic', 'Suspension', '20 mg', 'Anti-Infective', 'Pain Relief', 0.00, 308, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(483, 'Amoxicillin', 'Augmentin', 'Fluoroquinolone', 'Suspension', '200 mg', 'Respiratory', 'Hypertension', 0.00, 64, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(484, 'Lisinopril', 'Advil', 'Antiplatelet', 'Suspension', '5 mg', 'Cardiovascular', 'Hypertension', 0.00, 290, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(485, 'Ciprofloxacin', 'Zestril', 'Fluoroquinolone', 'Inhaler', '250 mg', 'Respiratory', 'Pain Relief', 0.00, 310, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(486, 'Salbutamol', 'Losec', 'Analgesic', 'Injection', '10 mg', 'Anti-Infective', 'High Cholesterol', 0.00, 289, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(487, 'Paracetamol', 'Ventolin', 'Antibiotic', 'Injection', '1000 mg', 'Cardiovascular', 'Bacterial Infections', 0.00, 74, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(488, 'Atorvastatin', 'Advil', 'Fluoroquinolone', 'Suspension', '10 mg', 'Anti-Infective', 'High Cholesterol', 0.00, 70, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(489, 'Paracetamol', 'Augmentin', 'Fluoroquinolone', 'Syrup', '250 mg', 'Pain Relief', 'Asthma', 0.00, 209, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(490, 'Lisinopril', 'Panadol', 'NSAID', 'Suspension', '10 mg', 'Pain Relief', 'High Cholesterol', 0.00, 55, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(491, 'Aspirin', 'Glucophage', 'Antidiabetic', 'Injection', '100 mg', 'Pain Relief', 'Heartburn', 0.00, 314, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(492, 'Aspirin', 'Advil', 'Bronchodilator', 'Injection', '200 mg', 'Diabetes Care', 'Blood Clots', 0.00, 475, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(493, 'Omeprazole', 'Ventolin', 'Antiplatelet', 'Capsule', '250 mg', 'Anti-Infective', 'Heartburn', 0.00, 429, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(494, 'Atorvastatin', 'Glucophage', 'Antidiabetic', 'Capsule', '250 mg', 'Pain Relief', 'Pain Relief', 0.00, 238, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(495, 'Omeprazole', 'Zestril', 'NSAID', 'Suspension', '20 mg', 'Pain Relief', 'Fever', 0.00, 488, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(496, 'Lisinopril', 'Advil', 'Analgesic', 'Tablet', '1000 mg', 'Cardiovascular', 'Hypertension', 0.00, 434, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(497, 'Amoxicillin', 'Zestril', 'Antidiabetic', 'Suspension', '100 mg', 'Diabetes Care', 'Pain Relief', 0.00, 95, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(498, 'Lisinopril', 'Panadol', 'Proton Pump Inhibitor', 'Syrup', '10 mg', 'Anti-Infective', 'Type 2 Diabetes', 0.00, 274, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(499, 'Salbutamol', 'Lipitor', 'Analgesic', 'Injection', '500 mg', 'Diabetes Care', 'Bacterial Infections', 0.00, 437, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(500, 'Salbutamol', 'Disprin', 'Proton Pump Inhibitor', 'Suspension', '1000 mg', 'Diabetes Care', 'Blood Clots', 0.00, 320, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(501, 'Paracetamol', 'Advil', 'Antiplatelet', 'Inhaler', '500 mg', 'Diabetes Care', 'Fever', 0.00, 478, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(502, 'Metformin', 'Losec', 'Bronchodilator', 'Capsule', '200 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 134, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(503, 'Aspirin', 'Advil', 'Analgesic', 'Inhaler', '1000 mg', 'Cardiovascular', 'Infection Control', 0.00, 85, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(504, 'Ibuprofen', 'Glucophage', 'Antibiotic', 'Syrup', '200 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 384, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(505, 'Ciprofloxacin', 'Cipro', 'Antidiabetic', 'Tablet', '500 mg', 'Anti-Infective', 'Blood Clots', 0.00, 296, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(506, 'Aspirin', 'Augmentin', 'Fluoroquinolone', 'Injection', '200 mg', 'Respiratory', 'Pain Relief', 0.00, 262, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(507, 'Amoxicillin', 'Lipitor', 'NSAID', 'Suspension', '250 mg', 'Gastrointestinal', 'Pain Relief', 0.00, 57, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(508, 'Ciprofloxacin', 'Ventolin', 'NSAID', 'Injection', '100 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 115, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(509, 'Salbutamol', 'Glucophage', 'Antidiabetic', 'Capsule', '100 mg', 'Cardiovascular', 'Type 2 Diabetes', 0.00, 331, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(510, 'Aspirin', 'Glucophage', 'Proton Pump Inhibitor', 'Suspension', '500 mg', 'Respiratory', 'Type 2 Diabetes', 0.00, 417, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(511, 'Paracetamol', 'Zestril', 'NSAID', 'Syrup', '200 mg', 'Pain Relief', 'Infection Control', 0.00, 368, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(512, 'Salbutamol', 'Glucophage', 'ACE Inhibitor', 'Inhaler', '500 mg', 'Anti-Infective', 'Infection Control', 0.00, 226, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(513, 'Ciprofloxacin', 'Zestril', 'Antidiabetic', 'Capsule', '20 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 415, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(514, 'Salbutamol', 'Losec', 'Analgesic', 'Syrup', '1000 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 353, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(515, 'Salbutamol', 'Losec', 'Bronchodilator', 'Injection', '20 mg', 'Anti-Infective', 'Blood Clots', 0.00, 354, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(516, 'Salbutamol', 'Cipro', 'Fluoroquinolone', 'Suspension', '250 mg', 'Anti-Infective', 'Infection Control', 0.00, 58, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(517, 'Salbutamol', 'Augmentin', 'ACE Inhibitor', 'Injection', '1000 mg', 'Respiratory', 'Fever', 0.00, 424, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(518, 'Salbutamol', 'Losec', 'ACE Inhibitor', 'Injection', '100 mg', 'Respiratory', 'Heartburn', 0.00, 477, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(519, 'Salbutamol', 'Losec', 'Antibiotic', 'Injection', '250 mg', 'Gastrointestinal', 'Infection Control', 0.00, 201, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(520, 'Lisinopril', 'Panadol', 'Fluoroquinolone', 'Syrup', '250 mg', 'Diabetes Care', 'Fever', 0.00, 47, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(521, 'Ciprofloxacin', 'Cipro', 'Statin', 'Inhaler', '250 mg', 'Pain Relief', 'Blood Clots', 0.00, 211, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(522, 'Ibuprofen', 'Disprin', 'Fluoroquinolone', 'Syrup', '10 mg', 'Pain Relief', 'Hypertension', 0.00, 230, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(523, 'Atorvastatin', 'Glucophage', 'Analgesic', 'Capsule', '100 mg', 'Respiratory', 'Heartburn', 0.00, 49, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(524, 'Aspirin', 'Augmentin', 'Antiplatelet', 'Suspension', '500 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 165, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(525, 'Salbutamol', 'Advil', 'Statin', 'Injection', '10 mg', 'Anti-Infective', 'Hypertension', 0.00, 159, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(526, 'Salbutamol', 'Zestril', 'NSAID', 'Capsule', '250 mg', 'Respiratory', 'Infection Control', 0.00, 41, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(527, 'Aspirin', 'Glucophage', 'ACE Inhibitor', 'Capsule', '100 mg', 'Gastrointestinal', 'Fever', 0.00, 345, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(528, 'Metformin', 'Zestril', 'Antidiabetic', 'Suspension', '20 mg', 'Diabetes Care', 'Fever', 0.00, 336, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(529, 'Paracetamol', 'Augmentin', 'Analgesic', 'Inhaler', '20 mg', 'Anti-Infective', 'Pain Relief', 0.00, 140, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(530, 'Ibuprofen', 'Glucophage', 'ACE Inhibitor', 'Injection', '100 mg', 'Diabetes Care', 'Blood Clots', 0.00, 46, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(531, 'Ciprofloxacin', 'Lipitor', 'Antibiotic', 'Suspension', '250 mg', 'Cardiovascular', 'Infection Control', 0.00, 220, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(532, 'Omeprazole', 'Panadol', 'NSAID', 'Syrup', '200 mg', 'Respiratory', 'Pain Relief', 0.00, 258, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(533, 'Omeprazole', 'Losec', 'Proton Pump Inhibitor', 'Injection', '500 mg', 'Cardiovascular', 'Type 2 Diabetes', 0.00, 345, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(534, 'Metformin', 'Augmentin', 'Antiplatelet', 'Syrup', '200 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 254, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(535, 'Ibuprofen', 'Ventolin', 'Statin', 'Suspension', '20 mg', 'Anti-Infective', 'Type 2 Diabetes', 0.00, 327, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(536, 'Salbutamol', 'Lipitor', 'ACE Inhibitor', 'Inhaler', '1000 mg', 'Gastrointestinal', 'Heartburn', 0.00, 263, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(537, 'Atorvastatin', 'Advil', 'Antibiotic', 'Syrup', '500 mg', 'Cardiovascular', 'Pain Relief', 0.00, 259, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(538, 'Omeprazole', 'Advil', 'Analgesic', 'Syrup', '250 mg', 'Gastrointestinal', 'Pain Relief', 0.00, 400, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(539, 'Aspirin', 'Glucophage', 'Proton Pump Inhibitor', 'Tablet', '200 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 371, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(540, 'Aspirin', 'Zestril', 'Proton Pump Inhibitor', 'Suspension', '1000 mg', 'Pain Relief', 'High Cholesterol', 0.00, 262, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(541, 'Ibuprofen', 'Losec', 'Fluoroquinolone', 'Capsule', '200 mg', 'Respiratory', 'Type 2 Diabetes', 0.00, 424, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(542, 'Paracetamol', 'Cipro', 'Antibiotic', 'Tablet', '5 mg', 'Diabetes Care', 'Hypertension', 0.00, 306, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(543, 'Atorvastatin', 'Zestril', 'Antibiotic', 'Syrup', '10 mg', 'Diabetes Care', 'Pain Relief', 0.00, 42, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(544, 'Paracetamol', 'Zestril', 'Antibiotic', 'Tablet', '200 mg', 'Anti-Infective', 'Heartburn', 0.00, 430, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(545, 'Amoxicillin', 'Zestril', 'ACE Inhibitor', 'Injection', '200 mg', 'Respiratory', 'Pain Relief', 0.00, 295, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(546, 'Omeprazole', 'Augmentin', 'Statin', 'Suspension', '5 mg', 'Diabetes Care', 'Bacterial Infections', 0.00, 193, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(547, 'Amoxicillin', 'Panadol', 'Fluoroquinolone', 'Capsule', '20 mg', 'Pain Relief', 'Pain Relief', 0.00, 247, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(548, 'Ciprofloxacin', 'Lipitor', 'Proton Pump Inhibitor', 'Injection', '5 mg', 'Anti-Infective', 'Blood Clots', 0.00, 126, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(549, 'Ciprofloxacin', 'Disprin', 'Antibiotic', 'Syrup', '10 mg', 'Cardiovascular', 'Heartburn', 0.00, 11, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(550, 'Lisinopril', 'Panadol', 'Proton Pump Inhibitor', 'Inhaler', '20 mg', 'Diabetes Care', 'Asthma', 0.00, 350, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(551, 'Salbutamol', 'Lipitor', 'ACE Inhibitor', 'Capsule', '500 mg', 'Respiratory', 'Infection Control', 0.00, 304, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(552, 'Ibuprofen', 'Glucophage', 'Statin', 'Tablet', '200 mg', 'Cardiovascular', 'Blood Clots', 0.00, 123, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(553, 'Aspirin', 'Lipitor', 'Antibiotic', 'Tablet', '5 mg', 'Respiratory', 'Infection Control', 0.00, 213, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(554, 'Amoxicillin', 'Losec', 'Antidiabetic', 'Suspension', '10 mg', 'Diabetes Care', 'Asthma', 0.00, 18, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(555, 'Metformin', 'Glucophage', 'Antibiotic', 'Syrup', '200 mg', 'Anti-Infective', 'Hypertension', 0.00, 17, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(556, 'Lisinopril', 'Advil', 'Bronchodilator', 'Injection', '1000 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 450, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(557, 'Ciprofloxacin', 'Glucophage', 'Analgesic', 'Capsule', '20 mg', 'Gastrointestinal', 'Pain Relief', 0.00, 156, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(558, 'Aspirin', 'Zestril', 'Analgesic', 'Syrup', '100 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 117, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(559, 'Paracetamol', 'Advil', 'Antidiabetic', 'Injection', '20 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 289, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(560, 'Salbutamol', 'Panadol', 'Antiplatelet', 'Inhaler', '1000 mg', 'Respiratory', 'Hypertension', 0.00, 82, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(561, 'Omeprazole', 'Disprin', 'Antiplatelet', 'Injection', '250 mg', 'Pain Relief', 'High Cholesterol', 0.00, 377, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(562, 'Paracetamol', 'Glucophage', 'Fluoroquinolone', 'Suspension', '250 mg', 'Gastrointestinal', 'Heartburn', 0.00, 31, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(563, 'Paracetamol', 'Disprin', 'Analgesic', 'Capsule', '500 mg', 'Pain Relief', 'Infection Control', 0.00, 127, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(564, 'Omeprazole', 'Ventolin', 'Proton Pump Inhibitor', 'Syrup', '250 mg', 'Respiratory', 'Heartburn', 0.00, 12, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(565, 'Atorvastatin', 'Disprin', 'Antibiotic', 'Syrup', '250 mg', 'Respiratory', 'Asthma', 0.00, 252, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(566, 'Lisinopril', 'Augmentin', 'Antibiotic', 'Suspension', '250 mg', 'Gastrointestinal', 'Bacterial Infections', 0.00, 388, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(567, 'Ciprofloxacin', 'Panadol', 'Bronchodilator', 'Tablet', '100 mg', 'Diabetes Care', 'Bacterial Infections', 0.00, 23, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(568, 'Amoxicillin', 'Advil', 'NSAID', 'Inhaler', '20 mg', 'Cardiovascular', 'Hypertension', 0.00, 497, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(569, 'Paracetamol', 'Zestril', 'NSAID', 'Injection', '20 mg', 'Pain Relief', 'Fever', 0.00, 235, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(570, 'Aspirin', 'Ventolin', 'Fluoroquinolone', 'Syrup', '10 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 372, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(571, 'Omeprazole', 'Disprin', 'Antibiotic', 'Capsule', '200 mg', 'Cardiovascular', 'Blood Clots', 0.00, 339, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(572, 'Atorvastatin', 'Augmentin', 'Proton Pump Inhibitor', 'Inhaler', '200 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 214, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(573, 'Atorvastatin', 'Advil', 'Fluoroquinolone', 'Suspension', '20 mg', 'Respiratory', 'Asthma', 0.00, 29, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(574, 'Omeprazole', 'Zestril', 'Antibiotic', 'Syrup', '5 mg', 'Gastrointestinal', 'Fever', 0.00, 204, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(575, 'Omeprazole', 'Ventolin', 'Fluoroquinolone', 'Suspension', '500 mg', 'Gastrointestinal', 'Asthma', 0.00, 112, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(576, 'Paracetamol', 'Advil', 'ACE Inhibitor', 'Capsule', '20 mg', 'Diabetes Care', 'Hypertension', 0.00, 440, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(577, 'Metformin', 'Lipitor', 'Fluoroquinolone', 'Capsule', '500 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 30, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(578, 'Salbutamol', 'Panadol', 'Antiplatelet', 'Tablet', '5 mg', 'Pain Relief', 'Blood Clots', 0.00, 296, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(579, 'Ibuprofen', 'Cipro', 'Antiplatelet', 'Syrup', '1000 mg', 'Respiratory', 'Blood Clots', 0.00, 433, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(580, 'Ciprofloxacin', 'Disprin', 'Proton Pump Inhibitor', 'Injection', '100 mg', 'Cardiovascular', 'Fever', 0.00, 401, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(581, 'Ibuprofen', 'Ventolin', 'ACE Inhibitor', 'Tablet', '5 mg', 'Cardiovascular', 'Heartburn', 0.00, 492, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(582, 'Aspirin', 'Cipro', 'Fluoroquinolone', 'Capsule', '5 mg', 'Anti-Infective', 'High Cholesterol', 0.00, 223, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(583, 'Aspirin', 'Disprin', 'Antiplatelet', 'Tablet', '500 mg', 'Pain Relief', 'Infection Control', 0.00, 56, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(584, 'Salbutamol', 'Lipitor', 'Fluoroquinolone', 'Inhaler', '200 mg', 'Gastrointestinal', 'Pain Relief', 0.00, 384, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(585, 'Amoxicillin', 'Ventolin', 'NSAID', 'Syrup', '200 mg', 'Diabetes Care', 'Blood Clots', 0.00, 346, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(586, 'Ibuprofen', 'Augmentin', 'Bronchodilator', 'Suspension', '1000 mg', 'Cardiovascular', 'Fever', 0.00, 252, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(587, 'Aspirin', 'Advil', 'NSAID', 'Inhaler', '5 mg', 'Cardiovascular', 'Hypertension', 0.00, 199, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(588, 'Omeprazole', 'Advil', 'Fluoroquinolone', 'Suspension', '1000 mg', 'Respiratory', 'Infection Control', 0.00, 397, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(589, 'Ciprofloxacin', 'Zestril', 'Proton Pump Inhibitor', 'Suspension', '1000 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 124, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(590, 'Omeprazole', 'Zestril', 'Analgesic', 'Inhaler', '500 mg', 'Cardiovascular', 'Blood Clots', 0.00, 355, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(591, 'Amoxicillin', 'Losec', 'Antibiotic', 'Capsule', '200 mg', 'Cardiovascular', 'Pain Relief', 0.00, 119, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(592, 'Metformin', 'Augmentin', 'Statin', 'Inhaler', '200 mg', 'Anti-Infective', 'Pain Relief', 0.00, 492, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(593, 'Ciprofloxacin', 'Losec', 'Antidiabetic', 'Syrup', '20 mg', 'Anti-Infective', 'Heartburn', 0.00, 353, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(594, 'Ibuprofen', 'Disprin', 'Analgesic', 'Inhaler', '100 mg', 'Cardiovascular', 'Hypertension', 0.00, 67, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(595, 'Ciprofloxacin', 'Disprin', 'Fluoroquinolone', 'Capsule', '20 mg', 'Anti-Infective', 'Type 2 Diabetes', 0.00, 349, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(596, 'Lisinopril', 'Ventolin', 'Antidiabetic', 'Syrup', '250 mg', 'Cardiovascular', 'Pain Relief', 0.00, 181, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(597, 'Aspirin', 'Augmentin', 'Analgesic', 'Inhaler', '10 mg', 'Respiratory', 'Pain Relief', 0.00, 213, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(598, 'Ibuprofen', 'Panadol', 'Antiplatelet', 'Injection', '20 mg', 'Cardiovascular', 'Heartburn', 0.00, 457, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(599, 'Salbutamol', 'Zestril', 'Bronchodilator', 'Tablet', '500 mg', 'Respiratory', 'Pain Relief', 0.00, 216, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(600, 'Metformin', 'Zestril', 'Analgesic', 'Injection', '500 mg', 'Diabetes Care', 'Heartburn', 0.00, 382, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(601, 'Ibuprofen', 'Disprin', 'Antidiabetic', 'Inhaler', '20 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 180, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(602, 'Amoxicillin', 'Disprin', 'Proton Pump Inhibitor', 'Capsule', '20 mg', 'Cardiovascular', 'Fever', 0.00, 43, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(603, 'Ciprofloxacin', 'Advil', 'Antibiotic', 'Inhaler', '200 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 355, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(604, 'Salbutamol', 'Losec', 'Fluoroquinolone', 'Tablet', '10 mg', 'Diabetes Care', 'Blood Clots', 0.00, 260, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(605, 'Ciprofloxacin', 'Zestril', 'Antiplatelet', 'Suspension', '200 mg', 'Anti-Infective', 'Pain Relief', 0.00, 402, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(606, 'Salbutamol', 'Lipitor', 'Fluoroquinolone', 'Capsule', '1000 mg', 'Pain Relief', 'Infection Control', 0.00, 421, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(607, 'Paracetamol', 'Augmentin', 'NSAID', 'Inhaler', '10 mg', 'Respiratory', 'Blood Clots', 0.00, 480, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(608, 'Ciprofloxacin', 'Zestril', 'Antiplatelet', 'Injection', '100 mg', 'Pain Relief', 'Hypertension', 0.00, 76, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(609, 'Ibuprofen', 'Ventolin', 'Antiplatelet', 'Syrup', '10 mg', 'Anti-Infective', 'Bacterial Infections', 0.00, 175, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(610, 'Amoxicillin', 'Lipitor', 'Bronchodilator', 'Suspension', '250 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 191, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(611, 'Paracetamol', 'Zestril', 'Antiplatelet', 'Capsule', '10 mg', 'Diabetes Care', 'Infection Control', 0.00, 367, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(612, 'Ibuprofen', 'Disprin', 'Fluoroquinolone', 'Suspension', '20 mg', 'Diabetes Care', 'Infection Control', 0.00, 279, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(613, 'Lisinopril', 'Losec', 'Fluoroquinolone', 'Suspension', '100 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 418, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(614, 'Aspirin', 'Panadol', 'Proton Pump Inhibitor', 'Inhaler', '100 mg', 'Diabetes Care', 'Heartburn', 0.00, 214, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(615, 'Amoxicillin', 'Panadol', 'Fluoroquinolone', 'Inhaler', '20 mg', 'Diabetes Care', 'Blood Clots', 0.00, 479, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(616, 'Paracetamol', 'Ventolin', 'Proton Pump Inhibitor', 'Inhaler', '1000 mg', 'Gastrointestinal', 'Heartburn', 0.00, 433, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(617, 'Omeprazole', 'Lipitor', 'Statin', 'Syrup', '5 mg', 'Pain Relief', 'Heartburn', 0.00, 363, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(618, 'Lisinopril', 'Losec', 'Analgesic', 'Syrup', '10 mg', 'Anti-Infective', 'Hypertension', 0.00, 459, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(619, 'Ciprofloxacin', 'Ventolin', 'Antidiabetic', 'Syrup', '500 mg', 'Anti-Infective', 'Type 2 Diabetes', 0.00, 296, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(620, 'Paracetamol', 'Glucophage', 'Statin', 'Suspension', '200 mg', 'Diabetes Care', 'Fever', 0.00, 495, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(621, 'Lisinopril', 'Zestril', 'Bronchodilator', 'Syrup', '1000 mg', 'Respiratory', 'Hypertension', 0.00, 70, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(622, 'Aspirin', 'Zestril', 'Analgesic', 'Injection', '20 mg', 'Anti-Infective', 'High Cholesterol', 0.00, 262, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(623, 'Atorvastatin', 'Lipitor', 'ACE Inhibitor', 'Tablet', '20 mg', 'Anti-Infective', 'Blood Clots', 0.00, 389, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(624, 'Salbutamol', 'Zestril', 'Analgesic', 'Suspension', '5 mg', 'Cardiovascular', 'Heartburn', 0.00, 257, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(625, 'Omeprazole', 'Zestril', 'Antiplatelet', 'Capsule', '250 mg', 'Gastrointestinal', 'Fever', 0.00, 72, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(626, 'Paracetamol', 'Cipro', 'Bronchodilator', 'Suspension', '10 mg', 'Diabetes Care', 'Type 2 Diabetes', 0.00, 211, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(627, 'Lisinopril', 'Augmentin', 'Antibiotic', 'Capsule', '20 mg', 'Anti-Infective', 'Pain Relief', 0.00, 219, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(628, 'Omeprazole', 'Ventolin', 'Bronchodilator', 'Suspension', '500 mg', 'Anti-Infective', 'Type 2 Diabetes', 0.00, 91, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(629, 'Ibuprofen', 'Disprin', 'Statin', 'Tablet', '250 mg', 'Anti-Infective', 'Infection Control', 0.00, 199, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(630, 'Metformin', 'Lipitor', 'Proton Pump Inhibitor', 'Capsule', '250 mg', 'Cardiovascular', 'Type 2 Diabetes', 0.00, 10, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(631, 'Omeprazole', 'Advil', 'Analgesic', 'Inhaler', '500 mg', 'Anti-Infective', 'Fever', 0.00, 65, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(632, 'Lisinopril', 'Glucophage', 'Antiplatelet', 'Inhaler', '10 mg', 'Gastrointestinal', 'Infection Control', 0.00, 390, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(633, 'Ciprofloxacin', 'Glucophage', 'Antibiotic', 'Suspension', '20 mg', 'Respiratory', 'Heartburn', 0.00, 210, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(634, 'Paracetamol', 'Panadol', 'Antiplatelet', 'Capsule', '250 mg', 'Anti-Infective', 'Fever', 0.00, 296, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(635, 'Metformin', 'Ventolin', 'Statin', 'Suspension', '10 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 423, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `medicines` (`id`, `drug_ingredient`, `brand_name`, `drug_class`, `dosage_form`, `strength`, `drug_category`, `used_for_what`, `price`, `quantity`, `created_at`, `updated_at`) VALUES
(636, 'Salbutamol', 'Augmentin', 'ACE Inhibitor', 'Capsule', '100 mg', 'Anti-Infective', 'Hypertension', 0.00, 210, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(637, 'Paracetamol', 'Panadol', 'NSAID', 'Injection', '5 mg', 'Gastrointestinal', 'Type 2 Diabetes', 0.00, 454, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(638, 'Lisinopril', 'Advil', 'Antiplatelet', 'Inhaler', '1000 mg', 'Pain Relief', 'Infection Control', 0.00, 408, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(639, 'Aspirin', 'Losec', 'Proton Pump Inhibitor', 'Syrup', '1000 mg', 'Respiratory', 'High Cholesterol', 0.00, 294, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(640, 'Paracetamol', 'Zestril', 'Bronchodilator', 'Tablet', '10 mg', 'Pain Relief', 'Heartburn', 0.00, 162, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(641, 'Ibuprofen', 'Glucophage', 'Bronchodilator', 'Capsule', '5 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 337, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(642, 'Salbutamol', 'Lipitor', 'Proton Pump Inhibitor', 'Inhaler', '500 mg', 'Pain Relief', 'Heartburn', 0.00, 174, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(643, 'Metformin', 'Lipitor', 'Statin', 'Suspension', '250 mg', 'Diabetes Care', 'Type 2 Diabetes', 0.00, 217, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(644, 'Atorvastatin', 'Losec', 'Proton Pump Inhibitor', 'Suspension', '5 mg', 'Cardiovascular', 'Infection Control', 0.00, 136, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(645, 'Aspirin', 'Advil', 'Antibiotic', 'Inhaler', '250 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 47, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(646, 'Atorvastatin', 'Losec', 'NSAID', 'Syrup', '10 mg', 'Anti-Infective', 'Heartburn', 0.00, 467, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(647, 'Lisinopril', 'Augmentin', 'Proton Pump Inhibitor', 'Inhaler', '500 mg', 'Gastrointestinal', 'Asthma', 0.00, 60, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(648, 'Metformin', 'Cipro', 'Proton Pump Inhibitor', 'Capsule', '200 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 340, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(649, 'Metformin', 'Advil', 'Bronchodilator', 'Injection', '5 mg', 'Pain Relief', 'Asthma', 0.00, 92, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(650, 'Omeprazole', 'Augmentin', 'Antidiabetic', 'Tablet', '200 mg', 'Respiratory', 'Fever', 0.00, 71, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(651, 'Ibuprofen', 'Cipro', 'Proton Pump Inhibitor', 'Inhaler', '250 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 280, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(652, 'Salbutamol', 'Zestril', 'Fluoroquinolone', 'Tablet', '250 mg', 'Respiratory', 'High Cholesterol', 0.00, 356, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(653, 'Omeprazole', 'Losec', 'Antidiabetic', 'Suspension', '500 mg', 'Cardiovascular', 'Pain Relief', 0.00, 105, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(654, 'Amoxicillin', 'Losec', 'ACE Inhibitor', 'Tablet', '1000 mg', 'Anti-Infective', 'Type 2 Diabetes', 0.00, 497, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(655, 'Aspirin', 'Lipitor', 'Analgesic', 'Tablet', '200 mg', 'Respiratory', 'Pain Relief', 0.00, 201, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(656, 'Ibuprofen', 'Ventolin', 'ACE Inhibitor', 'Capsule', '5 mg', 'Cardiovascular', 'Blood Clots', 0.00, 102, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(657, 'Aspirin', 'Panadol', 'Analgesic', 'Tablet', '1000 mg', 'Pain Relief', 'Pain Relief', 0.00, 480, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(658, 'Aspirin', 'Advil', 'Fluoroquinolone', 'Injection', '20 mg', 'Gastrointestinal', 'Heartburn', 0.00, 131, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(659, 'Omeprazole', 'Zestril', 'Antibiotic', 'Capsule', '10 mg', 'Anti-Infective', 'Hypertension', 0.00, 214, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(660, 'Amoxicillin', 'Cipro', 'ACE Inhibitor', 'Capsule', '200 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 220, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(661, 'Ibuprofen', 'Losec', 'Antidiabetic', 'Inhaler', '10 mg', 'Respiratory', 'Blood Clots', 0.00, 74, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(662, 'Ibuprofen', 'Ventolin', 'Proton Pump Inhibitor', 'Injection', '250 mg', 'Gastrointestinal', 'Infection Control', 0.00, 194, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(663, 'Amoxicillin', 'Disprin', 'Analgesic', 'Tablet', '250 mg', 'Gastrointestinal', 'Pain Relief', 0.00, 390, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(664, 'Lisinopril', 'Lipitor', 'Antibiotic', 'Capsule', '250 mg', 'Pain Relief', 'High Cholesterol', 0.00, 180, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(665, 'Salbutamol', 'Losec', 'NSAID', 'Capsule', '1000 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 134, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(666, 'Paracetamol', 'Cipro', 'Analgesic', 'Syrup', '20 mg', 'Cardiovascular', 'Bacterial Infections', 0.00, 300, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(667, 'Salbutamol', 'Disprin', 'Antibiotic', 'Injection', '20 mg', 'Diabetes Care', 'Heartburn', 0.00, 276, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(668, 'Atorvastatin', 'Advil', 'Proton Pump Inhibitor', 'Capsule', '1000 mg', 'Cardiovascular', 'Infection Control', 0.00, 394, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(669, 'Ciprofloxacin', 'Zestril', 'Antibiotic', 'Tablet', '100 mg', 'Cardiovascular', 'Bacterial Infections', 0.00, 408, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(670, 'Lisinopril', 'Losec', 'Antiplatelet', 'Suspension', '200 mg', 'Pain Relief', 'Infection Control', 0.00, 135, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(671, 'Lisinopril', 'Advil', 'Bronchodilator', 'Tablet', '5 mg', 'Gastrointestinal', 'Bacterial Infections', 0.00, 421, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(672, 'Metformin', 'Advil', 'Fluoroquinolone', 'Tablet', '1000 mg', 'Cardiovascular', 'Fever', 0.00, 491, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(673, 'Amoxicillin', 'Glucophage', 'Bronchodilator', 'Inhaler', '100 mg', 'Gastrointestinal', 'Infection Control', 0.00, 445, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(674, 'Metformin', 'Panadol', 'Antiplatelet', 'Inhaler', '20 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 452, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(675, 'Paracetamol', 'Advil', 'Analgesic', 'Inhaler', '1000 mg', 'Anti-Infective', 'Type 2 Diabetes', 0.00, 18, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(676, 'Aspirin', 'Augmentin', 'ACE Inhibitor', 'Syrup', '10 mg', 'Cardiovascular', 'Heartburn', 0.00, 166, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(677, 'Lisinopril', 'Panadol', 'ACE Inhibitor', 'Injection', '1000 mg', 'Respiratory', 'Type 2 Diabetes', 0.00, 494, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(678, 'Ciprofloxacin', 'Ventolin', 'Antiplatelet', 'Tablet', '20 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 26, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(679, 'Metformin', 'Panadol', 'Antibiotic', 'Suspension', '1000 mg', 'Cardiovascular', 'Heartburn', 0.00, 45, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(680, 'Metformin', 'Lipitor', 'Analgesic', 'Suspension', '1000 mg', 'Respiratory', 'Fever', 0.00, 239, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(681, 'Metformin', 'Losec', 'Statin', 'Capsule', '250 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 255, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(682, 'Aspirin', 'Lipitor', 'ACE Inhibitor', 'Capsule', '10 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 187, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(683, 'Atorvastatin', 'Augmentin', 'Fluoroquinolone', 'Inhaler', '250 mg', 'Respiratory', 'High Cholesterol', 0.00, 491, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(684, 'Aspirin', 'Glucophage', 'Analgesic', 'Syrup', '20 mg', 'Pain Relief', 'Hypertension', 0.00, 197, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(685, 'Atorvastatin', 'Losec', 'Antidiabetic', 'Inhaler', '20 mg', 'Pain Relief', 'Heartburn', 0.00, 159, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(686, 'Omeprazole', 'Disprin', 'Fluoroquinolone', 'Injection', '5 mg', 'Gastrointestinal', 'Pain Relief', 0.00, 322, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(687, 'Salbutamol', 'Augmentin', 'Proton Pump Inhibitor', 'Injection', '5 mg', 'Anti-Infective', 'Infection Control', 0.00, 326, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(688, 'Atorvastatin', 'Ventolin', 'ACE Inhibitor', 'Suspension', '200 mg', 'Gastrointestinal', 'Infection Control', 0.00, 495, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(689, 'Paracetamol', 'Ventolin', 'Antiplatelet', 'Injection', '100 mg', 'Gastrointestinal', 'Pain Relief', 0.00, 365, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(690, 'Ciprofloxacin', 'Advil', 'Fluoroquinolone', 'Injection', '200 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 83, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(691, 'Ciprofloxacin', 'Ventolin', 'Fluoroquinolone', 'Capsule', '10 mg', 'Cardiovascular', 'Type 2 Diabetes', 0.00, 161, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(692, 'Omeprazole', 'Losec', 'Fluoroquinolone', 'Tablet', '20 mg', 'Cardiovascular', 'Fever', 0.00, 44, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(693, 'Omeprazole', 'Augmentin', 'Analgesic', 'Injection', '5 mg', 'Anti-Infective', 'Asthma', 0.00, 157, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(694, 'Salbutamol', 'Lipitor', 'Antibiotic', 'Tablet', '5 mg', 'Diabetes Care', 'Heartburn', 0.00, 251, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(695, 'Lisinopril', 'Cipro', 'Proton Pump Inhibitor', 'Tablet', '250 mg', 'Cardiovascular', 'Asthma', 0.00, 463, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(696, 'Aspirin', 'Glucophage', 'Antiplatelet', 'Syrup', '20 mg', 'Cardiovascular', 'Pain Relief', 0.00, 460, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(697, 'Ciprofloxacin', 'Losec', 'Bronchodilator', 'Inhaler', '20 mg', 'Diabetes Care', 'Blood Clots', 0.00, 61, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(698, 'Lisinopril', 'Advil', 'Proton Pump Inhibitor', 'Suspension', '250 mg', 'Respiratory', 'Hypertension', 0.00, 66, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(699, 'Ibuprofen', 'Augmentin', 'Statin', 'Inhaler', '250 mg', 'Pain Relief', 'Type 2 Diabetes', 0.00, 297, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(700, 'Ibuprofen', 'Zestril', 'ACE Inhibitor', 'Syrup', '20 mg', 'Diabetes Care', 'Type 2 Diabetes', 0.00, 66, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(701, 'Atorvastatin', 'Glucophage', 'Antibiotic', 'Tablet', '10 mg', 'Diabetes Care', 'Blood Clots', 0.00, 193, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(702, 'Omeprazole', 'Ventolin', 'NSAID', 'Suspension', '200 mg', 'Pain Relief', 'Asthma', 0.00, 144, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(703, 'Paracetamol', 'Ventolin', 'Antiplatelet', 'Syrup', '500 mg', 'Gastrointestinal', 'Fever', 0.00, 355, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(704, 'Ibuprofen', 'Advil', 'Analgesic', 'Injection', '1000 mg', 'Diabetes Care', 'Blood Clots', 0.00, 142, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(705, 'Paracetamol', 'Advil', 'Statin', 'Inhaler', '5 mg', 'Respiratory', 'Asthma', 0.00, 177, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(706, 'Omeprazole', 'Disprin', 'Antiplatelet', 'Inhaler', '200 mg', 'Respiratory', 'Asthma', 0.00, 16, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(707, 'Lisinopril', 'Cipro', 'Proton Pump Inhibitor', 'Suspension', '10 mg', 'Diabetes Care', 'Heartburn', 0.00, 466, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(708, 'Metformin', 'Panadol', 'Antibiotic', 'Syrup', '10 mg', 'Respiratory', 'Fever', 0.00, 345, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(709, 'Omeprazole', 'Disprin', 'ACE Inhibitor', 'Injection', '5 mg', 'Cardiovascular', 'Blood Clots', 0.00, 84, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(710, 'Paracetamol', 'Advil', 'Antidiabetic', 'Inhaler', '500 mg', 'Pain Relief', 'High Cholesterol', 0.00, 371, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(711, 'Ibuprofen', 'Panadol', 'Antidiabetic', 'Injection', '20 mg', 'Respiratory', 'Bacterial Infections', 0.00, 461, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(712, 'Amoxicillin', 'Glucophage', 'ACE Inhibitor', 'Suspension', '1000 mg', 'Pain Relief', 'Hypertension', 0.00, 366, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(713, 'Aspirin', 'Cipro', 'Statin', 'Inhaler', '100 mg', 'Pain Relief', 'Infection Control', 0.00, 393, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(714, 'Metformin', 'Lipitor', 'NSAID', 'Tablet', '200 mg', 'Diabetes Care', 'Fever', 0.00, 388, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(715, 'Amoxicillin', 'Augmentin', 'Antidiabetic', 'Tablet', '5 mg', 'Cardiovascular', 'Fever', 0.00, 230, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(716, 'Salbutamol', 'Zestril', 'Bronchodilator', 'Tablet', '5 mg', 'Cardiovascular', 'Heartburn', 0.00, 319, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(717, 'Lisinopril', 'Zestril', 'ACE Inhibitor', 'Capsule', '5 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 274, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(718, 'Lisinopril', 'Augmentin', 'Antiplatelet', 'Syrup', '1000 mg', 'Gastrointestinal', 'Bacterial Infections', 0.00, 457, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(719, 'Atorvastatin', 'Disprin', 'Statin', 'Capsule', '20 mg', 'Respiratory', 'High Cholesterol', 0.00, 324, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(720, 'Atorvastatin', 'Advil', 'Proton Pump Inhibitor', 'Capsule', '500 mg', 'Gastrointestinal', 'Heartburn', 0.00, 132, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(721, 'Metformin', 'Zestril', 'Antibiotic', 'Syrup', '20 mg', 'Pain Relief', 'Pain Relief', 0.00, 330, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(722, 'Paracetamol', 'Advil', 'NSAID', 'Capsule', '500 mg', 'Respiratory', 'High Cholesterol', 0.00, 161, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(723, 'Ibuprofen', 'Zestril', 'Bronchodilator', 'Inhaler', '100 mg', 'Gastrointestinal', 'Fever', 0.00, 219, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(724, 'Salbutamol', 'Augmentin', 'Fluoroquinolone', 'Injection', '20 mg', 'Pain Relief', 'Infection Control', 0.00, 21, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(725, 'Paracetamol', 'Ventolin', 'ACE Inhibitor', 'Capsule', '100 mg', 'Respiratory', 'Pain Relief', 0.00, 100, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(726, 'Ibuprofen', 'Panadol', 'Proton Pump Inhibitor', 'Injection', '20 mg', 'Diabetes Care', 'Type 2 Diabetes', 0.00, 121, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(727, 'Lisinopril', 'Losec', 'Antidiabetic', 'Injection', '500 mg', 'Gastrointestinal', 'Asthma', 0.00, 454, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(728, 'Lisinopril', 'Losec', 'ACE Inhibitor', 'Tablet', '1000 mg', 'Pain Relief', 'Bacterial Infections', 0.00, 84, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(729, 'Omeprazole', 'Ventolin', 'NSAID', 'Syrup', '100 mg', 'Diabetes Care', 'Bacterial Infections', 0.00, 94, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(730, 'Omeprazole', 'Panadol', 'Fluoroquinolone', 'Tablet', '250 mg', 'Diabetes Care', 'Bacterial Infections', 0.00, 217, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(731, 'Lisinopril', 'Disprin', 'Fluoroquinolone', 'Inhaler', '10 mg', 'Anti-Infective', 'Infection Control', 0.00, 87, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(732, 'Paracetamol', 'Lipitor', 'ACE Inhibitor', 'Inhaler', '200 mg', 'Gastrointestinal', 'Pain Relief', 0.00, 348, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(733, 'Atorvastatin', 'Disprin', 'Proton Pump Inhibitor', 'Suspension', '20 mg', 'Diabetes Care', 'Heartburn', 0.00, 254, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(734, 'Salbutamol', 'Advil', 'Analgesic', 'Injection', '20 mg', 'Pain Relief', 'Pain Relief', 0.00, 419, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(735, 'Paracetamol', 'Panadol', 'Antiplatelet', 'Tablet', '5 mg', 'Respiratory', 'Blood Clots', 0.00, 183, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(736, 'Aspirin', 'Zestril', 'Bronchodilator', 'Tablet', '100 mg', 'Respiratory', 'Heartburn', 0.00, 497, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(737, 'Ibuprofen', 'Panadol', 'Statin', 'Suspension', '20 mg', 'Diabetes Care', 'Asthma', 0.00, 169, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(738, 'Ciprofloxacin', 'Advil', 'NSAID', 'Capsule', '20 mg', 'Pain Relief', 'Heartburn', 0.00, 251, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(739, 'Omeprazole', 'Lipitor', 'Analgesic', 'Syrup', '20 mg', 'Cardiovascular', 'Bacterial Infections', 0.00, 227, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(740, 'Amoxicillin', 'Ventolin', 'Antiplatelet', 'Injection', '10 mg', 'Cardiovascular', 'Asthma', 0.00, 387, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(741, 'Omeprazole', 'Cipro', 'Analgesic', 'Injection', '5 mg', 'Respiratory', 'Type 2 Diabetes', 0.00, 137, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(742, 'Paracetamol', 'Cipro', 'Analgesic', 'Inhaler', '100 mg', 'Respiratory', 'Pain Relief', 0.00, 222, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(743, 'Omeprazole', 'Augmentin', 'Antidiabetic', 'Inhaler', '100 mg', 'Gastrointestinal', 'Infection Control', 0.00, 242, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(744, 'Metformin', 'Losec', 'Analgesic', 'Suspension', '500 mg', 'Diabetes Care', 'Type 2 Diabetes', 0.00, 481, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(745, 'Atorvastatin', 'Glucophage', 'NSAID', 'Inhaler', '200 mg', 'Diabetes Care', 'Infection Control', 0.00, 119, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(746, 'Ciprofloxacin', 'Lipitor', 'Antidiabetic', 'Inhaler', '5 mg', 'Diabetes Care', 'Heartburn', 0.00, 63, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(747, 'Lisinopril', 'Disprin', 'Antibiotic', 'Inhaler', '250 mg', 'Diabetes Care', 'Bacterial Infections', 0.00, 235, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(748, 'Lisinopril', 'Augmentin', 'Proton Pump Inhibitor', 'Syrup', '250 mg', 'Cardiovascular', 'Heartburn', 0.00, 263, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(749, 'Metformin', 'Ventolin', 'Statin', 'Tablet', '10 mg', 'Respiratory', 'Bacterial Infections', 0.00, 58, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(750, 'Omeprazole', 'Advil', 'Statin', 'Capsule', '1000 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 164, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(751, 'Amoxicillin', 'Zestril', 'NSAID', 'Capsule', '20 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 468, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(752, 'Aspirin', 'Zestril', 'Analgesic', 'Tablet', '500 mg', 'Respiratory', 'Infection Control', 0.00, 171, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(753, 'Metformin', 'Ventolin', 'Analgesic', 'Suspension', '100 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 199, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(754, 'Ibuprofen', 'Zestril', 'Antidiabetic', 'Injection', '250 mg', 'Respiratory', 'Type 2 Diabetes', 0.00, 379, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(755, 'Amoxicillin', 'Disprin', 'ACE Inhibitor', 'Syrup', '250 mg', 'Pain Relief', 'Infection Control', 0.00, 203, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(756, 'Aspirin', 'Disprin', 'Fluoroquinolone', 'Syrup', '10 mg', 'Cardiovascular', 'Heartburn', 0.00, 89, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(757, 'Salbutamol', 'Augmentin', 'Proton Pump Inhibitor', 'Inhaler', '1000 mg', 'Cardiovascular', 'Blood Clots', 0.00, 67, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(758, 'Ibuprofen', 'Glucophage', 'Bronchodilator', 'Tablet', '500 mg', 'Diabetes Care', 'Blood Clots', 0.00, 272, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(759, 'Aspirin', 'Disprin', 'ACE Inhibitor', 'Inhaler', '20 mg', 'Respiratory', 'Heartburn', 0.00, 213, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(760, 'Aspirin', 'Losec', 'Antibiotic', 'Suspension', '1000 mg', 'Diabetes Care', 'Infection Control', 0.00, 336, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(761, 'Paracetamol', 'Losec', 'ACE Inhibitor', 'Capsule', '100 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 380, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(762, 'Ciprofloxacin', 'Disprin', 'Statin', 'Capsule', '250 mg', 'Diabetes Care', 'Heartburn', 0.00, 94, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(763, 'Paracetamol', 'Augmentin', 'NSAID', 'Inhaler', '100 mg', 'Diabetes Care', 'Blood Clots', 0.00, 465, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(764, 'Omeprazole', 'Cipro', 'Antibiotic', 'Inhaler', '200 mg', 'Diabetes Care', 'Blood Clots', 0.00, 131, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(765, 'Paracetamol', 'Advil', 'Analgesic', 'Tablet', '10 mg', 'Gastrointestinal', 'Pain Relief', 0.00, 379, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(766, 'Metformin', 'Losec', 'Bronchodilator', 'Injection', '200 mg', 'Diabetes Care', 'High Cholesterol', 0.00, 192, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(767, 'Lisinopril', 'Glucophage', 'NSAID', 'Inhaler', '500 mg', 'Pain Relief', 'Blood Clots', 0.00, 478, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(768, 'Ciprofloxacin', 'Advil', 'Fluoroquinolone', 'Injection', '1000 mg', 'Pain Relief', 'Fever', 3.00, 286, '0000-00-00 00:00:00', '2025-05-11 19:46:23'),
(769, 'Amoxicillin', 'Augmentin', 'Antidiabetic', 'Capsule', '5 mg', 'Pain Relief', 'Pain Relief', 0.00, 57, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(770, 'Salbutamol', 'Lipitor', 'Fluoroquinolone', 'Inhaler', '5 mg', 'Gastrointestinal', 'Hypertension', 0.00, 464, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(771, 'Aspirin', 'Zestril', 'NSAID', 'Capsule', '5 mg', 'Gastrointestinal', 'Fever', 0.00, 98, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(772, 'Ciprofloxacin', 'Cipro', 'NSAID', 'Injection', '1000 mg', 'Diabetes Care', 'Hypertension', 0.00, 250, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(773, 'Omeprazole', 'Zestril', 'Antiplatelet', 'Tablet', '250 mg', 'Cardiovascular', 'Blood Clots', 0.00, 23, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(774, 'Amoxicillin', 'Cipro', 'Bronchodilator', 'Inhaler', '1000 mg', 'Respiratory', 'Infection Control', 0.00, 434, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(775, 'Ciprofloxacin', 'Zestril', 'Statin', 'Suspension', '1000 mg', 'Diabetes Care', 'Hypertension', 0.00, 193, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(776, 'Metformin', 'Advil', 'Antibiotic', 'Suspension', '500 mg', 'Pain Relief', 'Pain Relief', 0.00, 454, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(777, 'Ibuprofen', 'Glucophage', 'Analgesic', 'Tablet', '250 mg', 'Respiratory', 'High Cholesterol', 0.00, 348, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(778, 'Ciprofloxacin', 'Lipitor', 'NSAID', 'Injection', '10 mg', 'Cardiovascular', 'High Cholesterol', 0.00, 66, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(779, 'Metformin', 'Augmentin', 'Bronchodilator', 'Capsule', '200 mg', 'Pain Relief', 'High Cholesterol', 0.00, 497, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(780, 'Aspirin', 'Advil', 'Antidiabetic', 'Tablet', '500 mg', 'Anti-Infective', 'Infection Control', 0.00, 86, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(781, 'Atorvastatin', 'Lipitor', 'Antidiabetic', 'Syrup', '100 mg', 'Gastrointestinal', 'Blood Clots', 0.00, 404, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(782, 'Omeprazole', 'Disprin', 'Antiplatelet', 'Injection', '5 mg', 'Respiratory', 'Asthma', 0.00, 302, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(783, 'Ciprofloxacin', 'Losec', 'Antiplatelet', 'Syrup', '100 mg', 'Diabetes Care', 'Infection Control', 0.00, 280, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(784, 'Amoxicillin', 'Disprin', 'Bronchodilator', 'Capsule', '100 mg', 'Gastrointestinal', 'Hypertension', 0.00, 185, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(785, 'Lisinopril', 'Ventolin', 'Statin', 'Syrup', '200 mg', 'Gastrointestinal', 'High Cholesterol', 0.00, 448, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(786, 'Ciprofloxacin', 'Zestril', 'Antibiotic', 'Capsule', '100 mg', 'Pain Relief', 'Pain Relief', 0.00, 169, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(787, 'Metformin', 'Glucophage', 'Analgesic', 'Syrup', '250 mg', 'Pain Relief', 'Fever', 0.00, 12, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(788, 'Atorvastatin', 'Losec', 'Statin', 'Capsule', '20 mg', 'Pain Relief', 'Asthma', 0.00, 51, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(789, 'Atorvastatin', 'Disprin', 'Statin', 'Capsule', '5 mg', 'Diabetes Care', 'Infection Control', 0.00, 311, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(790, 'Amoxicillin', 'Zestril', 'Proton Pump Inhibitor', 'Inhaler', '5 mg', 'Diabetes Care', 'Heartburn', 0.00, 406, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(791, 'Paracetamol', 'Panadol', 'Antidiabetic', 'Suspension', '100 mg', 'Respiratory', 'Blood Clots', 0.00, 267, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(792, 'Salbutamol', 'Augmentin', 'NSAID', 'Capsule', '500 mg', 'Cardiovascular', 'Pain Relief', 0.00, 329, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(793, 'paracitamol', 'adol', 'bills', 'bills', '1000 mg', 'Pain Relief', 'pain', 2.00, 21, '2025-05-06 02:27:48', '2025-06-16 19:02:51');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `prescribed_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `doctor_id`, `patient_id`, `notes`, `status`, `prescribed_date`, `created_at`) VALUES
(1, 1, 4, 'Hypertension management', 'completed', '2025-05-06 00:57:16', '2025-05-06 00:57:16'),
(2, 2, 4, 'Diabetes management', 'completed', '2025-05-06 00:57:16', '2025-05-06 00:57:16'),
(14, 3, 6, '', 'completed', '2025-05-06 02:25:49', '2025-05-06 02:25:49'),
(15, 3, 6, '', 'completed', '2025-05-07 08:33:16', '2025-05-07 08:33:16'),
(16, 3, 6, '', 'completed', '2025-05-09 16:41:16', '2025-05-09 16:41:16'),
(17, 3, 6, '', 'completed', '2025-05-11 19:41:50', '2025-05-11 19:41:50'),
(18, 3, 6, '', 'completed', '2025-05-14 16:51:04', '2025-05-14 16:51:04'),
(19, 3, 6, '', 'completed', '2025-05-14 17:07:15', '2025-05-14 17:07:15'),
(20, 3, 6, '', 'completed', '2025-05-16 19:16:34', '2025-05-16 19:16:34'),
(21, 3, 6, '', 'active', '2025-05-21 14:52:00', '2025-05-21 14:52:00'),
(22, 3, 6, '', 'active', '2025-06-10 18:19:22', '2025-06-10 18:19:22'),
(23, 4, 6, '', 'active', '2025-06-16 18:11:10', '2025-06-16 18:11:10'),
(24, 3, 6, '', 'active', '2025-06-17 02:45:52', '2025-06-17 02:45:52');

-- --------------------------------------------------------

--
-- Table structure for table `prescription_items`
--

CREATE TABLE `prescription_items` (
  `id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `dosage` varchar(50) NOT NULL,
  `frequency` varchar(50) NOT NULL,
  `duration` varchar(50) NOT NULL,
  `instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescription_items`
--

INSERT INTO `prescription_items` (`id`, `prescription_id`, `medicine_id`, `dosage`, `frequency`, `duration`, `instructions`, `created_at`) VALUES
(1, 14, 768, 'Standard dose', 'Advil (1000 mg)', '33', '', '2025-05-06 02:25:49'),
(2, 15, 793, 'Standard dose', 'adol (1000 mg)', '55', '', '2025-05-07 08:33:16'),
(3, 16, 768, 'Standard dose', 'Advil (1000 mg)', '22', '', '2025-05-09 16:41:16'),
(4, 17, 444, 'Standard dose', 'Advil (200 mg)', '22', '', '2025-05-11 19:41:50'),
(5, 18, 793, 'Standard dose', 'adol (1000 mg)', 'l;\'', '', '2025-05-14 16:51:04'),
(6, 19, 793, 'Standard dose', 'adol (1000 mg)', 'fg', '', '2025-05-14 17:07:15'),
(7, 20, 258, 'Standard dose', 'Panadol (20 mg)', '3', '', '2025-05-16 19:16:34'),
(8, 21, 793, 'Standard dose', 'adol (1000 mg)', 'h', '', '2025-05-21 14:52:00'),
(9, 22, 793, 'Standard dose', 'adol (1000 mg)', '22', '', '2025-06-10 18:19:22'),
(10, 23, 306, 'Standard dose', 'Augmentin (10 mg)', '2', '', '2025-06-16 18:11:10'),
(11, 24, 793, 'Standard dose', 'adol (1000 mg)', '2', '', '2025-06-17 02:45:52');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','doctor','pharmacist','lab','patient') NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','declined') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `specialization`, `phone`, `address`, `gender`, `date_of_birth`, `blood_group`, `emergency_contact`, `emergency_phone`, `allergies`, `profile_picture`, `created_at`, `status`) VALUES
(1, 'Ahmed ElShaikh', 'admin@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, '+201064072554', '100 Admin St', 'male', '1980-01-01', 'O+', 'Jane Smith', '333-222-1111', 'None', 'profile_1_1747241593.jpg', '2025-05-06 00:57:16', 'pending'),
(2, 'Ali Oleim', 'labtech@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lab', 'تحاليل', '+01069563650', 'sharkia', 'male', '1985-03-15', '', '', '', '', NULL, '2025-05-06 00:57:16', 'pending'),
(3, 'Ali Mohamed', 'doctor1@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'اسنان', '+201064072558', 'sharkia', 'male', '1990-01-01', '', '', '', '', 'download.jpg', '2025-05-06 00:57:16', 'pending'),
(4, 'Adham Fathy ', 'doctor2@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'عيون', '+201012602130', 'el obour', 'male', '1985-02-15', '', '', '', '', 'download2.jpg', '2025-05-06 00:57:16', 'pending'),
(6, 'Adham Alaa', 'patient1@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '', '+201063072554', 'Obour', 'male', '1975-04-25', '', '', '', '', NULL, '2025-05-06 00:57:16', 'approved'),
(7, 'Omar Hammam', 'patient2@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '', '+201005794673', 'Sherouk', 'male', '1980-05-30', '', '', '', '', NULL, '2025-05-06 00:57:16', 'declined'),
(8, 'Mina Mamdouh', 'pharmacist1@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pharmacist', '', '+201092887107', 'Moktam', 'male', '1985-07-15', '', '', '', '', NULL, '2025-05-06 00:57:16', 'pending'),
(13, 'Ghada Mohamed', 'pharmacist2@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pharmacist', '', '+201001274367', 'Moktam', 'female', '2000-03-05', '', '', '', '', NULL, '2025-05-16 18:39:02', 'pending'),
(19, 'Ahmed Mohamedd', 'patient3@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '', '+201552080680', 'sharkia', 'male', '2004-03-19', '', '', '', '', NULL, '2025-05-19 08:28:05', 'approved'),
(30, 'Clinton Moody', 'ryhyqe@mailinator.com', '$2y$10$szGCh87CY/BvuDcM32LFueQ8rSnGqEYdCW4m2O3vPhvBsN9Mecyc2', 'patient', NULL, '+201827903879', 'Dolore sunt ratione ', 'other', NULL, 'AB-', 'Lisandra Griffith', '+201937607331', 'Tenetur aliquip in o', NULL, '2025-06-17 02:34:58', 'approved'),
(32, 'Aladdin Benjamin', 'cacurewop@mailinator.com', '$2y$10$WOeg3mqufqn7WSRqSYp0OuX7.WX.xeVL3eW35R5N5qX2Bfw0lvpL6', 'patient', NULL, '+201135578763', 'Illo beatae dolore i', 'female', NULL, 'B-', 'George Mcmahon', '+201104891831', 'Aliquam quis odit do', NULL, '2025-06-17 08:31:21', 'pending'),
(33, 'Ainsley Lynn', 'qwe@qwe.com', '$2y$10$ZfY7WlXDl5t8Axbjs12uh.M//9a2KdrTgcXXuNENDagkArJmH8Dea', 'patient', NULL, '+201862125333', 'Nemo vel est volupt', 'male', NULL, 'O+', 'Sheila Holloway', '+201871116299', 'Sint est ad quo volu', NULL, '2025-06-17 09:09:34', 'approved');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `idx_doctor_appointments` (`doctor_id`,`appointment_date`,`slot_time`,`status`);

--
-- Indexes for table `doctor_schedule`
--
ALTER TABLE `doctor_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doctor_schedule` (`doctor_id`,`day_of_week`,`start_time`,`end_time`);

--
-- Indexes for table `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `medical_history`
--
ALTER TABLE `medical_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_id` (`prescription_id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `doctor_schedule`
--
ALTER TABLE `doctor_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `lab_tests`
--
ALTER TABLE `lab_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `medical_history`
--
ALTER TABLE `medical_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=795;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `prescription_items`
--
ALTER TABLE `prescription_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `doctor_schedule`
--
ALTER TABLE `doctor_schedule`
  ADD CONSTRAINT `fk_doctor_schedule_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD CONSTRAINT `lab_tests_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `lab_tests_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `medical_history`
--
ALTER TABLE `medical_history`
  ADD CONSTRAINT `medical_history_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `medical_history_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD CONSTRAINT `prescription_items_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`),
  ADD CONSTRAINT `prescription_items_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
