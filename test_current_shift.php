<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test current shift API
$user = \App\Models\User::where('email', 'worker_one@gmail.com')->first();

if ($user) {
    echo "Found user: " . $user->id . "\n";

    $currentShift = \App\Models\Shift::where('assigned_to', $user->id)
        ->where('status', 'clocked-in')
        ->with(['site', 'checkins' => function($query) {
            return $query->orderBy('checked_in_at', 'desc')->take(5);
        }])
        ->first();

    if ($currentShift) {
        echo "Current shift found: " . $currentShift->id . "\n";
        echo "Site ID: " . $currentShift->site_id . "\n";
        echo "Site loaded: " . ($currentShift->site ? 'Yes' : 'No') . "\n";
        echo "Site: " . ($currentShift->site ? $currentShift->site->name : 'No site assigned') . "\n";
        echo "Status: " . $currentShift->status . "\n";

        if ($currentShift->site) {
            echo "Site geofence: " . ($currentShift->site->geofence ?: 'None') . "\n";
            $geofence = $currentShift->site->geofence;
            echo "Geofence lat: " . ($geofence['latitude'] ?? 'N/A') . "\n";
            echo "Geofence lng: " . ($geofence['longitude'] ?? 'N/A') . "\n";
            echo "Geofence radius: " . ($geofence['radius'] ?? 'N/A') . "\n";
        } else {
            echo "Site relationship not loaded\n";
        }

        $checkinsCount = $currentShift->checkins->count();
        echo "Checkins count: " . $checkinsCount . "\n";

        if ($checkinsCount > 0) {
            $latestCheckin = $currentShift->checkins->first();
            echo "Latest checkin at: " . $latestCheckin->checked_in_at . "\n";
            echo "Latest checkin inside geofence: " . ($latestCheckin->inside_geofence ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "No current shift found\n";
    }
} else {
    echo "User not found\n";
}
