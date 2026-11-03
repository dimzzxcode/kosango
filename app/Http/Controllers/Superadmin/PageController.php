<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('superadmin/pages/Index', [
            'pages' => Page::query()->orderBy('title')->get(),
        ]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Page::query()->create([
            ...$data,
            'version' => 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Halaman dibuat.']);

        return back();
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $data = $request->validated();

        $page->update([
            ...$data,
            'version' => $page->version + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Halaman diperbarui.']);

        return back();
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Halaman dihapus.']);

        return back();
    }
}
