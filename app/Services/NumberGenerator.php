<?php

namespace App\Services;

use App\Models\NumberSequence;
use Illuminate\Support\Facades\DB;

class NumberGenerator
{
    public function next(string $type): string
    {
        return DB::transaction(function () use ($type): string {
            $date = now()->format('Ymd');
            $name = "{$type}-{$date}";

            $sequence = NumberSequence::query()
                ->where('name', $name)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                $sequence = NumberSequence::query()->create([
                    'name' => $name,
                    'last_number' => 0,
                ]);
            }

            $sequence->increment('last_number');
            $sequence->refresh();

            return sprintf('%s-%s-%04d', $type, $date, $sequence->last_number);
        });
    }
}
