<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCityRequest;
use App\Http\Requests\Admin\UpdateCityRequest;
use App\Models\City;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CityController extends Controller
{
    /**
     * List every city with its property count.
     */
    public function index(): Response
    {
        return Inertia::render('superadmin/cities/Index', [
            'cities' => City::query()
                ->withCount('properties')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Create a city. The slug is generated once and then kept stable.
     */
    public function store(StoreCityRequest $request): RedirectResponse
    {
        City::query()->create([
            'name' => $request->validated('name'),
            'slug' => $this->uniqueSlug($request->validated('name')),
            'province' => $request->validated('province'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kota ditambahkan.']);

        return back();
    }

    /**
     * Update a city without changing its slug.
     */
    public function update(UpdateCityRequest $request, City $city): RedirectResponse
    {
        $city->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kota diperbarui.']);

        return back();
    }

    /**
     * Delete a city that no longer has properties.
     */
    public function destroy(Request $request, City $city): RedirectResponse
    {
        abort_unless($request->user()->isSuperadmin(), 403);

        if ($city->properties()->exists()) {
            return back()->withErrors([
                'city' => 'Kota masih memiliki properti sehingga tidak dapat dihapus.',
            ]);
        }

        $city->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kota dihapus.']);

        return back();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (City::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
