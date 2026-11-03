<?php

namespace App\Repositories;

use App\Enums\PropertyStatus;
use App\Models\Property;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PropertyRepository
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters, int $perPage = 9): LengthAwarePaginator
    {
        return Property::query()
            ->published()
            ->with(['city', 'roomTypes.prices', 'facilities'])
            ->filter($filters)
            ->orderByDesc('rating_avg')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function featured(int $limit = 6): Collection
    {
        return Property::query()
            ->published()
            ->with(['city', 'roomTypes.prices'])
            ->orderByDesc('rating_avg')
            ->limit($limit)
            ->get();
    }

    public function findBySlug(string $slug, bool $withRelations = true): Property
    {
        $query = Property::query()->where('slug', $slug);

        if ($withRelations) {
            $query->with([
                'city',
                'images',
                'facilities',
                'roomTypes.prices',
                'roomTypes.rooms',
                'reviews.user',
            ]);
        }

        return $query->firstOrFail();
    }

    public function forAdmin(User $user): Builder
    {
        $query = Property::query()->with('city');

        if (! $user->isSuperadmin()) {
            $query->whereHas('admins', fn ($q) => $q->whereKey($user->id));
        }

        return $query->orderBy('name');
    }

    public function publishedCount(): int
    {
        return Property::query()->where('status', PropertyStatus::Published->value)->count();
    }
}
