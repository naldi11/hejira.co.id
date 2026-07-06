<?php
$file = 'app/Models/CashierShift.php';
$content = file_get_contents($file);

$search = <<<EOT
        \$closedAt = \$this->closed_at ?? now();

        \$summary = \Illuminate\Support\Facades\DB::table(\$transactionTable)
            ->where('created_by', \$this->user_id)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [\$this->opened_at, \$closedAt])
EOT;

$replace = <<<EOT
        \$closedAt = \$this->closed_at ?? now();

        \$previousShift = self::where('user_id', \$this->user_id)
            ->where('branch_id', \$this->branch_id)
            ->where('id', '<', \$this->id)
            ->orderBy('id', 'desc')
            ->first();

        \$startAt = \$previousShift ? \$previousShift->closed_at : \Carbon\Carbon::parse(\$this->opened_at)->startOfDay();

        \$summary = \Illuminate\Support\Facades\DB::table(\$transactionTable)
            ->where('created_by', \$this->user_id)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [\$startAt, \$closedAt])
EOT;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Fixed calculateSalesSummary\n";
