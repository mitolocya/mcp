<?php
/**
 * Model Context Protocol (MCP) - PHP Sample Server with API Key Protection
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- 1. API ANAHTARI KONTROLÜ ---
// Belirleyeceğiniz gizli anahtar (Bunu istemci tarafında da kullanacaksınız)
$SECRET_API_KEY = "BURAYA_GUCLU_BIR_ANAHTAR_YAZIN"; 

// Gelen isteğin başlıklarından (headers) API anahtarını alalım
$headers = getallheaders();
$clientApiKey = $headers['X-API-Key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';

if ($clientApiKey !== $SECRET_API_KEY) {
    http_response_code(401);
    echo json_encode([
        "jsonrpc" => "2.0",
        "error" => ["code" => -32000, "message" => "Unauthorized: Geçersiz veya eksik API anahtarı."],
        "id" => null
    ]);
    exit;
}
// ---------------------------------

// Read incoming JSON-RPC request
$input = file_get_contents('php://input');
$request = json_decode($input, true);

if (!$request || !isset($request['method'])) {
    echo json_encode([
        "jsonrpc" => "2.0",
        "error" => ["code" => -32600, "message" => "Invalid Request"],
        "id" => null
    ]);
    exit;
}

$id = $request['id'] ?? null;
$method = $request['method'];
$params = $request['params'] ?? [];

$response = [
    "jsonrpc" => "2.0",
    "id" => $id
];

switch ($method) {
    case 'initialize':
        $response['result'] = [
            "protocolVersion" => "2024-11-05",
            "capabilities" => [
                "tools" => [
                    "listChanged" => true
                ]
            ],
            "serverInfo" => [
                "name" => "php-mcp-website-server",
                "version" => "1.0.0"
            ]
        ];
        break;

    case 'tools/list':
        $response['result'] = [
            "tools" => [
                [
                    "name" => "get_site_stats",
                    "description" => "Fetches basic website statistics and system status.",
                    "inputSchema" => [
                        "type" => "object",
                        "properties" => [],
                        "required" => []
                    ]
                ]
            ]
        ];
        break;

    case 'tools/call':
        $toolName = $params['name'] ?? '';
        if ($toolName === 'get_site_stats') {
            $siteData = [
                "status" => "active",
                "active_users" => 1420,
                "server_load" => "Normal",
                "php_version" => phpversion()
            ];
            
            $response['result'] = [
                "content" => [
                    [
                        "type" => "text",
                        "text" => json_encode($siteData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    ]
                ]
            ];
        } else {
            $response['error'] = [
                "code" => -32601,
                "message" => "Tool not found: {$toolName}"
            ];
        }
        break;

    default:
        $response['error'] = [
            "code" => -32601,
            "message" => "Method not found: {$method}"
        ];
        break;
}

echo json_encode($response);
