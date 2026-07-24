<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hendhys\HendhysBranchRequestResource;
use App\Models\HendhysBranchRequest;
use App\Models\HendhysBranchRequestDetail;
use App\Models\Product;
use App\Models\Unit;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BranchRequestController extends Controller
{
    public function __construct(private NumberGeneratorService $numbers) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        $q = HendhysBranchRequest::with(['branch', 'creator', 'approver']);

        // Jika user adalah Cabang, hanya lihat request cabangnya sendiri
        if ($user->branch->type === 'cabang') {
            $q->where('branch_id', $user->branch_id);
        }

        if ($status = $request->status) {
            $q->where('status', $status);
        }
        if ($search = $request->search) {
            $q->where('request_number', 'like', "%$search%");
        }

        $requests = $q->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return Inertia::render('Hendhys/BranchRequests/Index', [
            'requests' => HendhysBranchRequestResource::collection($requests),
            'filters'  => $request->only('search', 'status'),
        ]);
    }

    public function create()
    {
        // Hanya cabang yang bisa buat request ke pusat
        if (auth()->user()->branch?->type !== 'cabang') {
            abort(403, 'Hanya Cabang yang dapat membuat request stok ke Pusat.');
        }

        $products = Product::where('status', 'active')
            ->visibleInHendhys()
            ->with('unit')
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'unit_id' => $p->unit_id]);

        $units = Unit::orderBy('name')->get()->map(fn ($u) => ['id' => $u->id, 'abbreviation' => $u->abbreviation]);

        return Inertia::render('Hendhys/BranchRequests/Create', [
            'products' => $products,
            'units'    => $units,
        ]);
    }

    public function store(Request $request)
    {
        if (auth()->user()->branch?->type !== 'cabang') {
            abort(403, 'Hanya Cabang yang dapat membuat request stok ke Pusat.');
        }

        $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_id' => 'required|exists:master_units,id',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $br = HendhysBranchRequest::create([
                    'request_number' => $this->numbers->generateYearly('BRQ-HND', 'hendhys_branch_requests', 'request_number'),
                    'branch_id' => auth()->user()->branch_id,
                    'date' => $request->date,
                    'status' => 'pending',
                    'notes' => $request->notes,
                    'requested_by' => auth()->id()
                ]);

                foreach ($request->items as $item) {
                    HendhysBranchRequestDetail::create([
                        'request_id' => $br->id,
                        'product_id' => $item['product_id'],
                        'quantity_requested' => $item['quantity'],
                        'unit_id' => $item['unit_id']
                    ]);
                }

                event(new \App\Events\BranchRequestCreated($br));
            });

            return redirect()->route('hendhys.branch-requests.index')
                ->with('success', 'Request stok ke Pusat berhasil dikirim.');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal membuat request: ' . $e->getMessage());
        }
    }

    public function show(HendhysBranchRequest $branchRequest)
    {
        $user = auth()->user();
        if ($user->branch->type === 'cabang' && $branchRequest->branch_id !== $user->branch_id) {
            abort(403, 'Akses ditolak.');
        }

        $branchRequest->load(['branch', 'creator', 'approver', 'details.product', 'details.unit', 'transferOuts']);

        return Inertia::render('Hendhys/BranchRequests/Show', [
            'branchRequest' => new HendhysBranchRequestResource($branchRequest),
        ]);
    }

    public function reject(Request $request, HendhysBranchRequest $branchRequest)
    {
        $user = auth()->user();
        if ($user->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $branchRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return redirect()->route('hendhys.branch-requests.show', $branchRequest->id)
            ->with('success', 'Request dari cabang berhasil ditolak.');
    }
}
