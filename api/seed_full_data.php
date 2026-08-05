<?php
/**
 * Seed Complete Checklist Data
 * Run once to populate all checklist items
 * Visit: http://localhost/inspection-platform/api/seed_full_data.php
 */

header('Content-Type: application/json');

require_once '../config/database.php';

try {
    $pdo = getDB();
    
    // Check if data already exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM checklist_items");
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['message' => 'Checklist data already exists. Delete first if you want to re-seed.']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Get entity type IDs
    $buildingId = $pdo->query("SELECT id FROM entity_types WHERE code='building'")->fetchColumn();
    $petrolId = $pdo->query("SELECT id FROM entity_types WHERE code='petrol'")->fetchColumn();
    
    // ============================================================
    // BUILDING CHECKLIST - 4 Sections, 47 Items
    // ============================================================
    
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
    
    // Building Section 1: General Assessment (7 items)
    $buildingItems = [
        // Section 1: General Assessment
        ['physical_assessment', 'Entity physical assessment (status, zone, usage, accessibility)', 1, '1'],
        ['construction_permit', 'Construction permit', 2, '1'],
        ['occupation_permit', 'Occupation permit', 3, '1'],
        ['eia_certificate', 'EIA certificate (where applicable)', 4, '1'],
        ['building_insurance', 'Building insurance', 5, '1'],
        ['building_user_insurance', 'Building user insurance', 6, '1'],
        ['security_staff_trained', 'Security managers, staff, and trained users', 7, '1'],
        
        // Section 2: Fire Safety Equipment (13 items)
        ['dcp_extinguisher', 'DCP (dry chemical powder) extinguisher', 1, '2'],
        ['co2_extinguisher', 'CO₂ (carbon dioxide) extinguisher', 2, '2'],
        ['foam_extinguisher', 'Foam (foam liquid) extinguisher', 3, '2'],
        ['auto_suppression', 'Automated suppression system (DSPA, FM200, etc.)', 4, '2'],
        ['fire_blanket', 'Fire blanket', 5, '2'],
        ['water_supply', 'Water for firefighting', 6, '2'],
        ['hose_reel', 'Hose reel (30m)', 7, '2'],
        ['fire_hydrants', 'Fire hydrants with Storz coupling (45mm or 75mm)', 8, '2'],
        ['water_reservoir', 'Water reservoir / tank', 9, '2'],
        ['water_pump', 'Water pump (8–15 bars)', 10, '2'],
        ['water_sprinkler', 'Water sprinkler system', 11, '2'],
        ['foam_sprinkler', 'Foam sprinkler system', 12, '2'],
        ['dust_vase', 'Dust vase with shovels', 13, '2'],
        
        // Section 3: Electrical Installation (12 items)
        ['electrical_cert', 'Electrical installation compliance certificate', 1, '3'],
        ['surge_protection', 'Surge protection system', 2, '3'],
        ['lightning_arrester', 'Para-lightning system (lightning arrester)', 3, '3'],
        ['generator', 'Generator', 4, '3'],
        ['ups', 'Uninterruptible power supply (UPS)', 5, '3'],
        ['elevator_controls', 'Elevator: accessible controls (900–1100mm)', 6, '3'],
        ['elevator_braille', 'Elevator: braille, tactile buttons and sound', 7, '3'],
        ['elevator_fire_sign', 'Elevator: fire warning sign (Kinyarwanda and English, ≥15mm)', 8, '3'],
        ['elevator_inspection', 'Elevator inspected every 6 months', 9, '3'],
        ['maintenance_logbook', 'Maintenance records / logbook available', 10, '3'],
        ['electrical_diagrams', 'Electrical diagrams available', 11, '3'],
        ['escalator_condition', 'Escalator condition', 12, '3'],
        
        // Section 4: Other Safety & Security (15 items)
        ['fire_alarm', 'Fire alarm system with control panel', 1, '4'],
        ['smoke_detectors', 'Smoke detectors', 2, '4'],
        ['gas_detectors', 'Gas detectors with shutters', 3, '4'],
        ['beam_detection', 'Beam detection', 4, '4'],
        ['heat_detection', 'Heat detection', 5, '4'],
        ['exit_routes', 'Emergency exit routes', 6, '4'],
        ['exit_signs', 'Emergency exit signs', 7, '4'],
        ['floor_plan', 'Floor plan', 8, '4'],
        ['evacuation_plan', 'Emergency evacuation plan', 9, '4'],
        ['level_signs', 'Number signs on each level', 10, '4'],
        ['elevator_fire_notice', 'Sign forbidding elevator use in case of fire', 11, '4'],
        ['emergency_numbers', 'Emergency response phone numbers (police, fire, ambulance)', 12, '4'],
        ['assembly_areas', 'Assembly areas and signs', 13, '4'],
        ['pwd_facilities', 'PWD facilities (toilet, signs, way)', 14, '4'],
        ['cctv', 'CCTV camera and control room (≥3 months storage)', 15, '4'],
        ['search_mechanism', 'Searching mechanism (scanner, detectors, mirror)', 16, '4'],
        ['first_aid', 'First aid boxes', 17, '4'],
        ['previously_visited', 'Previously visited / inspected before', 18, '4']
    ];
    
    // Insert building items
    foreach ($buildingItems as $item) {
        $stmt = $pdo->prepare("
            INSERT INTO checklist_items (section_id, item_code, label, sort_order)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $buildingSectionIds[$item[3]],
            $item[0],
            $item[1],
            $item[2]
        ]);
    }
    
    // ============================================================
    // PETROL STATION CHECKLIST - 10 Sections, 27 Items
    // ============================================================
    
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
    
    // Petrol Station Items
    $petrolItems = [
        // Section 1: Plot Size (1 item)
        ['plot_size_compliant', 'Plot size complies with requirements: minimum 1,500 m² (EV charging only) or 2,000 m² (EV charging with service bay)', 1, '1'],
        
        // Section 2: Road Safety (2 items)
        ['sight_distance', 'Minimum sight distance of 100 meters maintained from both the entrance and exit', 1, '2'],
        ['junction_distance', 'Minimum distance of 75 meters maintained between the nearest facility and the nearest road junction/intersection', 2, '2'],
        
        // Section 3: Fuel Tank Distance (1 item)
        ['tank_residential_distance', 'Fuel storage tanks, vents, and dispensers located at least 30 meters from residential plots/houses, or a 3m-high brick/concrete fence installed where this is not met', 1, '3'],
        
        // Section 4: Power Line Distance (1 item)
        ['power_line_clearance', 'Horizontal clearance from power lines meets requirements: minimum 15m for 15–220 kV lines, minimum 20m for 400 kV lines', 1, '4'],
        
        // Section 5: Sensitive Areas (1 item)
        ['sensitive_area_distance', 'Minimum 300 meters maintained from sensitive areas (memorial sites, religious facilities, correctional facilities, health centers/clinics/hospitals, schools, national security facilities, markets, hotels, sports stadiums, airports, etc.)', 1, '5'],
        
        // Section 6: Firefighting Systems (11 items)
        ['fire_hydrant_45', 'Fire hydrant with 45mm Storz coupling installed', 1, '6'],
        ['extinguishers_set', 'Fire extinguishers available: 9kg dry powder, 9kg CO₂, 9kg foam, and two (2) 25kg dry powder extinguishers', 2, '6'],
        ['hose_reel_functional', 'Fire hose reel installed and fully functional', 3, '6'],
        ['dry_sand_shovels', 'Dry fine sand available together with two (2) shovels', 4, '6'],
        ['smoke_detectors_p', 'Smoke detectors installed and operational', 5, '6'],
        ['fire_alarm_p', 'Fire alarm system with control panel installed and functional', 6, '6'],
        ['evacuation_plan_p', 'Emergency evacuation plan available and clearly displayed with signage', 7, '6'],
        ['assembly_point', 'Emergency assembly point identified and properly signposted', 8, '6'],
        ['emergency_hotline', 'Emergency hotline displayed and easily accessible', 9, '6'],
        ['water_reserve_30', 'Water reserve tank with minimum capacity of 30 m³ available', 10, '6'],
        ['water_pump_8bar', 'Water pressure pump of 8 bar installed for firefighting', 11, '6'],
        
        // Section 7: Warning Signs (3 items)
        ['warning_pictograms', 'Warning notices and pictograms boldly installed on canopy columns, vent pipes, and storage tank areas', 1, '7'],
        ['signs_visible_7_5m', 'All warning signs clearly visible and readable from a minimum distance of 7.5 meters', 2, '7'],
        ['entrance_exit_signs', 'Entrance and exit signs illuminated/retro-reflective, readable from a minimum distance of 50 meters', 3, '7'],
        
        // Section 8: Electrical Installation (5 items)
        ['dispenser_breaker', 'Dispenser circuit fitted with an isolating circuit breaker', 1, '8'],
        ['emergency_stop', 'Emergency stop switch installed to shut down the entire electrical system', 2, '8'],
        ['auto_power_backup', 'Automatic power backup system installed', 3, '8'],
        ['lightning_protection', 'Lightning protection system installed', 4, '8'],
        ['certified_electrician', 'All electrical installations carried out by a certified electrical practitioner', 5, '8'],
        
        // Section 9: Sanitary Facilities (3 items)
        ['sanitary_facilities', 'Sanitary facilities provided per regulations: designated toilets for male (with urinals), female, and persons with disabilities', 1, '9'],
        ['changing_rooms', 'At least two (2) changing rooms available and accessible', 2, '9'],
        ['sewage_system', 'Functional sewage treatment system installed and properly operating', 3, '9'],
        
        // Section 10: Permitting (5 items)
        ['eia_cert_p', 'Copy of EIA certificate', 1, '10'],
        ['construction_permit_p', 'Construction permit', 2, '10'],
        ['occupation_permit_p', 'Occupation permit', 3, '10'],
        ['tank_calibration_cert', 'Fuel tank calibration certificate from a licensed service provider', 4, '10'],
        ['six_month_testing', 'Dispensers and safety/security equipment tested every six (6) months by a competent authority with valid certification', 5, '10']
    ];
    
    // Insert petrol items
    foreach ($petrolItems as $item) {
        $stmt = $pdo->prepare("
            INSERT INTO checklist_items (section_id, item_code, label, sort_order)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $petrolSectionIds[$item[3]],
            $item[0],
            $item[1],
            $item[2]
        ]);
    }
    
    $pdo->commit();
    
    // Get counts
    $buildingCount = $pdo->query("SELECT COUNT(*) FROM checklist_items WHERE section_id IN (SELECT id FROM checklist_sections WHERE entity_type_id = $buildingId)")->fetchColumn();
    $petrolCount = $pdo->query("SELECT COUNT(*) FROM checklist_items WHERE section_id IN (SELECT id FROM checklist_sections WHERE entity_type_id = $petrolId)")->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'message' => 'Checklist data seeded successfully!',
        'building_items' => (int)$buildingCount,
        'petrol_items' => (int)$petrolCount,
        'total_items' => (int)$buildingCount + (int)$petrolCount
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