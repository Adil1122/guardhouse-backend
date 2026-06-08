<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== USER-SITE RELATIONSHIP ANALYSIS ===\n\n";

// Get user worker_one@gmail.com
$user = App\Models\User::where('email', 'worker_one@gmail.com')->first();
echo "USER DETAILS:\n";
echo "ID: " . $user->id . "\n";
echo "Email: " . $user->email . "\n";
echo "Name: " . $user->first_name . " " . $user->last_name . "\n";
echo "Role: " . $user->role . "\n";
echo "Status: " . $user->status . "\n\n";

// Get all shifts for this user
echo "ASSIGNED SHIFTS (via Shifts table):\n";
echo "=====================================\n";
$shifts = App\Models\Shift::where('assigned_to', $user->id)
    ->with(['site', 'assignedUser'])
    ->get();

foreach ($shifts as $shift) {
    echo "Shift ID: " . $shift->id . "\n";
    echo "Site ID: " . $shift->site_id . " (Site: " . $shift->site->name . ")\n";
    echo "Assigned to User ID: " . $shift->assigned_to . " (" . $shift->assignedUser->email . ")\n";
    echo "Status: " . $shift->status . "\n";
    echo "Start Date: " . $shift->start_date . "\n";
    echo "Start Time: " . $shift->start_time . "\n";
    echo "End Time: " . $shift->end_time . "\n";
    echo "Service Type: " . $shift->service_type . "\n";
    echo "---\n";
}

echo "\nDATABASE RELATIONSHIP STRUCTURE:\n";
echo "=================================\n";
echo "1. Users Table (users)\n";
echo "   - id (PK)\n";
echo "   - email\n";
echo "   - role\n";
echo "   - first_name, last_name\n\n";

echo "2. Sites Table (sites)\n";
echo "   - id (PK)\n";
echo "   - name\n";
echo "   - geofence (JSON)\n\n";

echo "3. Shifts Table (shifts) - MIDDLE TABLE\n";
echo "   - id (PK)\n";
echo "   - site_id (FK -> sites.id)\n";
echo "   - assigned_to (FK -> users.id)\n";
echo "   - status (created, offered, confirmed, clocked-in, etc.)\n";
echo "   - start_date, start_time, end_time\n";
echo "   - service_type\n\n";

echo "RELATIONSHIP TYPE:\n";
echo "================\n";
echo "Many-to-Many relationship between Users and Sites through Shifts table:\n";
echo "- One User can have many Shifts\n";
echo "- One Site can have many Shifts\n";
echo "- Each Shift connects exactly one User to exactly one Site\n\n";

echo "HOW ASSIGNMENT WORKS:\n";
echo "====================\n";
echo "1. Admin creates a Shift record\n";
echo "2. Shift.site_id = ID of the site where work is assigned\n";
echo "3. Shift.assigned_to = ID of the user who will work there\n";
echo "4. Shift.status tracks the assignment state (offered, confirmed, clocked-in, etc.)\n\n";

echo "CURRENT ACTIVE ASSIGNMENT:\n";
echo "========================\n";
$activeShift = App\Models\Shift::where('assigned_to', $user->id)
    ->where('status', 'clocked-in')
    ->with('site')
    ->first();

if ($activeShift) {
    echo "User is currently CLOCKED-IN at:\n";
    echo "Site: " . $activeShift->site->name . " (ID: " . $activeShift->site_id . ")\n";
    echo "Shift ID: " . $activeShift->id . "\n";
    echo "Geofence: " . json_encode($activeShift->site->geofence) . "\n";
} else {
    echo "No active clocked-in shift found for this user.\n";
}

echo "\nALL SITES THIS USER HAS BEEN ASSIGNED TO:\n";
echo "======================================\n";
$assignedSites = App\Models\Shift::where('assigned_to', $user->id)
    ->with('site')
    ->get()
    ->pluck('site')
    ->unique('id');

foreach ($assignedSites as $site) {
    echo "- " . $site->name . " (ID: " . $site->id . ")\n";
}
