<?php

namespace Garden;

use Garden\Exception\Error;

class ErrorHandler
{

    /**
     * init error handler
     */
    public static function register()
    {
        if (class_exists('\\Kuria\Error\ErrorHandler')) {
            self::kuria();
        } else {
            // Make all errors into exceptions.
            set_error_handler([self::class, 'errorHandler'], E_ALL);
        }
    }

    private static function kuria()
    {
        $handler = new \Kuria\Error\ErrorHandler();
        $handler->setDebug(Config::get('main.debug', true));
        $handler->register();

        if (Config::get('main.logs', true)) {
            $handler->on('fatal', static function ($exception) {
                /**
                 * @var \Exception $exception
                 */
                $logFilePath = GDN_LOGS . '/' . date('Y') . '/' . date('m') . '/';
                $old_umask = umask(0);
                @mkdir($logFilePath, 0775, true);
                umask($old_umask);

                $entry = sprintf(
                    "[%s] %s - %s in file %s on line %d\n",
                    date('Y-m-d H:i:s'),
                    \Kuria\Error\Util\Debug::getExceptionName($exception),
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine()
                );

                file_put_contents($logFilePath . date('d') . '.log', $entry, FILE_APPEND | LOCK_EX);
            });
        }
    }

    /**
     * @param $number
     * @param $message
     * @param $file
     * @param $line
     * @param $args
     * @throws Error
     */
    public static function errorHandler($number, $message, $file, $line, $args)
    {
        $error_reporting = error_reporting();
        // Ignore errors that are below the current error reporting level.
        if (($error_reporting & $number) !== $number) {
            return;
        }

        $backtrace = debug_backtrace();

        throw new Error($message, $number, $file, $line, $args, $backtrace);
    }
}
