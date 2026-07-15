<?php

namespace App\Services;

class NumberToWordsFormatter
{
    /**
     * Convert numeric amount to words using Indian terminology (Rupees, Lakh, Crore).
     *
     * @param float|int $amount
     * @return string
     */
    public static function convert($amount): string
    {
        $amount = round($amount);
        if ($amount == 0) {
            return 'RUPEES ZERO ONLY';
        }

        $isNegative = $amount < 0;
        $amount = abs($amount);

        $words = '';
        // Conditionally use NumberFormatter if it exists
        if (class_exists(\NumberFormatter::class)) {
            try {
                $f = new \NumberFormatter("en_IN", \NumberFormatter::SPELLOUT);
                $spellout = $f->format($amount);
                if ($spellout !== false) {
                    $words = str_replace('-', ' ', $spellout);
                }
            } catch (\Throwable $e) {
                // Fallback to custom implementation
            }
        }

        // If words is still empty or didn't use Indian terms, use our custom Indian numbering system formatting
        if (empty($words) || (!str_contains(strtolower($words), 'lakh') && !str_contains(strtolower($words), 'crore') && $amount >= 100000)) {
            $words = self::convertToIndianStyle($amount);
        }

        // Clean up formatting
        $words = trim(preg_replace('/\s+/', ' ', $words));

        $prefix = $isNegative ? 'MINUS ' : '';
        return 'RUPEES ' . $prefix . strtoupper($words) . ' ONLY';
    }

    /**
     * Convert number to words in Indian numbering system recursively.
     */
    public static function convertToIndianStyle(int $num): string
    {
        if ($num === 0) {
            return '';
        }

        $ones = [
            1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
            6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten',
            11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen',
            16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen', 19 => 'nineteen'
        ];

        $tens = [
            2 => 'twenty', 3 => 'thirty', 4 => 'forty', 5 => 'fifty',
            6 => 'sixty', 7 => 'seventy', 8 => 'eighty', 9 => 'ninety'
        ];

        if ($num < 20) {
            return $ones[$num];
        }

        if ($num < 100) {
            $t = intval($num / 10);
            $r = $num % 10;
            return $tens[$t] . ($r > 0 ? '-' . $ones[$r] : '');
        }

        if ($num < 1000) {
            $h = intval($num / 100);
            $r = $num % 100;
            return $ones[$h] . ' hundred' . ($r > 0 ? ' ' . self::convertToIndianStyle($r) : '');
        }

        if ($num < 100000) {
            $th = intval($num / 1000);
            $r = $num % 1000;
            return self::convertToIndianStyle($th) . ' thousand' . ($r > 0 ? ' ' . self::convertToIndianStyle($r) : '');
        }

        if ($num < 10000000) {
            $lk = intval($num / 100000);
            $r = $num % 100000;
            return self::convertToIndianStyle($lk) . ' lakh' . ($r > 0 ? ' ' . self::convertToIndianStyle($r) : '');
        }

        $cr = intval($num / 10000000);
        $r = $num % 10000000;
        return self::convertToIndianStyle($cr) . ' crore' . ($r > 0 ? ' ' . self::convertToIndianStyle($r) : '');
    }
}
