<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Exception;

/**
 * An exception for php errors that also includes the error context and a backtrace.
 */
class Error extends \ErrorException {

    protected $context;
    protected $backtrace;

    /**
     * Initialize an instance of the {@link Error} class.
     *
     * @param string $message The error message.
     * @param int $number The error number.
     * @param string $filename The file where the error occurred.
     * @param int $line The line number in the file.
     * @param array $context The currently defined variables when the error occured.
     * @param array $backtrace A debug backtrace from when the error occurred.
     */
    public function __construct($message, $number = 500, $filename = __FILE__, $line = __LINE__, $context = [], $backtrace = []) {
        parent::__construct($message, $number, 0, $filename, $line);
        $this->context = $context;
        $this->backtrace = $backtrace;
    }

    /**
     * Get the debug backtrace from the error.
     *
     * @return array Returns the backtrace.
     */
    public function getBacktrace() {
        return $this->backtrace;
    }

    /**
     * Gets a longer description for the exception.
     *
     * @return string Returns the description of the exception or an empty string if there isn't one.
     */
    public function getDescription() {
        return $this->context['description'] ?? '';
    }

    /**
     * Get the error context.
     *
     * @return array
     */
    public function getContext() {
        return $this->context;
    }
}