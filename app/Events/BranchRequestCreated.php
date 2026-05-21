<?php

namespace App\Events;

use App\Models\HendhysBranchRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BranchRequestCreated implements ShouldBroadcast
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
        return [
            'id'             => $this->request->id,
            'request_number' => $this->request->request_number,
            'branch_name'    => $this->request->branch->name,
            'message'        => 'Request stok baru dari Cabang ' . $this->request->branch->name,
            'url'            => route('hendhys.branch-requests.show', $this->request->id),
        ];
    }
}
