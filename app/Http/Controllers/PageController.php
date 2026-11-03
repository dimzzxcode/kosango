<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function show(Page $page): Response
    {
        return Inertia::render('pages/Show', [
            'page' => $page,
            'pages' => Page::query()->get(['slug', 'title']),
        ]);
    }
}
