<?php

namespace App\Listeners;

use App\Events\TimesheetUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Timesheet;
use App\Models\ManualBillableItem;
use Carbon\Carbon;

class HandleTimesheetUpdated
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TimesheetUpdated $event): void
    {
        $timesheet = $event->timesheet;
        $oldData = $event->oldData;

        if ($timesheet->status === 'approved' && $oldData['status'] !== 'approved') {
            $serviceAmount = $this->calculateServiceAmount($timesheet->id);
            $timesheet->update([
                'service_total_amount' => $serviceAmount['total_amount'],
                'service_breakdown' => $serviceAmount['applied_rates']
            ]);
        }
    }

    private function calculateServiceAmount($timesheetId)
    {
        $timesheet = Timesheet::with(['shift.timeclockLogs', 'shift.timesheet', 'serviceGroup.rates'])->findOrFail($timesheetId);
        $serviceGroup = $timesheet->serviceGroup;
        $baseRate = $serviceGroup->base_rate ?? 0;

        if ($serviceGroup->mode === 'flat') {
            return [
                'total_amount' => $baseRate,
                'applied_rates' => [[
                    'service_group_name' => $serviceGroup->name,
                    'service_rate' => [
                        'id' => null,
                        'rate' => $baseRate,
                        'day_matched' => null,
                        'work_duration' => 'full-service',
                        'full_time_range' => null,
                        'overlapped_time_range' => null,
                        'calculated_service_amount' => $baseRate
                    ],
                    'time_clock_log' => []
                ]]
            ];
        }

        $totalAmount = 0;
        $appliedRatesSummary = [];

        foreach ($timesheet->shift->timeclockLogs as $log) {

            $segments = $this->splitLogByDay($log);

            foreach ($segments as $segment) {
                $segmentStart = $segment['start'];
                $segmentEnd = $segment['end'];
                $day = strtolower($segmentStart->format('D'));

                $logStart = $segmentStart->format('H:i:s');
                $logEnd = $segmentEnd->format('H:i:s');

                $remainingMinutes = $segmentStart->diffInMinutes($segmentEnd);
                $rates = $serviceGroup->rates->filter(fn($rate) => in_array($day, $rate->days));
                
                foreach ($rates as $rate) {
                    $rateStart = $this->timeToMinutes($rate->from_time);
                    $rateEnd = $this->timeToMinutes($rate->to_time);
                    $overnightRate = false;
                    if ($rateEnd <= $rateStart) {
                        $overnightRate = true;
                        $rateEnd += 24 * 60;
                    }

                    $segStartMinutes = $this->timeToMinutes($logStart);
                    $segEndMinutes = $this->timeToMinutes($logEnd);
                    if ($segEndMinutes <= $segStartMinutes) {
                        $segEndMinutes += 24 * 60;
                    }

                    $start = max($segStartMinutes, $rateStart);
                    $end = min($segEndMinutes, $rateEnd);
                    if ($start < $end) {
                        $minutes = $end - $start;
                        $calculatedAmount = ($minutes / 60) * $rate->rate;
                        $totalAmount += $calculatedAmount;
                        $remainingMinutes -= $minutes;

                        $appliedRatesSummary[] = [
                            'service_group_name' => $serviceGroup->name,
                            'service_rate' => [
                                'id' => $rate->id,
                                'rate' => $rate->rate,
                                'day_matched' => $day,
                                'work_duration' => $minutes,
                                'full_time_range' => $rate->from_time . ' - ' . $rate->to_time,
                                'overlapped_time_range' => $this->minutesToTime($start) . ' - ' . $this->minutesToTime($end),
                                'calculated_service_amount' => round($calculatedAmount, 2)
                            ],
                            'time_clock_log' => [
                                'id' => $log->id,
                                'full_time_range' => $logStart . ' - ' . $logEnd,
                                'overlapped_time_range' => $this->minutesToTime($start) . ' - ' . $this->minutesToTime($end)
                            ]
                        ];
                    }
                }

                // fallback to base rate for unmatched minutes
                if ($remainingMinutes > 0 && $baseRate > 0) {
                    $calculatedAmount = ($remainingMinutes / 60) * $baseRate;
                    $totalAmount += $calculatedAmount;

                    $appliedRatesSummary[] = [
                        'service_group_name' => $serviceGroup->name,
                        'service_rate' => [
                            'id' => null,
                            'rate' => $baseRate,
                            'day_matched' => $day,
                            'work_duration' => $remainingMinutes,
                            'full_time_range' => null,
                            'overlapped_time_range' => $logStart . ' - ' . $logEnd,
                            'calculated_service_amount' => round($calculatedAmount, 2)
                        ],
                        'time_clock_log' => [
                            'id' => $log->id,
                            'full_time_range' => $logStart . ' - ' . $logEnd,
                            'overlapped_time_range' => $logStart . ' - ' . $logEnd
                        ]
                    ];
                }
            }
        }

        return [
            'total_amount' => round($totalAmount, 2),
            'applied_rates' => $appliedRatesSummary
        ];
    }

    private function splitLogByDay($log)
    {
        $segments = [];
        $tz = $log->shift->timesheet->timezone ?? 'UTC';
        $start = Carbon::parse($log->clocked_in, $tz);
        $end = Carbon::parse($log->clocked_out, $tz);

        while ($start->lt($end)) {
            $segmentEnd = $start->copy()->endOfDay();
            if ($segmentEnd->gt($end)) {
                $segmentEnd = $end->copy();
            }

            $segments[] = [
                'start' => $start->copy(),
                'end' => $segmentEnd->copy()
            ];

            $start = $segmentEnd->copy()->addSecond();
        }

        return $segments;
    }

    private function timeToMinutes($time)
    {
        // $time format: "HH:MM:SS"
        [$hours, $minutes, $seconds] = explode(':', $time);
        return ((int)$hours * 60) + (int)$minutes + ((int)$seconds / 60);
    }

    private function minutesToTime($minutes)
    {
        $hours = floor($minutes / 60) % 24;
        $days = floor($minutes / 1440);
        $mins = floor($minutes % 60);
        $secs = floor(($minutes - floor($minutes)) * 60);

        return sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
    }

    private function minutesDiff($start, $end)
    {
        return $end->diffInMinutes($start);
    }
}
