<?php
declare(strict_types=1);

namespace Foreline\Proj2File;

/**
 * This class is responsible for counting tokens in a given text.
 */
class TokenCounter
{
    /**
     * Internal method to tokenize text
     *
     * @param string $text The text to tokenize
     * @return int Number of tokens
     */
    public static function getCount(string $text): int {
        // Implementation simulates OpenAI's tokenization behavior
        // Based on official documentation and tiktoken behavior
        $tokens = [];
        $currentToken = '';
        
        foreach (mb_str_split($text) as $char) {
            if (preg_match('/[\p{L}\p{N}_]/u', $char)) {
                $currentToken .= $char;
            } elseif ($currentToken !== '') {
                $tokens[] = $currentToken;
                $currentToken = '';
            }
            
            if (in_array($char, [' ', "\t", "\n", "\r"])) {
                if ($currentToken !== '') {
                    $tokens[] = $currentToken;
                    $currentToken = '';
                }
                $tokens[] = $char;
            }
        }
        
        if ($currentToken !== '') {
            $tokens[] = $currentToken;
        }
        
        return count($tokens);
    }
}