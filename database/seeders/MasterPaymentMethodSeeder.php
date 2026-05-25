<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class MasterPaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'entity_scope' => 'all',
                'name'         => 'Tunai',
                'type'         => 'tunai',
                'is_active'    => true,
            ],
            [
                'entity_scope' => 'all',
                'name'         => 'Transfer Bank',
                'type'         => 'kartu_debit',
                'is_active'    => true,
            ],
            [
                'entity_scope' => 'all',
                'name'         => 'Qris',
                'type'         => 'kartu_debit',
                'is_active'    => true,
            ],
            [
                'entity_scope' => 'all',
                'name'         => 'Kredit / Piutang',
                'type'         => 'kredit',
                'is_active'    => true,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['name' => $method['name']],
                $method
            );
        }
    }
}
