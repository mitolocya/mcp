# PHP Model Context Protocol (MCP) Website Connector

This project enables PHP-based websites or SaaS applications to securely connect with AI clients (such as Claude Desktop) using the **Model Context Protocol (MCP)** standard, protected by an **API Key** authentication mechanism.

---

## 1. Generating a Secure API Key

To secure your MCP server, you need a strong, unguessable API key. You can generate one using your operating system's terminal or command line:

### On Windows (PowerShell)
Run the following command in PowerShell to generate a secure random string:
```powershell
[Convert]::ToBase64String([System.Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
```
Copy the resulting output string to use as your API key.

### On Linux / macOS (Terminal)
Run the following command to generate a random 32-byte hex string:
```bash
openssl rand -hex 32
```
Copy the resulting output string to use as your API key.

---

## 2. Server Implementation (`mcp-server.php`)

Create a file named `mcp-server.php` on your server and paste the following PHP code:

```php
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

// --- API KEY VALIDATION ---
$SECRET_API_KEY = "YOUR_GENERATED_SECURE_KEY"; // Replace with your generated key

$headers = function_exists('getallheaders') ? getallheaders() : [];
$clientApiKey = $headers['X-API-Key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';

$envApiKey = getenv('MCP_API_KEY');
if ($envApiKey && $clientApiKey === '') {
    $clientApiKey = $envApiKey;
}

if ($clientApiKey !== $SECRET_API_KEY) {
    http_response_code(401);
    echo json_encode([
        "jsonrpc" => "2.0",
        "error" => ["code" => -32000, "message" => "Unauthorized: Invalid or missing API key."],
        "id" => null
    ]);
    exit;
}
// --------------------------

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
```

---

## 3. Installation & Setup

1. **Configure your API Key:**
   In `mcp-server.php`, update `$SECRET_API_KEY` with the key you generated in Step 1.
2. **Upload / Place the File:** 
   Place `mcp-server.php` in your local project directory or upload it to your web server.
3. **Verify PHP Version:** 
   Ensure your environment is running **PHP 8.1 or higher**.

---

## 4. Connecting with Claude Desktop

Add your PHP script as an MCP server by updating your Claude Desktop configuration file (`claude_desktop_config.json`).

### Configuration File Paths:
- **Windows:** `%APPDATA%\Claude\claude_desktop_config.json`
- **Linux:** `~/.config/Claude/claude_desktop_config.json`
- **macOS:** `~/Library/Application Support/Claude/claude_desktop_config.json`

### Configuration Example:

```json
{
  "mcpServers": {
    "my-php-website": {
      "command": "php",
      "args": [
        "C:\\path\\to\\your\\mcp-server.php"
      ],
      "env": {
        "MCP_API_KEY": "YOUR_GENERATED_SECURE_KEY"
      }
    }
  }
}
```

> **Note for Windows Users:** Make sure to use double backslashes (`\\`) in the file path inside JSON (e.g., `C:\\Users\\BWT\\projects\\mcp-server.php`).

---

## 5. Testing the Server

You can test if the server requires the API key correctly by making a `POST` request or testing via terminal:

```bash
curl -X POST https://yourdomain.com/mcp-server.php \
     -H "Content-Type: application/json" \
     -H "X-API-Key: YOUR_GENERATED_SECURE_KEY" \
     -d '{"jsonrpc": "2.0", "method": "initialize", "id": 1}'
```
