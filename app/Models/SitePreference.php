<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\SitePreferenceResource;

class SitePreference extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['site_id', 'reference_id', 'mode', 'setting'];

    public static function getResource()
    {
        return SitePreferenceResource::class;
    }

    public function getValidationRules($action)
    {
        return [
            'site_id' => 'required|exists:sites,id',
            'reference_id' => 'required|integer',
            'mode' => 'required|in:staff-setting,form-setting',
            'setting' => 'required|in:preferred,blacklisted,enabled',
        ];
    }

    public static function scopeApplyFilter($query, $filter, $filterId)
    {
        if (empty($filter) || $filter === 'all') {
            return $query;
        }

        $query->where('mode', $filter);
        return $query;
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function staffUser()
    {
        return $this->belongsTo(User::class, 'reference_id');
    }

    public function form()
    {
        return $this->belongsTo(Form::class, 'reference_id');
    }
}
