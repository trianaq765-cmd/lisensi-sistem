<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/../src/LicenseManager.php';

$manager = new LicenseManager();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// Cek API Key untuk endpoint protected
$protected = ['generate', 'list', 'revoke', 'stats'];
if (in_array($action, $protected)) {
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $data['api_key'] ?? '';
    if ($apiKey !== API_KEY) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

switch ($action) {
    case 'validate':
        echo json_encode($manager->validate($data['license_key'] ?? ''));
        break;
    case 'activate':
        echo json_encode($manager->activate($data['license_key'] ?? '', $data['hardware_id'] ?? ''));
        break;
    case 'deactivate':
        echo json_encode($manager->deactivate($data['license_key'] ?? '', $data['hardware_id'] ?? ''));
        break;
    case 'generate':
        echo json_encode($manager->generate($data));
        break;
    case 'list':
        echo json_encode(['success' => true, 'licenses' => $manager->getAll()]);
        break;
    case 'revoke':
        echo json_encode($manager->revoke($data['license_key'] ?? ''));
        break;
    case 'stats':
        echo json_encode(['success' => true, 'stats' => $manager->getStats()]);
        break;
    default:
        echo json_encode(['error' => 'Invalid action', 'actions' => ['validate','activate','deactivate','generate','list','revoke','stats']]);
}
