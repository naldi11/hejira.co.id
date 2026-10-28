<?php

namespace App\Services;

use App\Models\CashierShift;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Bounded batch queries for the shifts visible on one report page. */
class ShiftReportSummaries
{
    public function load(Collection $shifts): void
    {
        if ($shifts->isEmpty()) return;
        $previous = DB::table('master_cashier_shifts as s')->whereIn('s.id', $shifts->pluck('id')->unique())
            ->select('s.id')->selectSub(function ($q) {
                $q->from('master_cashier_shifts as prev')->select('prev.closed_at')
                    ->whereColumn('prev.user_id', 's.user_id')->whereColumn('prev.entity', 's.entity')->whereColumn('prev.id', '<', 's.id')
                    ->where(fn ($w) => $w->where('s.entity', '!=', 'hendhys')->orWhereColumn('prev.branch_id', 's.branch_id')
                        ->orWhere(fn ($n) => $n->whereNull('prev.branch_id')->whereNull('s.branch_id')))
                    ->whereNotNull('prev.closed_at')->orderByDesc('prev.id')->limit(1);
            }, 'previous_closed_at')->get()->keyBy('id');
        $now = now();
        foreach ($shifts->unique('id')->groupBy('entity') as $entity => $rows) {
            $periods = null; $openPeriods = null;
            foreach ($rows as $shift) {
                $opened = Carbon::parse($shift->opened_at);
                $lastClosed = $previous[$shift->id]->previous_closed_at ?? null;
                $start = $lastClosed && Carbon::parse($lastClosed)->isSameDay($opened)
                    ? Carbon::parse($lastClosed) : $opened->copy()->startOfDay();
                $this->addPeriod($periods, $shift, $start, $shift->closed_at ?? $now);
                if ($shift->status === 'open') $this->addPeriod($openPeriods, $shift, $opened, $now);
            }
            $table = $entity === 'jihans' ? 'jihans' : 'hendhys';
            $summary = $this->payments($table, $periods)->select('periods.shift_id')
                ->selectRaw(CashierShift::paymentSummaryColumns($table === 'hendhys'))->groupBy('periods.shift_id')->get()->keyBy('shift_id');
            $expected = $openPeriods ? $this->payments($table, $openPeriods)->select('periods.shift_id')
                ->selectRaw(CashierShift::paymentSummaryColumns($table === 'hendhys'))
                ->groupBy('periods.shift_id')->get()->keyBy('shift_id') : collect();
            // A shift may occur both in the page and the active-shift card.
            foreach ($shifts->where('entity', $entity) as $shift) {
                $totals = isset($summary[$shift->id]) ? (array) $summary[$shift->id] : array_fill_keys(['tunai', 'transfer', 'kartu_debit', 'kartu_kredit', 'kredit'], 0);
                unset($totals['shift_id']);
                $shift->payment_summary = $totals;
                if ($shift->status === 'open') $shift->expected_cash = $shift->starting_cash + (int) ($expected[$shift->id]->tunai ?? 0);
            }
        }
    }

    private function addPeriod(&$query, CashierShift $shift, $start, $end): void
    {
        $row = DB::query()->selectRaw("? as shift_id, ? as user_id, ? as start_at, ? as end_at, CASE WHEN (SELECT type FROM master_branches WHERE id = ?) = 'pusat' THEN NULL ELSE ? END as branch_id", [
            $shift->id, $shift->user_id, Carbon::parse($start)->toDateTimeString(), Carbon::parse($end)->toDateTimeString(), $shift->branch_id, $shift->branch_id,
        ]);
        if ($query) $query->unionAll($row); else $query = $row;
    }

    private function payments(string $table, $periods)
    {
        return DB::table($table.'_transaction_payments as p')
            ->join($table.'_transactions as t', 't.id', '=', 'p.transaction_id')
            ->joinSub($periods, 'periods', fn ($join) => $join->on('t.created_by', '=', 'periods.user_id')
                ->where(fn ($q) => $q->whereColumn('t.shift_id', 'periods.shift_id')->orWhere(fn ($legacy) => $legacy
                    ->whereNull('t.shift_id')->whereColumn('t.created_at', '>=', 'periods.start_at')->whereColumn('t.created_at', '<=', 'periods.end_at')
                    ->when($table === 'hendhys', fn ($b) => $b->whereRaw('COALESCE(t.branch_id, 0) = COALESCE(periods.branch_id, 0)')))))
            ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->where('t.status', '!=', 'cancelled');
    }
}
