<?php
declare(strict_types=1);

namespace Foreline\IO;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use Stringable;

/**
 *
 */
class Response
{
    private static array $advices = [];
    private static bool $debug = true;
    
    // ANSI color/style codes
    private const RESET      = "\033[0m";
    private const WHITE      = "\033[37m";
    private const GREEN      = "\033[32m";
    private const YELLOW     = "\033[33m";
    private const LIGHT_GRAY = "\033[37m";
    private const BG_RED     = "\033[41m";
    private const BG_BLUE    = "\033[44m";
    private const BG_YELLOW  = "\033[43m";
    private const BG_BLACK   = "\033[40m";
    
    private static function style(string $text, string $codes): string
    {
        // Skip ANSI codes when output is not a terminal
        if (!self::supportsColor()) {
            return $text;
        }
        return $codes . $text . self::RESET;
    }
    
    private static function supportsColor(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return str_contains((string)(getenv('ANSICON') ?: ''), 'x')
                || getenv('ConEmuANSI') === 'ON'
                || getenv('TERM_PROGRAM') === 'vscode'
                || getenv('WT_SESSION') !== false;
        }
        return function_exists('posix_isatty') && posix_isatty(STDOUT);
    }
    
    /**
     * @param string $text
     * @return void
     */
    public static function warn(string $text = ''): void
    {
        print self::style($text, self::WHITE . self::BG_YELLOW) . PHP_EOL;
        flush();
    }
    
    /**
     * @param string $text
     * @return void
     */
    public static function help(string $text = ''): void
    {
        print self::style($text, self::GREEN) . PHP_EOL;
        flush();
    }
    
    /**
     * @param string $text
     * @param bool $ln
     * @return void
     */
    public static function advice(string $text = '', bool $ln = true): void
    {
        if ( !in_array($text, self::$advices) ) {
            self::$advices[] = $text;
        }
        
        print
            self::style($text, self::YELLOW . self::BG_BLACK) .
            ( $ln ? PHP_EOL : '' )
        ;
        
        flush();
    }
    
    /**
     * @return void
     */
    public static function printAdvices(): void
    {
        foreach ( self::$advices as $advice ) {
            self::advice($advice);
        }
    }
    
    /**
     * @return array
     */
    public static function getAdvices(): array
    {
        return self::$advices;
    }
    
    /**
     * @param string $text
     * @return void
     */
    public static function error(string $text = ''): void
    {
        print self::style($text, self::WHITE . self::BG_RED) . PHP_EOL;
        flush();
    }
    
    /**
     * @param Exception $e
     * @param int $returnCode
     * @return mixed
     */
    public static function exception(Exception $e, int $returnCode = 1): int
    {
        self::error($e->getMessage());
        
        if ( self::$debug ) {
            self::showTrace($e);
        } else {
            self::advice('use --debug option for trace');
        }
        
        return $returnCode;
    }
    
    /**
     * Requires --debug argument
     * @param string $text
     * @return void
     */
    public static function debug(string $text = ''): void
    {
        if ( !self::$debug ) {
            return;
        }
        print self::style($text, self::LIGHT_GRAY) . PHP_EOL;
        flush();
    }
    
    /**
     * @param string $text
     * @return string
     */
    public static function getDebug(string $text = ''): string
    {
        return self::style($text, self::LIGHT_GRAY);
    }
    
    /**
     * @param string $text
     * @param bool $ln
     * @return void
     */
    public static function info(string $text = '', bool $ln = true): void
    {
        print
            self::style($text, self::WHITE . self::BG_BLUE) .
            ( $ln ? PHP_EOL : '')
        ;
        flush();
    }
    
    /**
     * @param Exception $e
     * @return void
     */
    public static function showTrace(Exception $e): void
    {
        self::debug('Trace: ');
        
        foreach ( $e->getTrace() as $key => $trace ) {
            self::debug(
                "\t" .
                $key . ': ' .
                $trace['file'] . ': ' .
                $trace['line'] . ' ' .
                (array_key_exists('message', $trace) ? $trace['message'] : '')
            );
        }
    }
    
    /**
     * @param string $question
     * @param array $options
     * @param string $defaultValue
     * @return bool
     */
    public static function request(string $question, array $options = ['y', 'yes', 'Y', 'Yes'], string $defaultValue = 'no'): bool
    {
        Response::info($question, false);
        Response::advice(' options: [' . implode(', ', $options) . '] ', false);
        Response::warn('default: [' . $defaultValue . ']');
        
        $handle = fopen('php://stdin', 'rb');
        
        if ( false === $answer = fgets($handle) ) {
            return false;
        }
        
        $answer = trim($answer);
        
        if ( !in_array($answer, $options) ) {
            return false;
        }
        
        return true;
    }
    
    public function emergency(Stringable|string $message, array $context = []): void
    {
        // TODO: Implement emergency() method.
    }
    
    public function alert(Stringable|string $message, array $context = []): void
    {
        // TODO: Implement alert() method.
    }
    
    public function critical(Stringable|string $message, array $context = []): void
    {
        // TODO: Implement critical() method.
    }
    
    public function warning(Stringable|string $message, array $context = []): void
    {
        // TODO: Implement warning() method.
    }
    
    public function notice(Stringable|string $message, array $context = []): void
    {
        // TODO: Implement notice() method.
    }
    
    public function log($level, Stringable|string $message, array $context = []): void
    {
        // TODO: Implement log() method.
    }
}