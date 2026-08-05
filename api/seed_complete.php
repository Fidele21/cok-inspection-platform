<?php
/**
 * Complete Database Seeder with Error Handling
 * Visit: http://localhost/inspection-platform/api/seed_complete.php
 */

header('Content-Type: application/json');

require_once '../config/database.php';

try {
    $pdo = getDB();
    
    // Check if entity types exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM entity_types");
    if ($stmt->fetchColumn() < 2) {
        // Insert entity types
        $pdo->exec("
            INSERT IGNORE INTO entity_types (code, name, description) VALUES
            ('building', 'Occupied Building', 'Fire & Security checklist for buildings categories 4/5'),
            ('petrol', 'Petrol Station', 'Fire & Security checklist for petrol service stations')
        ");
    }
    
    // Clear existing data
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE inspection_answers");
    $pdo->exec("TRUNCATE TABLE inspection_team");
    $pdo->exec("TRUNCATE TABLE reports");
    $pdo->exec("TRUNCATE TABLE inspections");
    $pdo->exec("TRUNCATE TABLE entities");
    $pdo->exec("TRUNCATE TABLE checklist_items");
    $pdo->exec("TRUNCATE TABLE checklist_sections");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Reset auto-increment
    $pdo->exec("ALTER TABLE checklist_sections AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE checklist_items AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE entities AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE inspections AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE inspection_answers AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE inspection_team AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE reports AUTO_INCREMENT = 1");
    
    // Get entity type IDs
    $buildingId = $pdo->query("SELECT id FROM entity_types WHERE code='building'")->fetchColumn();
    $petrolId = $pdo->query("SELECT id FROM entity_types WHERE code='petrol'")->fetchColumn();
    
    if (!$buildingId || !$petrolId) {
        throw new Exception("Entity types not found. Please run the initial migration first.");
    }
    
    $pdo->beginTransaction();
    
    // ============================================================
    // BUILDING CHECKLIST
    // ============================================================
    
    // Insert Building Sections
    $buildingSections = [
        ['1', 'General Assessment', 1],
        ['2', 'Fire Safety Equipment', 2],
        ['3', 'Electrical Installation', 3],
        ['4', 'Other Safety & Security', 4]
    ];
    
    $buildingSectionIds = [];
    foreach ($buildingSections as $s) {
        $stmt = $pdo->prepare("
            INSERT INTO checklist_sections (entity_type_id, section_number, title, sort_order)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$buildingId, $s[0], $s[1], $s[2]]);
        $buildingSectionIds[$s[0]] = $pdo->lastInsertId();
    }
    
    // Building Items
    $buildingItems = [
        // Section 1: General Assessment (7 items)
        ['section' => '1', 'code' => 'physical_assessment', 'label' => 'Entity physical assessment (status, zone, usage, accessibility)', 'sort' => 1],
        ['section' => '1', 'code' => 'construction_permit', 'label' => 'Construction permit', 'sort' => 2],
        ['section' => '1', 'code' => 'occupation_permit', 'label' => 'Occupation permit', 'sort' => 3],
        ['section' => '1', 'code' => 'eia_certificate', 'label' => 'EIA certificate (where applicable)', 'sort' => 4],
        ['section' => '1', 'code' => 'building_insurance', 'label' => 'Building insurance', 'sort' => 5],
        ['section' => '1', 'code' => 'building_user_insurance', 'label' => 'Building user insurance', 'sort' => 6],
        ['section' => '1', 'code' => 'security_staff_trained', 'label' => 'Security managers, staff, and trained users', 'sort' => 7],
        
        // Section 2: Fire Safety Equipment (13 items)
        ['section' => '2', 'code' => 'dcp_extinguisher', 'label' => 'DCP (dry chemical powder) extinguisher', 'sort' => 1],
        ['section' => '2', 'code' => 'co2_extinguisher', 'label' => 'CO₂ (carbon dioxide) extinguisher', 'sort' => 2],
        ['section' => '2', 'code' => 'foam_extinguisher', 'label' => 'Foam (foam liquid) extinguisher', 'sort' => 3],
        ['section' => '2', 'code' => 'auto_suppression', 'label' => 'Automated suppression system (DSPA, FM200, etc.)', 'sort' => 4],
        ['section' => '2', 'code' => 'fire_blanket', 'label' => 'Fire blanket', 'sort' => 5],
        ['section' => '2', 'code' => 'water_supply', 'label' => 'Water for firefighting', 'sort' => 6],
        ['section' => '2', 'code' => 'hose_reel', 'label' => 'Hose reel (30m)', 'sort' => 7],
        ['section' => '2', 'code' => 'fire_hydrants', 'label' => 'Fire hydrants with Storz coupling (45mm or 75mm)', 'sort' => 8],
        ['section' => '2', 'code' => 'water_reservoir', 'label' => 'Water reservoir / tank', 'sort' => 9],
        ['section' => '2', 'code' => 'water_pump', 'label' => 'Water pump (8–15 bars)', 'sort' => 10],
        ['section' => '2', 'code' => 'water_sprinkler', 'label' => 'Water sprinkler system', 'sort' => 11],
        ['section' => '2', 'code' => 'foam_sprinkler', 'label' => 'Foam sprinkler system', 'sort' => 12],
        ['section' => '2', 'code' => 'dust_vase', 'label' => 'Dust vase with shovels', 'sort' => 13],
        
        // Section 3: Electrical Installation (12 items)
        ['section' => '3', 'code' => 'electrical_cert', 'label' => 'Electrical installation compliance certificate', 'sort' => 1],
        ['section' => '3', 'code' => 'surge_protection', 'label' => 'Surge protection system', 'sort' => 2],
        ['section' => '3', 'code' => 'lightning_arrester', 'label' => 'Para-lightning system (lightning arrester)', 'sort' => 3],
        ['section' => '3', 'code' => 'generator', 'label' => 'Generator', 'sort' => 4],
        ['section' => '3', 'code' => 'ups', 'label' => 'Uninterruptible power supply (UPS)', 'sort' => 5],
        ['section' => '3', 'code' => 'elevator_controls', 'label' => 'Elevator: accessible controls (900–1100mm)', 'sort' => 6],
        ['section' => '3', 'code' => 'elevator_braille', 'label' => 'Elevator: braille, tactile buttons and sound', 'sort' => 7],
        ['section' => '3', 'code' => 'elevator_fire_sign', 'label' => 'Elevator: fire warning sign (Kinyarwanda and English, ≥15mm)', 'sort' => 8],
        ['section' => '3', 'code' => 'elevator_inspection', 'label' => 'Elevator inspected every 6 months', 'sort' => 9],
        ['section' => '3', 'code' => 'maintenance_logbook', 'label' => 'Maintenance records / logbook available', 'sort' => 10],
        ['section' => '3', 'code' => 'electrical_diagrams', 'label' => 'Electrical diagrams available', 'sort' => 11],
        ['section' => '3', 'code' => 'escalator_condition', 'label' => 'Escalator condition', 'sort' => 12],
        
        // Section 4: Other Safety & Security (18 items)
        ['section' => '4', 'code' => 'fire_alarm', 'label' => 'Fire alarm system with control panel', 'sort' => 1],
        ['section' => '4', 'code' => 'smoke_detectors', 'label' => 'Smoke detectors', 'sort' => 2],
        ['section' => '4', 'code' => 'gas_detectors', 'label' => 'Gas detectors with shutters', 'sort' => 3],
        ['section' => '4', 'code' => 'beam_detection', 'label' => 'Beam detection', 'sort' => 4],
        ['section' => '4', 'code' => 'heat_detection', 'label' => 'Heat detection', 'sort' => 5],
        ['section' => '4', 'code' => 'exit_routes', 'label' => 'Emergency exit routes', 'sort' => 6],
        ['section' => '4', 'code' => 'exit_signs', 'label' => 'Emergency exit signs', 'sort' => 7],
        ['section' => '4', 'code' => 'floor_plan', 'label' => 'Floor plan', 'sort' => 8],
        ['section' => '4', 'code' => 'evacuation_plan', 'label' => 'Emergency evacuation plan', 'sort' => 9],
        ['section' => '4', 'code' => 'level_signs', 'label' => 'Number signs on each level', 'sort' => 10],
        ['section' => '4', 'code' => 'elevator_fire_notice', 'label' => 'Sign forbidding elevator use in case of fire', 'sort' => 11],
        ['section' => '4', 'code' => 'emergency_numbers', 'label' => 'Emergency response phone numbers (police, fire, ambulance)', 'sort' => 12],
        ['section' => '4', 'code' => 'assembly_areas', 'label' => 'Assembly areas and signs', 'sort' => 13],
        ['section' => '4', 'code' => 'pwd_facilities', 'label' => 'PWD facilities (toilet, signs, way)', 'sort' => 14],
        ['section' => '4', 'code' => 'cctv', 'label' => 'CCTV camera and control room (≥3 months storage)', 'sort' => 15],
        ['section' => '4', 'code' => 'search_mechanism', 'label' => 'Searching mechanism (scanner, detectors, mirror)', 'sort' => 16],
        ['section' => '4', 'code' => 'first_aid', 'label' => 'First aid boxes', 'sort' => 17],
        ['section' => '4', 'code' => 'previously_visited', 'label' => 'Previously visited / inspected before', 'sort' => 18]
    ];
    
    // Insert building items
    $buildingCount = 0;
    foreach ($buildingItems as $item) {
        $stmt = $pdo->prepare("
            INSERT INTO checklist_items (section_id, item_code, label, sort_order)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $buildingSectionIds[$item['section']],
            $item['code'],
            $item['label'],
            $item['sort']
        ]);
        $buildingCount++;
    }
    
    // ============================================================
    // PETROL STATION CHECKLIST
    // ============================================================
    
    // Insert Petrol Sections
    $petrolSections = [
        ['1', 'Plot Size Requirements', 1],
        ['2', 'Road Safety Considerations', 2],
        ['3', 'Distance of Fuel Tanks from Residential Houses', 3],
        ['4', 'Distance Between Service Station and Power Line', 4],
        ['5', 'Distance from Sensitive Areas', 5],
        ['6', 'Firefighting and Security Systems', 6],
        ['7', 'Warning Signs at Petrol Station', 7],
        ['8', 'Electrical Installation Requirements', 8],
        ['9', 'Sanitary Facilities', 9],
        ['10', 'Permitting and Licensing', 10]
    ];
    
    $petrolSectionIds = [];
    foreach ($petrolSections as $s) {
        $stmt = $pdo->prepare("
            INSERT INTO checklist_sections (entity_type_id, section_number, title, sort_order)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$petrolId, $s[0], $s[1], $s[2]]);
        $petrolSectionIds[$s[0]] = $pdo->lastInsertId();
    }
    
    // Petrol Items
    $petrolItems = [
        ['section' => '1', 'code' => 'plot_size_compliant', 'label' => 'Plot size complies with requirements: minimum 1,500 m² (EV charging only) or 2,000 m² (EV charging with service bay)', 'sort' => 1],
        ['section' => '2', 'code' => 'sight_distance', 'label' => 'Minimum sight distance of 100 meters maintained from both the entrance and exit', 'sort' => 1],
        ['section' => '2', 'code' => 'junction_distance', 'label' => 'Minimum distance of 75 meters maintained between the nearest facility and the nearest road junction/intersection', 'sort' => 2],
        ['section' => '3', 'code' => 'tank_residential_distance', 'label' => 'Fuel storage tanks, vents, and dispensers located at least 30 meters from residential plots/houses, or a 3m-high brick/concrete fence installed where this is not met', 'sort' => 1],
        ['section' => '4', 'code' => 'power_line_clearance', 'label' => 'Horizontal clearance from power lines meets requirements: minimum 15m for 15–220 kV lines, minimum 20m for 400 kV lines', 'sort' => 1],
        ['section' => '5', 'code' => 'sensitive_area_distance', 'label' => 'Minimum 300 meters maintained from sensitive areas (memorial sites, religious facilities, correctional facilities, health centers/clinics/hospitals, schools, national security facilities, markets, hotels, sports stadiums, airports, etc.)', 'sort' => 1],
        ['section' => '6', 'code' => 'fire_hydrant_45', 'label' => 'Fire hydrant with 45mm Storz coupling installed', 'sort' => 1],
        ['section' => '6', 'code' => 'extinguishers_set', 'label' => 'Fire extinguishers available: 9kg dry powder, 9kg CO₂, 9kg foam, and two (2) 25kg dry powder extinguishers', 'sort' => 2],
        ['section' => '6', 'code' => 'hose_reel_functional', 'label' => 'Fire hose reel installed and fully functional', 'sort' => 3],
        ['section' => '6', 'code' => 'dry_sand_shovels', 'label' => 'Dry fine sand available together with two (2) shovels', 'sort' => 4],
        ['section' => '6', 'code' => 'smoke_detectors_p', 'label' => 'Smoke detectors installed and operational', 'sort' => 5],
        ['section' => '6', 'code' => 'fire_alarm_p', 'label' => 'Fire alarm system with control panel installed and functional', 'sort' => 6],
        ['section' => '6', 'code' => 'evacuation_plan_p', 'label' => 'Emergency evacuation plan available and clearly displayed with signage', 'sort' => 7],
        ['section' => '6', 'code' => 'assembly_point', 'label' => 'Emergency assembly point identified and properly signposted', 'sort' => 8],
        ['section' => '6', 'code' => 'emergency_hotline', 'label' => 'Emergency hotline displayed and easily accessible', 'sort' => 9],
        ['section' => '6', 'code' => 'water_reserve_30', 'label' => 'Water reserve tank with minimum capacity of 30 m³ available', 'sort' => 10],
        ['section' => '6', 'code' => 'water_pump_8bar', 'label' => 'Water pressure pump of 8 bar installed for firefighting', 'sort' => 11],
        ['section' => '7', 'code' => 'warning_pictograms', 'label' => 'Warning notices and pictograms boldly installed on canopy columns, vent pipes, and storage tank areas', 'sort' => 1],
        ['section' => '7', 'code' => 'signs_visible_7_5m', 'label' => 'All warning signs clearly visible and readable from a minimum distance of 7.5 meters', 'sort' => 2],
        ['section' => '7', 'code' => 'entrance_exit_signs', 'label' => 'Entrance and exit signs illuminated/retro-reflective, readable from a minimum distance of 50 meters', 'sort' => 3],
        ['section' => '8', 'code' => 'dispenser_breaker', 'label' => 'Dispenser circuit fitted with an isolating circuit breaker', 'sort' => 1],
        ['section' => '8', 'code' => 'emergency_stop', 'label' => 'Emergency stop switch installed to shut down the entire electrical system', 'sort' => 2],
        ['section' => '8', 'code' => 'auto_power_backup', 'label' => 'Automatic power backup system installed', 'sort' => 3],
        ['section' => '8', 'code' => 'lightning_protection', 'label' => 'Lightning protection system installed', 'sort' => 4],
        ['section' => '8', 'code' => 'certified_electrician', 'label' => 'All electrical installations carried out by a certified electrical practitioner', 'sort' => 5],
        ['section' => '9', 'code' => 'sanitary_facilities', 'label' => 'Sanitary facilities provided per regulations: designated toilets for male (with urinals), female, and persons with disabilities', 'sort' => 1],
        ['section' => '9', 'code' => 'changing_rooms', 'label' => 'At least two (2) changing rooms available and accessible', 'sort' => 2],
        ['section' => '9', 'code' => 'sewage_system', 'label' => 'Functional sewage treatment system installed and properly operating', 'sort' => 3],
        ['section' => '10', 'code' => 'eia_cert_p', 'label' => 'Copy of EIA certificate', 'sort' => 1],
        ['section' => '10', 'code' => 'construction_permit_p', 'label' => 'Construction permit', 'sort' => 2],
        ['section' => '10', 'code' => 'occupation_permit_p', 'label' => 'Occupation permit', 'sort' => 3],
        ['section' => '10', 'code' => 'tank_calibration_cert', 'label' => 'Fuel tank calibration certificate from a licensed service provider', 'sort' => 4],
        ['section' => '10', 'code' => 'six_month_testing', 'label' => 'Dispensers and safety/security equipment tested every six (6) months by a competent authority with valid certification', 'sort' => 5]
    ];
    
    // Insert petrol items
    $petrolCount = 0;
    foreach ($petrolItems as $item) {
        $stmt = $pdo->prepare("
            INSERT INTO checklist_items (section_id, item_code, label, sort_order)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $petrolSectionIds[$item['section']],
            $item['code'],
            $item['label'],
            $item['sort']
        ]);
        $petrolCount++;
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => '✅ Database seeded successfully!',
        'building_sections' => count($buildingSections),
        'building_items' => $buildingCount,
        'petrol_sections' => count($petrolSections),
        'petrol_items' => $petrolCount,
        'total_items' => $buildingCount + $petrolCount
    ]);
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>