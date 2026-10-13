<?php

namespace Tests\Unit;

use App\Http\Resources\Gudang\GudangReturnResource;
use App\Http\Resources\Hendhys\HendhysGudangReturnResource;
use App\Models\GudangReturn;
use Illuminate\Http\Request;
use Tests\TestCase;

class ReturnDateTest extends TestCase
{
    public function test_return_resources_keep_calendar_date_and_expose_recording_time_in_wib(): void
    {
        $return = new GudangReturn;
        $return->forceFill([
            'id' => 207, 'return_number' => 'RET-HND-GDG-20260156',
            'from_entity' => 'jihans', 'date' => '2026-10-13',
            'created_at' => '2026-10-13 01:41:07',
        ]);

        foreach ([GudangReturnResource::class, HendhysGudangReturnResource::class] as $resource) {
            $data = json_decode(json_encode((new $resource($return))->resolve(Request::create('/'))), true);
            $this->assertSame('2026-10-13', $data['date']);
            $this->assertSame('13/10/2026 01:41:07 WIB', $data['recorded_at']);
        }
    }

    public function test_explicit_or_existing_transaction_dates_are_not_replaced_by_recording_date(): void
    {
        $return = new GudangReturn;
        $return->forceFill([
            'from_entity' => 'jihans', 'date' => '2026-10-12',
            'created_at' => '2026-10-13 01:41:07',
        ]);
        foreach ([GudangReturnResource::class, HendhysGudangReturnResource::class] as $resource) {
            $data = (new $resource($return))->resolve(Request::create('/'));
            $this->assertSame('2026-10-12', $data['date']);
            $this->assertSame('13/10/2026 01:41:07 WIB', $data['recorded_at']);
        }
    }
}
