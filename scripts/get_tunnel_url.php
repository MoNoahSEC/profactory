<?php
/**
 * Detects the active public HTTPS tunnel URL (Cloudflare or Ngrok).
 */

$rootDir = dirname(__DIR__);
$cfLog = $rootDir . '/cloudflare.log';
$publicUrl = null;

// 1. Try Ngrok API
try {
    $ctx = stream_context_create(['http' => ['timeout' => 1]]);
    $ngrokJson = @file_get_contents('http://127.0.0.1:4040/api/tunnels', false, $ctx);
    if ($ngrokJson) {
        $data = json_decode($ngrokJson, true);
        if (!empty($data['tunnels'][0]['public_url'])) {
            $publicUrl = $data['tunnels'][0]['public_url'];
        }
    }
} catch (\Throwable $e) {}

// 2. Try Cloudflare Tunnel Log
if (!$publicUrl && file_exists($cfLog)) {
    $content = file_get_contents($cfLog);
    if (preg_match('/https:\/\/[a-zA-Z0-9\.\-]+\.trycloudflare\.com/', $content, $matches)) {
        $publicUrl = $matches[0];
    }
}

if (!$publicUrl) {
    $publicUrl = 'http://localhost:8000';
}

echo $publicUrl;
