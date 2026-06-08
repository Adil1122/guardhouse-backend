<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING SHIFT-SITE LINKING ===\n\n";

// Fix current shift - add site_id 6
$currentShift = \App\Models\Shift::find(4);
if ($currentShift) {
    $currentShift->site_id = 6; // Assign to Main Office Building
    $currentShift->save();
    echo "Updated current shift ID 4 with site_id 6\n";
}

// Fix historical shifts - add site_id 6 to all
$historicalShifts = \App\Models\Shift::whereIn('id', [5, 6, 7, 8, 9])->get();
foreach ($historicalShifts as $shift) {
    $shift->site_id = 6; // Assign to Main Office Building
    $shift->save();
    echo "Updated historical shift ID {$shift->id} with site_id 6\n";
}

echo "\n=== VERIFICATION ===\n";
$allShifts = \App\Models\Shift::where('assigned_to', 21)
    ->where('status', '!=', 'created')
    ->get();

echo "All shifts for user 21:\n";
foreach ($allShifts as $shift) {
    echo "Shift ID: {$shift->id}, Site ID: {$shift->site_id}, Status: {$shift->status}\n";
}
