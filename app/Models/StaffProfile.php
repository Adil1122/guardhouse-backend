<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Http\Resources\StaffProfileResource;

class StaffProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'unique_id',
        'preferred_first_name',
        'preferred_last_name',
        'sia_badge_number',
        'contact_number',
        'emergency_contact',
        'service_status',
        'gender',
        'bank_details',
        'tax_number',
        'default_pay_group_id',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $profile) {
            if (empty($profile->unique_id)) {
                do {
                    $id = str_pad((string) random_int(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
                } while (self::where('unique_id', $id)->exists());

                $profile->unique_id = $id;
            }
        });
    }

    protected $casts = ['emergency_contact' => 'array', 'bank_details' => 'array'];

    public static function getResource()
    {
        return StaffProfileResource::class;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function defaultPayGroup()
    {
        return $this->belongsTo(PayGroup::class, 'default_pay_group_id');
    }

    public function compliances()
    {
        return $this->hasMany(StaffCompliance::class, 'staff_profile_id');
    }

    public function privileges()
    {
        return $this->hasMany(StaffPrivilege::class);
    }
}
