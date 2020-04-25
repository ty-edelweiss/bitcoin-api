<?php

namespace App\Traits;

trait JsonLineConverter {

    public function loadJsonLine(string $path)
    {
        $fp = fopen($path, 'r');
 
        $data = [];
        while (!feof($fp)) {
            $line = fgets($fp);

            if (empty($line)) {
                continue;
            }

            $data[] = json_decode($line, true);
        }

        fclose($fp);

        return $data;
    }
}