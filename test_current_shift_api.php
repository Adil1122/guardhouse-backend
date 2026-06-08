<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate the API call to debug
$user = \App\Models\User::where('email', 'worker_one@gmail.com')->first();

if ($user) {
    try {
        // Simulate the exact same query as the API
        $currentShift = \App\Models\Shift::where('assigned_to', $user->id)
            ->where('status', 'clocked-in')
            ->with(['site', 'checkins' => function($query) {
                return $query->orderBy('checked_in_at', 'desc')->take(5);
            }])
            ->first();
        
        if ($currentShift) {
            echo "SUCCESS: Current shift found\n";
            echo "Shift ID: {$currentShift->id}\n";
            echo "Site ID: {$currentShift->site_id}\n";
            echo "Site Name: " . ($currentShift->site ? $currentShift->site->name : 'NULL') . "\n";
            echo "Site Address: " . ($currentShift->site ? $currentShift->site->address : 'NULL') . "\n";
            echo "Geofence: " . ($currentShift->site ? $currentShift->site->geofence : 'NULL') . "\n";
            
            // Format response like the API
            $response = [
                'data' => [
                    'id' => $currentShift->id,
                    'site_id' => $currentShift->site_id,
                    'site_name' => $currentShift->site ? $currentShift->site->name : null,
                    'site_address' => is_string($currentShift->site->address) 
                        ? $currentShift->site->address 
                        : ($currentShift->site->address['formatted_address'] ?? null),
                    'geofence' => $currentShift->site ? $currentShift->site->geofence : null,
                    'start_time' => $currentShift->start_time,
                    'inside_geofence' => false, // Will be calculated dynamically
                    'inside_geofence_since' => null,
                    'last_checkin' => null,
                    'checkins_count' => $currentShift->checkins->count(),
                    'checkpoints' => $currentShift->site ? $currentShift->site->checkpoints->map(function ($checkpoint) {
                        return [
                            'id' => $checkpoint->id,
                            'name' => $checkpoint->name,
                            'geofence' => $checkpoint->geofence,
                            'qr_code_token' => $checkpoint->qr_code_token,
                        ];
                    })->collect() : collect([]),
                ]
            ];
            
            echo "API Response:\n";
            echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "ERROR: No current shift found\n";
        }
    } catch (Exception $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
        echo "Trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "ERROR: User not found\n";
}
