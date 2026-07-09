<?php

namespace App\Http\Middleware;

use App\Models\TransferRequest;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Props shared with every Inertia response.
     *
     * Kept intentionally lean — page-specific data belongs in controllers,
     * not here. Closures are evaluated lazily so badge counts only run the
     * query for roles that actually display them.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user ? [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'email'  => $user->email,
                    'entity' => $user->entity,
                    'roles'  => $user->getRoleNames(),
                    'branch' => $user ? (function() use ($user) {
                        $branchId = session('active_branch_id') ?: $user->branch_id;
                        if (!$branchId) return null;
                        $branch = \App\Models\Branch::find($branchId);
                        return $branch ? [
                            'id'   => $branch->id,
                            'name' => $branch->name,
                            'type' => $branch->type,
                        ] : null;
                    })() : null,
                ] : null,
            ],

            // One-off flash messages → React reads these to fire toasts.
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],

            // Dynamic system notifications shared with front-end components
            'notifications' => [
                'gudang_pending' => fn () => $user && $user->hasAnyRole(['admin_gudang', 'owner'])
                    ? TransferRequest::where('status', 'pending')->count()
                    : 0,
                'items' => function() use ($user) {
                    if (!$user) return [];

                    $items = [];
                    $roles = $user->getRoleNames();
                    $isAdminGudang = $roles->contains('admin_gudang');
                    $isOwner = $roles->contains('owner');
                    $isAdminJihans = $roles->contains('admin_jihans') || $roles->contains('kasir_jihans');
                    $isAdminHendhys = $roles->contains('admin_hendhys') || $roles->contains('kasir_hendhys');

                    if ($isAdminGudang || $isOwner) {
                        // 1. Pending transfer requests from outlets
                        $trCount = TransferRequest::where('status', 'pending')->count();
                        if ($trCount > 0) {
                            $items[] = [
                                'id' => 'gudang_tr_pending',
                                'title' => 'Permintaan Transfer Baru',
                                'message' => "Ada {$trCount} permintaan transfer dari outlet menunggu persetujuan Anda.",
                                'path' => '/gudang/transfer-requests',
                                'icon' => 'arrow_forward',
                                'type' => 'warning',
                                'time' => 'Baru saja'
                            ];
                        }

                        // 2. Low stock items in Gudang
                        $lowStockCount = \App\Models\JihansGudangStock::join('master_products', 'jihans_gudang_stock.product_id', '=', 'master_products.id')
                            ->where('master_products.status', 'active')
                            ->whereRaw('jihans_gudang_stock.quantity <= master_products.stock_min')
                            ->count();
                        if ($lowStockCount > 0) {
                            $items[] = [
                                'id' => 'gudang_low_stock',
                                'title' => 'Stok Gudang Menipis',
                                'message' => "Ada {$lowStockCount} produk di Gudang Utama di bawah batas minimum safety stock.",
                                'path' => '/gudang/stock',
                                'icon' => 'warning',
                                'type' => 'danger',
                                'time' => 'Hari ini'
                            ];
                        }

                        // 3. Draft/sent POs awaiting action
                        $poCount = \App\Models\PurchaseOrder::whereIn('status', ['draft', 'sent'])->count();
                        if ($poCount > 0) {
                            $items[] = [
                                'id' => 'gudang_po_pending',
                                'title' => 'Purchase Order Menunggu',
                                'message' => "Ada {$poCount} PO yang belum diselesaikan (Draft / Terkirim).",
                                'path' => '/gudang/po',
                                'icon' => 'shopping_cart',
                                'type' => 'info',
                                'time' => 'Hari ini'
                            ];
                        }
                    }

                    if ($isAdminJihans || $isOwner) {
                        // 1. Incoming transfers from Gudang in transit
                        $transitCount = \App\Models\TransferOut::where('to_entity', 'jihans')->where('status', 'sent')->count();
                        if ($transitCount > 0) {
                            $items[] = [
                                'id' => 'jihans_transit',
                                'title' => 'Pengiriman Gudang Tiba',
                                'message' => "Ada {$transitCount} pengiriman dalam perjalanan dari Gudang Utama. Segera konfirmasi.",
                                'path' => '/jihans/transfer-requests',
                                'icon' => 'local_shipping',
                                'type' => 'info',
                                'time' => 'Baru saja'
                            ];
                        }

                        // 2. Incoming transfers from Hendhys Pusat in transit
                        $hendhysTransitCount = \App\Models\HendhysTransferToBranch::whereHas('branch', function ($query) {
                            $query->where('entity', 'jihans');
                        })->where('status', 'sent')->count();

                        if ($hendhysTransitCount > 0) {
                            $items[] = [
                                'id' => 'jihans_hendhys_transit',
                                'title' => 'Pengiriman Hendhys Pusat Tiba',
                                'message' => "Ada {$hendhysTransitCount} pengiriman dalam perjalanan dari Hendhys Pusat. Segera konfirmasi.",
                                'path' => '/jihans/transfer-from-hendhys',
                                'icon' => 'local_shipping',
                                'type' => 'info',
                                'time' => 'Baru saja'
                            ];
                        }

                        // 2. Low stock in Jihans
                        $lowStockCount = \App\Models\JihansRetailStock::join('master_products', 'jihans_retail_stock.product_id', '=', 'master_products.id')
                            ->where('master_products.status', 'active')
                            ->whereRaw('jihans_retail_stock.quantity <= master_products.stock_min')
                            ->count();
                        if ($lowStockCount > 0) {
                            $items[] = [
                                'id' => 'jihans_low_stock',
                                'title' => 'Stok Outlet Menipis',
                                'message' => "Ada {$lowStockCount} produk di outlet Jihans di bawah safety stock.",
                                'path' => '/jihans/stock',
                                'icon' => 'warning',
                                'type' => 'danger',
                                'time' => 'Hari ini'
                            ];
                        }
                    }

                    if ($user->entity === 'hendhys' || $isOwner) {
                        $isPusat = $user->branch && $user->branch->type === 'pusat';

                        if ($isPusat) {
                            // --- PUSAT NOTIFICATIONS: Gudang to Hendhys Pusat ---
                            if ($user->hasAnyRole(['admin_hendhys', 'super_admin_hendhys', 'owner'])) {
                                $transitCount = \App\Models\TransferOut::where('to_entity', 'hendhys')
                                    ->where('branch_id', $user->branch_id)
                                    ->where('status', 'sent')
                                    ->count();
                                if ($transitCount > 0) {
                                    $items[] = [
                                        'id' => 'hendhys_transit',
                                        'title' => 'Pengiriman Gudang Tiba',
                                        'message' => "Ada {$transitCount} pengiriman dalam perjalanan dari Gudang Utama. Segera konfirmasi.",
                                        'path' => '/hendhys/transfer-requests',
                                        'icon' => 'local_shipping',
                                        'type' => 'info',
                                        'time' => 'Baru saja'
                                    ];
                                }
                            }
                        } else {
                            // --- CABANG NOTIFICATIONS: Hendhys Pusat to Hendhys Cabang ---
                            if ($user->hasAnyRole(['kasir_hendhys', 'admin_hendhys', 'super_admin_hendhys', 'owner'])) {
                                $transitCount = \App\Models\HendhysTransferToBranch::where('branch_id', $user->branch_id)
                                    ->where('status', 'sent')
                                    ->count();
                                if ($transitCount > 0) {
                                    $items[] = [
                                        'id' => 'hendhys_cabang_transit',
                                        'title' => 'Pengiriman Pusat Tiba',
                                        'message' => "Ada {$transitCount} pengiriman dalam perjalanan dari Hendhys Pusat. Segera konfirmasi.",
                                        'path' => '/hendhys/transfer-to-branch',
                                        'icon' => 'local_shipping',
                                        'type' => 'info',
                                        'time' => 'Baru saja'
                                    ];
                                }

                                // Transit direct Gudang transfers (Gudang to Hendhys Cabang)
                                $gudangTransitCount = \App\Models\TransferOut::where('to_entity', 'hendhys')
                                    ->where('branch_id', $user->branch_id)
                                    ->where('status', 'sent')
                                    ->count();
                                if ($gudangTransitCount > 0) {
                                    $items[] = [
                                        'id' => 'hendhys_gudang_transit',
                                        'title' => 'Pengiriman Gudang Tiba',
                                        'message' => "Ada {$gudangTransitCount} pengiriman dalam perjalanan dari Gudang Utama. Segera konfirmasi.",
                                        'path' => '/hendhys/transfer-to-branch?tab=gudang',
                                        'icon' => 'local_shipping',
                                        'type' => 'info',
                                        'time' => 'Baru saja'
                                    ];
                                }
                            }
                        }

                        // 2. Low stock in Hendhys
                        if ($user->hasAnyRole(['admin_hendhys', 'super_admin_hendhys', 'owner'])) {
                            if ($isPusat) {
                                $lowStockCount = \App\Models\HendhysStockPusat::join('master_products', 'hendhys_stock_pusat.product_id', '=', 'master_products.id')
                                    ->where('master_products.status', 'active')
                                    ->whereRaw('hendhys_stock_pusat.quantity <= master_products.stock_min')
                                    ->count();
                            } else {
                                $lowStockCount = \App\Models\HendhysStockBranch::join('master_products', 'hendhys_stock_branch.product_id', '=', 'master_products.id')
                                    ->where('hendhys_stock_branch.branch_id', $user->branch_id)
                                    ->where('master_products.status', 'active')
                                    ->whereRaw('hendhys_stock_branch.quantity <= master_products.stock_min')
                                    ->count();
                            }

                            if ($lowStockCount > 0) {
                                $items[] = [
                                    'id' => 'hendhys_low_stock',
                                    'title' => 'Stok Outlet Menipis',
                                    'message' => "Ada {$lowStockCount} produk di outlet Hendhys di bawah safety stock.",
                                    'path' => '/hendhys/stock',
                                    'icon' => 'warning',
                                    'type' => 'danger',
                                    'time' => 'Hari ini'
                                ];
                            }
                        }
                    }

                    return $items;
                }
            ],
        ];
    }
}
