<?php
$file = 'app/Http/Controllers/Jihans/ReportController.php';
$content = file_get_contents($file);
$content = str_replace(
    'groupByRaw("DATE_FORMAT(t.date, \'%Y-%m\')")', 
    'groupByRaw("DATE_FORMAT(t.date, \'%Y-%m\'), DATE_FORMAT(t.date, \'%M %Y\')")', 
    $content
);
file_put_contents($file, $content);
echo "Berhasil memperbaiki Jihans ReportController";
