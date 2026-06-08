<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Site;
use App\Models\Shift;
use App\Models\Checkin;

class WorkerTestDataSeeder extends Seeder
{
    public function run()
    {
        // Get or create the worker user
        $worker = User::where('email', 'worker_one@gmail.com')->first();
        
        if (!$worker) {
            $worker = User::create([
                'first_name' => 'Worker',
                'last_name' => 'One',
                'email' => 'worker_one@gmail.com',
                'password' => bcrypt('password123'),
                'role' => 'security-officer',
                'email_verified_at' => now(),
            ]);
        }

        // Create a site for the worker
        $site = Site::firstOrCreate([
            'name' => 'Main Office Building',
            'address' => '123 Business Ave, Suite 100, New York, NY 10001',
            'description' => 'Primary security location for corporate headquarters',
            'customer_id' => 1,
            'geofence' => json_encode([
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'radius' => 500,
                'check_in_distance' => 500
            ])
        ]);

        // Create current shift for today
        $now = now();
        $shiftStart = $now->copy()->subHour(1); // 1 hour ago
        $shiftEnd = $now->copy()->addHours(3);   // 3 hours from now

        $shift = Shift::create([
            'user_id' => $worker->id,
            'site_id' => $site->id,
            'start_date' => $now->toDateString(),
            'end_date' => $now->toDateString(),
            'start_time' => $shiftStart->toTimeString(),
            'end_time' => $shiftEnd->toTimeString(),
            'status' => 'in_progress',
            'created_by' => 1, // Admin user ID
        ]);

        // Create some historical shifts
        for ($i = 1; $i <= 5; $i++) {
            $pastDate = $now->copy()->subDays($i);
            $pastStart = $pastDate->copy()->setTime(9, 0);
            $pastEnd = $pastDate->copy()->setTime(17, 0);

            Shift::create([
                'user_id' => $worker->id,
                'site_id' => $site->id,
                'start_date' => $pastDate->toDateString(),
                'end_date' => $pastDate->toDateString(),
                'start_time' => $pastStart->toTimeString(),
                'end_time' => $pastEnd->toTimeString(),
                'status' => 'completed',
                'created_by' => 1,
                'created_at' => $pastDate,
                'updated_at' => $pastDate,
            ]);
        }

        // Create some check-in records for historical shifts
        $historicalShifts = Shift::where('user_id', $worker->id)
            ->where('status', 'completed')
            ->get();

        foreach ($historicalShifts as $historicalShift) {
            // Create 2-3 check-ins per shift
            for ($j = 1; $j <= rand(2, 3); $j++) {
                $checkinTime = $pastDate->copy()
                    ->setTime(9 + ($j * 2), 0);

                Checkin::create([
                    'shift_id' => $historicalShift->id,
                    'user_id' => $worker->id,
                    'latitude' => 40.7128 + (rand(-100, 100) / 10000),
                    'longitude' => -74.0060 + (rand(-100, 100) / 10000),
                    'location_description' => "Security checkpoint $j",
                    'notes' => "Routine patrol check-in $j",
                    'type' => 'regular',
                    'inside_geofence' => true,
                    'distance_from_site' => rand(0, 100),
                    'checked_in_at' => $checkinTime,
                    'created_at' => $checkinTime,
                    'updated_at' => $checkinTime,
                ]);
            }
        }

        echo "Worker test data created successfully!\n";
        echo "Site ID: {$site->id}\n";
        echo "Worker ID: {$worker->id}\n";
        echo "Current Shift ID: {$shift->id}\n";
    }
}
