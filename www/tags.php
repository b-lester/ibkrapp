<?php
declare(strict_types=1);

/**
 * Manage ticker tags in tags.json with file locking to prevent race conditions.
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

    // Use file locking for atomic updates
    $fp = fopen($tagsFile, 'c+');
    if (!$fp) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not open data file']);
        exit;
    }

    if (flock($fp, LOCK_EX)) {
        $filesize = filesize($tagsFile);
        $content = $filesize > 0 ? fread($fp, $filesize) : '{}';
        $tags = json_decode($content, true);
        
        if ($tags === null && json_last_error() !== JSON_ERROR_NONE) {
            $tags = [];
        }

        if ($tag === '') {
            unset($tags[$ticker]);
        } else {
            $tags[$ticker] = $tag;
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($tags, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Could not lock data file']);
        fclose($fp);
        exit;
    }
    fclose($fp);

    echo json_encode(['success' => true, 'tags' => $tags]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
