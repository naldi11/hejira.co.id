<?php
$dir = new RecursiveDirectoryIterator("resources/views");
foreach(new RecursiveIteratorIterator($dir) as $f) {
    if(!$f->isFile() || !str_ends_with($f->getFilename(), '.blade.php')) continue;
    $c = file_get_contents($f);
    if(mb_strpos($c, "Â·") !== false || mb_strpos($c, "â€"") !== false) {
        echo "FOUND: " . $f->getPathname() . "\n";
        $lines = explode("\n", $c);
        foreach($lines as $i => $l) {
            if(mb_strpos($l, "Â·") !== false || mb_strpos($l, "â€"") !== false) {
                echo "  Line " . ($i+1) . ": " . trim($l) . "\n";
            }
        }
    }
}
echo "Scan complete.\n";
