<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreUserRequest;
use App\Http\Requests\Master\UpdateUserRequest;
use App\Http\Resources\Master\UserResource;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Master/Users/Index', [
            'users' => User::with(['branch', 'roles'])->orderBy('name')->get()->map(fn ($u) => [
                'id'        => $u->id,
                'name'      => $u->name,
                'email'     => $u->email,
                'entity'    => $u->entity,
                'branch_id' => $u->branch_id,
                'branch'    => $u->branch?->name,
                'roles'     => $u->roles->pluck('name')->values()->all(),
                'role'      => $u->roles->first()?->name,
                'is_active' => (bool) $u->is_active,
            ])->values()->all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/Users/Form', $this->formOptions());
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'entity'     => $data['entity'],
            'branch_id'  => $data['branch_id'] ?? null,
            'is_active'  => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        $user->assignRole($data['role']);

        return redirect()->route('master.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $user->load('roles');

        return Inertia::render('Master/Users/Form', [
            ...$this->formOptions(),
            'user' => new UserResource($user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        $payload = [
            'name'      => $data['name'],
            'email'     => $data['email'],
            'entity'    => $data['entity'],
            'branch_id' => $data['branch_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $user->syncRoles([$data['role']]);

        return redirect()->route('master.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('master.users.index')->with('success', 'User berhasil dihapus.');
    }

    /** Shared option payload for the create/edit form. */
    private function formOptions(): array
    {
        return [
            'branches' => Branch::where('is_active', true)->orderBy('name')->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'roles'    => Role::orderBy('name')->pluck('name'),
        ];
    }
}
