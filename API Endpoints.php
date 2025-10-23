<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/DistrictModel.php';

$database = new Database();
$db = $database->getConnection();
$districtModel = new DistrictModel($db);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if (isset($_GET['action'])) {
            switch($_GET['action']) {
                case 'getStates':
                    getStates($db);
                    break;
                case 'getDistricts':
                    if (isset($_GET['state_id'])) {
                        getDistricts($db, $_GET['state_id']);
                    }
                    break;
                case 'getDistrictData':
                    if (isset($_GET['district_id'])) {
                        getDistrictData($db, $_GET['district_id']);
                    }
                    break;
                case 'getNationalStats':
                    getNationalStats($db);
                    break;
                case 'getTopDistricts':
                    getTopDistricts($db);
                    break;
            }
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        if (isset($data->action)) {
            switch($data->action) {
                case 'logLocation':
                    logUserLocation($db, $data);
                    break;
                case 'updateLanguagePref':
                    updateLanguagePreference($db, $data);
                    break;
            }
        }
        break;
}

function getStates($db) {
    $query = "SELECT state_id, state_name, state_name_hindi FROM states ORDER BY state_name";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $states = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "data" => $states]);
}

function getDistricts($db, $state_id) {
    $query = "SELECT district_id, district_name, district_name_hindi
              FROM districts
              WHERE state_id = :state_id
              ORDER BY district_name";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":state_id", $state_id);
    $stmt->execute();

    $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "data" => $districts]);
}

function getDistrictData($db, $district_id) {
    $districtModel = new DistrictModel($db);
    $performance = $districtModel->getDistrictPerformance($district_id);

    if ($performance) {
        echo json_encode(["success" => true, "data" => $performance]);
    } else {
        echo json_encode(["success" => false, "message" => "Data not found"]);
    }
}

function getNationalStats($db) {
    $query = "SELECT * FROM national_statistics
              ORDER BY stat_date DESC
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "data" => $stats]);
}

function getTopDistricts($db) {
    $query = "SELECT * FROM top_performing_districts_view LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "data" => $districts]);
}

function logUserLocation($db, $data) {
    $query = "INSERT INTO user_location_logs
              (session_id, latitude, longitude, state_detected, district_detected, accuracy_level, user_agent)
              VALUES (:session_id, :latitude, :longitude, :state_detected, :district_detected, :accuracy_level, :user_agent)";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":session_id", $data->session_id);
    $stmt->bindParam(":latitude", $data->latitude);
    $stmt->bindParam(":longitude", $data->longitude);
    $stmt->bindParam(":state_detected", $data->state_detected);
    $stmt->bindParam(":district_detected", $data->district_detected);
    $stmt->bindParam(":accuracy_level", $data->accuracy_level);
    $stmt->bindParam(":user_agent", $data->user_agent);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
}

function updateLanguagePreference($db, $data) {
    $query = "INSERT INTO user_language_preferences (session_id, preferred_language)
              VALUES (:session_id, :preferred_language)
              ON DUPLICATE KEY UPDATE preferred_language = :preferred_language, updated_at = CURRENT_TIMESTAMP";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":session_id", $data->session_id);
    $stmt->bindParam(":preferred_language", $data->preferred_language);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
}
?>