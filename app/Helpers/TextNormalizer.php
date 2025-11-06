<?php

namespace App\Helpers;

class TextNormalizer
{
    public static function normalize(string $s): string
    {
        $s = trim($s);
        return class_exists(\Normalizer::class)
            ? \Normalizer::normalize($s, \Normalizer::FORM_C)
            : $s;
    }

    public static function decode(string $s): string
    {
        return html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function regVariants(): array
    {
        return ['®','&reg;','&#174;'];
    }

    public static function patterns(string $phrase): array
    {
        $phrase = self::normalize($phrase);
        if ($phrase === '') {
            return [];
        }

        $out = [];
        $out[] = $phrase;

        $placeholder = '__REG__';
        $withPlaceholder = str_replace(self::regVariants(), $placeholder, $phrase);

        if ($withPlaceholder !== $phrase) {
            foreach (self::regVariants() as $v) {
                foreach (['%s',' %s ','  %s  ','%s ',' %s'] as $mask) {
                    $out[] = str_replace($placeholder, sprintf($mask, $v), $withPlaceholder);
                }
            }
        }

        $tokens = preg_split('/\s+/', $phrase);
        foreach ($tokens as $t) {
            if (strlen($t) >= 3) {
                $out[] = $t;
            }
        }

        return array_values(array_unique($out));
    }

    public static function clean(string $s): string
    {
        $enc = mb_detect_encoding($s, mb_detect_order(), true) ?: 'UTF-8';
        $s   = iconv($enc, 'UTF-8//IGNORE', $s) ?: '';
        $s = preg_replace('/[\x{FEFF}\x{200B}\x{200E}\x{200F}\x{00A0}]+/u', '', $s);
        $s = preg_replace('/[^\P{C}\t\r\n]+/u', '', $s);
        return trim($s);
    }
}
