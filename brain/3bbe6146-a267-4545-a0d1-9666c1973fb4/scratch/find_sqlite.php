<?php
function findSqliteFiles($dir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'sqlite') {
            echo $file->getPathname() . " (" . $file->getSize() . " bytes)\n";
        }
    }
}

echo "Searching for sqlite files in current folder:\n";
findSqliteFiles(__DIR__ . '/../../../../..'); // Go up to C:\Users\Lenovo
