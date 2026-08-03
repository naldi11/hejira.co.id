<?php

namespace App\Events;

use App\Models\HendhysBranchRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BranchRequestCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public HendhysBranchRequest $request) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('hendhys.pusat.notifications'),
            new PrivateChannel('owner.notifications'),
        ];
    }

    public function broadcastWith(): array
    {
        // ?-> + fallback: request tanpa branch (data lama / branch terhapus) tidak
        // boleh membuat broadcast gagal dengan fatal error di dalam queue worker.
        $branchName = $this->request->branch?->name ?? 'Tidak diketahui';

        return [
            'id'             => $this->request->id,
            'request_number' => $this->request->request_number,
            'branch_name'    => $branchName,
            'message'        => 'Request stok baru dari Cabang ' . $branchName,
            'url'            => route('hendhys.branch-requests.show', $this->request->id),
        ];
    }
}
