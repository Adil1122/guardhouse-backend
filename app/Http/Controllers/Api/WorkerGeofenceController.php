<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\Checkin;
use App\Models\Site;
use App\Services\GeofenceService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class WorkerGeofenceController extends Controller
{
    protected $geofenceService;

    public function __construct(GeofenceService $geofenceService)
    {
        $this->geofenceService = $geofenceService;
    }

    /**
     * Get current shift with geofence status
     */
    public function getCurrentShift(Request $request)
    {
        $user = $request->user();
        
        $currentShift = Shift::where('assigned_to', $user->id)
            ->where('status', 'clocked-in')
            ->with(['site', 'site.checkpoints'])
            ->first();

        if (!$currentShift) {
            return response()->json([
                'message' => 'No active shift found',
                'data' => null
            ], 404);
        }

        // Get latest checkin to determine geofence status
        $latestCheckin = Checkin::where('shift_id', $currentShift->id)
            ->orderBy('checked_in_at', 'desc')
            ->first();

        $insideGeofence = false;
        $insideGeofenceSince = null;

        if ($latestCheckin) {
            $insideGeofence = $latestCheckin->inside_geofence;
            if ($insideGeofence) {
                $insideGeofenceSince = $latestCheckin->checked_in_at;
            }
        }

        return response()->json([
            'data' => [
                'id' => $currentShift->id,
                'site_id' => $currentShift->site_id,
                'site_name' => $currentShift->site->name,
                'site_address' => is_string($currentShift->site->address) 
                    ? $currentShift->site->address 
                    : ($currentShift->site->address['formatted_address'] ?? 'Address not available'),
                'geofence' => $currentShift->site->geofence,
                'start_time' => $currentShift->start_time,
                'inside_geofence' => $insideGeofence,
                'inside_geofence_since' => $insideGeofenceSince,
                'last_checkin' => $latestCheckin ? $latestCheckin->checked_in_at : null,
                'checkins_count' => Checkin::where('shift_id', $currentShift->id)->count(),
                'checkpoints' => $currentShift->site->checkpoints->map(function ($checkpoint) {
                    return [
                        'id' => $checkpoint->id,
                        'name' => $checkpoint->name,
                        'geofence' => $checkpoint->geofence,
                        'qr_code_token' => $checkpoint->qr_code_token,
                    ];
                }),
            ]
        ]);
    }

    /**
     * Update worker location and check geofence status
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $user = $request->user();
        
        $currentShift = Shift::where('assigned_to', $user->id)
            ->where('status', 'clocked-in')
            ->with('site')
            ->first();

        if (!$currentShift) {
            return response()->json([
                'message' => 'No active shift found',
                'inside_geofence' => false
            ], 404);
        }

        $siteGeofence = $currentShift->site->geofence;
        $insideGeofence = $this->geofenceService->isWithinGeofence(
            $request->latitude,
            $request->longitude,
            $siteGeofence
        );

        $distance = $this->geofenceService->calculateDistance(
            $request->latitude,
            $request->longitude,
            (float) $siteGeofence['lat'],
            (float) $siteGeofence['lon']
        );
        
        // Cap distance to prevent database overflow (max 99.99 for decimal:2)
        $distance = min($distance, 99.99);

        // Store location update (optional - for tracking)
        // You can create a worker_locations table if needed for detailed tracking

        return response()->json([
            'inside_geofence' => $insideGeofence,
            'distance_from_site' => $distance,
            'site_name' => $currentShift->site->name,
            'check_in_distance' => $siteGeofence['check_in_distance'] ?? 100,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Submit checkin with photo evidence
     */
    public function submitCheckin(Request $request)
    {
        try {
            \Log::info('Submit checkin request data: ', $request->all());
            
            $request->validate([
                'latitude' => 'required|string|regex:/^-?\d+\.?\d*$/',
                'longitude' => 'required|string|regex:/^-?\d+\.?\d*$/',
                'location_description' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:1000',
                'type' => 'nullable|in:regular,incident,checkpoint',
                'site_checkpoint_id' => 'nullable|exists:site_checkpoints,id',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            ]);

            $user = $request->user();
            \Log::info('User found: ' . $user->id);
            
            $currentShift = Shift::where('assigned_to', $user->id)
                ->where('status', 'clocked-in')
                ->with('site')
                ->first();

            if (!$currentShift) {
                $upcomingShift = Shift::where('assigned_to', $user->id)
                    ->whereIn('status', ['scheduled', 'offered', 'created', 'confirmed'])
                    ->orderBy('start_date', 'asc')
                    ->with('site')
                    ->first();
                    
                if ($upcomingShift) {
                    $upcomingShift->status = 'clocked-in';
                    $upcomingShift->save();
                    
                    \App\Models\ShiftTimeclockLog::create([
                        'shift_id' => $upcomingShift->id,
                        'user_id' => $user->id,
                        'type' => 'clock_in',
                        'clocked_in' => now(),
                    ]);
                    
                    $currentShift = $upcomingShift;
                } else {
                    return response()->json([
                        'message' => 'No active or upcoming shift found.',
                    ], 400);
                }
            }
            
            \Log::info('Current shift found: ' . $currentShift->id);

            $siteGeofence = $currentShift->site->geofence ?? null;
            \Log::info('Site geofence: ' . json_encode($siteGeofence));
            
            $latitude = (float) $request->latitude;
            $longitude = (float) $request->longitude;
            
            $insideGeofence = false;
            $distance = 0;
            
            if ($siteGeofence) {
                try {
                    $insideGeofence = $this->geofenceService->isWithinGeofence(
                        $latitude,
                        $longitude,
                        $siteGeofence
                    );

                    $distance = $this->geofenceService->calculateDistance(
                        $latitude,
                        $longitude,
                        (float) $siteGeofence['lat'],
                        (float) $siteGeofence['lon']
                    );
                    
                    // Cap distance to prevent database overflow (max 99.99 for decimal:2)
                    $distance = min($distance, 99.99);
                } catch (\Exception $geofenceException) {
                    \Log::error('Geofence calculation error: ' . $geofenceException->getMessage());
                    // Continue without geofence calculation
                }
            }

            $photoPath = null;
            $photoLat = null;
            $photoLng = null;

            // Handle photo upload
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $filename = time() . '_' . $photo->getClientOriginalName();
                $photo->move(public_path('storage/checkin_photos'), $filename);
                $photoPath = 'checkin_photos/' . $filename;
                
                // Extract GPS data from photo if available
                // For now, we'll use the provided coordinates
                $photoLat = $latitude;
                $photoLng = $longitude;
            }

            DB::beginTransaction();
            try {
                \Log::info('Creating checkin record...');
                
                $checkinData = [
                    'shift_id' => $currentShift->id,
                    'site_checkpoint_id' => $request->site_checkpoint_id,
                    'user_id' => $user->id,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'location_description' => $request->location_description,
                    'notes' => $request->notes,
                    'type' => $request->type ?? 'regular',
                    'photo_path' => $photoPath,
                    'photo_lat' => $photoLat,
                    'photo_lng' => $photoLng,
                    'checked_in_at' => now(),
                    'inside_geofence' => $insideGeofence,
                    'distance_from_site' => $distance,
                ];
                
                \Log::info('Checkin data: ' . json_encode($checkinData));
                
                $checkin = Checkin::create($checkinData);
                \Log::info('Checkin created with ID: ' . $checkin->id);

                // If it's a checkpoint scan, create shift_checkpoint_scan record
                if ($request->site_checkpoint_id) {
                    $currentShift->checkpointScans()->create([
                        'site_checkpoint_id' => $request->site_checkpoint_id,
                    ]);
                }

                DB::commit();

                return response()->json([
                    'message' => 'Check-in submitted successfully',
                    'data' => [
                        'id' => $checkin->id,
                        'inside_geofence' => $insideGeofence,
                        'distance_from_site' => $distance,
                        'photo_path' => $photoPath,
                        'checked_in_at' => $checkin->checked_in_at,
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Database transaction error: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                
                // Delete uploaded photo if transaction failed
                if ($photoPath) {
                    $filePath = public_path('storage/' . $photoPath);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                
                return response()->json([
                    'message' => 'Failed to submit check-in: ' . $e->getMessage(),
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Submit checkin error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'message' => 'Failed to submit check-in: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get checkin history for current shift
     */
    public function getCheckinHistory(Request $request)
    {
        $user = $request->user();
        
        $currentShift = Shift::where('assigned_to', $user->id)
            ->where('status', 'clocked-in')
            ->first();

        if (!$currentShift) {
            return response()->json([
                'message' => 'No active shift found',
                'data' => []
            ]);
        }

        $checkins = Checkin::where('shift_id', $currentShift->id)
            ->with(['checkpoint'])
            ->orderBy('checked_in_at', 'desc')
            ->get()
            ->map(function ($checkin) {
                return [
                    'id' => $checkin->id,
                    'location_description' => $checkin->location_description,
                    'notes' => $checkin->notes,
                    'type' => $checkin->type,
                    'photo_path' => $checkin->photo_path,
                    'checked_in_at' => $checkin->checked_in_at,
                    'inside_geofence' => $checkin->inside_geofence,
                    'distance_from_site' => $checkin->distance_from_site,
                    'checkpoint' => $checkin->checkpoint ? [
                        'id' => $checkin->checkpoint->id,
                        'name' => $checkin->checkpoint->name,
                    ] : null,
                ];
            });

        return response()->json([
            'data' => $checkins
        ]);
    }

    /**
     * Get duty history for worker
     */
    public function getDutyHistory(Request $request)
    {
        $user = $request->user();
        
        $shifts = Shift::where('assigned_to', $user->id)
            ->whereIn('status', ['clocked-out', 'clocked-out-offsite'])
            ->with(['site', 'checkins'])
            ->orderBy('start_date', 'desc')
            ->paginate($request->get('per_page', 20));

        $shifts->getCollection()->transform(function ($shift) {
            $checkinsCount = $shift->checkins->count();
            $photosCount = $shift->checkins->whereNotNull('photo_path')->count();
            
            return [
                'id' => $shift->id,
                'site_name' => $shift->site->name,
                'site_address' => $shift->site->address['formatted_address'] ?? 'Address not available',
                'date' => $shift->start_date,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
                'duration_hours' => $this->calculateDurationHours($shift),
                'status' => $shift->status,
                'checkins_count' => $checkinsCount,
                'photos_count' => $photosCount,
                'created_at' => $shift->created_at,
            ];
        });

        return response()->json([
            'data' => $shifts->items(),
            'pagination' => [
                'current_page' => $shifts->currentPage(),
                'per_page' => $shifts->perPage(),
                'total' => $shifts->total(),
                'last_page' => $shifts->lastPage(),
            ]
        ]);
    }

    /**
     * Get shift details with all checkins
     */
    public function getShiftDetails($shiftId, Request $request)
    {
        $user = $request->user();
        
        $shift = Shift::where('assigned_to', $user->id)
            ->where('id', $shiftId)
            ->with(['site', 'checkins' => function ($query) {
                $query->with('checkpoint')->orderBy('checked_in_at', 'desc');
            }])
            ->first();

        if (!$shift) {
            return response()->json([
                'message' => 'Shift not found',
            ], 404);
        }

        $checkins = $shift->checkins->map(function ($checkin) {
            return [
                'id' => $checkin->id,
                'location_description' => $checkin->location_description,
                'notes' => $checkin->notes,
                'type' => $checkin->type,
                'photo_path' => $checkin->photo_path,
                'checked_in_at' => $checkin->checked_in_at,
                'inside_geofence' => $checkin->inside_geofence,
                'distance_from_site' => $checkin->distance_from_site,
                'checkpoint' => $checkin->checkpoint ? [
                    'id' => $checkin->checkpoint->id,
                    'name' => $checkin->checkpoint->name,
                ] : null,
            ];
        });

        return response()->json([
            'data' => [
                'id' => $shift->id,
                'site_name' => $shift->site->name,
                'site_address' => $shift->site->address['formatted_address'] ?? 'Address not available',
                'date' => $shift->start_date,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
                'duration_hours' => $this->calculateDurationHours($shift),
                'status' => $shift->status,
                'checkins_count' => $shift->checkins->count(),
                'photos_count' => $shift->checkins->whereNotNull('photo_path')->count(),
                'checkins' => $checkins,
            ]
        ]);
    }

    private function calculateDurationHours($shift)
    {
        if (!$shift->start_time || !$shift->end_time) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($shift->start_date . ' ' . $shift->start_time);
        $end = \Carbon\Carbon::parse($shift->start_date . ' ' . $shift->end_time);
        
        return $end->diffInHours($start);
    }

    /**
     * Get a specific checkin details
     */
    public function getCheckin($id)
    {
        try {
            $user = request()->user();
            
            $checkin = Checkin::where('id', $id)
                ->where('user_id', $user->id)
                ->with(['shift.site'])
                ->first();

            if (!$checkin) {
                return response()->json([
                    'message' => 'Check-in not found'
                ], 404);
            }

            return response()->json([
                'data' => $checkin
            ]);
        } catch (\Exception $e) {
            \Log::error('Get checkin error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to get check-in: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a specific checkin
     */
    public function updateCheckin($id, Request $request)
    {
        try {
            $user = request()->user();
            
            $checkin = Checkin::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$checkin) {
                return response()->json([
                    'message' => 'Check-in not found'
                ], 404);
            }

            $request->validate([
                'location_description' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:1000',
                'type' => 'nullable|in:regular,incident,checkpoint',
            ]);

            $checkin->update([
                'location_description' => $request->location_description ?? $checkin->location_description,
                'notes' => $request->notes ?? $checkin->notes,
                'type' => $request->type ?? $checkin->type,
            ]);

            return response()->json([
                'message' => 'Check-in updated successfully',
                'data' => $checkin
            ]);
        } catch (\Exception $e) {
            \Log::error('Update checkin error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update check-in: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a specific checkin
     */
    public function deleteCheckin($id)
    {
        try {
            $user = request()->user();
            
            $checkin = Checkin::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$checkin) {
                return response()->json([
                    'message' => 'Check-in not found'
                ], 404);
            }

            // Delete photo if exists
            if ($checkin->photo_path) {
                $filePath = public_path('storage/' . $checkin->photo_path);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $checkin->delete();

            return response()->json([
                'message' => 'Check-in deleted successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Delete checkin error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete check-in: ' . $e->getMessage()
            ], 500);
        }
    }
}
