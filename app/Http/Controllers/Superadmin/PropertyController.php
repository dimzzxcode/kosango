<?php

namespace App\Http\Controllers\Superadmin;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignAdminRequest;
use App\Http\Requests\Admin\PropertyRequest;
use App\Models\City;
use App\Models\Facility;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use App\Services\ImageStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function __construct(private readonly ImageStorageService $images) {}

    /**
     * List every property with its room inventory.
     */
    public function index(): Response
    {
        return Inertia::render('superadmin/properties/Index', [
            'properties' => Property::query()
                ->with('city:id,name')
                ->withCount(['roomTypes', 'rooms'])
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('superadmin/properties/Form', [
            'property' => null,
            'cities' => City::query()->orderBy('name')->get(['id', 'name', 'province']),
            'facilities' => Facility::query()->orderBy('name')->get(['id', 'name']),
            'types' => $this->propertyTypes(),
        ]);
    }

    public function store(PropertyRequest $request): RedirectResponse
    {
        if (! $request->hasFile('thumbnail')) {
            return back()->withErrors(['thumbnail' => 'Thumbnail properti wajib diunggah.']);
        }

        $property = DB::transaction(function () use ($request): Property {
            $data = $request->validated();

            $property = Property::query()->create([
                'city_id' => $data['city_id'],
                'created_by' => $request->user()->id,
                'type' => $data['type'],
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'description' => $data['description'],
                'address' => $data['address'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'whatsapp_number' => $data['whatsapp_number'] ?? null,
                'status' => PropertyStatus::Draft,
                'thumbnail_path' => $this->images->storePublic($request->file('thumbnail')),
                'thumbnail_alt' => $data['thumbnail_alt'] ?? $data['name'],
                'rating_avg' => 0,
                'reviews_count' => 0,
            ]);

            $property->facilities()->sync($data['facilities'] ?? []);
            $this->storeGallery($property, $request->file('gallery', []));

            return $property;
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Properti dibuat sebagai draft. Lengkapi tipe kamar dan kamar sebelum publikasi.',
        ]);

        return to_route('superadmin.properties.edit', $property);
    }

    public function edit(Property $property): Response
    {
        $property->load([
            'city',
            'images',
            'facilities:id,name',
            'roomTypes' => fn ($query) => $query->with(['prices', 'rooms']),
            'rooms',
            'admins:id,name,email',
        ]);

        return Inertia::render('superadmin/properties/Form', [
            'property' => $property,
            'cities' => City::query()->orderBy('name')->get(['id', 'name', 'province']),
            'facilities' => Facility::query()->orderBy('name')->get(['id', 'name']),
            'types' => $this->propertyTypes(),
            'admins' => User::query()
                ->where('role', UserRole::Admin->value)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function update(PropertyRequest $request, Property $property): RedirectResponse
    {
        DB::transaction(function () use ($request, $property): void {
            $data = $request->validated();

            $property->fill([
                'city_id' => $data['city_id'],
                'type' => $data['type'],
                'name' => $data['name'],
                'description' => $data['description'],
                'address' => $data['address'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'whatsapp_number' => $data['whatsapp_number'] ?? null,
                'thumbnail_alt' => $data['thumbnail_alt'] ?? $property->thumbnail_alt,
            ]);

            if ($request->hasFile('thumbnail')) {
                $this->images->delete($property->thumbnail_path);
                $property->thumbnail_path = $this->images->storePublic($request->file('thumbnail'));
            }

            $property->save();
            $property->facilities()->sync($data['facilities'] ?? []);
            $this->storeGallery($property, $request->file('gallery', []));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Properti diperbarui.']);

        return back();
    }

    public function destroy(Property $property): RedirectResponse
    {
        $property->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Properti dihapus.']);

        return to_route('superadmin.properties.index');
    }

    /**
     * Publish or unpublish a property.
     */
    public function publish(Property $property): RedirectResponse
    {
        if ($property->status === PropertyStatus::Draft && ! $property->canBePublished()) {
            return back()->withErrors([
                'property' => 'Properti membutuhkan thumbnail, tipe kamar dengan tarif aktif, dan minimal satu kamar tersedia.',
            ]);
        }

        $property->update([
            'status' => $property->status === PropertyStatus::Published
                ? PropertyStatus::Draft
                : PropertyStatus::Published,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $property->status === PropertyStatus::Published
                ? 'Properti dipublikasikan.'
                : 'Properti dikembalikan ke draft.',
        ]);

        return back();
    }

    /**
     * Remove one gallery image.
     */
    public function destroyImage(Property $property, PropertyImage $image): RedirectResponse
    {
        abort_unless($image->property_id === $property->id, 404);

        $image->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gambar dihapus.']);

        return back();
    }

    /**
     * Persist a new gallery order.
     */
    public function reorderImages(Request $request, Property $property): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $owned = $property->images()->pluck('id')->all();

        if (count($data['ids']) !== count($owned) || array_diff($owned, $data['ids']) !== []) {
            throw ValidationException::withMessages([
                'ids' => 'Urutan galeri tidak sesuai dengan gambar properti ini.',
            ]);
        }

        DB::transaction(function () use ($data): void {
            foreach ($data['ids'] as $index => $id) {
                PropertyImage::query()->whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Urutan galeri diperbarui.']);

        return back();
    }

    /**
     * Sync the admins assigned to the property.
     */
    public function admins(AssignAdminRequest $request, Property $property): RedirectResponse
    {
        $property->admins()->sync($request->validated('admin_ids') ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Penugasan admin diperbarui.']);

        return back();
    }

    /**
     * @param  array<int, UploadedFile>|null  $files
     */
    private function storeGallery(Property $property, ?array $files): void
    {
        if ($files === null || $files === []) {
            return;
        }

        $existing = $property->images()->count();
        $limit = (int) config('booking.max_gallery_images');
        $order = (int) $property->images()->max('sort_order');

        foreach ($files as $file) {
            if ($existing >= $limit) {
                break;
            }

            $property->images()->create([
                'path' => $this->images->storePublic($file),
                'alt' => $property->name,
                'sort_order' => ++$order,
            ]);

            $existing++;
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Property::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function propertyTypes(): array
    {
        return collect(PropertyType::cases())->map(fn (PropertyType $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
        ])->all();
    }
}
