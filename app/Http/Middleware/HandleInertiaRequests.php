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
                    'entity' => $user->entity,
                    'roles'  => $user->getRoleNames(),
                ] : null,
            ],

            // One-off flash messages → React reads these to fire toasts.
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],

            // Sidebar badge: pending transfer requests awaiting Gudang approval.
            'notifications' => [
                'gudang_pending' => fn () => $user && $user->hasAnyRole(['admin_gudang', 'owner'])
                    ? TransferRequest::where('status', 'pending')->count()
                    : 0,
            ],
        ];
    }
}
