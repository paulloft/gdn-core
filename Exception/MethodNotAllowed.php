<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Exception;

/**
 * An exception that represents a 405 method not allowed exception.
 */
class MethodNotAllowed extends Client {

    /**
     * Initialize the {@link MethodNotAllowed}.
     *
     * @param string $method The http method that's not allowed.
     * @param array $allow An array http methods that are allowed.
     */
    public function __construct($method, array $allow = []) {
        $message = sprintf('%s not allowed.', strtoupper($method));
        parent::__construct($message, 405, ['HTTP_ALLOW' => strtoupper(implode(', ', $allow))]);
    }

    /**
     * Get the allowed http methods.
     *
     * @return array Returns an array of allowed methods.
     */
    public function getAllow() {
        return array_map('trim', explode(',', $this->context['HTTP_ALLOW']));
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize() {
        $result = parent::jsonSerialize();
        $result['allow'] = $this->getAllow();
        
        return $result;
    }
}
