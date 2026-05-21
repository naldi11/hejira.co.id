<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['branch', 'roles'])->get();
        return view('master.users.index', compact('users'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        $roles = Role::all();
        return view('master.users.form', compact('branches', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:master_users',
            'password' => 'required|string|min:8',
            'entity' => 'required|in:gudang,jihans,hendhys,owner,all',
            'branch_id' => 'nullable|exists:master_branches,id',
            'role' => 'required|exists:roles,name'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'entity' => $request->entity,
            'branch_id' => $request->branch_id,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        $user->assignRole($request->role);

        return redirect()->route('master.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $branches = Branch::where('is_active', true)->get();
        $roles = Role::all();
        return view('master.users.form', compact('user', 'branches', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:master_users,email,' . $user->id,
            'entity' => 'required|in:gudang,jihans,hendhys,owner,all',
            'branch_id' => 'nullable|exists:master_branches,id',
            'role' => 'required|exists:roles,name'
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'entity' => $request->entity,
            'branch_id' => $request->branch_id,
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8']);
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->syncRoles([$request->role]);

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
}
