<?php

namespace App\Observers;

use App\Models\PropertyImage;
use App\Services\ImageStorageService;

class PropertyImageObserver
{
    public function __construct(private readonly ImageStorageService $images) {}

    /**
     * Remove the stored file when the gallery row is deleted.
     */
    public function deleted(PropertyImage $image): void
    {
        $this->images->delete($image->path);
    }
}
