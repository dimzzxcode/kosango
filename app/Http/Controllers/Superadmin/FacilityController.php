<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FacilityRequest;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FacilityController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('superadmin/facilities/Index', [
            'facilities' => Facility::query()
                ->withCount('properties')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(FacilityRequest $request): RedirectResponse
    {
        Facility::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fasilitas ditambahkan.']);

        return back();
    }

    public function update(FacilityRequest $request, Facility $facility): RedirectResponse
    {
        $facility->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fasilitas diperbarui.']);

        return back();
    }

    public function destroy(Facility $facility): RedirectResponse
    {
        $facility->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fasilitas dihapus.']);

        return back();
    }
}
