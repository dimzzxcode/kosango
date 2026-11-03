<?php

namespace App\Services;

use App\Models\StatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StatusHistoryRecorder
{
    public function record(
        Model $model,
        ?string $from,
        string $to,
        ?User $actor = null,
        ?string $note = null,
    ): StatusHistory {
        return $model->morphMany(StatusHistory::class, 'historable')->create([
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actor?->id,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
