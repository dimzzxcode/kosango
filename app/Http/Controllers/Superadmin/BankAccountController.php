<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BankAccountRequest;
use App\Models\BankAccount;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BankAccountController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('superadmin/bank-accounts/Index', [
            'accounts' => BankAccount::query()
                ->with('property:id,name')
                ->orderBy('bank_name')
                ->get(),
            'properties' => Property::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(BankAccountRequest $request): RedirectResponse
    {
        BankAccount::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rekening bank ditambahkan.']);

        return back();
    }

    public function update(BankAccountRequest $request, BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rekening bank diperbarui.']);

        return back();
    }

    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rekening bank dihapus.']);

        return back();
    }
}
