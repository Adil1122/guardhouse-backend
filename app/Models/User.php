<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Auth\Passwords\CanResetPassword;
use App\Notifications\ResetPasswordCustom;
use Illuminate\Support\Str;

class User extends Authenticatable implements CanResetPasswordContract
{
    use HasApiTokens, Notifiable, CanResetPassword, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['role', 'first_name', 'last_name', 'email', 'password', 'status', 'image', 'api_token', 'email_verified_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    /**
     * Automatically generates a 60-character API token
     * when a new model instance is being created.
     *
     * @return array<string, string>
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            $user->api_token = Str::random(60);
        });
    }

    /**
     * UserCreated event: Tasks to perform after user is onboarded
     */
    public $createdEvent = UserCreated::class;

    public function sendPasswordResetNotification($token)
    {
        $url = config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($this->email);
        $this->notify(new ResetPasswordCustom($url));
    }

    public function staffProfile()
    {
        return $this->hasOne(StaffProfile::class, 'user_id');
    }
    
    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class, 'user_id');
    }
}
