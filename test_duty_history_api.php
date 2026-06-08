<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING DUTY HISTORY API ===\n\n";

// Simulate the exact API request
try {
    // Get user (simulate authenticated user)
    $user = \App\Models\User::where('email', 'worker_one@gmail.com')->first();
    
    if (!$user) {
        echo "ERROR: User not found\n";
        exit(1);
    }
    
    echo "User found: ID {$user->id}, Role: {$user->role}\n";
    
    // Create mock request with user
    $request = new \Illuminate\Http\Request();
    $request->setUserResolver(function() use ($user) {
        return $user;
    });
    
    // Set query parameters
    $request->query->set('page', 1);
    $request->query->set('per_page', 20);
    
    // Get geofence service from container
    $geofenceService = $app->make(\App\Services\GeofenceService::class);
    
    // Create controller with dependency injection
    $controller = new \App\Http\Controllers\Api\WorkerGeofenceController($geofenceService);
    
    // Call the controller method
    $response = $controller->getDutyHistory($request);
    
    echo "API Response:\n";
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Content:\n";
    echo $response->getContent() . "\n";
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
