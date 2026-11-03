<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * List users with the properties they are assigned to.
     */
    public function index(): Response
    {
        return Inertia::render('superadmin/users/Index', [
            'users' => User::query()
                ->with('assignedProperties:id,name')
                ->orderBy('name')
                ->get(),
            'properties' => Property::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User dibuat.']);

        return back();
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
        ]);

        if (filled($data['password'] ?? null)) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        if ($user->role->isStaff() === false) {
            $user->assignedProperties()->detach();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User diperbarui.']);

        return back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'Anda tidak dapat menghapus akun sendiri.']);
        }

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User dihapus.']);

        return back();
    }

    /**
     * Assign a staff member to one or more properties.
     */
    public function assign(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'property_ids' => ['array'],
            'property_ids.*' => ['integer', 'exists:properties,id'],
        ]);

        $user->assignedProperties()->sync($data['property_ids'] ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Penugasan properti diperbarui.']);

        return back();
    }
}
