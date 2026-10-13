<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreUserRequest;
use App\Http\Requests\Master\UpdateUserRequest;
use App\Http\Resources\Master\UserResource;
use App\Models\Branch;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private ActivityLogService $logger) {}

    public function index(Request $request)
    {
        $filters = $request->only('search', 'role', 'entity', 'branch_id', 'status', 'per_page');
        $units = config('user_access.units');
        if (User::where('entity', 'owner')->exists()) {
            $units[] = ['value' => 'owner', 'label' => 'Pemantauan seluruh unit', 'sublabel' => 'Khusus akun pemilik'];
        }

        $query = User::with(['branch', 'roles'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")))
            ->when($request->filled('role'), fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $request->role)))
            ->when($request->filled('entity'), fn ($q) => $q->where('entity', $request->entity))
            // 'none' mencakup cabang belum ditentukan maupun akun tanpa cabang.
            ->when($request->filled('branch_id'), fn ($q) => $request->branch_id === 'none'
                ? $q->whereNull('branch_id')
                : $q->where('branch_id', $request->branch_id))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'aktif'))
            ->orderBy('name');

        $users = \App\Support\ListPage::paginate($query, $request);

        return Inertia::render('Master/Users/Index', [
            'pagination' => \App\Support\ListPage::metadata($users),
            'users' => $users->getCollection()->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'entity' => $u->entity,
                'branch_id' => $u->branch_id,
                'branch' => $u->branch?->name,
                'branch_details' => $u->branch ? $u->branch->only(['name', 'code', 'type', 'is_active']) : null,
                'roles' => $u->roles->pluck('name')->values()->all(),
                'role' => $u->roles->first()?->name,
                'is_active' => (bool) $u->is_active,
                'can_manage' => auth()->user()->can('update', $u),
            ])->values()->all(),
            'filters' => $filters,
            // Total tanpa filter, supaya pengguna tahu berapa yang tersaring.
            'totalUsers' => User::count(),
            'roleOptions' => Role::orderBy('name')->pluck('name'),
            'unit_options' => $units,
            'role_descriptions' => config('user_access.roles'),
            // Cabang nonaktif yang masih memiliki pengguna tetap bisa dicari.
            'branchOptions' => Branch::where(fn ($q) => $q->where('is_active', true)->orWhereHas('users'))
                ->orderBy('name')->get(['id', 'name', 'code', 'type', 'entity', 'is_active']),
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
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'entity' => $data['entity'],
            'branch_id' => $data['branch_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        $user->assignRole($data['role']);

        // Manajemen user (role, penempatan, aktivasi) adalah resource paling sensitif
        // di Master, tapi satu-satunya yang dulu tidak meninggalkan jejak audit.
        $this->logger->log(
            'create',
            'master.user',
            "Tambah user: {$user->name} ({$user->email}) sebagai {$data['role']}",
            $user,
            null,
            $this->auditable($user, $data['role'])
        );

        return redirect()->route('master.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        Gate::authorize('update', $user);
        $user->load('roles');

        return Inertia::render('Master/Users/Form', [
            ...$this->formOptions($user),
            'user' => new UserResource($user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'entity' => $data['entity'],
            'branch_id' => $data['branch_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];

        $passwordChanged = ! empty($data['password']);
        if ($passwordChanged) {
            $payload['password'] = Hash::make($data['password']);
        }

        $old = $this->auditable($user, $user->roles->first()?->name);

        $user->update($payload);
        $user->syncRoles([$data['role']]);

        $this->logger->log(
            'update',
            'master.user',
            "Update user: {$user->name}".($passwordChanged ? ' (password direset)' : ''),
            $user,
            $old,
            $this->auditable($user->fresh(), $data['role'])
        );

        return redirect()->route('master.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $old = $this->auditable($user, $user->roles->first()?->name);
        $name = $user->name;

        $user->delete();

        $this->logger->log('delete', 'master.user', "Hapus user: {$name}", null, $old, null);

        return redirect()->route('master.users.index')->with('success', 'User berhasil dihapus.');
    }

    /**
     * Cuplikan data user untuk audit log — sengaja TANPA kolom password
     * agar hash kredensial tidak pernah ikut tersimpan di master_activity_logs.
     */
    private function auditable(User $user, ?string $role): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'entity' => $user->entity,
            'branch_id' => $user->branch_id,
            'is_active' => (bool) $user->is_active,
            'role' => $role,
        ];
    }

    /** Shared option payload for the create/edit form. */
    private function formOptions(?User $user = null): array
    {
        return [
            'branches' => Branch::where(function ($query) use ($user) {
                $query->where('is_active', true);
                if ($user?->branch_id) {
                    $query->orWhere('id', $user->branch_id);
                }
            })->orderBy('name')->get(['id', 'name', 'code', 'type', 'entity', 'is_active']),
            'roles' => Role::where('name', '!=', User::ROLE_OWNER)->orderBy('name')->pluck('name'),
            'entity_roles' => config('user_access.entity_roles'),
            'unit_options' => config('user_access.units'),
            'role_descriptions' => config('user_access.roles'),
        ];
    }
}
