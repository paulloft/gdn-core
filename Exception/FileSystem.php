<?php


namespace Garden\Exception;

class FileSystem extends Client {
    public function __construct($message = '', $code = 500, array $context = [])
    {
        parent::__construct($message, $code);
        $this->context = $context;
    }
}