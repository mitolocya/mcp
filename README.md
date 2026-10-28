bu readme dosyasınıda duzenle yenı koda gore

# PHP Model Context Protocol (MCP) Website Connector

This project enables PHP-based websites or SaaS applications to securely connect with AI clients (such as Claude Desktop) using the **Model Context Protocol (MCP)** standard.

## Installation

1. Upload the `mcp-server.php` file to an accessible directory on your web server.
2. Ensure your server is running PHP 8.1 or higher.
3. Test your endpoint: `https://yourdomain.com/mcp-server.php`

## Connecting with Claude Desktop

Add your website as an MCP server by updating your Claude Desktop configuration file (`claude_desktop_config.json`).

Configuration file paths:
- **macOS:** `~/Library/Application Support/Claude/claude_desktop_config.json`
- **Windows:** `%APPDATA%\Claude\claude_desktop_config.json`

Add the following configuration block:

```json
{
  "mcpServers": {
    "my-php-website": {
      "command": "php",
      "args": [
        "/path/to/your/mcp-server.php"
      ]
    }
  }
}
