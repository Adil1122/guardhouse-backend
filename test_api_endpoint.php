<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING CURRENT-SHIFT API ===\n\n";

// Simulate the exact API request
try {
    // Create a mock request object
    $request = new \Illuminate\Http\Request();
    
    // Get user (simulate authenticated user)
    $user = \App\Models\User::where('email', 'worker_one@gmail.com')->first();
    
    if (!$user) {
        echo "ERROR: User not found\n";
        exit(1);
    }
    
    echo "User found: ID {$user->id}, Role: {$user->role}\n";
    
    // Set user in request (simulate authentication)
    $request->setUserResolver(function() use ($user) {
        return $user;
    });
    
    // Call the controller method directly
    $controller = new \App\Http\Controllers\Api\WorkerGeofenceController();
    $response = $controller->getCurrentShift($request);
    
    echo "API Response:\n";
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Content:\n";
    echo $response->getContent() . "\n";
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
