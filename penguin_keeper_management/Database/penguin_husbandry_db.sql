-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 27, 2026 at 07:49 AM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `penguin_husbandry_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_values` longtext DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `description`, `created_at`) VALUES
(1, 1, 'UPDATE', 'penguins', 11, '{\"name\":\"Abby\",\"species\":\"Humboldt Penguin\",\"sex\":\"Female\",\"hatch_date\":\"2024-02-15\",\"origin_type\":\"Captive-bred\",\"arrival_date\":\"2024-03-01\",\"enclosure_id\":2,\"status\":\"Quarantine\",\"notes\":\"Medical issue: respiratory fungal infections, mosquito-borne parasites.\"}', '{\"name\":\"Abby\",\"species\":\"Humboldt Penguin\",\"sex\":\"Female\",\"hatch_date\":\"2024-02-15\",\"origin_type\":\"Captive-bred\",\"arrival_date\":\"2024-03-01\",\"enclosure_id\":1,\"status\":\"Active\",\"notes\":null}', 'Updated penguin profile: P-011', '2026-11-20 15:31:49'),
(2, 1, 'UPDATE', 'penguins', 1, '{\"name\":\"Koda\",\"species\":\"Humboldt Penguin\",\"sex\":\"Male\",\"hatch_date\":\"2021-03-14\",\"origin_type\":\"Captive-bred\",\"arrival_date\":\"2021-03-14\",\"enclosure_id\":1,\"status\":\"Active\",\"notes\":null}', '{\"name\":\"Koda\",\"species\":\"Humboldt Penguin\",\"sex\":\"Male\",\"hatch_date\":null,\"origin_type\":\"Captive-bred\",\"arrival_date\":null,\"enclosure_id\":1,\"status\":\"Active\",\"notes\":null}', 'Updated penguin profile: P-001', '2026-11-20 15:39:41'),
(3, 1, 'UPDATE', 'penguins', 1, '{\"name\":\"Koda\",\"species\":\"Humboldt Penguin\",\"sex\":\"Male\",\"hatch_date\":null,\"origin_type\":\"Captive-bred\",\"arrival_date\":null,\"enclosure_id\":1,\"status\":\"Active\",\"notes\":null}', '{\"name\":\"Koda\",\"species\":\"Humboldt Penguin\",\"sex\":\"Male\",\"hatch_date\":\"2021-03-14\",\"origin_type\":\"Captive-bred\",\"arrival_date\":\"2021-03-14\",\"enclosure_id\":1,\"status\":\"Active\",\"notes\":null}', 'Updated penguin profile: P-001', '2026-11-20 15:41:53'),
(4, 1, 'UPDATE', 'penguins', 10, '{\"name\":\"Ruby\",\"species\":\"Humboldt Penguin\",\"sex\":\"Female\",\"hatch_date\":\"2024-02-15\",\"origin_type\":null,\"arrival_date\":\"2024-03-01\",\"enclosure_id\":1,\"status\":\"Active\",\"notes\":null}', '{\"name\":\"Ruby\",\"species\":\"Humboldt Penguin\",\"sex\":\"Female\",\"hatch_date\":\"2024-02-15\",\"origin_type\":\"Captive-bred\",\"arrival_date\":\"2024-03-01\",\"enclosure_id\":1,\"status\":\"Active\",\"notes\":null}', 'Updated penguin profile: P-010', '2026-11-20 15:54:19'),
(5, 1, 'UPDATE', 'penguins', 11, '{\"name\":\"Abby\",\"species\":\"Humboldt Penguin\",\"sex\":\"Female\",\"hatch_date\":\"2024-02-15\",\"origin_type\":\"Captive-bred\",\"arrival_date\":\"2024-03-01\",\"enclosure_id\":1,\"status\":\"Active\",\"notes\":null}', '{\"name\":\"Abby\",\"species\":\"Humboldt Penguin\",\"sex\":\"Female\",\"hatch_date\":\"2024-02-15\",\"origin_type\":\"Captive-bred\",\"arrival_date\":\"2024-03-01\",\"enclosure_id\":null,\"status\":\"Deceased\",\"notes\":\"Respiratory fungal infections recurrence\\uff0cdeterioration spreading to both lungs.\\r\\nDeceased on 20-Nov-2026, 16:22.\\r\\nReason: Both lungs functionality failure, widespread lung consolidation, nodules, cavitation, tissue necrosis\"}', 'Updated penguin profile: P-011', '2026-11-20 16:30:04'),
(6, 1, 'ARCHIVE', 'penguins', 11, '{\"animal_code\":\"P-011\",\"name\":\"Abby\",\"status\":\"Deceased\",\"archived_at\":null,\"archived_by\":null}', '{\"animal_code\":\"P-011\",\"name\":\"Abby\",\"status\":\"Deceased\",\"archived_at\":\"CURRENT_TIMESTAMP\",\"archived_by\":1}', 'Archived penguin profile: P-011', '2026-11-20 16:30:13'),
(7, 1, 'UPDATE', 'enclosures', 3, '{\"enclosure_id\":3,\"enclosure_code\":\"E-03\",\"enclosure_name\":\"Clinic\",\"capacity\":3,\"status\":\"Active\",\"notes\":null,\"created_at\":\"2026-11-20 17:23:32\"}', '{\"enclosure_code\":\"E-03\",\"enclosure_name\":\"Clinic\",\"capacity\":3,\"status\":\"Active\",\"notes\":\"Place for veterinary to treat penguins with medical issue.\"}', 'Updated enclosure E-03', '2026-11-21 14:45:20'),
(8, 1, 'UPDATE', 'enclosures', 3, '{\"enclosure_id\":3,\"enclosure_code\":\"E-03\",\"enclosure_name\":\"Clinic\",\"capacity\":3,\"status\":\"Active\",\"notes\":\"Place for veterinary to treat penguins with medical issue.\",\"created_at\":\"2026-11-20 17:23:32\"}', '{\"enclosure_code\":\"E-03\",\"enclosure_name\":\"Clinic\",\"capacity\":2,\"status\":\"Active\",\"notes\":\"Place for veterinary to treat penguins with medical issue.\"}', 'Updated enclosure E-03', '2026-11-21 14:46:03'),
(9, 1, 'UPDATE', 'enclosures', 3, '{\"enclosure_id\":3,\"enclosure_code\":\"E-03\",\"enclosure_name\":\"Clinic\",\"capacity\":2,\"status\":\"Active\",\"notes\":\"Place for veterinary to treat penguins with medical issue.\",\"created_at\":\"2026-11-20 17:23:32\"}', '{\"enclosure_code\":\"E-03\",\"enclosure_name\":\"Clinic\",\"capacity\":3,\"status\":\"Active\",\"notes\":\"Place for veterinary to treat penguins with medical issue.\"}', 'Updated enclosure E-03', '2026-11-21 14:46:06'),
(10, 1, 'UPDATE', 'enclosures', 3, '{\"enclosure_id\":3,\"enclosure_code\":\"E-03\",\"enclosure_name\":\"Clinic\",\"capacity\":3,\"status\":\"Active\",\"notes\":\"Place for veterinary to treat penguins with medical issue.\",\"created_at\":\"2026-11-20 17:23:32\"}', '{\"enclosure_code\":\"E-03\",\"enclosure_name\":\"Clinical\",\"capacity\":1,\"status\":\"Maintenance\",\"notes\":\"Place for veterinary to treat penguins with medical issue.\"}', 'Updated enclosure E-03', '2026-11-21 14:47:29'),
(11, 1, 'UPDATE', 'enclosures', 3, '{\"enclosure_id\":3,\"enclosure_code\":\"E-03\",\"enclosure_name\":\"Clinical\",\"capacity\":1,\"status\":\"Maintenance\",\"notes\":\"Place for veterinary to treat penguins with medical issue.\",\"created_at\":\"2026-11-20 17:23:32\"}', '{\"enclosure_code\":\"E-03\",\"enclosure_name\":\"Clinic\",\"capacity\":3,\"status\":\"Active\",\"notes\":\"Place for veterinary to treat penguins with medical issue.\"}', 'Updated enclosure E-03', '2026-11-21 14:47:53'),
(12, 1, 'UPDATE', 'enclosures', 3, '{\"enclosure_id\":3,\"enclosure_code\":\"E-03\",\"enclosure_name\":\"Clinic\",\"capacity\":3,\"status\":\"Active\",\"notes\":\"Place for veterinary to treat penguins with medical issue.\",\"created_at\":\"2026-11-20 17:23:32\"}', '{\"enclosure_code\":\"E-03\",\"enclosure_name\":\"Clinic\",\"capacity\":3,\"status\":\"Active\",\"notes\":\"Place for veterinary to treat penguins with medical issue.\"}', 'Updated enclosure E-03', '2026-11-21 14:48:05'),
(13, 1, 'UPDATE', 'enclosures', 3, '{\"enclosure_id\":3,\"enclosure_code\":\"E-03\",\"enclosure_name\":\"Clinic\",\"capacity\":3,\"status\":\"\",\"notes\":\"Place for veterinary to treat penguins with medical issue.\",\"created_at\":\"2026-11-20 17:23:32\"}', '{\"enclosure_code\":\"E-03\",\"enclosure_name\":\"Clinic\",\"capacity\":3,\"status\":\"Active\",\"notes\":\"Place for veterinary to treat penguins with medical issue.\"}', 'Updated enclosure E-03', '2026-11-21 14:58:23'),
(14, 1, 'ARCHIVE', 'enclosures', 4, '{\"enclosure_id\":4,\"enclosure_code\":\"E-04\",\"enclosure_name\":\"Testing\",\"capacity\":1,\"status\":\"Active\",\"notes\":\"Testing\",\"created_at\":\"2026-11-21 15:36:06\",\"archived_at\":null,\"archived_by\":null}', '{\"enclosure_code\":\"E-04\",\"enclosure_name\":\"Testing\",\"status\":\"Active\",\"archived_at\":\"2026-11-21 08:36:09\",\"archived_by\":1}', 'Archived enclosure E-04', '2026-11-21 15:36:09'),
(15, 1, 'FINALIZE', 'feeding_records', 11, '{\"status\":\"Draft\"}', '{\"status\":\"Finalized\"}', 'Feeding record finalized.', '2026-11-22 04:06:15'),
(16, 1, 'DELETE', 'feeding_records', 12, '{\"feeding_id\":12,\"penguin_id\":8,\"keeper_id\":1,\"feeding_date\":\"2026-11-22\",\"feeding_time\":\"04:16:00\",\"food_type\":\"Snack\",\"planned_grams\":\"0.01\",\"consumed_grams\":\"0.01\",\"notes\":\"Testing\",\"created_at\":\"2026-11-22 04:16:58\",\"status\":\"Draft\"}', NULL, 'Draft feeding record deleted by its creator.', '2026-11-22 04:17:36'),
(17, 1, 'UPDATE', 'feeding_records', 13, '{\"penguin_id\":8,\"feeding_date\":\"2026-11-22\",\"feeding_time\":\"04:18:00\",\"food_type\":\"Snack\",\"planned_grams\":\"0.01\",\"consumed_grams\":\"0.01\",\"notes\":\"Testing\"}', '{\"penguin_id\":8,\"feeding_date\":\"2026-11-22\",\"feeding_time\":\"04:18\",\"food_type\":\"Snacks\",\"planned_grams\":\"0.01\",\"consumed_grams\":\"0.01\",\"notes\":\"Testings\"}', 'Draft feeding record updated.', '2026-11-22 04:32:47'),
(18, 1, 'REOPEN', 'feeding_records', 11, '{\"status\":\"Finalized\"}', '{\"status\":\"Draft\"}', 'Senior Keeper reopened a finalized feeding record for correction.', '2026-11-22 04:39:16'),
(19, 1, 'DELETE', 'feeding_records', 13, '{\"feeding_id\":13,\"penguin_id\":8,\"keeper_id\":1,\"feeding_date\":\"2026-11-22\",\"feeding_time\":\"04:18:00\",\"food_type\":\"Snacks\",\"planned_grams\":\"0.01\",\"consumed_grams\":\"0.01\",\"notes\":\"Testings\",\"created_at\":\"2026-11-22 04:18:24\",\"status\":\"Draft\"}', NULL, 'Draft feeding record deleted by its creator.', '2026-11-22 04:44:18'),
(20, 2, 'DELETE', 'feeding_records', 11, '{\"feeding_id\":11,\"penguin_id\":7,\"keeper_id\":2,\"feeding_date\":\"2026-11-21\",\"feeding_time\":\"16:16:00\",\"food_type\":\"Shrimp\",\"planned_grams\":\"0.01\",\"consumed_grams\":\"0.01\",\"notes\":\"Testings\",\"created_at\":\"2026-11-21 16:16:31\",\"status\":\"Draft\"}', NULL, 'Draft feeding record deleted by its creator.', '2026-11-22 04:45:37'),
(21, 2, 'UPDATE', 'husbandry_observations', 3, '{\"observation_id\":3,\"penguin_id\":3,\"keeper_id\":2,\"observation_date\":\"2026-11-22\",\"observation_time\":\"04:11:00\",\"appetite\":\"Testing\",\"activity_level\":\"Testing\",\"behaviour\":\"Testing\",\"physical_concern\":\"Testing\",\"notes\":\"Testing\",\"status\":\"Draft\",\"created_at\":\"2026-11-22 11:12:25\"}', '{\"penguin_id\":3,\"observation_date\":\"2026-11-22\",\"observation_time\":\"04:11\",\"appetite\":\"Testings\",\"activity_level\":\"Testings\",\"behaviour\":\"Testings\",\"physical_concern\":\"Testings\",\"notes\":\"Testings\"}', 'Updated own Draft husbandry observation.', '2026-11-22 11:17:50'),
(22, 2, 'DELETE', 'husbandry_observations', 3, '{\"observation_id\":3,\"penguin_id\":3,\"keeper_id\":2,\"observation_date\":\"2026-11-22\",\"observation_time\":\"04:11:00\",\"appetite\":\"Testings\",\"activity_level\":\"Testings\",\"behaviour\":\"Testings\",\"physical_concern\":\"Testings\",\"notes\":\"Testings\",\"status\":\"Draft\",\"created_at\":\"2026-11-22 11:12:25\"}', NULL, 'Deleted own Draft husbandry observation.', '2026-11-22 11:44:40'),
(23, 1, 'DELETE', 'husbandry_observations', 2, '{\"observation_id\":2,\"penguin_id\":2,\"keeper_id\":1,\"observation_date\":\"2026-11-22\",\"observation_time\":\"03:57:00\",\"appetite\":null,\"activity_level\":null,\"behaviour\":null,\"physical_concern\":null,\"notes\":null,\"status\":\"Draft\",\"created_at\":\"2026-11-22 10:57:47\"}', NULL, 'Deleted own Draft husbandry observation.', '2026-11-22 11:45:50'),
(24, 1, 'SUBMIT', 'husbandry_observations', 1, '{\"observation_id\":1,\"penguin_id\":1,\"keeper_id\":1,\"observation_date\":\"2026-11-22\",\"observation_time\":\"03:56:00\",\"appetite\":\"Testing\",\"activity_level\":\"Testing\",\"behaviour\":null,\"physical_concern\":\"Testing\",\"notes\":\"Testing\",\"status\":\"Draft\",\"created_at\":\"2026-11-22 10:57:32\"}', '{\"observation_id\":1,\"penguin_id\":1,\"keeper_id\":1,\"observation_date\":\"2026-11-22\",\"observation_time\":\"03:56:00\",\"appetite\":\"Testing\",\"activity_level\":\"Testing\",\"behaviour\":null,\"physical_concern\":\"Testing\",\"notes\":\"Testing\",\"status\":\"Submitted\",\"created_at\":\"2026-11-22 10:57:32\"}', 'Submitted own Draft husbandry observation for review.', '2026-11-22 11:50:11'),
(25, 2, 'SUBMIT', 'husbandry_observations', 4, '{\"observation_id\":4,\"penguin_id\":10,\"keeper_id\":2,\"observation_date\":\"2026-11-22\",\"observation_time\":\"04:52:00\",\"appetite\":\"testing\",\"activity_level\":\"testing\",\"behaviour\":\"testing\",\"physical_concern\":\"testing\",\"notes\":\"testing\",\"status\":\"Draft\",\"created_at\":\"2026-11-22 11:52:49\"}', '{\"observation_id\":4,\"penguin_id\":10,\"keeper_id\":2,\"observation_date\":\"2026-11-22\",\"observation_time\":\"04:52:00\",\"appetite\":\"testing\",\"activity_level\":\"testing\",\"behaviour\":\"testing\",\"physical_concern\":\"testing\",\"notes\":\"testing\",\"status\":\"Submitted\",\"created_at\":\"2026-11-22 11:52:49\"}', 'Submitted own Draft husbandry observation for review.', '2026-11-22 11:53:43'),
(26, 1, 'REVIEW', 'husbandry_observations', 4, '{\"observation_id\":4,\"penguin_id\":10,\"keeper_id\":2,\"observation_date\":\"2026-11-22\",\"observation_time\":\"04:52:00\",\"appetite\":\"testing\",\"activity_level\":\"testing\",\"behaviour\":\"testing\",\"physical_concern\":\"testing\",\"notes\":\"testing\",\"status\":\"Submitted\",\"created_at\":\"2026-11-22 11:52:49\",\"reviewed_by\":null,\"reviewed_at\":null,\"review_notes\":null}', '{\"observation_id\":4,\"penguin_id\":10,\"keeper_id\":2,\"observation_date\":\"2026-11-22\",\"observation_time\":\"04:52:00\",\"appetite\":\"testing\",\"activity_level\":\"testing\",\"behaviour\":\"testing\",\"physical_concern\":\"testing\",\"notes\":\"testing\",\"status\":\"Reviewed\",\"created_at\":\"2026-11-22 11:52:49\",\"reviewed_by\":1,\"reviewed_at\":\"2026-11-22 12:04:28\",\"review_notes\":null}', 'Reviewed a submitted husbandry observation.', '2026-11-22 12:04:28'),
(27, 1, 'CREATE', 'weight_records', 1, NULL, '{\"penguin_id\":1,\"measurement_date\":\"2026-11-22\",\"measurement_time\":\"16:12\",\"weight_kg\":\"0.01\",\"notes\":\"Testing\"}', 'Created a penguin weight measurement record.', '2026-11-22 16:12:51'),
(28, 2, 'CREATE', 'weight_records', 2, NULL, '{\"penguin_id\":2,\"measurement_date\":\"2026-11-22\",\"measurement_time\":\"16:16\",\"weight_kg\":\"0.01\",\"notes\":\"testing\"}', 'Created a penguin weight measurement record.', '2026-11-22 16:16:33'),
(29, 2, 'UPDATE', 'weight_records', 2, '{\"weight_id\":2,\"penguin_id\":2,\"keeper_id\":2,\"measurement_date\":\"2026-11-22\",\"measurement_time\":\"16:16:00\",\"weight_kg\":\"0.01\",\"notes\":\"testing\",\"created_at\":\"2026-11-22 16:16:33\"}', '{\"weight_id\":2,\"penguin_id\":2,\"keeper_id\":2,\"measurement_date\":\"2026-11-22\",\"measurement_time\":\"16:16\",\"weight_kg\":\"0.02\",\"notes\":\"testing\",\"created_at\":\"2026-11-22 16:16:33\"}', 'Updated own penguin weight measurement record.', '2026-11-22 16:24:29'),
(30, 2, 'UPDATE', 'weight_records', 2, '{\"weight_id\":2,\"penguin_id\":2,\"keeper_id\":2,\"measurement_date\":\"2026-11-22\",\"measurement_time\":\"16:16:00\",\"weight_kg\":\"0.02\",\"notes\":\"testing\",\"created_at\":\"2026-11-22 16:16:33\"}', '{\"weight_id\":2,\"penguin_id\":2,\"keeper_id\":2,\"measurement_date\":\"2026-11-22\",\"measurement_time\":\"16:16\",\"weight_kg\":\"0.02\",\"notes\":\"testings\",\"created_at\":\"2026-11-22 16:16:33\"}', 'Updated own penguin weight measurement record.', '2026-11-22 16:24:41'),
(31, 1, 'DELETE', 'weight_records', 1, '{\"weight_id\":1,\"penguin_id\":1,\"keeper_id\":1,\"measurement_date\":\"2026-11-22\",\"measurement_time\":\"16:12:00\",\"weight_kg\":\"0.01\",\"notes\":\"Testing\",\"created_at\":\"2026-11-22 16:12:51\"}', NULL, 'Deleted own weight record.', '2026-11-22 16:33:59'),
(32, 2, 'CREATE', 'health_alerts', 1, NULL, '{\"penguin_id\":10,\"report_date\":\"2026-11-22\",\"priority\":\"Urgent\",\"observation\":\"Testing\",\"status\":\"Pending\"}', 'Reported a health or behaviour concern.', '2026-11-22 16:51:39'),
(33, 1, 'CREATE', 'health_alerts', 2, NULL, '{\"penguin_id\":10,\"report_date\":\"2026-11-22\",\"report_time\":\"17:09\",\"priority\":\"Routine\",\"observation\":\"testing\",\"status\":\"Pending\"}', 'Reported a health or behaviour concern.', '2026-11-22 17:10:14'),
(34, 2, 'CREATE', 'health_alerts', 3, NULL, '{\"penguin_id\":9,\"report_date\":\"2026-11-22\",\"report_time\":\"17:19\",\"priority\":\"Attention\",\"observation\":\"testing\",\"status\":\"Pending\"}', 'Reported a health or behaviour concern.', '2026-11-22 17:20:08'),
(35, 1, 'REVIEW', 'health_alerts', 2, '{\"alert_id\":2,\"penguin_id\":10,\"reported_by\":1,\"report_date\":\"2026-11-22\",\"report_time\":\"17:09:00\",\"priority\":\"Routine\",\"observation\":\"testing\",\"status\":\"Pending\",\"reviewed_by\":null,\"review_notes\":null,\"resolved_at\":null}', '{\"status\":\"Reviewed\",\"reviewed_by\":1,\"review_notes\":\"\",\"resolved_at\":null}', 'Reviewed a health alert.', '2026-11-22 17:31:47'),
(36, 1, 'REVIEW', 'health_alerts', 2, '{\"alert_id\":2,\"penguin_id\":10,\"reported_by\":1,\"report_date\":\"2026-11-22\",\"report_time\":\"17:09:00\",\"priority\":\"Routine\",\"observation\":\"testing\",\"status\":\"Reviewed\",\"reviewed_by\":1,\"review_notes\":\"\",\"resolved_at\":null}', '{\"status\":\"Resolved\",\"reviewed_by\":1,\"review_notes\":\"\",\"resolved_at\":\"2026-11-22 10:32:57\"}', 'Resolved a health alert.', '2026-11-22 17:32:57'),
(37, 1, 'REVIEW', 'health_alerts', 3, '{\"alert_id\":3,\"penguin_id\":9,\"reported_by\":2,\"report_date\":\"2026-11-22\",\"report_time\":\"17:19:00\",\"priority\":\"Attention\",\"observation\":\"testing\",\"status\":\"Pending\",\"reviewed_by\":null,\"review_notes\":null,\"resolved_at\":null}', '{\"status\":\"Reviewed\",\"reviewed_by\":1,\"review_notes\":\"testing\",\"resolved_at\":null}', 'Reviewed a health alert.', '2026-11-22 17:33:33'),
(38, 1, 'CREATE', 'health_alerts', 4, NULL, '{\"penguin_id\":8,\"report_date\":\"2026-11-22\",\"report_time\":\"17:39\",\"priority\":\"Attention\",\"observation\":\"testing\",\"status\":\"Pending\"}', 'Reported a health or behaviour concern.', '2026-11-22 17:39:09'),
(39, 1, 'CREATE', 'environmental_readings', 1, NULL, '{\"enclosure_id\":1,\"reading_date\":\"2026-11-22\",\"reading_time\":\"19:54\",\"pool_temperature\":\"0.01\",\"ph\":\"0.01\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":\"testing\"}', 'Created environmental reading record', '2026-11-22 19:58:57'),
(40, 1, 'CREATE', 'environmental_readings', 2, NULL, '{\"enclosure_id\":2,\"reading_date\":\"2026-11-22\",\"reading_time\":\"19:59\",\"pool_temperature\":\"0.01\",\"ph\":\"\",\"salinity\":\"\",\"chlorine\":\"\",\"notes\":\"\"}', 'Created environmental reading record', '2026-11-22 20:00:39'),
(41, 2, 'CREATE', 'environmental_readings', 3, NULL, '{\"enclosure_id\":3,\"reading_date\":\"2026-11-22\",\"reading_time\":\"20:02\",\"pool_temperature\":\"\",\"ph\":\"\",\"salinity\":\"\",\"chlorine\":\"\",\"notes\":\"\"}', 'Created environmental reading record', '2026-11-22 20:02:21'),
(42, 3, 'CREATE', 'environmental_readings', 4, NULL, '{\"enclosure_id\":1,\"reading_date\":\"2026-11-22\",\"reading_time\":\"20:27\",\"pool_temperature\":\"\",\"ph\":\"\",\"salinity\":\"\",\"chlorine\":\"\",\"notes\":\"\"}', 'Created environmental reading record', '2026-11-22 20:27:18'),
(43, 2, 'UPDATE', 'environmental_readings', 3, '{\"reading_id\":3,\"enclosure_id\":3,\"keeper_id\":2,\"reading_date\":\"2026-11-22\",\"reading_time\":\"20:02:00\",\"pool_temperature\":null,\"ph\":null,\"salinity\":null,\"chlorine\":null,\"notes\":null}', '{\"enclosure_id\":3,\"reading_date\":\"2026-11-22\",\"reading_time\":\"20:02:00\",\"pool_temperature\":\"\",\"ph\":\"\",\"salinity\":\"\",\"chlorine\":\"\",\"notes\":\"\"}', 'Updated environmental reading record', '2026-11-22 20:53:05'),
(44, 2, 'UPDATE', 'environmental_readings', 3, '{\"reading_id\":3,\"enclosure_id\":3,\"keeper_id\":2,\"reading_date\":\"2026-11-22\",\"reading_time\":\"20:02:00\",\"pool_temperature\":null,\"ph\":null,\"salinity\":null,\"chlorine\":null,\"notes\":null}', '{\"enclosure_id\":3,\"reading_date\":\"2026-11-22\",\"reading_time\":\"20:02:00\",\"pool_temperature\":\"0.01\",\"ph\":\"0.01\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":\"testing\"}', 'Updated environmental reading record', '2026-11-22 20:53:21'),
(45, 1, 'UPDATE', 'environmental_readings', 4, '{\"reading_id\":4,\"enclosure_id\":1,\"keeper_id\":3,\"reading_date\":\"2026-11-22\",\"reading_time\":\"20:27:00\",\"pool_temperature\":null,\"ph\":null,\"salinity\":null,\"chlorine\":null,\"notes\":null}', '{\"enclosure_id\":1,\"reading_date\":\"2026-11-22\",\"reading_time\":\"20:27:00\",\"pool_temperature\":\"\",\"ph\":\"\",\"salinity\":\"\",\"chlorine\":\"\",\"notes\":\"\"}', 'Updated environmental reading record', '2026-11-22 20:53:48'),
(46, 1, 'UPDATE', 'environmental_readings', 4, '{\"reading_id\":4,\"enclosure_id\":1,\"keeper_id\":3,\"reading_date\":\"2026-11-22\",\"reading_time\":\"20:27:00\",\"pool_temperature\":null,\"ph\":null,\"salinity\":null,\"chlorine\":null,\"notes\":null}', '{\"enclosure_id\":1,\"reading_date\":\"2026-11-22\",\"reading_time\":\"20:27:00\",\"pool_temperature\":\"0.01\",\"ph\":\"0.01\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":\"testing\"}', 'Updated environmental reading record', '2026-11-22 20:54:02'),
(47, 1, 'UPDATE', 'environmental_readings', 3, '{\"reading_id\":3,\"enclosure_id\":3,\"keeper_id\":2,\"reading_date\":\"2026-11-22\",\"reading_time\":\"20:02:00\",\"pool_temperature\":\"0.0\",\"ph\":\"0.0\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":\"testing\"}', '{\"enclosure_id\":3,\"reading_date\":\"2026-11-22\",\"reading_time\":\"20:59:00\",\"pool_temperature\":\"0.0\",\"ph\":\"0.0\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":\"testing\"}', 'Updated environmental reading record', '2026-11-22 20:54:38'),
(48, 2, 'DELETE', 'environmental_readings', 3, '{\"reading_id\":3,\"enclosure_id\":3,\"keeper_id\":2,\"reading_date\":\"2026-11-22\",\"reading_time\":\"20:59:00\",\"pool_temperature\":\"0.0\",\"ph\":\"0.0\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":\"testing\"}', NULL, 'Deleted environmental reading #3', '2026-11-22 21:03:18'),
(49, 2, 'CREATE', 'enrichment_records', 1, NULL, '{\"penguin_id\":1,\"activity_date\":\"2026-11-22\",\"activity_time\":\"21:33\",\"enrichment_type\":\"testing\",\"activity_description\":\"testing\",\"observed_response\":\"testing\",\"notes\":\"testing\"}', 'Created enrichment activity record', '2026-11-22 21:40:26'),
(50, 2, 'CREATE', 'enrichment_records', 2, NULL, '{\"penguin_id\":2,\"activity_date\":\"2026-11-22\",\"activity_time\":\"21:41\",\"enrichment_type\":\"testing\",\"activity_description\":\"testing\",\"observed_response\":\"\",\"notes\":\"\"}', 'Created enrichment activity record', '2026-11-22 21:41:58'),
(51, 1, 'UPDATE', 'enrichment_records', 1, '{\"penguin_id\":1,\"activity_date\":\"2026-11-22\",\"activity_time\":\"21:33:00\",\"enrichment_type\":\"testing\",\"activity_description\":\"testing\",\"observed_response\":\"testing\",\"notes\":\"testing\"}', '{\"penguin_id\":1,\"activity_date\":\"2026-11-22\",\"activity_time\":\"21:33\",\"enrichment_type\":\"testing\",\"activity_description\":\"testing\",\"observed_response\":\"testing\",\"notes\":\"testing\"}', 'Updated enrichment record #1', '2026-11-22 22:08:07'),
(52, 1, 'UPDATE', 'enrichment_records', 1, '{\"penguin_id\":1,\"activity_date\":\"2026-11-22\",\"activity_time\":\"21:33:00\",\"enrichment_type\":\"testing\",\"activity_description\":\"testing\",\"observed_response\":\"testing\",\"notes\":\"testing\"}', '{\"penguin_id\":1,\"activity_date\":\"2026-11-22\",\"activity_time\":\"21:33\",\"enrichment_type\":\"testings\",\"activity_description\":\"testings\",\"observed_response\":\"testings\",\"notes\":\"testings\"}', 'Updated enrichment record #1', '2026-11-22 22:08:31'),
(53, 1, 'UPDATE', 'enrichment_records', 2, '{\"penguin_id\":2,\"activity_date\":\"2026-11-22\",\"activity_time\":\"21:41:00\",\"enrichment_type\":\"testing\",\"activity_description\":\"testing\",\"observed_response\":null,\"notes\":null}', '{\"penguin_id\":2,\"activity_date\":\"2026-11-22\",\"activity_time\":\"21:41\",\"enrichment_type\":\"testings\",\"activity_description\":\"testings\",\"observed_response\":null,\"notes\":null}', 'Updated enrichment record #2', '2026-11-22 22:09:55'),
(54, 2, 'UPDATE', 'enrichment_records', 1, '{\"penguin_id\":1,\"activity_date\":\"2026-11-22\",\"activity_time\":\"21:33:00\",\"enrichment_type\":\"testings\",\"activity_description\":\"testings\",\"observed_response\":\"testings\",\"notes\":\"testings\"}', '{\"penguin_id\":1,\"activity_date\":\"2026-11-22\",\"activity_time\":\"21:33\",\"enrichment_type\":\"testingss\",\"activity_description\":\"testingss\",\"observed_response\":\"testingss\",\"notes\":\"testingss\"}', 'Updated enrichment record #1', '2026-11-22 22:12:23'),
(55, 3, 'CREATE', 'enrichment_records', 3, NULL, '{\"penguin_id\":5,\"activity_date\":\"2026-11-22\",\"activity_time\":\"22:12\",\"enrichment_type\":\"testing\",\"activity_description\":\"testing\",\"observed_response\":\"\",\"notes\":\"\"}', 'Created enrichment activity record', '2026-11-22 22:13:16'),
(56, 2, 'UPDATE', 'enrichment_records', 2, '{\"penguin_id\":2,\"activity_date\":\"2026-11-22\",\"activity_time\":\"21:41:00\",\"enrichment_type\":\"testings\",\"activity_description\":\"testings\",\"observed_response\":null,\"notes\":null}', '{\"penguin_id\":2,\"activity_date\":\"2026-11-22\",\"activity_time\":\"21:41\",\"enrichment_type\":\"testings\",\"activity_description\":\"testingss\",\"observed_response\":null,\"notes\":null}', 'Updated enrichment record #2', '2026-11-22 22:22:53'),
(57, 2, 'DELETE', 'enrichment_records', 2, '{\"enrichment_id\":2,\"penguin_id\":2,\"keeper_id\":2,\"activity_date\":\"2026-11-22\",\"activity_time\":\"21:41:00\",\"enrichment_type\":\"testings\",\"activity_description\":\"testingss\",\"observed_response\":null,\"notes\":null}', NULL, 'Deleted enrichment record #2', '2026-11-22 22:23:05'),
(58, 2, 'SEND', 'mailbox_messages', 1, NULL, '{\"recipient_id\":4,\"subject\":\"testing\"}', 'Keeper sent a mailbox message.', '2026-11-22 22:39:37'),
(59, 4, 'SEND', 'mailbox_messages', 2, NULL, '{\"recipient_id\":2,\"subject\":\"testing\"}', 'Keeper sent a mailbox message.', '2026-11-22 22:44:21'),
(60, 2, 'ARCHIVE', 'mailbox_messages', 1, '{\"sender_archived_at\":null}', '{\"sender_archived_at\":\"2026-11-23 05:40:12\"}', 'Keeper archived their own mailbox copy.', '2026-11-23 12:40:12'),
(61, 2, 'ARCHIVE', 'mailbox_messages', 2, '{\"recipient_archived_at\":null}', '{\"recipient_archived_at\":\"2026-11-23 05:42:44\"}', 'Keeper archived their own mailbox copy.', '2026-11-23 12:42:44'),
(62, 1, 'REOPEN', 'feeding_records', 8, '{\"status\":\"Finalized\"}', '{\"status\":\"Draft\"}', 'Senior Keeper reopened a finalized feeding record for correction.', '2026-11-23 17:41:29'),
(63, 1, 'CREATE', 'weight_records', 3, NULL, '{\"penguin_id\":1,\"measurement_date\":\"2026-11-23\",\"measurement_time\":\"19:08\",\"weight_kg\":\"0.01\",\"notes\":\"\"}', 'Created a penguin weight measurement record.', '2026-11-23 19:08:51'),
(64, 4, 'ARCHIVE', 'mailbox_messages', 2, '{\"sender_archived_at\":null}', '{\"sender_archived_at\":\"2026-11-23 12:46:10\"}', 'Keeper archived their own mailbox copy.', '2026-11-23 19:46:10'),
(65, 1, 'CREATE', 'environmental_readings', 5, NULL, '{\"enclosure_id\":1,\"reading_date\":\"2026-11-23\",\"reading_time\":\"21:12\",\"pool_temperature\":\"\",\"ph\":\"\",\"salinity\":\"\",\"chlorine\":\"\",\"notes\":\"\"}', 'Created environmental reading record', '2026-11-23 21:13:28'),
(66, 1, 'UPDATE', 'environmental_readings', 5, '{\"reading_id\":5,\"enclosure_id\":1,\"keeper_id\":1,\"reading_date\":\"2026-11-23\",\"reading_time\":\"21:12:00\",\"pool_temperature\":null,\"ph\":null,\"salinity\":null,\"chlorine\":null,\"notes\":null}', '{\"enclosure_id\":1,\"reading_date\":\"2026-11-23\",\"reading_time\":\"21:12:00\",\"pool_temperature\":\"0.01\",\"ph\":\"0.01\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":\"\"}', 'Updated environmental reading record', '2026-11-23 21:13:55'),
(67, 1, 'UPDATE', 'environmental_readings', 5, '{\"reading_id\":5,\"enclosure_id\":1,\"keeper_id\":1,\"reading_date\":\"2026-11-23\",\"reading_time\":\"21:12:00\",\"pool_temperature\":\"0.0\",\"ph\":\"0.0\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":null}', '{\"enclosure_id\":1,\"reading_date\":\"2026-11-23\",\"reading_time\":\"21:12:00\",\"pool_temperature\":\"0.01\",\"ph\":\"0.0\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":\"\"}', 'Updated environmental reading record', '2026-11-23 21:14:02'),
(68, 1, 'UPDATE', 'environmental_readings', 5, '{\"reading_id\":5,\"enclosure_id\":1,\"keeper_id\":1,\"reading_date\":\"2026-11-23\",\"reading_time\":\"21:12:00\",\"pool_temperature\":\"0.0\",\"ph\":\"0.0\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":null}', '{\"enclosure_id\":1,\"reading_date\":\"2026-11-23\",\"reading_time\":\"21:12:00\",\"pool_temperature\":\"1\",\"ph\":\"0.0\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":\"\"}', 'Updated environmental reading record', '2026-11-23 21:14:17'),
(69, 1, 'UPDATE', 'environmental_readings', 5, '{\"reading_id\":5,\"enclosure_id\":1,\"keeper_id\":1,\"reading_date\":\"2026-11-23\",\"reading_time\":\"21:12:00\",\"pool_temperature\":\"1.0\",\"ph\":\"0.0\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":null}', '{\"enclosure_id\":1,\"reading_date\":\"2026-11-23\",\"reading_time\":\"21:12:00\",\"pool_temperature\":\"1.03\",\"ph\":\"0.0\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":\"\"}', 'Updated environmental reading record', '2026-11-23 21:14:24'),
(70, 1, 'UPDATE', 'environmental_readings', 4, '{\"reading_id\":4,\"enclosure_id\":1,\"keeper_id\":3,\"reading_date\":\"2026-11-22\",\"reading_time\":\"20:27:00\",\"pool_temperature\":\"0.0\",\"ph\":\"0.0\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":\"testing\"}', '{\"enclosure_id\":1,\"reading_date\":\"2026-11-22\",\"reading_time\":\"20:27:00\",\"pool_temperature\":\"1\",\"ph\":\"1\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":\"testing\"}', 'Updated environmental reading record', '2026-11-23 21:14:54'),
(71, 1, 'UPDATE', 'environmental_readings', 2, '{\"reading_id\":2,\"enclosure_id\":2,\"keeper_id\":1,\"reading_date\":\"2026-11-22\",\"reading_time\":\"19:59:00\",\"pool_temperature\":\"0.0\",\"ph\":null,\"salinity\":null,\"chlorine\":null,\"notes\":null}', '{\"enclosure_id\":2,\"reading_date\":\"2026-11-22\",\"reading_time\":\"19:59:00\",\"pool_temperature\":\"1\",\"ph\":\"\",\"salinity\":\"\",\"chlorine\":\"\",\"notes\":\"\"}', 'Updated environmental reading record', '2026-11-23 21:15:02'),
(72, 1, 'UPDATE', 'environmental_readings', 1, '{\"reading_id\":1,\"enclosure_id\":1,\"keeper_id\":1,\"reading_date\":\"2026-11-22\",\"reading_time\":\"19:54:00\",\"pool_temperature\":\"0.0\",\"ph\":\"0.0\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":\"testing\"}', '{\"enclosure_id\":1,\"reading_date\":\"2026-11-22\",\"reading_time\":\"19:54:00\",\"pool_temperature\":\"1\",\"ph\":\"0.0\",\"salinity\":\"0.01\",\"chlorine\":\"0.01\",\"notes\":\"testing\"}', 'Updated environmental reading record', '2026-11-23 21:15:06'),
(73, 1, 'CREATE', 'enrichment_records', 4, NULL, '{\"penguin_id\":1,\"activity_date\":\"2026-11-25\",\"activity_time\":\"14:18\",\"enrichment_type\":\"Dancing\",\"activity_description\":\"testing\",\"observed_response\":\"Positive\",\"notes\":\"testing\"}', 'Created enrichment activity record', '2026-11-25 14:19:06'),
(74, 1, 'SUBMIT', 'husbandry_observations', 6, '{\"observation_id\":6,\"penguin_id\":1,\"keeper_id\":1,\"observation_date\":\"2026-11-25\",\"observation_time\":\"14:14:00\",\"appetite\":\"Good\",\"activity_level\":\"Vibrant\",\"behaviour\":\"Good\",\"physical_concern\":null,\"notes\":\"testing\",\"status\":\"Draft\",\"created_at\":\"2026-11-25 14:15:15\",\"reviewed_by\":null,\"reviewed_at\":null,\"review_notes\":null}', '{\"observation_id\":6,\"penguin_id\":1,\"keeper_id\":1,\"observation_date\":\"2026-11-25\",\"observation_time\":\"14:14:00\",\"appetite\":\"Good\",\"activity_level\":\"Vibrant\",\"behaviour\":\"Good\",\"physical_concern\":null,\"notes\":\"testing\",\"status\":\"Submitted\",\"created_at\":\"2026-11-25 14:15:15\",\"reviewed_by\":null,\"reviewed_at\":null,\"review_notes\":null}', 'Submitted own Draft husbandry observation for review.', '2026-11-25 14:50:10'),
(75, 1, 'CREATE', 'health_alerts', 6, NULL, '{\"penguin_id\":1,\"report_date\":\"2026-11-25\",\"report_time\":\"18:09\",\"priority\":\"Urgent\",\"observation\":\"testing\",\"status\":\"Pending\"}', 'Reported a health or behaviour concern.', '2026-11-25 18:09:26'),
(76, 1, 'REVIEW', 'health_alerts', 6, '{\"alert_id\":6,\"penguin_id\":1,\"reported_by\":1,\"report_date\":\"2026-11-25\",\"report_time\":\"18:09:00\",\"priority\":\"Urgent\",\"observation\":\"testing\",\"status\":\"Pending\",\"reviewed_by\":null,\"review_notes\":null,\"resolved_at\":null}', '{\"status\":\"Resolved\",\"reviewed_by\":1,\"review_notes\":\"testing\",\"resolved_at\":\"2026-11-25 11:09:43\"}', 'Resolved a health alert.', '2026-11-25 18:09:43'),
(77, 1, 'SUBMIT', 'husbandry_observations', 262, '{\"observation_id\":262,\"penguin_id\":1,\"keeper_id\":1,\"observation_date\":\"2026-11-25\",\"observation_time\":\"18:19:00\",\"appetite\":\"Good\",\"activity_level\":\"Vibrant\",\"behaviour\":\"Good\",\"physical_concern\":\"testing\",\"notes\":\"testing\",\"status\":\"Draft\",\"created_at\":\"2026-11-25 18:19:24\",\"reviewed_by\":null,\"reviewed_at\":null,\"review_notes\":null}', '{\"observation_id\":262,\"penguin_id\":1,\"keeper_id\":1,\"observation_date\":\"2026-11-25\",\"observation_time\":\"18:19:00\",\"appetite\":\"Good\",\"activity_level\":\"Vibrant\",\"behaviour\":\"Good\",\"physical_concern\":\"testing\",\"notes\":\"testing\",\"status\":\"Submitted\",\"created_at\":\"2026-11-25 18:19:24\",\"reviewed_by\":null,\"reviewed_at\":null,\"review_notes\":null}', 'Submitted own Draft husbandry observation for review.', '2026-11-25 18:19:45'),
(78, 1, 'CREATE', 'weight_records', 14, NULL, '{\"penguin_id\":1,\"measurement_date\":\"2026-11-27\",\"measurement_time\":\"11:53\",\"weight_kg\":\"4\",\"notes\":\"testing\"}', 'Created a penguin weight measurement record.', '2026-11-27 11:53:55'),
(79, 2, 'CREATE', 'health_alerts', 7, NULL, '{\"penguin_id\":1,\"report_date\":\"2026-11-27\",\"report_time\":\"13:34\",\"priority\":\"Routine\",\"observation\":\"testing\",\"status\":\"Pending\"}', 'Reported a health or behaviour concern.', '2026-11-27 13:34:56'),
(80, 1, 'REOPEN', 'feeding_records', 420, '{\"status\":\"Finalized\"}', '{\"status\":\"Draft\"}', 'Senior Keeper reopened a finalized feeding record for correction.', '2026-11-27 13:39:51');

