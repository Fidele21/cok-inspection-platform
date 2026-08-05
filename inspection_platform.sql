-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 30, 2026 at 12:13 PM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 8.1.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inspection_platform`
--

-- --------------------------------------------------------

--
-- Table structure for table `checklist_items`
--

CREATE TABLE `checklist_items` (
  `id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `item_code` varchar(50) DEFAULT NULL,
  `label` text NOT NULL,
  `sort_order` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `checklist_items`
--

INSERT INTO `checklist_items` (`id`, `section_id`, `item_code`, `label`, `sort_order`) VALUES
(1, 1, 'physical_assessment', 'Entity physical assessment (status, zone, usage, accessibility)', 1),
(2, 1, 'construction_permit', 'Construction permit', 2),
(3, 1, 'occupation_permit', 'Occupation permit', 3),
(4, 1, 'eia_certificate', 'EIA certificate (where applicable)', 4),
(5, 1, 'building_insurance', 'Building insurance', 5),
(6, 1, 'building_user_insurance', 'Building user insurance', 6),
(7, 1, 'security_staff_trained', 'Security managers, staff, and trained users', 7),
(8, 2, 'dcp_extinguisher', 'DCP (dry chemical powder) extinguisher', 1),
(9, 2, 'co2_extinguisher', 'CO₂ (carbon dioxide) extinguisher', 2),
(10, 2, 'foam_extinguisher', 'Foam (foam liquid) extinguisher', 3),
(11, 2, 'auto_suppression', 'Automated suppression system (DSPA, FM200, etc.)', 4),
(12, 2, 'fire_blanket', 'Fire blanket', 5),
(13, 2, 'water_supply', 'Water for firefighting', 6),
(14, 2, 'hose_reel', 'Hose reel (30m)', 7),
(15, 2, 'fire_hydrants', 'Fire hydrants with Storz coupling (45mm or 75mm)', 8),
(16, 2, 'water_reservoir', 'Water reservoir / tank', 9),
(17, 2, 'water_pump', 'Water pump (8–15 bars)', 10),
(18, 2, 'water_sprinkler', 'Water sprinkler system', 11),
(19, 2, 'foam_sprinkler', 'Foam sprinkler system', 12),
(20, 2, 'dust_vase', 'Dust vase with shovels', 13),
(21, 3, 'electrical_cert', 'Electrical installation compliance certificate', 1),
(22, 3, 'surge_protection', 'Surge protection system', 2),
(23, 3, 'lightning_arrester', 'Para-lightning system (lightning arrester)', 3),
(24, 3, 'generator', 'Generator', 4),
(25, 3, 'ups', 'Uninterruptible power supply (UPS)', 5),
(26, 3, 'elevator_controls', 'Elevator: accessible controls (900–1100mm)', 6),
(27, 3, 'elevator_braille', 'Elevator: braille, tactile buttons and sound', 7),
(28, 3, 'elevator_fire_sign', 'Elevator: fire warning sign (Kinyarwanda and English, ≥15mm)', 8),
(29, 3, 'elevator_inspection', 'Elevator inspected every 6 months', 9),
(30, 3, 'maintenance_logbook', 'Maintenance records / logbook available', 10),
(31, 3, 'electrical_diagrams', 'Electrical diagrams available', 11),
(32, 3, 'escalator_condition', 'Escalator condition', 12),
(33, 4, 'fire_alarm', 'Fire alarm system with control panel', 1),
(34, 4, 'smoke_detectors', 'Smoke detectors', 2),
(35, 4, 'gas_detectors', 'Gas detectors with shutters', 3),
(36, 4, 'beam_detection', 'Beam detection', 4),
(37, 4, 'heat_detection', 'Heat detection', 5),
(38, 4, 'exit_routes', 'Emergency exit routes', 6),
(39, 4, 'exit_signs', 'Emergency exit signs', 7),
(40, 4, 'floor_plan', 'Floor plan', 8),
(41, 4, 'evacuation_plan', 'Emergency evacuation plan', 9),
(42, 4, 'level_signs', 'Number signs on each level', 10),
(43, 4, 'elevator_fire_notice', 'Sign forbidding elevator use in case of fire', 11),
(44, 4, 'emergency_numbers', 'Emergency response phone numbers (police, fire, ambulance)', 12),
(45, 4, 'assembly_areas', 'Assembly areas and signs', 13),
(46, 4, 'pwd_facilities', 'PWD facilities (toilet, signs, way)', 14),
(47, 4, 'cctv', 'CCTV camera and control room (≥3 months storage)', 15),
(48, 4, 'search_mechanism', 'Searching mechanism (scanner, detectors, mirror)', 16),
(49, 4, 'first_aid', 'First aid boxes', 17),
(50, 4, 'previously_visited', 'Previously visited / inspected before', 18),
(51, 5, 'plot_size_compliant', 'Plot size complies with requirements: minimum 1,500 m² (EV charging only) or 2,000 m² (EV charging with service bay)', 1),
(52, 6, 'sight_distance', 'Minimum sight distance of 100 meters maintained from both the entrance and exit', 1),
(53, 6, 'junction_distance', 'Minimum distance of 75 meters maintained between the nearest facility and the nearest road junction/intersection', 2),
(54, 7, 'tank_residential_distance', 'Fuel storage tanks, vents, and dispensers located at least 30 meters from residential plots/houses, or a 3m-high brick/concrete fence installed where this is not met', 1),
(55, 8, 'power_line_clearance', 'Horizontal clearance from power lines meets requirements: minimum 15m for 15–220 kV lines, minimum 20m for 400 kV lines', 1),
(56, 9, 'sensitive_area_distance', 'Minimum 300 meters maintained from sensitive areas (memorial sites, religious facilities, correctional facilities, health centers/clinics/hospitals, schools, national security facilities, markets, hotels, sports stadiums, airports, etc.)', 1),
(57, 10, 'fire_hydrant_45', 'Fire hydrant with 45mm Storz coupling installed', 1),
(58, 10, 'extinguishers_set', 'Fire extinguishers available: 9kg dry powder, 9kg CO₂, 9kg foam, and two (2) 25kg dry powder extinguishers', 2),
(59, 10, 'hose_reel_functional', 'Fire hose reel installed and fully functional', 3),
(60, 10, 'dry_sand_shovels', 'Dry fine sand available together with two (2) shovels', 4),
(61, 10, 'smoke_detectors_p', 'Smoke detectors installed and operational', 5),
(62, 10, 'fire_alarm_p', 'Fire alarm system with control panel installed and functional', 6),
(63, 10, 'evacuation_plan_p', 'Emergency evacuation plan available and clearly displayed with signage', 7),
(64, 10, 'assembly_point', 'Emergency assembly point identified and properly signposted', 8),
(65, 10, 'emergency_hotline', 'Emergency hotline displayed and easily accessible', 9),
(66, 10, 'water_reserve_30', 'Water reserve tank with minimum capacity of 30 m³ available', 10),
(67, 10, 'water_pump_8bar', 'Water pressure pump of 8 bar installed for firefighting', 11),
(68, 11, 'warning_pictograms', 'Warning notices and pictograms boldly installed on canopy columns, vent pipes, and storage tank areas', 1),
(69, 11, 'signs_visible_7_5m', 'All warning signs clearly visible and readable from a minimum distance of 7.5 meters', 2),
(70, 11, 'entrance_exit_signs', 'Entrance and exit signs illuminated/retro-reflective, readable from a minimum distance of 50 meters', 3),
(71, 12, 'dispenser_breaker', 'Dispenser circuit fitted with an isolating circuit breaker', 1),
(72, 12, 'emergency_stop', 'Emergency stop switch installed to shut down the entire electrical system', 2),
(73, 12, 'auto_power_backup', 'Automatic power backup system installed', 3),
(74, 12, 'lightning_protection', 'Lightning protection system installed', 4),
(75, 12, 'certified_electrician', 'All electrical installations carried out by a certified electrical practitioner', 5),
(76, 13, 'sanitary_facilities', 'Sanitary facilities provided per regulations: designated toilets for male (with urinals), female, and persons with disabilities', 1),
(77, 13, 'changing_rooms', 'At least two (2) changing rooms available and accessible', 2),
(78, 13, 'sewage_system', 'Functional sewage treatment system installed and properly operating', 3),
(79, 14, 'eia_cert_p', 'Copy of EIA certificate', 1),
(80, 14, 'construction_permit_p', 'Construction permit', 2),
(81, 14, 'occupation_permit_p', 'Occupation permit', 3),
(82, 14, 'tank_calibration_cert', 'Fuel tank calibration certificate from a licensed service provider', 4),
(83, 14, 'six_month_testing', 'Dispensers and safety/security equipment tested every six (6) months by a competent authority with valid certification', 5);

