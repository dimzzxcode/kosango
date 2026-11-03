<?php

namespace App\Observers;

use App\Models\Property;
use App\Services\ImageStorageService;

class PropertyObserver
{
    public function __construct(private readonly ImageStorageService $images) {}

    /**
     * Remove the stored files right before the property is force deleted.
     */
    public function forceDeleting(Property $property): void
    {
        $this->images->delete($property->thumbnail_path);

        $property->images()
            ->pluck('path')
            ->each(fn (string $path) => $this->images->delete($path));
    }
}
