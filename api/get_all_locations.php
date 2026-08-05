<?php
/**
 * Get all City of Kigali locations for dropdowns
 * GET /api/get_all_locations.php
 */


require_once __DIR__ . '/../includes/api_guard.php';
api_boot();                       // JSON headers + login required

header('Content-Type: application/json');

// Complete City of Kigali location data
$kigaliLocations = [
    'Gasabo' => [
        'sectors' => [
            'Bumbogo' => ['Kinyaga', 'Musave', 'Mvuzo', 'Ngara', 'Nkuzuzu', 'Rwampara'],
            'Gatsata' => ['Karuruma', 'Nyamabuye', 'Nyamugari'],
            'Gikomero' => ['Gasagara', 'Gicaca', 'Kibara', 'Munini', 'Murambi'],
            'Gisozi' => ['Musezero', 'Ruhango'],
            'Jabana' => ['Akamatamu', 'Bweramvura', 'Kabuye', 'Kidashya','Ngiryi'],
            'Jali' => ['Agateko', 'Buhiza','Muko', 'Nkusi','Nyabuliba','Nyakabungo','Nyamitanga'],
            'Kacyiru' => ['Kamatamu', 'Kamutwa','Kibaza'],
            'Kimihurura' => ['Kamukina','Kimihurura', 'Rugando'],
            'Kimironko' => ['Bibare','Kibagabaga', 'Nyagatovu'],
            'Kinyinya' => ['Gacuriro','Gasharu','Kagugu','Murama'],
            'Ndera' => ['Bwiza', 'Cyaruzunge', 'Kibenga', 'Masoro', 'Mukuyu','Rudashya'],
            'Nduba' => ['Butare','Gasanze','Gasura','Gatunga','Muremure','Sha','Shango'],
            'Remera' => ['Nyabisindu','Nyarutarama','Rukiri I','Rukiri II'],
            'Rusororo' => ['Bisenga','Gasagara','Kabuga 1','Kabuga 2','Kinyana','Mbandazi','Nyagahinga','Ruhanga'],
            'Rutunga' => ['Gasabo','Kabaliza','Kagabiro','Kacyatwa','Kibenga','Ndatemwa'],
        ]
    ],
    'Kicukiro' => [
        'sectors' => [
            'Gahanga' => ['Gahanga', 'Kagasa', 'Karembure', 'Murinja', 'Nunga','Rwabutenge'],
            'Gatenga' => ['Gatenga', 'Karambo', 'Nyanza', 'Nyarurama'],
            'Gikondo' => ['Kansere', 'Kinunga', 'Kagunga'],
            'Kagarama' => ['Kanserege', 'Muyange', 'Rukatsa'],
            'Kanombe' => ['Busanza', 'Karama', 'Kabeza', 'Rubirizi'],
            'Kicukiro' => ['Gasharu', 'Kagina', 'Kicukiro', 'Ngoma'],
            'Kigarama' => ['Bwerankori', 'Karugira','Kigarama', 'Nyarurama', 'Rwampara'],
            'Masaka' => ['Ayabaraya','Cyimo', 'Gako', 'Gitaraga', 'Mbabe','Rusheshe'],
            'Niboye' => ['Gatare', 'Niboye', 'Nyakabanda'],
            'Nyarugunga' => ['Kamashashi', 'Nonko', 'Rwimbogo']
        ]
    ],
    'Nyarugenge' => [
        'sectors' => [
            'Gitega' => ['Akabahizi', 'Akabeza', 'Gacyamo', 'Kora', 'Kigarama','Kinyange'],
            'Kanyinya' => ['Nyamweru', 'Nzove','Taba'],
            'Kigali' => ['Kigali', 'Mwendo', 'Nyabugogo', 'Ruriba', 'Rwesero'],
            'Kimisagara' => ['Kamuhoza', 'Katabaro', 'Kimisagara'],
            'Mageragere' => ['Kankuba', 'Kavumu', 'Mataba', 'Ntungamo', 'Nyarufunzo','Nyarurenzi','Runzenze'],
            'Muhima' => ['Amahoro', 'Kabasengerezi','Kabeza', 'Nyabugogo','Rugenge', 'Tetero', 'Ubumwe'],
            'Nyakabanda' => ['Munanira I','Munanira II','Nyakabanda I','Nyakabanda II'],
            'Nyamirambo' => ['Cyivugiza', 'Gasharu', 'Mumena', 'Rugarama'],
            'Nyarugenge' => ['Agatare','Biryogo','Kiyovu','Rwampara'],
            'Rwezamenyo' => ['Kabuguru I', 'Kabuguru II', 'Rwezamenyo I','Rwezamenyo II']
            ]
    ]
];

echo json_encode(['success' => true, 'data' => $kigaliLocations]);
?>