<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\SiteDocumentResource;
use App\Events\SiteDocumentDeleted;

class SiteDocument extends Model
{
    protected $fillable = ['site_id', 'name', 'files', 'offsite_visibility'];

    protected $casts = [
        'files' => 'array',
        'offsite_visibility' => 'boolean',
    ];

    public $deletedEvent = SiteDocumentDeleted::class;

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public static function getResource()
    {
        return SiteDocumentResource::class;
    }

    public static function getValidationRules($action)
    {
        $rules = [
            'site_id' => 'required|exists:sites,id',
            'name' => 'nullable|string|max:50',
            'files' => 'nullable|array',
            'offsite_visibility' => 'nullable|boolean',
        ];

        return $rules;
    }
}
