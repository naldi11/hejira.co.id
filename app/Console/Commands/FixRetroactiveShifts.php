<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\CashierShift;
use Carbon\Carbon;

class FixRetroactiveShifts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hejira:fix-shifts {date : The date to fix in YYYY-MM-DD format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Injects closed Laci Kasir (shifts) for transactions that occurred before the feature was deployed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date');
        $this->info("Starting retroactive shift injection for date: {$date}");

        $entities = [
            'hendhys' => 'hendhys_transactions',
            'jihans' => 'jihans_transactions'
        ];

        foreach ($entities as $entity => $table) {
            $this->info("Processing entity: {$entity}");

            $transactions = DB::table($table)
                ->whereDate('created_at', $date)
                ->orderBy('created_at')
                ->get();

            if ($transactions->isEmpty()) {
                $this->info("No transactions found for {$entity} on {$date}");
                continue;
            }

            $grouped = $transactions->groupBy('created_by');

            foreach ($grouped as $userId => $trxs) {
                $user = User::find($userId);
                if (!$user) continue;

                $firstTrx = $trxs->first();
                $lastTrx = $trxs->last();

                // Check if shift already exists for this user on this date
                $exists = CashierShift::where('entity', $entity)
                    ->where('user_id', $userId)
                    ->whereDate('opened_at', $date)
                    ->exists();

                if ($exists) {
                    $this->warn("Shift already exists for {$user->name} on {$date}. Skipping.");
                    continue;
                }

                $openedAt = Carbon::parse($firstTrx->created_at)->subMinutes(5);
                $closedAt = Carbon::parse($lastTrx->created_at)->addMinutes(5);

                $shift = new CashierShift();
                $shift->entity = $entity;
                $shift->user_id = $userId;
                $shift->opened_at = $openedAt;
                $shift->closed_at = $closedAt;
                $shift->starting_cash = 0;
                $shift->status = 'closed';
                $shift->save(); // Save first to generate ID for calculation

                // Calculate cash
                $expectedCash = $shift->calculateExpectedCashSoFar();
                
                $shift->expected_cash = $expectedCash;
                $shift->actual_cash = $expectedCash; // Set actual equals expected (discrepancy = 0)
                $shift->discrepancy = 0;
                $shift->save();

                $this->info("✓ Created shift for {$user->name} ({$entity}). Total TRXs: " . $trxs->count() . ". Expected Cash: Rp " . number_format($expectedCash, 0, ',', '.'));
            }
        }

        $this->info("Done!");
    }
}
