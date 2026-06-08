<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'worker_one@gmail.com')->first();
echo "User ID: " . $user->id . PHP_EOL;
echo "User Name: " . $user->name . PHP_EOL;

$shifts = App\Models\Shift::where('assigned_to', $user->id)->with('site')->get();
foreach($shifts as $shift) {
    echo "Shift ID: " . $shift->id . PHP_EOL;
    echo "Site ID: " . $shift->site_id . PHP_EOL;
    echo "Site Name: " . $shift->site->name . PHP_EOL;
    echo "Status: " . $shift->status . PHP_EOL;
    echo "---" . PHP_EOL;
}