-- --------------------------------------------------------

--
-- Table structure for table `enclosures`
--

CREATE TABLE `enclosures` (
  `enclosure_id` int(11) NOT NULL,
  `enclosure_code` varchar(20) NOT NULL,
  `enclosure_name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL,
  `status` enum('Active','Maintenance') DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enclosures`
--

INSERT INTO `enclosures` (`enclosure_id`, `enclosure_code`, `enclosure_name`, `capacity`, `status`, `notes`, `created_at`, `archived_at`, `archived_by`) VALUES
(1, 'E-01', 'Humboldt Penguin Main Habitat', 20, 'Active', 'Main habitat for the Humboldt penguin group.', '2026-10-01 20:08:42', NULL, NULL),
(2, 'E-02', 'Penguin Playground', 20, 'Active', 'Alternative area for penguin group.', '2026-10-01 20:08:42', NULL, NULL),
(3, 'E-03', 'Clinic', 3, 'Active', 'Place for veterinary to treat penguins with medical issue.', '2026-10-01 17:23:32', NULL, NULL),
(4, 'E-04', 'Testing', 1, 'Active', 'Testing', '2026-11-21 15:36:06', '2026-11-21 15:36:09', 1);

-- --------------------------------------------------------

--
-- Table structure for table `enrichment_records`
--

CREATE TABLE `enrichment_records` (
  `enrichment_id` int(11) NOT NULL,
  `penguin_id` int(11) NOT NULL,
  `keeper_id` int(11) NOT NULL,
  `activity_date` date DEFAULT NULL,
  `activity_time` time DEFAULT NULL,
  `enrichment_type` varchar(60) NOT NULL,
  `activity_description` text DEFAULT NULL,
  `observed_response` enum('Positive','Neutral','Negative') DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrichment_records`
--

INSERT INTO `enrichment_records` (`enrichment_id`, `penguin_id`, `keeper_id`, `activity_date`, `activity_time`, `enrichment_type`, `activity_description`, `observed_response`, `notes`) VALUES
(5, 1, 4, '2026-11-23', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(6, 1, 2, '2026-11-23', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(7, 1, 4, '2026-11-24', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(8, 1, 2, '2026-11-24', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(9, 1, 4, '2026-11-25', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(10, 1, 2, '2026-11-25', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(11, 1, 4, '2026-11-26', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(12, 1, 2, '2026-11-26', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(13, 1, 4, '2026-11-27', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Neutral', 'Keeper observed the penguin response during the enrichment activity.'),
(14, 1, 2, '2026-11-27', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Neutral', 'Keeper observed the penguin response during the enrichment activity.'),
(15, 2, 4, '2026-11-23', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(16, 2, 2, '2026-11-23', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(17, 2, 4, '2026-11-24', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(18, 2, 2, '2026-11-24', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(19, 2, 4, '2026-11-25', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(20, 2, 2, '2026-11-25', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(21, 2, 4, '2026-11-26', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Neutral', 'Keeper observed the penguin response during the enrichment activity.'),
(22, 2, 2, '2026-11-26', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Neutral', 'Keeper observed the penguin response during the enrichment activity.'),
(23, 2, 4, '2026-11-27', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(24, 2, 2, '2026-11-27', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(25, 3, 4, '2026-11-23', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(26, 3, 2, '2026-11-23', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(27, 3, 4, '2026-11-24', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(28, 3, 2, '2026-11-24', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(29, 3, 4, '2026-11-25', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Neutral', 'Keeper observed the penguin response during the enrichment activity.'),
(30, 3, 2, '2026-11-25', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Neutral', 'Keeper observed the penguin response during the enrichment activity.'),
(31, 3, 4, '2026-11-26', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(32, 3, 2, '2026-11-26', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(33, 3, 4, '2026-11-27', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(34, 3, 2, '2026-11-27', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(35, 4, 4, '2026-11-23', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(36, 4, 2, '2026-11-23', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(37, 4, 4, '2026-11-24', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Neutral', 'Keeper observed the penguin response during the enrichment activity.'),
(38, 4, 2, '2026-11-24', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Neutral', 'Keeper observed the penguin response during the enrichment activity.'),
(39, 4, 4, '2026-11-25', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(40, 4, 2, '2026-11-25', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(41, 4, 4, '2026-11-26', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(42, 4, 2, '2026-11-26', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(43, 4, 4, '2026-11-27', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(44, 4, 2, '2026-11-27', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(45, 5, 4, '2026-11-23', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Neutral', 'Keeper observed the penguin response during the enrichment activity.'),
(46, 5, 2, '2026-11-23', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Neutral', 'Keeper observed the penguin response during the enrichment activity.'),
(47, 5, 4, '2026-11-24', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(48, 5, 2, '2026-11-24', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(49, 5, 4, '2026-11-25', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(50, 5, 2, '2026-11-25', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(51, 5, 4, '2026-11-26', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(52, 5, 2, '2026-11-26', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(53, 5, 4, '2026-11-27', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(54, 5, 2, '2026-11-27', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(55, 6, 4, '2026-11-23', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(56, 6, 2, '2026-11-23', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(57, 6, 4, '2026-11-24', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(58, 6, 2, '2026-11-24', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(59, 6, 4, '2026-11-25', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(60, 6, 2, '2026-11-25', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(61, 6, 4, '2026-11-26', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(62, 6, 2, '2026-11-26', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(63, 6, 4, '2026-11-27', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(64, 6, 2, '2026-11-27', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(65, 7, 4, '2026-11-23', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(66, 7, 2, '2026-11-23', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(67, 7, 4, '2026-11-24', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(68, 7, 2, '2026-11-24', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(69, 7, 4, '2026-11-25', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(70, 7, 2, '2026-11-25', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(71, 7, 4, '2026-11-26', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(72, 7, 2, '2026-11-26', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(73, 7, 4, '2026-11-27', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(74, 7, 2, '2026-11-27', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(75, 8, 4, '2026-11-23', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(76, 8, 2, '2026-11-23', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(77, 8, 4, '2026-11-24', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(78, 8, 2, '2026-11-24', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(79, 8, 4, '2026-11-25', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(80, 8, 2, '2026-11-25', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(81, 8, 4, '2026-11-26', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(82, 8, 2, '2026-11-26', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(83, 8, 4, '2026-11-27', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Neutral', 'Keeper observed the penguin response during the enrichment activity.'),
(84, 8, 2, '2026-11-27', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Neutral', 'Keeper observed the penguin response during the enrichment activity.'),
(85, 9, 4, '2026-11-23', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(86, 9, 2, '2026-11-23', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(87, 9, 4, '2026-11-24', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(88, 9, 2, '2026-11-24', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(89, 9, 4, '2026-11-25', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(90, 9, 2, '2026-11-25', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(91, 9, 4, '2026-11-26', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Neutral', 'Keeper observed the penguin response during the enrichment activity.'),
(92, 9, 2, '2026-11-26', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Neutral', 'Keeper observed the penguin response during the enrichment activity.'),
(93, 9, 4, '2026-11-27', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(94, 9, 2, '2026-11-27', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(95, 10, 4, '2026-11-23', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(96, 10, 2, '2026-11-23', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(97, 10, 4, '2026-11-24', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(98, 10, 2, '2026-11-24', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(99, 10, 4, '2026-11-25', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Neutral', 'Keeper observed the penguin response during the enrichment activity.'),
(100, 10, 2, '2026-11-25', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Neutral', 'Keeper observed the penguin response during the enrichment activity.'),
(101, 10, 4, '2026-11-26', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(102, 10, 2, '2026-11-26', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(103, 10, 4, '2026-11-27', '10:00:00', 'Feeding Enrichment', 'Floating feeding enrichment using food items distributed across the pool.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.'),
(104, 10, 2, '2026-11-27', '14:00:00', 'Cognitive Enrichment', 'Puzzle feeder and safe floating objects provided to encourage exploration.', 'Positive', 'Keeper observed the penguin response during the enrichment activity.');

-- --------------------------------------------------------

--
-- Table structure for table `environmental_readings`
--

CREATE TABLE `environmental_readings` (
  `reading_id` int(11) NOT NULL,
  `enclosure_id` int(11) NOT NULL,
  `keeper_id` int(11) NOT NULL,
  `reading_date` date DEFAULT NULL,
  `reading_time` time DEFAULT NULL,
  `pool_temperature` decimal(4,1) DEFAULT NULL,
  `ph` decimal(3,1) DEFAULT NULL,
  `salinity` decimal(5,2) DEFAULT NULL,
  `chlorine` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `environmental_readings`
--

INSERT INTO `environmental_readings` (`reading_id`, `enclosure_id`, `keeper_id`, `reading_date`, `reading_time`, `pool_temperature`, `ph`, `salinity`, `chlorine`, `notes`) VALUES
(6, 1, 4, '2026-11-23', '14:00:00', 15.2, 7.5, 32.00, 0.00, 'Routine enclosure environmental reading recorded by Junior Keeper.'),
(7, 2, 4, '2026-11-23', '14:00:00', 14.8, 7.6, 32.20, 0.00, 'Routine enclosure environmental reading recorded by Junior Keeper.'),
(8, 1, 4, '2026-11-24', '14:00:00', 14.8, 7.6, 32.20, 0.00, 'Routine enclosure environmental reading recorded by Junior Keeper.'),
(9, 2, 4, '2026-11-24', '14:00:00', 14.9, 7.7, 32.40, 0.00, 'Routine enclosure environmental reading recorded by Junior Keeper.'),
(10, 1, 4, '2026-11-25', '14:00:00', 14.9, 7.7, 32.40, 0.00, 'Routine enclosure environmental reading recorded by Junior Keeper.'),
(11, 2, 4, '2026-11-25', '14:00:00', 15.0, 7.5, 32.60, 0.00, 'Routine enclosure environmental reading recorded by Junior Keeper.'),
(12, 1, 4, '2026-11-26', '14:00:00', 15.0, 7.5, 32.60, 0.00, 'Routine enclosure environmental reading recorded by Junior Keeper.'),
(13, 2, 4, '2026-11-26', '14:00:00', 15.1, 7.6, 32.00, 0.00, 'Routine enclosure environmental reading recorded by Junior Keeper.'),
(14, 1, 4, '2026-11-27', '14:00:00', 15.1, 7.6, 32.00, 0.00, 'Routine enclosure environmental reading recorded by Junior Keeper.'),
(15, 2, 4, '2026-11-27', '14:00:00', 15.2, 7.7, 32.20, 0.00, 'Routine enclosure environmental reading recorded by Junior Keeper.');

-- --------------------------------------------------------

--
-- Table structure for table `feeding_records`
--

CREATE TABLE `feeding_records` (
  `feeding_id` int(11) NOT NULL,
  `penguin_id` int(11) NOT NULL,
  `keeper_id` int(11) NOT NULL,
  `feeding_date` date NOT NULL,
  `feeding_time` time NOT NULL,
  `food_type` varchar(80) NOT NULL,
  `planned_grams` decimal(7,2) DEFAULT NULL,
  `consumed_grams` decimal(7,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('Draft','Finalized') NOT NULL DEFAULT 'Draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feeding_records`
--

INSERT INTO `feeding_records` (`feeding_id`, `penguin_id`, `keeper_id`, `feeding_date`, `feeding_time`, `food_type`, `planned_grams`, `consumed_grams`, `notes`, `created_at`, `status`) VALUES
(271, 1, 2, '2026-11-23', '07:00:00', 'Herring and capelin mix', 150.00, 144.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(272, 1, 4, '2026-11-23', '12:00:00', 'Herring and capelin mix', 150.00, 149.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(273, 1, 2, '2026-11-23', '17:00:00', 'Herring and capelin mix', 150.00, 135.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(274, 1, 2, '2026-11-24', '07:00:00', 'Herring and capelin mix', 150.00, 145.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(275, 1, 4, '2026-11-24', '12:00:00', 'Herring and capelin mix', 150.00, 150.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(276, 1, 2, '2026-11-24', '17:00:00', 'Herring and capelin mix', 150.00, 136.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(277, 1, 2, '2026-11-25', '07:00:00', 'Herring and capelin mix', 150.00, 146.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(278, 1, 4, '2026-11-25', '12:00:00', 'Herring and capelin mix', 150.00, 132.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(279, 1, 2, '2026-11-25', '17:00:00', 'Herring and capelin mix', 150.00, 137.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(280, 1, 2, '2026-11-26', '07:00:00', 'Herring and capelin mix', 150.00, 147.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(281, 1, 4, '2026-11-26', '12:00:00', 'Herring and capelin mix', 150.00, 133.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(282, 1, 2, '2026-11-26', '17:00:00', 'Herring and capelin mix', 150.00, 138.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(283, 1, 2, '2026-11-27', '07:00:00', 'Herring and capelin mix', 150.00, 148.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(284, 1, 4, '2026-11-27', '12:00:00', 'Herring and capelin mix', 150.00, 134.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(285, 1, 2, '2026-11-27', '17:00:00', 'Herring and capelin mix', 150.00, 139.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(286, 2, 2, '2026-11-23', '07:00:00', 'Herring and capelin mix', 150.00, 145.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(287, 2, 4, '2026-11-23', '12:00:00', 'Herring and capelin mix', 150.00, 150.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(288, 2, 2, '2026-11-23', '17:00:00', 'Herring and capelin mix', 150.00, 136.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(289, 2, 2, '2026-11-24', '07:00:00', 'Herring and capelin mix', 150.00, 146.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(290, 2, 4, '2026-11-24', '12:00:00', 'Herring and capelin mix', 150.00, 132.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(291, 2, 2, '2026-11-24', '17:00:00', 'Herring and capelin mix', 150.00, 137.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(292, 2, 2, '2026-11-25', '07:00:00', 'Herring and capelin mix', 150.00, 147.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(293, 2, 4, '2026-11-25', '12:00:00', 'Herring and capelin mix', 150.00, 133.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(294, 2, 2, '2026-11-25', '17:00:00', 'Herring and capelin mix', 150.00, 138.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(295, 2, 2, '2026-11-26', '07:00:00', 'Herring and capelin mix', 150.00, 148.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(296, 2, 4, '2026-11-26', '12:00:00', 'Herring and capelin mix', 150.00, 134.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(297, 2, 2, '2026-11-26', '17:00:00', 'Herring and capelin mix', 150.00, 139.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(298, 2, 2, '2026-11-27', '07:00:00', 'Herring and capelin mix', 150.00, 149.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(299, 2, 4, '2026-11-27', '12:00:00', 'Herring and capelin mix', 150.00, 135.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(300, 2, 2, '2026-11-27', '17:00:00', 'Herring and capelin mix', 150.00, 140.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(301, 3, 2, '2026-11-23', '07:00:00', 'Herring and capelin mix', 150.00, 146.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(302, 3, 4, '2026-11-23', '12:00:00', 'Herring and capelin mix', 150.00, 132.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(303, 3, 2, '2026-11-23', '17:00:00', 'Herring and capelin mix', 150.00, 137.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(304, 3, 2, '2026-11-24', '07:00:00', 'Herring and capelin mix', 150.00, 147.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(305, 3, 4, '2026-11-24', '12:00:00', 'Herring and capelin mix', 150.00, 133.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(306, 3, 2, '2026-11-24', '17:00:00', 'Herring and capelin mix', 150.00, 138.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(307, 3, 2, '2026-11-25', '07:00:00', 'Herring and capelin mix', 150.00, 148.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(308, 3, 4, '2026-11-25', '12:00:00', 'Herring and capelin mix', 150.00, 134.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(309, 3, 2, '2026-11-25', '17:00:00', 'Herring and capelin mix', 150.00, 139.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(310, 3, 2, '2026-11-26', '07:00:00', 'Herring and capelin mix', 150.00, 149.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(311, 3, 4, '2026-11-26', '12:00:00', 'Herring and capelin mix', 150.00, 135.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(312, 3, 2, '2026-11-26', '17:00:00', 'Herring and capelin mix', 150.00, 140.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(313, 3, 2, '2026-11-27', '07:00:00', 'Herring and capelin mix', 150.00, 150.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(314, 3, 4, '2026-11-27', '12:00:00', 'Herring and capelin mix', 150.00, 136.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(315, 3, 2, '2026-11-27', '17:00:00', 'Herring and capelin mix', 150.00, 141.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(316, 4, 2, '2026-11-23', '07:00:00', 'Herring and capelin mix', 150.00, 147.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(317, 4, 4, '2026-11-23', '12:00:00', 'Herring and capelin mix', 150.00, 133.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(318, 4, 2, '2026-11-23', '17:00:00', 'Herring and capelin mix', 150.00, 138.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(319, 4, 2, '2026-11-24', '07:00:00', 'Herring and capelin mix', 150.00, 148.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(320, 4, 4, '2026-11-24', '12:00:00', 'Herring and capelin mix', 150.00, 134.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(321, 4, 2, '2026-11-24', '17:00:00', 'Herring and capelin mix', 150.00, 139.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(322, 4, 2, '2026-11-25', '07:00:00', 'Herring and capelin mix', 150.00, 149.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(323, 4, 4, '2026-11-25', '12:00:00', 'Herring and capelin mix', 150.00, 135.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(324, 4, 2, '2026-11-25', '17:00:00', 'Herring and capelin mix', 150.00, 140.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(325, 4, 2, '2026-11-26', '07:00:00', 'Herring and capelin mix', 150.00, 150.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(326, 4, 4, '2026-11-26', '12:00:00', 'Herring and capelin mix', 150.00, 136.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(327, 4, 2, '2026-11-26', '17:00:00', 'Herring and capelin mix', 150.00, 141.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(328, 4, 2, '2026-11-27', '07:00:00', 'Herring and capelin mix', 150.00, 132.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(329, 4, 4, '2026-11-27', '12:00:00', 'Herring and capelin mix', 150.00, 137.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(330, 4, 2, '2026-11-27', '17:00:00', 'Herring and capelin mix', 150.00, 142.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(331, 5, 2, '2026-11-23', '07:00:00', 'Herring and capelin mix', 150.00, 148.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(332, 5, 4, '2026-11-23', '12:00:00', 'Herring and capelin mix', 150.00, 134.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(333, 5, 2, '2026-11-23', '17:00:00', 'Herring and capelin mix', 150.00, 139.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(334, 5, 2, '2026-11-24', '07:00:00', 'Herring and capelin mix', 150.00, 149.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(335, 5, 4, '2026-11-24', '12:00:00', 'Herring and capelin mix', 150.00, 135.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(336, 5, 2, '2026-11-24', '17:00:00', 'Herring and capelin mix', 150.00, 140.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(337, 5, 2, '2026-11-25', '07:00:00', 'Herring and capelin mix', 150.00, 150.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(338, 5, 4, '2026-11-25', '12:00:00', 'Herring and capelin mix', 150.00, 136.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(339, 5, 2, '2026-11-25', '17:00:00', 'Herring and capelin mix', 150.00, 141.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(340, 5, 2, '2026-11-26', '07:00:00', 'Herring and capelin mix', 150.00, 132.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(341, 5, 4, '2026-11-26', '12:00:00', 'Herring and capelin mix', 150.00, 137.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(342, 5, 2, '2026-11-26', '17:00:00', 'Herring and capelin mix', 150.00, 142.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(343, 5, 2, '2026-11-27', '07:00:00', 'Herring and capelin mix', 150.00, 133.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(344, 5, 4, '2026-11-27', '12:00:00', 'Herring and capelin mix', 150.00, 138.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(345, 5, 2, '2026-11-27', '17:00:00', 'Herring and capelin mix', 150.00, 143.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(346, 6, 2, '2026-11-23', '07:00:00', 'Herring and capelin mix', 150.00, 149.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(347, 6, 4, '2026-11-23', '12:00:00', 'Herring and capelin mix', 150.00, 135.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(348, 6, 2, '2026-11-23', '17:00:00', 'Herring and capelin mix', 150.00, 140.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(349, 6, 2, '2026-11-24', '07:00:00', 'Herring and capelin mix', 150.00, 150.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(350, 6, 4, '2026-11-24', '12:00:00', 'Herring and capelin mix', 150.00, 136.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(351, 6, 2, '2026-11-24', '17:00:00', 'Herring and capelin mix', 150.00, 141.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(352, 6, 2, '2026-11-25', '07:00:00', 'Herring and capelin mix', 150.00, 132.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(353, 6, 4, '2026-11-25', '12:00:00', 'Herring and capelin mix', 150.00, 137.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(354, 6, 2, '2026-11-25', '17:00:00', 'Herring and capelin mix', 150.00, 142.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(355, 6, 2, '2026-11-26', '07:00:00', 'Herring and capelin mix', 150.00, 133.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(356, 6, 4, '2026-11-26', '12:00:00', 'Herring and capelin mix', 150.00, 138.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(357, 6, 2, '2026-11-26', '17:00:00', 'Herring and capelin mix', 150.00, 143.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(358, 6, 2, '2026-11-27', '07:00:00', 'Herring and capelin mix', 150.00, 134.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(359, 6, 4, '2026-11-27', '12:00:00', 'Herring and capelin mix', 150.00, 139.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(360, 6, 2, '2026-11-27', '17:00:00', 'Herring and capelin mix', 150.00, 144.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(361, 7, 2, '2026-11-23', '07:00:00', 'Herring and capelin mix', 150.00, 150.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(362, 7, 4, '2026-11-23', '12:00:00', 'Herring and capelin mix', 150.00, 136.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(363, 7, 2, '2026-11-23', '17:00:00', 'Herring and capelin mix', 150.00, 141.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(364, 7, 2, '2026-11-24', '07:00:00', 'Herring and capelin mix', 150.00, 132.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(365, 7, 4, '2026-11-24', '12:00:00', 'Herring and capelin mix', 150.00, 137.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(366, 7, 2, '2026-11-24', '17:00:00', 'Herring and capelin mix', 150.00, 142.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(367, 7, 2, '2026-11-25', '07:00:00', 'Herring and capelin mix', 150.00, 133.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(368, 7, 4, '2026-11-25', '12:00:00', 'Herring and capelin mix', 150.00, 138.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(369, 7, 2, '2026-11-25', '17:00:00', 'Herring and capelin mix', 150.00, 143.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(370, 7, 2, '2026-11-26', '07:00:00', 'Herring and capelin mix', 150.00, 134.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(371, 7, 4, '2026-11-26', '12:00:00', 'Herring and capelin mix', 150.00, 139.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(372, 7, 2, '2026-11-26', '17:00:00', 'Herring and capelin mix', 150.00, 144.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(373, 7, 2, '2026-11-27', '07:00:00', 'Herring and capelin mix', 150.00, 135.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(374, 7, 4, '2026-11-27', '12:00:00', 'Herring and capelin mix', 150.00, 140.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(375, 7, 2, '2026-11-27', '17:00:00', 'Herring and capelin mix', 150.00, 145.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(376, 8, 2, '2026-11-23', '07:00:00', 'Herring and capelin mix', 150.00, 132.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(377, 8, 4, '2026-11-23', '12:00:00', 'Herring and capelin mix', 150.00, 137.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(378, 8, 2, '2026-11-23', '17:00:00', 'Herring and capelin mix', 150.00, 142.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(379, 8, 2, '2026-11-24', '07:00:00', 'Herring and capelin mix', 150.00, 133.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(380, 8, 4, '2026-11-24', '12:00:00', 'Herring and capelin mix', 150.00, 138.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(381, 8, 2, '2026-11-24', '17:00:00', 'Herring and capelin mix', 150.00, 143.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(382, 8, 2, '2026-11-25', '07:00:00', 'Herring and capelin mix', 150.00, 134.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(383, 8, 4, '2026-11-25', '12:00:00', 'Herring and capelin mix', 150.00, 139.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(384, 8, 2, '2026-11-25', '17:00:00', 'Herring and capelin mix', 150.00, 144.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(385, 8, 2, '2026-11-26', '07:00:00', 'Herring and capelin mix', 150.00, 135.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(386, 8, 4, '2026-11-26', '12:00:00', 'Herring and capelin mix', 150.00, 140.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(387, 8, 2, '2026-11-26', '17:00:00', 'Herring and capelin mix', 150.00, 145.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(388, 8, 2, '2026-11-27', '07:00:00', 'Herring and capelin mix', 150.00, 136.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(389, 8, 4, '2026-11-27', '12:00:00', 'Herring and capelin mix', 150.00, 141.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(390, 8, 2, '2026-11-27', '17:00:00', 'Herring and capelin mix', 150.00, 146.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(391, 9, 2, '2026-11-23', '07:00:00', 'Herring and capelin mix', 150.00, 133.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(392, 9, 4, '2026-11-23', '12:00:00', 'Herring and capelin mix', 150.00, 138.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(393, 9, 2, '2026-11-23', '17:00:00', 'Herring and capelin mix', 150.00, 143.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(394, 9, 2, '2026-11-24', '07:00:00', 'Herring and capelin mix', 150.00, 134.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(395, 9, 4, '2026-11-24', '12:00:00', 'Herring and capelin mix', 150.00, 139.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(396, 9, 2, '2026-11-24', '17:00:00', 'Herring and capelin mix', 150.00, 144.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(397, 9, 2, '2026-11-25', '07:00:00', 'Herring and capelin mix', 150.00, 135.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(398, 9, 4, '2026-11-25', '12:00:00', 'Herring and capelin mix', 150.00, 140.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(399, 9, 2, '2026-11-25', '17:00:00', 'Herring and capelin mix', 150.00, 145.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(400, 9, 2, '2026-11-26', '07:00:00', 'Herring and capelin mix', 150.00, 136.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(401, 9, 4, '2026-11-26', '12:00:00', 'Herring and capelin mix', 150.00, 141.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(402, 9, 2, '2026-11-26', '17:00:00', 'Herring and capelin mix', 150.00, 146.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(403, 9, 2, '2026-11-27', '07:00:00', 'Herring and capelin mix', 150.00, 137.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(404, 9, 4, '2026-11-27', '12:00:00', 'Herring and capelin mix', 150.00, 142.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(405, 9, 2, '2026-11-27', '17:00:00', 'Herring and capelin mix', 150.00, 147.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(406, 10, 2, '2026-11-23', '07:00:00', 'Herring and capelin mix', 150.00, 134.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(407, 10, 4, '2026-11-23', '12:00:00', 'Herring and capelin mix', 150.00, 139.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(408, 10, 2, '2026-11-23', '17:00:00', 'Herring and capelin mix', 150.00, 144.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(409, 10, 2, '2026-11-24', '07:00:00', 'Herring and capelin mix', 150.00, 135.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(410, 10, 4, '2026-11-24', '12:00:00', 'Herring and capelin mix', 150.00, 140.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(411, 10, 2, '2026-11-24', '17:00:00', 'Herring and capelin mix', 150.00, 145.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(412, 10, 2, '2026-11-25', '07:00:00', 'Herring and capelin mix', 150.00, 136.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(413, 10, 4, '2026-11-25', '12:00:00', 'Herring and capelin mix', 150.00, 141.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(414, 10, 2, '2026-11-25', '17:00:00', 'Herring and capelin mix', 150.00, 146.00, 'Good feeding response; food consumed steadily.', '2026-11-25 17:58:55', 'Finalized'),
(415, 10, 2, '2026-11-26', '07:00:00', 'Herring and capelin mix', 150.00, 137.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(416, 10, 4, '2026-11-26', '12:00:00', 'Herring and capelin mix', 150.00, 142.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(417, 10, 2, '2026-11-26', '17:00:00', 'Herring and capelin mix', 150.00, 147.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(418, 10, 2, '2026-11-27', '07:00:00', 'Herring and capelin mix', 150.00, 138.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(419, 10, 4, '2026-11-27', '12:00:00', 'Herring and capelin mix', 150.00, 143.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Finalized'),
(420, 10, 2, '2026-11-27', '17:00:00', 'Herring and capelin mix', 150.00, 148.00, 'Normal feeding response; no unusual behaviour observed.', '2026-11-25 17:58:55', 'Draft'),
(526, 1, 4, '2026-11-25', '18:43:00', 'Squirrel', 100.00, 100.00, 'testing', '2026-11-25 18:44:09', 'Draft'),
(527, 1, 1, '2026-11-27', '11:45:00', 'testing', 100.00, 100.00, 'testing', '2026-11-27 11:46:23', 'Draft');

-- --------------------------------------------------------

--
-- Table structure for table `health_alerts`
--

CREATE TABLE `health_alerts` (
  `alert_id` int(11) NOT NULL,
  `penguin_id` int(11) NOT NULL,
  `reported_by` int(11) NOT NULL,
  `report_date` date DEFAULT NULL,
  `report_time` time DEFAULT NULL,
  `priority` enum('Routine','Attention','Urgent') DEFAULT 'Routine',
  `observation` text NOT NULL,
  `status` enum('Pending','Reviewed','Resolved') DEFAULT 'Pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `health_alerts`
--

INSERT INTO `health_alerts` (`alert_id`, `penguin_id`, `reported_by`, `report_date`, `report_time`, `priority`, `observation`, `status`, `reviewed_by`, `review_notes`, `resolved_at`) VALUES
(5, 4, 4, '2026-11-24', '12:35:00', 'Attention', 'Reduced appetite observed during midday feeding. Penguin remained alert and active. Senior review requested.', 'Resolved', 3, 'Reviewed by Senior Keeper. Subsequent observations recorded normal appetite and activity. Continue routine monitoring.', '2026-11-26 10:00:00'),
(6, 1, 1, '2026-11-25', '18:09:00', 'Urgent', 'testing', 'Resolved', 1, 'testing', '2026-11-25 18:09:43'),
(7, 1, 2, '2026-11-27', '13:34:00', 'Routine', 'testing', 'Pending', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `husbandry_observations`
--

CREATE TABLE `husbandry_observations` (
  `observation_id` int(11) NOT NULL,
  `penguin_id` int(11) NOT NULL,
  `keeper_id` int(11) NOT NULL,
  `observation_date` date NOT NULL,
  `observation_time` time NOT NULL,
  `appetite` enum('Good','Moderate','Poor') DEFAULT NULL,
  `activity_level` enum('Vibrant','Moderate','Poor') DEFAULT NULL,
  `behaviour` enum('Good','Moderate','Poor') DEFAULT NULL,
  `physical_concern` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Draft','Submitted','Reviewed') DEFAULT 'Draft',
  `created_at` datetime DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `husbandry_observations`
--

INSERT INTO `husbandry_observations` (`observation_id`, `penguin_id`, `keeper_id`, `observation_date`, `observation_time`, `appetite`, `activity_level`, `behaviour`, `physical_concern`, `notes`, `status`, `created_at`, `reviewed_by`, `reviewed_at`, `review_notes`) VALUES
(7, 1, 2, '2026-11-23', '07:30:00', 'Moderate', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(8, 1, 4, '2026-11-23', '12:30:00', 'Moderate', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(9, 1, 2, '2026-11-23', '17:30:00', 'Moderate', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(10, 1, 2, '2026-11-24', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(11, 1, 4, '2026-11-24', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(12, 1, 2, '2026-11-24', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(13, 1, 2, '2026-11-25', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(14, 1, 4, '2026-11-25', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(15, 1, 2, '2026-11-25', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(16, 1, 2, '2026-11-26', '07:30:00', 'Good', 'Vibrant', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(17, 1, 4, '2026-11-26', '12:30:00', 'Good', 'Vibrant', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(18, 1, 2, '2026-11-26', '17:30:00', 'Good', 'Vibrant', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(19, 1, 2, '2026-11-27', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(20, 1, 4, '2026-11-27', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(21, 1, 2, '2026-11-27', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(22, 2, 2, '2026-11-23', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(23, 2, 4, '2026-11-23', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(24, 2, 2, '2026-11-23', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(25, 2, 2, '2026-11-24', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(26, 2, 4, '2026-11-24', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(27, 2, 2, '2026-11-24', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(28, 2, 2, '2026-11-25', '07:30:00', 'Good', 'Vibrant', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(29, 2, 4, '2026-11-25', '12:30:00', 'Good', 'Vibrant', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(30, 2, 2, '2026-11-25', '17:30:00', 'Good', 'Vibrant', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(31, 2, 2, '2026-11-26', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(32, 2, 4, '2026-11-26', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(33, 2, 2, '2026-11-26', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(34, 2, 2, '2026-11-27', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(35, 2, 4, '2026-11-27', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(36, 2, 2, '2026-11-27', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(37, 3, 2, '2026-11-23', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(38, 3, 4, '2026-11-23', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(39, 3, 2, '2026-11-23', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(40, 3, 2, '2026-11-24', '07:30:00', 'Good', 'Vibrant', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(41, 3, 4, '2026-11-24', '12:30:00', 'Good', 'Vibrant', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(42, 3, 2, '2026-11-24', '17:30:00', 'Good', 'Vibrant', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(43, 3, 2, '2026-11-25', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(44, 3, 4, '2026-11-25', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(45, 3, 2, '2026-11-25', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(46, 3, 2, '2026-11-26', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(47, 3, 4, '2026-11-26', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(48, 3, 2, '2026-11-26', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(49, 3, 2, '2026-11-27', '07:30:00', 'Good', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(50, 3, 4, '2026-11-27', '12:30:00', 'Good', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(51, 3, 2, '2026-11-27', '17:30:00', 'Good', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(52, 4, 2, '2026-11-23', '07:30:00', 'Good', 'Vibrant', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(53, 4, 4, '2026-11-23', '12:30:00', 'Good', 'Vibrant', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(54, 4, 2, '2026-11-23', '17:30:00', 'Good', 'Vibrant', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(55, 4, 2, '2026-11-24', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(56, 4, 4, '2026-11-24', '12:30:00', 'Poor', 'Moderate', 'Good', NULL, 'Reduced appetite observed during midday feeding. Senior review requested.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(57, 4, 2, '2026-11-24', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(58, 4, 2, '2026-11-25', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(59, 4, 4, '2026-11-25', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(60, 4, 2, '2026-11-25', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(61, 4, 2, '2026-11-26', '07:30:00', 'Good', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(62, 4, 4, '2026-11-26', '12:30:00', 'Good', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(63, 4, 2, '2026-11-26', '17:30:00', 'Good', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(64, 4, 2, '2026-11-27', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(65, 4, 4, '2026-11-27', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(66, 4, 2, '2026-11-27', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(67, 5, 2, '2026-11-23', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(68, 5, 4, '2026-11-23', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(69, 5, 2, '2026-11-23', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(70, 5, 2, '2026-11-24', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(71, 5, 4, '2026-11-24', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(72, 5, 2, '2026-11-24', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(73, 5, 2, '2026-11-25', '07:30:00', 'Good', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(74, 5, 4, '2026-11-25', '12:30:00', 'Good', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(75, 5, 2, '2026-11-25', '17:30:00', 'Good', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(76, 5, 2, '2026-11-26', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(77, 5, 4, '2026-11-26', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(78, 5, 2, '2026-11-26', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(79, 5, 2, '2026-11-27', '07:30:00', 'Moderate', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(80, 5, 4, '2026-11-27', '12:30:00', 'Moderate', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(81, 5, 2, '2026-11-27', '17:30:00', 'Moderate', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(82, 6, 2, '2026-11-23', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(83, 6, 4, '2026-11-23', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(84, 6, 2, '2026-11-23', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(85, 6, 2, '2026-11-24', '07:30:00', 'Good', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(86, 6, 4, '2026-11-24', '12:30:00', 'Good', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(87, 6, 2, '2026-11-24', '17:30:00', 'Good', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(88, 6, 2, '2026-11-25', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(89, 6, 4, '2026-11-25', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(90, 6, 2, '2026-11-25', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(91, 6, 2, '2026-11-26', '07:30:00', 'Moderate', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(92, 6, 4, '2026-11-26', '12:30:00', 'Moderate', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(93, 6, 2, '2026-11-26', '17:30:00', 'Moderate', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(94, 6, 2, '2026-11-27', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(95, 6, 4, '2026-11-27', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(96, 6, 2, '2026-11-27', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(97, 7, 2, '2026-11-23', '07:30:00', 'Good', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(98, 7, 4, '2026-11-23', '12:30:00', 'Good', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(99, 7, 2, '2026-11-23', '17:30:00', 'Good', 'Moderate', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(100, 7, 2, '2026-11-24', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(101, 7, 4, '2026-11-24', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(102, 7, 2, '2026-11-24', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(103, 7, 2, '2026-11-25', '07:30:00', 'Moderate', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(104, 7, 4, '2026-11-25', '12:30:00', 'Moderate', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(105, 7, 2, '2026-11-25', '17:30:00', 'Moderate', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(106, 7, 2, '2026-11-26', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(107, 7, 4, '2026-11-26', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(108, 7, 2, '2026-11-26', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(109, 7, 2, '2026-11-27', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(110, 7, 4, '2026-11-27', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(111, 7, 2, '2026-11-27', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(112, 8, 2, '2026-11-23', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(113, 8, 4, '2026-11-23', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(114, 8, 2, '2026-11-23', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(115, 8, 2, '2026-11-24', '07:30:00', 'Moderate', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(116, 8, 4, '2026-11-24', '12:30:00', 'Moderate', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(117, 8, 2, '2026-11-24', '17:30:00', 'Moderate', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(118, 8, 2, '2026-11-25', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(119, 8, 4, '2026-11-25', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(120, 8, 2, '2026-11-25', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(121, 8, 2, '2026-11-26', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(122, 8, 4, '2026-11-26', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(123, 8, 2, '2026-11-26', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(124, 8, 2, '2026-11-27', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(125, 8, 4, '2026-11-27', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(126, 8, 2, '2026-11-27', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(127, 9, 2, '2026-11-23', '07:30:00', 'Moderate', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(128, 9, 4, '2026-11-23', '12:30:00', 'Moderate', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(129, 9, 2, '2026-11-23', '17:30:00', 'Moderate', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(130, 9, 2, '2026-11-24', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(131, 9, 4, '2026-11-24', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(132, 9, 2, '2026-11-24', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(133, 9, 2, '2026-11-25', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(134, 9, 4, '2026-11-25', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(135, 9, 2, '2026-11-25', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(136, 9, 2, '2026-11-26', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(137, 9, 4, '2026-11-26', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(138, 9, 2, '2026-11-26', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(139, 9, 2, '2026-11-27', '07:30:00', 'Good', 'Moderate', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(140, 9, 4, '2026-11-27', '12:30:00', 'Good', 'Moderate', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(141, 9, 2, '2026-11-27', '17:30:00', 'Good', 'Moderate', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(142, 10, 2, '2026-11-23', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(143, 10, 4, '2026-11-23', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(144, 10, 2, '2026-11-23', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-23 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(145, 10, 2, '2026-11-24', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(146, 10, 4, '2026-11-24', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(147, 10, 2, '2026-11-24', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-24 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(148, 10, 2, '2026-11-25', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(149, 10, 4, '2026-11-25', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(150, 10, 2, '2026-11-25', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-25 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(151, 10, 2, '2026-11-26', '07:30:00', 'Good', 'Moderate', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(152, 10, 4, '2026-11-26', '12:30:00', 'Good', 'Moderate', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(153, 10, 2, '2026-11-26', '17:30:00', 'Good', 'Moderate', 'Moderate', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-26 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(154, 10, 2, '2026-11-27', '07:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(155, 10, 4, '2026-11-27', '12:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 3, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(156, 10, 2, '2026-11-27', '17:30:00', 'Good', 'Vibrant', 'Good', NULL, 'Routine observation during feeding. No unusual behaviour observed.', 'Reviewed', '2026-11-25 17:59:42', 1, '2026-11-27 18:00:00', 'Reviewed by Senior Keeper. Recorded appetite, activity and behaviour reviewed.'),
(262, 1, 1, '2026-11-25', '18:19:00', 'Good', 'Vibrant', 'Good', 'testing', 'testing', 'Submitted', '2026-11-25 18:19:24', NULL, NULL, NULL),
(263, 1, 1, '2026-11-27', '11:54:00', 'Good', 'Vibrant', 'Good', 'testing', 'testing', 'Draft', '2026-11-27 11:55:14', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `mailbox_messages`
--

CREATE TABLE `mailbox_messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message_body` text NOT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  `sender_archived_at` datetime DEFAULT NULL,
  `recipient_archived_at` datetime DEFAULT NULL,
  `status` enum('Active','Archived') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mailbox_messages`
--

INSERT INTO `mailbox_messages` (`message_id`, `sender_id`, `recipient_id`, `subject`, `message_body`, `sent_at`, `read_at`, `sender_archived_at`, `recipient_archived_at`, `status`) VALUES
(1, 2, 4, 'testing', 'testing', '2026-11-22 22:39:37', '2026-11-22 22:47:05', '2026-11-23 12:40:12', NULL, 'Active'),
(2, 4, 2, 'testing', 'testing', '2026-11-22 22:44:21', '2026-11-23 12:42:38', '2026-11-23 19:46:10', '2026-11-23 12:42:44', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `penguins`
--

CREATE TABLE `penguins` (
  `penguin_id` int(11) NOT NULL,
  `animal_code` varchar(20) NOT NULL,
  `name` varchar(60) NOT NULL,
  `species` varchar(80) NOT NULL,
  `sex` enum('Male','Female','Unknown') DEFAULT 'Unknown',
  `hatch_date` date DEFAULT NULL,
  `origin_type` varchar(50) DEFAULT NULL,
  `arrival_date` date DEFAULT NULL,
  `enclosure_id` int(11) DEFAULT NULL,
  `status` enum('Active','Quarantine','Transferred','Deceased') DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `penguins`
--

INSERT INTO `penguins` (`penguin_id`, `animal_code`, `name`, `species`, `sex`, `hatch_date`, `origin_type`, `arrival_date`, `enclosure_id`, `status`, `notes`, `created_at`, `archived_at`, `archived_by`) VALUES
(1, 'P-001', 'Koda', 'Humboldt Penguin', 'Male', '2021-03-14', 'Captive-bred', '2021-03-14', 1, 'Active', NULL, '2026-11-19 20:09:35', NULL, NULL),
(2, 'P-002', 'Luna', 'Humboldt Penguin', 'Female', '2020-11-02', 'Captive-bred', '2020-11-02', 1, 'Active', NULL, '2026-11-19 20:09:35', NULL, NULL),
(3, 'P-003', 'Pico', 'Humboldt Penguin', 'Male', '2022-01-27', 'Captive-bred', '2022-01-27', 1, 'Active', NULL, '2026-11-19 20:09:35', NULL, NULL),
(4, 'P-004', 'Nori', 'Humboldt Penguin', 'Female', '2021-07-08', 'Captive-bred', '2021-07-08', 1, 'Active', NULL, '2026-11-19 20:09:35', NULL, NULL),
(5, 'P-005', 'Milo', 'Humboldt Penguin', 'Male', '2023-02-19', 'Captive-bred', '2023-02-19', 1, 'Active', NULL, '2026-11-19 20:09:35', NULL, NULL),
(6, 'P-006', 'Sora', 'Humboldt Penguin', 'Female', '2022-08-11', 'Captive-bred', '2022-08-11', 1, 'Active', NULL, '2026-11-19 20:09:35', NULL, NULL),
(7, 'P-007', 'Tiko', 'Humboldt Penguin', 'Male', '2020-05-23', 'Captive-bred', '2020-05-23', 1, 'Active', NULL, '2026-11-19 20:09:35', NULL, NULL),
(8, 'P-008', 'Poppy', 'Humboldt Penguin', 'Female', '2023-06-01', 'Captive-bred', '2023-06-01', 1, 'Active', NULL, '2026-11-19 20:09:35', NULL, NULL),
(9, 'P-009', 'Frost', 'Humboldt Penguin', 'Male', '2024-02-15', 'Captive-bred', '2024-03-01', 1, 'Active', NULL, '2026-11-20 15:08:38', NULL, NULL),
(10, 'P-010', 'Ruby', 'Humboldt Penguin', 'Female', '2024-02-15', 'Captive-bred', '2024-03-01', 1, 'Active', NULL, '2026-11-20 15:10:53', NULL, NULL),
(11, 'P-011', 'Abby', 'Humboldt Penguin', 'Female', '2024-02-15', 'Captive-bred', '2024-03-01', NULL, 'Deceased', 'Respiratory fungal infections recurrence，deterioration spreading to both lungs.\r\nDeceased on 20-Nov-2026, 16:22.\r\nReason: Both lungs functionality failure, widespread lung consolidation, nodules, cavitation, tissue necrosis', '2026-11-20 15:17:47', '2026-11-20 16:30:13', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `staff_code` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Senior Keeper','Junior Keeper') NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `staff_code`, `full_name`, `email`, `password_hash`, `role`, `status`, `created_at`) VALUES
(1, 'SK-001', 'Sarah Tan', 'SarahTan@gmail.com', '$2y$12$uKgplyUf7j6IeUgxI5/PmOKxXFT71aK04zlWukp8o4KL00AuKLeje', 'Senior Keeper', 'Active', '2026-11-19 21:39:55'),
(2, 'JK-001', 'Amir Rahman', 'AmirRahman@gmail.com', '$2y$12$XyJuLtW7x1584uV90OH81.zDN9IkcDS.wFmsxGmmBjiV8TJYB9k2q', 'Junior Keeper', 'Active', '2026-11-19 22:12:31'),
(3, 'SK-002', 'Priya Singh', 'PriyaSingh@gmail.com', '$2y$12$iCHhEGMt7y7I4GNxXy4Jr.2Ez2Ira393uZgJGNBVhbHc2xe0n1aDe', 'Senior Keeper', 'Active', '2026-11-19 22:36:11'),
(4, 'JK-002', 'John Wick', 'JohnWick@gmail.com', '$2y$12$bf6bo3/Nj6hg.TQs760NAONh3Gpnt0hz7hSN1wU175cjhBsTqditG', 'Junior Keeper', 'Active', '2026-11-22 11:21:33');

-- --------------------------------------------------------

--
-- Table structure for table `weight_records`
--

CREATE TABLE `weight_records` (
  `weight_id` int(11) NOT NULL,
  `penguin_id` int(11) NOT NULL,
  `keeper_id` int(11) NOT NULL,
  `measurement_date` date NOT NULL,
  `measurement_time` time NOT NULL,
  `weight_kg` decimal(5,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `weight_records`
--

INSERT INTO `weight_records` (`weight_id`, `penguin_id`, `keeper_id`, `measurement_date`, `measurement_time`, `weight_kg`, `notes`, `created_at`) VALUES
(4, 1, 2, '2026-11-25', '09:00:00', 4.35, 'Scheduled weekly weight measurement.', '2026-11-25 18:00:59'),
(5, 2, 2, '2026-11-25', '09:00:00', 4.10, 'Scheduled weekly weight measurement.', '2026-11-25 18:00:59'),
(6, 3, 2, '2026-11-25', '09:00:00', 4.20, 'Scheduled weekly weight measurement.', '2026-11-25 18:00:59'),
(7, 4, 2, '2026-11-25', '09:00:00', 4.05, 'Scheduled weekly weight measurement.', '2026-11-25 18:00:59'),
(8, 5, 2, '2026-11-25', '09:00:00', 3.85, 'Scheduled weekly weight measurement.', '2026-11-25 18:00:59'),
(9, 6, 2, '2026-11-25', '09:00:00', 4.15, 'Scheduled weekly weight measurement.', '2026-11-25 18:00:59'),
(10, 7, 2, '2026-11-25', '09:00:00', 4.50, 'Scheduled weekly weight measurement.', '2026-11-25 18:00:59'),
(11, 8, 2, '2026-11-25', '09:00:00', 3.90, 'Scheduled weekly weight measurement.', '2026-11-25 18:00:59'),
(12, 9, 2, '2026-11-25', '09:00:00', 3.75, 'Scheduled weekly weight measurement.', '2026-11-25 18:00:59'),
(13, 10, 2, '2026-11-25', '09:00:00', 3.80, 'Scheduled weekly weight measurement.', '2026-11-25 18:00:59'),
(14, 1, 1, '2026-11-27', '11:53:00', 4.00, 'testing', '2026-11-27 11:53:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_audit_user` (`user_id`),
  ADD KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_audit_date` (`created_at`);

--
-- Indexes for table `enclosures`
--
ALTER TABLE `enclosures`
  ADD PRIMARY KEY (`enclosure_id`),
  ADD UNIQUE KEY `enclosure_code` (`enclosure_code`),
  ADD KEY `fk_enclosures_archived_by` (`archived_by`);

--
-- Indexes for table `enrichment_records`
--
ALTER TABLE `enrichment_records`
  ADD PRIMARY KEY (`enrichment_id`),
  ADD KEY `fk_enrichment_penguin` (`penguin_id`),
  ADD KEY `fk_enrichment_keeper` (`keeper_id`);

--
-- Indexes for table `environmental_readings`
--
ALTER TABLE `environmental_readings`
  ADD PRIMARY KEY (`reading_id`),
  ADD KEY `fk_environment_enclosure` (`enclosure_id`),
  ADD KEY `fk_environment_keeper` (`keeper_id`);

--
-- Indexes for table `feeding_records`
--
ALTER TABLE `feeding_records`
  ADD PRIMARY KEY (`feeding_id`),
  ADD KEY `fk_feeding_penguin` (`penguin_id`),
  ADD KEY `fk_feeding_keeper` (`keeper_id`);

--
-- Indexes for table `health_alerts`
--
ALTER TABLE `health_alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `fk_alert_penguin` (`penguin_id`),
  ADD KEY `fk_alert_reporter` (`reported_by`),
  ADD KEY `fk_alert_reviewer` (`reviewed_by`);

--
-- Indexes for table `husbandry_observations`
--
ALTER TABLE `husbandry_observations`
  ADD PRIMARY KEY (`observation_id`),
  ADD KEY `fk_observation_penguin` (`penguin_id`),
  ADD KEY `fk_observation_keeper` (`keeper_id`),
  ADD KEY `fk_observations_reviewed_by` (`reviewed_by`);

--
-- Indexes for table `mailbox_messages`
--
ALTER TABLE `mailbox_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `fk_message_sender` (`sender_id`),
  ADD KEY `idx_message_recipient` (`recipient_id`,`status`,`sent_at`);

--
-- Indexes for table `penguins`
--
ALTER TABLE `penguins`
  ADD PRIMARY KEY (`penguin_id`),
  ADD UNIQUE KEY `animal_code` (`animal_code`),
  ADD KEY `fk_penguin_enclosure` (`enclosure_id`),
  ADD KEY `fk_penguins_archived_by` (`archived_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `staff_code` (`staff_code`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `weight_records`
--
ALTER TABLE `weight_records`
  ADD PRIMARY KEY (`weight_id`),
  ADD KEY `fk_weight_penguin` (`penguin_id`),
  ADD KEY `fk_weight_keeper` (`keeper_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `enclosures`
--
ALTER TABLE `enclosures`
  MODIFY `enclosure_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `enrichment_records`
--
ALTER TABLE `enrichment_records`
  MODIFY `enrichment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `environmental_readings`
--
ALTER TABLE `environmental_readings`
  MODIFY `reading_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `feeding_records`
--
ALTER TABLE `feeding_records`
  MODIFY `feeding_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=528;

--
-- AUTO_INCREMENT for table `health_alerts`
--
ALTER TABLE `health_alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `husbandry_observations`
--
ALTER TABLE `husbandry_observations`
  MODIFY `observation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=264;

--
-- AUTO_INCREMENT for table `mailbox_messages`
--
ALTER TABLE `mailbox_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `penguins`
--
ALTER TABLE `penguins`
  MODIFY `penguin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `weight_records`
--
ALTER TABLE `weight_records`
  MODIFY `weight_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `enclosures`
--
ALTER TABLE `enclosures`
  ADD CONSTRAINT `fk_enclosures_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `enrichment_records`
--
ALTER TABLE `enrichment_records`
  ADD CONSTRAINT `fk_enrichment_keeper` FOREIGN KEY (`keeper_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enrichment_penguin` FOREIGN KEY (`penguin_id`) REFERENCES `penguins` (`penguin_id`) ON UPDATE CASCADE;

--
-- Constraints for table `environmental_readings`
--
ALTER TABLE `environmental_readings`
  ADD CONSTRAINT `fk_environment_enclosure` FOREIGN KEY (`enclosure_id`) REFERENCES `enclosures` (`enclosure_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_environment_keeper` FOREIGN KEY (`keeper_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `feeding_records`
--
ALTER TABLE `feeding_records`
  ADD CONSTRAINT `fk_feeding_keeper` FOREIGN KEY (`keeper_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_feeding_penguin` FOREIGN KEY (`penguin_id`) REFERENCES `penguins` (`penguin_id`) ON UPDATE CASCADE;

--
-- Constraints for table `health_alerts`
--
ALTER TABLE `health_alerts`
  ADD CONSTRAINT `fk_alert_penguin` FOREIGN KEY (`penguin_id`) REFERENCES `penguins` (`penguin_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_alert_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_alert_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `husbandry_observations`
--
ALTER TABLE `husbandry_observations`
  ADD CONSTRAINT `fk_observation_keeper` FOREIGN KEY (`keeper_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_observation_penguin` FOREIGN KEY (`penguin_id`) REFERENCES `penguins` (`penguin_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_observations_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `mailbox_messages`
--
ALTER TABLE `mailbox_messages`
  ADD CONSTRAINT `fk_message_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_message_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `penguins`
--
ALTER TABLE `penguins`
  ADD CONSTRAINT `fk_penguin_enclosure` FOREIGN KEY (`enclosure_id`) REFERENCES `enclosures` (`enclosure_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_penguins_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `weight_records`
--
ALTER TABLE `weight_records`
  ADD CONSTRAINT `fk_weight_keeper` FOREIGN KEY (`keeper_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_weight_penguin` FOREIGN KEY (`penguin_id`) REFERENCES `penguins` (`penguin_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
