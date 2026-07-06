<?php
$file = 'app/Models/CashierShift.php';
$content = file_get_contents($file);

$search = <<<EOT
        \$closedAt = \$this->closed_at ?? now();

        \$summary = \Illuminate\Support\Facades\DB::table(\$paymentTable . ' as p')
            ->join(\$transactionTable . ' as t', 't.id', '=', 'p.transaction_id')
            ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->where('t.created_by', \$this->user_id)
            ->where('t.status', '!=', 'cancelled')
            ->whereBetween('t.created_at', [\$this->opened_at, \$closedAt])
EOT;

$replace = <<<EOT
        \$closedAt = \$this->closed_at ?? now();
        
        \$previousShift = self::where('user_id', \$this->user_id)
            ->where('branch_id', \$this->branch_id)
            ->where('id', '<', \$this->id)
            ->orderBy('id', 'desc')
            ->first();

        \$startAt = \$previousShift ? \$previousShift->closed_at : \Carbon\Carbon::parse(\$this->opened_at)->startOfDay();

        \$summary = \Illuminate\Support\Facades\DB::table(\$paymentTable . ' as p')
            ->join(\$transactionTable . ' as t', 't.id', '=', 'p.transaction_id')
            ->leftJoin('master_payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->where('t.created_by', \$this->user_id)
            ->where('t.status', '!=', 'cancelled')
            ->whereBetween('t.created_at', [\$startAt, \$closedAt])
EOT;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Fixed calculatePaymentSummary\n";
