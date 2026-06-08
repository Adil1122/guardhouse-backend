<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== USER-SITE LINKING EXPLANATION ===\n\n";

// User ID 21
$user = \App\Models\User::find(21);
echo "USER (ID: 21):\n";
echo "Email: {$user->email}\n";
echo "Role: {$user->role}\n\n";

// Site ID 6
$site = \App\Models\Site::find(6);
echo "SITE (ID: 6):\n";
echo "Name: {$site->name}\n";
echo "Address: {$site->address}\n\n";

// THE LINKING MECHANISM
echo "HOW THEY ARE LINKED:\n";
echo "1. User ID 21 is assigned to shifts via 'assigned_to' column\n";
echo "2. Shifts are linked to Site ID 6 via 'site_id' column\n";
echo "3. This creates an indirect relationship: User -> Shifts -> Site\n\n";

// Show the linking shifts
$shifts = \App\Models\Shift::where('assigned_to', 21)
    ->where('site_id', 6)
    ->get();

echo "SHIFTS THAT LINK USER 21 TO SITE 6:\n";
foreach ($shifts as $shift) {
    echo "Shift ID: {$shift->id}\n";
    echo "User ID (assigned_to): {$shift->assigned_to}\n";
    echo "Site ID: {$shift->site_id}\n";
    echo "Date: {$shift->start_date}\n";
    echo "Status: {$shift->status}\n";
    echo "---\n";
}

echo "THERE IS NO DIRECT USER-SITE RELATIONSHIP!\n";
echo "The linking is done through the shifts table as a bridge.\n";
