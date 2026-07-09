<?php

namespace App\Http\Resources\Hendhys;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** A transfer-to-branch (pusat → cabang) distribution. */
class HendhysTransferToBranchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'transfer_number' => $this->transfer_number,
            'date'            => $this->created_at ? $this->created_at->isoFormat('D MMMM YYYY, HH:mm') : \Carbon\Carbon::parse($this->date)->isoFormat('D MMMM YYYY'),
            'status'          => $this->status,
            'notes'           => $this->notes,
            'branch'          => $this->whenLoaded('branch', fn () => $this->branch?->name),
            'branch_request'  => $this->whenLoaded('branchRequest', fn () => $this->branchRequest?->request_number),
            'creator'         => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'receiver'        => $this->whenLoaded('receiver', fn () => $this->receiver?->name),
            'receive_notes'   => $this->receive_notes,
            'receive_kendala' => $this->receive_kendala,

            'details' => $this->whenLoaded('details', fn () => $this->details->map(fn ($d) => [
                'id'                => $d->id,
                'product'           => $d->product?->name ?? '-',
                'product_code'      => $d->product?->code ?? '-',
                'quantity'          => (float) $d->quantity,
                'received_quantity' => $d->received_quantity !== null ? (float) $d->received_quantity : null,
                'kondisi'           => $d->kondisi,
                'unit'              => $d->unit?->abbreviation ?? 'PCS',
            ])),

            'photos' => $this->whenLoaded('photos', fn () => $this->photos->map(fn ($p) => [
                'id'   => $p->id,
                'path' => $p->path,
            ])),
        ];
    }
}
