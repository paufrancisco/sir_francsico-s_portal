<?php

namespace App\Support;

class ProfanityFilter
{
    // Matched as whole words
    private const WORDS = [
        'fuck', 'fucker', 'shit', 'bitch', 'asshole', 'bastard', 'dick', 'pussy', 'cunt',
        'slut', 'whore', 'nigga', 'nigger', 'retard', 'fag', 'faggot',
        'puta', 'putang', 'gago', 'gaga', 'ulol', 'tarantado', 'pakyu', 'tangina',
        'putangina', 'leche', 'lintik', 'punyeta', 'bwisit', 'siraulo', 'pekpek',
        'titi', 'kantot', 'burat', 'hindot', 'shunga', 'inutil', 'tanga', 'bobo',
    ];

    // Long enough to be matched even when letters are spaced out or glued to other words
    private const SQUASHED = [
        'fuck', 'bitch', 'nigga', 'nigger', 'putangina', 'tangina', 'tarantado',
        'punyeta', 'pakyu', 'pekpek', 'kantot', 'hindot',
    ];

    private static function collapse(string $s): string
    {
        return preg_replace('/(.)\1+/u', '$1', $s);
    }

    public static function contains(string $text): bool
    {
        $t = mb_strtolower($text);
        $t = strtr($t, ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '@' => 'a', '$' => 's']);

        $words = array_map([self::class, 'collapse'], self::WORDS);
        $tokens = preg_split('/[^a-z]+/u', $t, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($tokens as $tok) {
            if (in_array(self::collapse($tok), $words, true)) {
                return true;
            }
        }

        $squashed = self::collapse(preg_replace('/[^a-z]+/u', '', $t));
        foreach (self::SQUASHED as $w) {
            if (str_contains($squashed, self::collapse($w))) {
                return true;
            }
        }

        return false;
    }
}