-- --------------------------------------------------------

--
-- Table structure for table `checklist_sections`
--

CREATE TABLE `checklist_sections` (
  `id` int(11) NOT NULL,
  `entity_type_id` int(11) DEFAULT NULL,
  `section_number` varchar(10) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `sort_order` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `checklist_sections`
--

INSERT INTO `checklist_sections` (`id`, `entity_type_id`, `section_number`, `title`, `sort_order`) VALUES
(1, 1, '1', 'General Assessment', 1),
(2, 1, '2', 'Fire Safety Equipment', 2),
(3, 1, '3', 'Electrical Installation', 3),
(4, 1, '4', 'Other Safety & Security', 4),
(5, 2, '1', 'Plot Size Requirements', 1),
(6, 2, '2', 'Road Safety Considerations', 2),
(7, 2, '3', 'Distance of Fuel Tanks from Residential Houses', 3),
(8, 2, '4', 'Distance Between Service Station and Power Line', 4),
(9, 2, '5', 'Distance from Sensitive Areas', 5),
(10, 2, '6', 'Firefighting and Security Systems', 6),
(11, 2, '7', 'Warning Signs at Petrol Station', 7),
(12, 2, '8', 'Electrical Installation Requirements', 8),
(13, 2, '9', 'Sanitary Facilities', 9),
(14, 2, '10', 'Permitting and Licensing', 10);

-- --------------------------------------------------------

--
-- Table structure for table `entities`
--

CREATE TABLE `entities` (
  `id` int(11) NOT NULL,
  `entity_type_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `owner` varchar(150) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `use_type` varchar(100) DEFAULT NULL,
  `upi` varchar(50) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `sector` varchar(100) DEFAULT NULL,
  `cell` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `entities`
--

INSERT INTO `entities` (`id`, `entity_type_id`, `name`, `owner`, `telephone`, `email`, `use_type`, `upi`, `district`, `sector`, `cell`, `created_at`, `updated_at`) VALUES
(1, 1, 'AFOO', '', '0782253882', 'itangfidele2@gmail.com', '', '', '', '', '', '2026-07-29 15:12:19', '2026-07-29 15:12:19');

-- --------------------------------------------------------

--
-- Table structure for table `entity_types`
--

CREATE TABLE `entity_types` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `entity_types`
--

INSERT INTO `entity_types` (`id`, `code`, `name`, `description`, `created_at`) VALUES
(1, 'building', 'Occupied Building', 'Fire & Security checklist for buildings categories 4/5', '2026-07-16 11:43:57'),
(2, 'petrol', 'Petrol Station', 'Fire & Security checklist for petrol service stations', '2026-07-16 11:43:57');

-- --------------------------------------------------------

--
-- Table structure for table `inspections`
--

CREATE TABLE `inspections` (
  `id` int(11) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `inspection_date` date NOT NULL,
  `inspector_name` varchar(100) DEFAULT NULL,
  `status` enum('draft','completed','archived') DEFAULT 'draft',
  `observations` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `owner_recommendations` text DEFAULT NULL,
  `owner_rep_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `inspections`
--

INSERT INTO `inspections` (`id`, `entity_id`, `inspection_date`, `inspector_name`, `status`, `observations`, `recommendations`, `owner_recommendations`, `owner_rep_name`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-07-29', 'City of Kigali Inspector', 'completed', '', '', '', '', '2026-07-29 15:12:19', '2026-07-29 15:12:19');

-- --------------------------------------------------------

--
-- Table structure for table `inspection_answers`
--

CREATE TABLE `inspection_answers` (
  `id` int(11) NOT NULL,
  `inspection_id` int(11) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `status` enum('yes','no','na','') DEFAULT '',
  `comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `inspection_answers`
--

INSERT INTO `inspection_answers` (`id`, `inspection_id`, `item_id`, `status`, `comment`) VALUES
(1, 1, 1, 'yes', ''),
(2, 1, 2, 'yes', ''),
(3, 1, 8, 'yes', ''),
(4, 1, 9, 'yes', ''),
(5, 1, 13, 'no', ''),
(6, 1, 14, 'no', ''),
(7, 1, 15, 'no', ''),
(8, 1, 45, 'no', ''),
(9, 1, 46, 'no', '');

-- --------------------------------------------------------

--
-- Table structure for table `inspection_team`
--

CREATE TABLE `inspection_team` (
  `id` int(11) NOT NULL,
  `inspection_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `institution` varchar(100) DEFAULT NULL,
  `signature` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `inspection_team`
--

INSERT INTO `inspection_team` (`id`, `inspection_id`, `name`, `institution`, `signature`) VALUES
(1, 1, 'ITANGISHAKA Fidele', 'DFG', '');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `inspection_id` int(11) DEFAULT NULL,
  `report_number` varchar(50) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `inspection_id`, `report_number`, `content`, `generated_at`) VALUES
(1, 1, 'COK/INSP/2026/C4CA42', '<div style=\'font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 40px;\'><div style=\'display: flex; justify-content: space-between; border-bottom: 3px solid #122A4B; padding-bottom: 16px; margin-bottom: 24px;\'><div><h1 style=\'color: #122A4B; margin: 0;\'>City of Kigali</h1><p style=\'color: #5B6270; margin: 4px 0 0;\'>Electrical & Mechanical Inspection Unit</p></div><div style=\'text-align: right; font-size: 13px; color: #5B6270;\'>Ref: COK/INSP/2026/C4CA42<br>Date: 2026-07-29</div></div><h2 style=\'color: #122A4B;\'>Fire & Security Inspection Report</h2><p><strong>Entity:</strong> AFOO</p><p><strong>Type:</strong> Occupied Building</p><p><strong>Location:</strong> , </p><div style=\'background: #f8f9fa; padding: 16px; border-radius: 6px; margin: 16px 0;\'><h3 style=\'margin-top: 0;\'>Compliance Summary</h3><div style=\'display: flex; gap: 30px; flex-wrap: wrap;\'><div><span style=\'color: #1F7A54; font-weight: 700; font-size: 24px;\'>4</span> Compliant</div><div><span style=\'color: #B93C2C; font-weight: 700; font-size: 24px;\'>5</span> Non-Compliant</div><div><span style=\'color: #B4790E; font-weight: 700; font-size: 24px;\'>0</span> Not Applicable</div><div><span style=\'font-weight: 700; font-size: 24px;\'>44%</span> Compliance Rate</div></div></div><h3 style=\'color: #B93C2C;\'>Non-Compliant Items</h3><div style=\'padding: 8px 0; border-bottom: 1px solid #eee;\'><div style=\'font-size: 12px; color: #5B6270;\'>Fire Safety Equipment</div><div>Water for firefighting</div></div><div style=\'padding: 8px 0; border-bottom: 1px solid #eee;\'><div style=\'font-size: 12px; color: #5B6270;\'>Fire Safety Equipment</div><div>Hose reel (30m)</div></div><div style=\'padding: 8px 0; border-bottom: 1px solid #eee;\'><div style=\'font-size: 12px; color: #5B6270;\'>Fire Safety Equipment</div><div>Fire hydrants with Storz coupling (45mm or 75mm)</div></div><div style=\'padding: 8px 0; border-bottom: 1px solid #eee;\'><div style=\'font-size: 12px; color: #5B6270;\'>Other Safety &amp; Security</div><div>Assembly areas and signs</div></div><div style=\'padding: 8px 0; border-bottom: 1px solid #eee;\'><div style=\'font-size: 12px; color: #5B6270;\'>Other Safety &amp; Security</div><div>PWD facilities (toilet, signs, way)</div></div></div>', '2026-07-29 15:12:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `checklist_items`
--
ALTER TABLE `checklist_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `checklist_sections`
--
ALTER TABLE `checklist_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `entity_type_id` (`entity_type_id`);

--
-- Indexes for table `entities`
--
ALTER TABLE `entities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `entity_type_id` (`entity_type_id`);

--
-- Indexes for table `entity_types`
--
ALTER TABLE `entity_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `inspections`
--
ALTER TABLE `inspections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `entity_id` (`entity_id`);

--
-- Indexes for table `inspection_answers`
--
ALTER TABLE `inspection_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inspection_id` (`inspection_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `inspection_team`
--
ALTER TABLE `inspection_team`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inspection_id` (`inspection_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `inspection_id` (`inspection_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `checklist_items`
--
ALTER TABLE `checklist_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `checklist_sections`
--
ALTER TABLE `checklist_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `entities`
--
ALTER TABLE `entities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `entity_types`
--
ALTER TABLE `entity_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inspections`
--
ALTER TABLE `inspections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inspection_answers`
--
ALTER TABLE `inspection_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `inspection_team`
--
ALTER TABLE `inspection_team`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `checklist_items`
--
ALTER TABLE `checklist_items`
  ADD CONSTRAINT `checklist_items_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `checklist_sections` (`id`);

--
-- Constraints for table `checklist_sections`
--
ALTER TABLE `checklist_sections`
  ADD CONSTRAINT `checklist_sections_ibfk_1` FOREIGN KEY (`entity_type_id`) REFERENCES `entity_types` (`id`);

--
-- Constraints for table `entities`
--
ALTER TABLE `entities`
  ADD CONSTRAINT `entities_ibfk_1` FOREIGN KEY (`entity_type_id`) REFERENCES `entity_types` (`id`);

--
-- Constraints for table `inspections`
--
ALTER TABLE `inspections`
  ADD CONSTRAINT `inspections_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`);

--
-- Constraints for table `inspection_answers`
--
ALTER TABLE `inspection_answers`
  ADD CONSTRAINT `inspection_answers_ibfk_1` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inspection_answers_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `checklist_items` (`id`);

--
-- Constraints for table `inspection_team`
--
ALTER TABLE `inspection_team`
  ADD CONSTRAINT `inspection_team_ibfk_1` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
