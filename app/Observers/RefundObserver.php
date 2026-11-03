<?php

namespace App\Observers;

use App\Models\Refund;
use App\Services\ImageStorageService;

class RefundObserver
{
    public function __construct(private readonly ImageStorageService $images) {}

    /**
     * Remove the private refund proof when the refund is force deleted.
     */
    public function forceDeleting(Refund $refund): void
    {
        $this->images->delete($refund->proof_path, 'local');
    }
}
