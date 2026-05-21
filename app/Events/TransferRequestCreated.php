<?php

namespace App\Events;

use App\Models\TransferRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransferRequestCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TransferRequest $request) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('gudang.notifications'),
            new PrivateChannel('owner.notifications'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id'             => $this->request->id,
            'request_number' => $this->request->request_number,
            'from_entity'    => $this->request->from_entity_label,
            'message'        => 'Permintaan transfer baru dari ' . $this->request->from_entity_label,
            'url'            => route('gudang.transfer-requests.show', $this->request->id),
        ];
    }
}
