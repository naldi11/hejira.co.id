<?php

namespace Tests\Unit;

use App\Http\Resources\Gudang\TransferOutResource;
use App\Http\Resources\Hendhys\HendhysTransferToBranchResource;
use App\Models\HendhysTransferToBranch;
use App\Models\TransferOut;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Tests\TestCase;

class TransferDateTest extends TestCase
{
    public function test_backdated_transfers_keep_their_business_date_and_show_the_separate_recording_time(): void
    {
        foreach ($this->transfers() as [$transfer, $resource]) {
            $transfer->forceFill([
                'date' => '2026-10-12',
                'created_at' => '2026-10-13 01:41:07',
            ]);

            $data = (new $resource($transfer))->resolve(Request::create('/'));

            $this->assertSame('2026-10-12', $data['date'], $resource);
            $this->assertSame('13/10/2026 01:41:07 WIB', $data['recorded_at'], $resource);
        }
    }

    public function test_recording_time_with_an_explicit_offset_is_converted_to_wib_without_changing_the_business_date(): void
    {
        foreach ($this->transfers() as [$transfer, $resource]) {
            $transfer->setRawAttributes([
                'date' => '2026-12-30',
                'created_at' => '2026-12-31T17:00:07+00:00',
            ]);

            $data = (new $resource($transfer))->resolve(Request::create('/'));

            $this->assertSame('2026-12-30', $data['date'], $resource);
            $this->assertSame('01/01/2027 00:00:07 WIB', $data['recorded_at'], $resource);
        }
    }

    public function test_missing_recording_time_does_not_replace_or_invent_a_business_date(): void
    {
        foreach ($this->transfers() as [$transfer, $resource]) {
            $transfer->forceFill(['date' => '2026-10-12', 'created_at' => null]);
            $data = (new $resource($transfer))->resolve(Request::create('/'));

            $this->assertSame('2026-10-12', $data['date'], $resource);
            $this->assertNull($data['recorded_at'], $resource);

            $transfer->forceFill(['date' => null, 'created_at' => '2026-10-13 01:41:07']);
            $data = (new $resource($transfer))->resolve(Request::create('/'));

            $this->assertNull($data['date'], $resource);
            $this->assertSame('13/10/2026 01:41:07 WIB', $data['recorded_at'], $resource);
        }
    }

    /** @return array<array{Model, class-string}> */
    private function transfers(): array
    {
        return [
            [new TransferOut, TransferOutResource::class],
            [new HendhysTransferToBranch, HendhysTransferToBranchResource::class],
        ];
    }
}
