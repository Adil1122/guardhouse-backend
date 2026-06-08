<?php

namespace App\Listeners;

use App\Events\SiteDocumentDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;

class HandleSiteDocumentDeleted
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
    public function handle(SiteDocumentDeleted $event): void
    {
        $data = $event->data;

        if (isset($data['files']) && is_array($data['files'])) {
            foreach ($data['files'] as $fileName) {
                Storage::disk('public')->delete('site-documents/' . $fileName);
            }
        }
    }
}
