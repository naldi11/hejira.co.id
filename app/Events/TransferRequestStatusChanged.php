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

class TransferRequestStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TransferRequest $request) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->request->requested_by . '.notifications'),
            new PrivateChannel('owner.notifications'),
        ];
    }

    public function broadcastWith(): array
    {
        $statusLabel = match($this->request->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default    => $this->request->status,
        };

        return [
            'id'             => $this->request->id,
            'request_number' => $this->request->request_number,
            'status'         => $this->request->status,
            'message'        => 'Permintaan transfer ' . $this->request->request_number . ' telah ' . $statusLabel,
            'url'            => $this->request->from_entity == 'jihans' 
                                ? route('jihans.transfer-requests.show', $this->request->id)
                                : route('hendhys.transfer-requests.show', $this->request->id),
        ];
    }
}
