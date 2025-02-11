<?php
declare(strict_types=1);

namespace Foreline\IO;

use Colors\Color;
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
    
    /**
     * @param string $text
     * @return Color
     */
    private static function print(string $text): Color
    {
        flush();
        $c = new Color($text);
        $c->setUserStyles([
            'error' => ['white', 'bg_red'],
            'warn' => ['white', 'bg_light_yellow'],
            'info' => ['white', 'bg_blue'],
            'help' => ['green'],
            'advice' => ['yellow', 'bg_black'],
            'debug' => ['light_gray', 'bg_default'],
        ]);
        return $c;
    }
    
    /**
     * @param string $text
     * @return void
     * @noinspection PhpUndefinedFieldInspection
     */
    public static function warn(string $text = ''): void
    {
        print self::print($text)->warn . PHP_EOL;
        //ob_flush();
        flush();
    }
    
    /**
     * @param string $text
     * @return void
     * @noinspection PhpUndefinedFieldInspection
     */
    public static function help(string $text = ''): void
    {
        print self::print($text)->help . PHP_EOL;
        //ob_flush();
        flush();
    }
    
    /**
     * @param string $text
     * @param bool $ln
     * @return void
     * @noinspection PhpUndefinedFieldInspection
     */
    public static function advice(string $text = '', bool $ln = true): void
    {
        if ( !in_array($text, self::$advices) ) {
            self::$advices[] = $text;
        }
        
        print
            self::print($text)->advice .
            ( $ln ? PHP_EOL : '' )
        ;
        
        //ob_flush();
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
     * @noinspection PhpUndefinedFieldInspection
     */
    public static function error(string $text = ''): void
    {
        print self::print($text)->error . PHP_EOL;
        //ob_flush();
        flush();
    }
    
    /**
     * @param Exception $e
     * @return void
     */
    #[NoReturn]
    public static function exception(Exception $e): void
    {
        self::error($e->getMessage());
        if ( self::$debug ) {
            self::showTrace($e);
        } else {
            self::advice('use --debug option for trace');
        }
        //exit(1);
    }
    
    /**
     * Requires --debug argument
     * @param string $text
     * @return void
     * @noinspection PhpUndefinedFieldInspection
     */
    public static function debug(string $text = ''): void
    {
        if ( !self::$debug ) {
            return;
        }
        print self::print($text)->debug . PHP_EOL;
        //ob_flush();
        flush();
    }
    
    /**
     * @param string $text
     * @return string
     * @noinspection PhpUndefinedFieldInspection
     */
    public static function getDebug(string $text = ''): string
    {
        return (string) self::print($text)->debug;
    }
    
    /**
     * @param string $text
     * @param bool $ln
     * @return void
     * @noinspection PhpUndefinedFieldInspection
     */
    public static function info(string $text = '', bool $ln = true): void
    {
        print
            self::print($text)->info .
            ( $ln ? PHP_EOL : '')
        ;
        //ob_flush();
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