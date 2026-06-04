<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KaryawanController extends Controller
{
    use ScopesMasterData;

    public function __construct(private ActivityLogService $logger) {}

    public function index(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $q = Karyawan::whereIn('entity_scope', [$info['scope'], 'all']);

        if ($search = $request->search) {
            $q->where('name', 'like', "%$search%");
        }

        if ($request->status !== null && $request->status !== '') {
            $q->where('is_active', $request->status);
        }

        $karyawans = $q->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('Master/Karyawan/Index', [
            'karyawans'    => $karyawans,
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function create(Request $request)
    {
        $info = $this->getScopeInfo($request);
        return Inertia::render('Master/Karyawan/Form', [
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function store(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $data = $request->validate([
            'name'         => 'required|string|max:150',
            'phone'        => 'nullable|string|max:20',
            'address'      => 'nullable|string',
            'is_active'    => 'boolean',
            'entity_scope' => 'nullable|in:gudang,jihans,hendhys,all',
        ]);

        $data['entity_scope'] = $request->input('entity_scope', $info['scope']);
        $data['is_active']    = $request->boolean('is_active', true);

        $karyawan = Karyawan::create($data);
        $this->logger->log('create', 'master.karyawan', "Tambah karyawan: {$karyawan->name}", $karyawan);

        return redirect()->route($info['route'] . 'karyawan.index')
            ->with('success', "Karyawan {$karyawan->name} berhasil ditambahkan.");
    }

    public function edit(Request $request, Karyawan $karyawan)
    {
        $info = $this->getScopeInfo($request);
        return Inertia::render('Master/Karyawan/Form', [
            'karyawan'     => $karyawan,
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function update(Request $request, Karyawan $karyawan)
    {
        $info = $this->getScopeInfo($request);
        $data = $request->validate([
            'name'         => 'required|string|max:150',
            'phone'        => 'nullable|string|max:20',
            'address'      => 'nullable|string',
            'is_active'    => 'boolean',
            'entity_scope' => 'nullable|in:gudang,jihans,hendhys,all',
        ]);

        $old = $karyawan->toArray();
        $data['is_active']    = $request->boolean('is_active', true);
        $data['entity_scope'] = $request->input('entity_scope', $karyawan->entity_scope);
        $karyawan->update($data);

        $this->logger->log('update', 'master.karyawan', "Update karyawan: {$karyawan->name}", $karyawan, $old, $karyawan->fresh()->toArray());

        return redirect()->route($info['route'] . 'karyawan.index')
            ->with('success', "Karyawan {$karyawan->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, Karyawan $karyawan)
    {
        $info = $this->getScopeInfo($request);
        $name = $karyawan->name;
        $karyawan->delete();
        $this->logger->log('delete', 'master.karyawan', "Hapus karyawan: $name");

        return redirect()->route($info['route'] . 'karyawan.index')
            ->with('success', "Karyawan $name berhasil dihapus.");
    }
}
