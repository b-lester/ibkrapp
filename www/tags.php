<?php
declare(strict_types=1);

/**
 * Manage ticker tags in tags.json
 */

header('Content-Type: application/json; charset=utf-8');

$tagsFile = __DIR__ . '/tags.json';

// Initialize file if not exists
if (!file_exists($tagsFile)) {
    file_put_contents($tagsFile, json_encode([], JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo file_get_contents($tagsFile);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['ticker']) || !isset($input['tag'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing ticker or tag']);
        exit;
    }

    $ticker = strtoupper(trim($input['ticker']));
    $tag = trim($input['tag']);

    $tags = json_decode(file_get_contents($tagsFile), true);
    
    if ($tag === '') {
        unset($tags[$ticker]);
    } else {
        $tags[$ticker] = $tag;
    }

    file_put_contents($tagsFile, json_encode($tags, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true, 'tags' => $tags]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
