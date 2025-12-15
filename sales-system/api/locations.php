<?php
/**
 * Locations API
 * Provides Vietnamese administrative divisions (Provinces, Districts, Wards)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    errorResponse('Method not allowed', 405);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'provinces':
        getProvinces();
        break;

    case 'districts':
        getDistricts();
        break;

    case 'wards':
        getWards();
        break;

    case 'search':
        searchLocations();
        break;

    default:
        errorResponse('Invalid action. Use: provinces, districts, wards, or search', 400);
}

/**
 * Get all provinces
 */
function getProvinces() {
    $provinces = db()->fetchAll(
        "SELECT id, code, name, name_en FROM provinces ORDER BY name ASC"
    );

    successResponse('', ['provinces' => $provinces]);
}

/**
 * Get districts by province
 */
function getDistricts() {
    if (!isset($_GET['province_id']) || empty($_GET['province_id'])) {
        errorResponse('province_id is required', 400);
    }

    $provinceId = (int)$_GET['province_id'];

    $districts = db()->fetchAll(
        "SELECT id, code, name, name_en, province_id
         FROM districts
         WHERE province_id = ?
         ORDER BY name ASC",
        [$provinceId]
    );

    successResponse('', ['districts' => $districts]);
}

/**
 * Get wards by district
 */
function getWards() {
    if (!isset($_GET['district_id']) || empty($_GET['district_id'])) {
        errorResponse('district_id is required', 400);
    }

    $districtId = (int)$_GET['district_id'];

    $wards = db()->fetchAll(
        "SELECT id, code, name, name_en, district_id
         FROM wards
         WHERE district_id = ?
         ORDER BY name ASC",
        [$districtId]
    );

    successResponse('', ['wards' => $wards]);
}

/**
 * Search locations by name
 */
function searchLocations() {
    if (!isset($_GET['q']) || empty($_GET['q'])) {
        errorResponse('Search query (q) is required', 400);
    }

    $query = '%' . sanitize($_GET['q']) . '%';
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;

    $results = [];

    // Search provinces
    $provinces = db()->fetchAll(
        "SELECT id, code, name, 'province' as type
         FROM provinces
         WHERE name LIKE ? OR name_en LIKE ?
         LIMIT {$limit}",
        [$query, $query]
    );

    foreach ($provinces as $province) {
        $results[] = [
            'id' => $province['id'],
            'name' => $province['name'],
            'type' => 'province',
            'full_name' => $province['name']
        ];
    }

    // Search districts
    $districts = db()->fetchAll(
        "SELECT d.id, d.code, d.name, p.name as province_name, 'district' as type
         FROM districts d
         JOIN provinces p ON d.province_id = p.id
         WHERE d.name LIKE ? OR d.name_en LIKE ?
         LIMIT {$limit}",
        [$query, $query]
    );

    foreach ($districts as $district) {
        $results[] = [
            'id' => $district['id'],
            'name' => $district['name'],
            'type' => 'district',
            'full_name' => $district['name'] . ', ' . $district['province_name']
        ];
    }

    // Search wards
    $wards = db()->fetchAll(
        "SELECT w.id, w.code, w.name, d.name as district_name, p.name as province_name, 'ward' as type
         FROM wards w
         JOIN districts d ON w.district_id = d.id
         JOIN provinces p ON d.province_id = p.id
         WHERE w.name LIKE ? OR w.name_en LIKE ?
         LIMIT {$limit}",
        [$query, $query]
    );

    foreach ($wards as $ward) {
        $results[] = [
            'id' => $ward['id'],
            'name' => $ward['name'],
            'type' => 'ward',
            'full_name' => $ward['name'] . ', ' . $ward['district_name'] . ', ' . $ward['province_name']
        ];
    }

    successResponse('', ['results' => $results, 'total' => count($results)]);
}
