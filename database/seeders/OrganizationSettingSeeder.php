<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\OrganizationSetting;

class OrganizationSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        OrganizationSetting::updateOrCreate(
            ['id' => 1],
            [
                'two_factor_auth' => false,
                'live_ops_sorting' => 'time-desc',
                'custom_clockin_questionnaire' => [
                    'Are you wearing your uniform correctly?',
                    'Are you feeling well enough for your shift?',
                ],
                'shift_alert_response_time' => 15,
                'default_pay_group_id' => 1,
                'default_service_group_id' => 1,
                'genfence_check_in_distance' => 200,
                'enable_digital_occurrence_logs' => true,
                'updated_by' => 2,
            ]
        );
    }
}
