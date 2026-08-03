<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransferRequest;
use App\Models\HendhysBranchRequest;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function getCounts()
    {
        $user = auth()->user();
        $counts = [
            'gudang_pending' => 0,
            'hendhys_pusat_pending' => 0,
            'my_transfer_requests' => 0,
        ];

        // 1. Gudang Count (for Admin Gudang & Owner)
        if ($user->hasRole('super_admin') || $user->hasRole('owner')) {
            $counts['gudang_pending'] = TransferRequest::where('status', 'pending')->count();
        }

        // 2. Hendhys Pusat Count (for Hendhys Pusat & Owner)
        // Pakai ?-> : user tanpa branch_id (mis. akun baru yang belum ditempatkan)
        // sebelumnya membuat endpoint ini fatal error, padahal ia dipanggil di
        // hampir setiap halaman untuk badge notifikasi.
        if (($user->hasRole('kasir_hendhys') && $user->branch?->type === 'pusat') || $user->hasRole('owner')) {
            $counts['hendhys_pusat_pending'] = HendhysBranchRequest::where('status', 'pending')->count();
        }

        // 3. Status updates for the user's own requests (simplified check for recent changes)
        // This is more for triggering browser notifications if the count of approved/rejected changed
        // but for now let's just return the main pending counts for badges.

        return response()->json($counts);
    }
}
