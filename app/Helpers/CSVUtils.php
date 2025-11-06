<?php

namespace App\Helpers;

class CSVUtils
{
    public static function countLines(string $path): int
    {
        $c = 0;
        $h = fopen($path, 'r');

        if (!$h) {
            return 0;
        }

        while (!feof($h)) {
            fgets($h);
            $c++;
        }
        
        fclose($h);
        return $c;
    }
}
