<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING WITH PROPER REFERER ===\n\n";

// Create a proper HTTP request simulation with referer
$headers = [
    'Authorization' => 'Bearer 113|YH2gEkUwYXu6DB72wt7Kc5Dzygi73PL9ooprLpVPd7ce21af',
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',
    'Referer' => 'http://localhost:3000' // Add proper referer
];

$context = stream_context_create([
    'http' => [
        'header' => implode("\r\n", array_map(
            function ($v, $k) { return "$k: $v"; },
            array_keys($headers),
            $headers
        )),
        'method' => 'GET',
        'ignore_errors' => true,
    ]
]);

// Test current-shift endpoint
echo "Testing current-shift with referer...\n";
$url = 'http://127.0.0.1:8000/api/worker/current-shift';
$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "ERROR: Failed to make request\n";
} else {
    $trimmed = trim($response);
    if (strpos($trimmed, '{') === 0) {
        echo "✅ Current-shift API working\n";
        $json = json_decode($response, true);
        echo "Site name: " . ($json['data']['site_name'] ?? 'Not found') . "\n";
    } else {
        echo "❌ Current-shift still blocked\n";
    }
}

// Test duty-history endpoint
echo "\nTesting duty-history with referer...\n";
$url2 = 'http://127.0.0.1:8000/api/worker/duty-history?page=1&per_page=20';
$response2 = file_get_contents($url2, false, $context);

if ($response2 === false) {
    echo "ERROR: Failed to make request\n";
} else {
    $trimmed2 = trim($response2);
    if (strpos($trimmed2, '{') === 0) {
        echo "✅ Duty-history API working\n";
        $json2 = json_decode($response2, true);
        echo "Data count: " . count($json2['data'] ?? []) . "\n";
    } else {
        echo "❌ Duty-history still blocked\n";
    }
}
