<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Models\HendhysBranchRequest;
use App\Models\HendhysBranchRequestDetail;
use App\Models\Hendhys\Product;
use App\Models\Hendhys\Unit;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return view('hendhys.branch-requests.index', compact('requests'));
    }

    public function create()
    {
        // Hanya cabang yang bisa buat request ke pusat
        if (auth()->user()->branch->type !== 'cabang') {
            abort(403, 'Hanya Cabang yang dapat membuat request stok ke Pusat.');
        }

        $products = Product::where('status', 'active')
            ->whereIn('entity_scope', ['hendhys', 'all'])
            ->orderBy('name')
            ->get();
        $units = Unit::all();

        return view('hendhys.branch-requests.form', compact('products', 'units'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->branch->type !== 'cabang') {
            abort(403, 'Hanya Cabang yang dapat membuat request stok ke Pusat.');
        }

        $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:hendhys_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_id' => 'required|exists:hendhys_units,id',
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
        return view('hendhys.branch-requests.show', compact('branchRequest'));
    }
}
