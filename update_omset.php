<?php
$content = file_get_contents('app/Http/Controllers/Owner/DashboardController.php');

// Fix all_transactions
$content = preg_replace(
    '/\$subtitle = \'Total Omset: Rp \' \. number_format\(\$list->sum\(\'grand_total\'\), 0, \',\', \'\.\'\);/',
    '$subtitle = \'Total Omset: Rp \' . number_format((clone $query)->sum(\'grand_total\'), 0, \',\', \'.\');',
    $content
);

// Actually, $query is not defined for all_transactions in the same way, let's just do a string replace carefully.
