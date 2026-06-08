<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING DUTY HISTORY WITH AUTH MIDDLEWARE ===\n\n";

// Create a proper HTTP request simulation
$headers = [
    'Authorization' => 'Bearer 113|YH2gEkUwYXu6DB72wt7Kc5Dzygi73PL9ooprLpVPd7ce21af',
    'Content-Type' => 'application/json',
    'Accept' => 'application/json'
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

// Test the actual endpoint
$url = 'http://127.0.0.1:8000/api/worker/duty-history?page=1&per_page=20';
echo "Testing URL: $url\n";
echo "Authorization: " . $headers['Authorization'] . "\n\n";

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "ERROR: Failed to make request\n";
} else {
    echo "Response:\n";
    echo $response . "\n";
    
    // Parse response to see if it's JSON
    $json = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "Valid JSON response\n";
        if (isset($json['data'])) {
            echo "Data count: " . count($json['data']) . "\n";
        }
    } else {
        echo "Invalid JSON: " . json_last_error_msg() . "\n";
    }
}
