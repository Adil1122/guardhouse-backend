<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DATABASE RECORDS REFERENCE ===\n\n";

// Check worker user
echo "WORKER USER:\n";
$worker = \App\Models\User::where('email', 'worker_one@gmail.com')->first();
if ($worker) {
    echo "Table: users\n";
    echo "ID: {$worker->id}\n";
    echo "Email: {$worker->email}\n";
    echo "Role: {$worker->role}\n";
    echo "Created: {$worker->created_at}\n\n";
}

// Check assigned site
echo "ASSIGNED SITE:\n";
$site = \App\Models\Site::where('name', 'Main Office Building')->first();
if ($site) {
    echo "Table: sites\n";
    echo "ID: {$site->id}\n";
    echo "Name: {$site->name}\n";
    echo "Address: {$site->address}\n";
    echo "Type: {$site->type}\n";
    echo "Geofence: {$site->geofence}\n";
    echo "Created: {$site->created_at}\n\n";
}

// Check current shift
echo "CURRENT SHIFT:\n";
$currentShift = \App\Models\Shift::where('assigned_to', $worker->id)
    ->where('status', 'clocked-in')
    ->first();
if ($currentShift) {
    echo "Table: shifts\n";
    echo "ID: {$currentShift->id}\n";
    echo "User ID: {$currentShift->assigned_to}\n";
    echo "Site ID: {$currentShift->site_id}\n";
    echo "Start Date: {$currentShift->start_date}\n";
    echo "End Date: {$currentShift->end_date}\n";
    echo "Start Time: {$currentShift->start_time}\n";
    echo "End Time: {$currentShift->end_time}\n";
    echo "Status: {$currentShift->status}\n";
    echo "Created: {$currentShift->created_at}\n\n";
}

// Check historical shifts for duty history
echo "HISTORICAL SHIFTS (Duty History):\n";
$historicalShifts = \App\Models\Shift::where('assigned_to', $worker->id)
    ->where('status', 'clocked-out')
    ->orderBy('start_date', 'desc')
    ->get();

foreach ($historicalShifts as $shift) {
    echo "Table: shifts\n";
    echo "ID: {$shift->id}\n";
    echo "Date: {$shift->start_date}\n";
    echo "Start Time: {$shift->start_time}\n";
    echo "End Time: {$shift->end_time}\n";
    echo "Status: {$shift->status}\n";
    echo "Created: {$shift->created_at}\n";
    echo "---\n";
}

// Check checkin records
echo "\nCHECK-IN RECORDS:\n";
$checkins = \App\Models\Checkin::where('user_id', $worker->id)
    ->orderBy('checked_in_at', 'desc')
    ->get();

foreach ($checkins as $checkin) {
    echo "Table: checkins\n";
    echo "ID: {$checkin->id}\n";
    echo "Shift ID: {$checkin->shift_id}\n";
    echo "User ID: {$checkin->user_id}\n";
    echo "Location: {$checkin->latitude}, {$checkin->longitude}\n";
    echo "Type: {$checkin->type}\n";
    echo "Photo Path: " . ($checkin->photo_path ?? 'None') . "\n";
    echo "Inside Geofence: " . ($checkin->inside_geofence ? 'Yes' : 'No') . "\n";
    echo "Checked In At: {$checkin->checked_in_at}\n";
    echo "---\n";
}

echo "\n=== SUMMARY TABLES ===\n";
echo "1. users - User accounts (ID: {$worker->id})\n";
echo "2. sites - Site locations (ID: {$site->id})\n";
echo "3. shifts - Work shifts (Current: {$currentShift->id}, Historical: " . $historicalShifts->count() . " records)\n";
echo "4. checkins - Check-in records (" . $checkins->count() . " records)\n";
