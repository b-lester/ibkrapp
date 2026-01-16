<?php
declare(strict_types=1);

/**
 * Manage user preferences in preferences.json
 */

header('Content-Type: application/json; charset=utf-8');

$prefsFile = __DIR__ . '/preferences.json';

// Initialize file if not exists
if (!file_exists($prefsFile)) {
    file_put_contents($prefsFile, json_encode([], JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo file_get_contents($prefsFile);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }

    $prefs = json_decode(file_get_contents($prefsFile), true);
    
    // Merge new preferences into existing ones
    foreach ($input as $key => $value) {
        $prefs[$key] = $value;
    }

    file_put_contents($prefsFile, json_encode($prefs, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true, 'preferences' => $prefs]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
