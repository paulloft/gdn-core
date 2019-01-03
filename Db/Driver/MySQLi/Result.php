<?php

namespace Garden\Db\Driver\MySQLi;
/**
 * MySQLi database result.   See [Results](/database/results) for usage and examples.
 *
 * @package    Kohana/Database
 * @category   Query/Result
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Result extends \Garden\Db\Database\Result {

    protected $internalRow = 0;

    public function __construct($result, $sql, $asObject = false, array $params = null)
    {
        parent::__construct($result, $sql, $asObject, $params);

        // Find the number of rows in the result
        $this->_total_rows = $result->num_rows;
    }

    public function __destruct()
    {
        if (is_resource($this->_result)) {
            $this->_result->free();
        }
    }

    public function seek($offset)
    {
        if ($this->offsetExists($offset) && $this->_result->data_seek($offset)) {
            // Set the current row to the offset
            $this->_current_row = $this->internalRow = $offset;

            return true;
        }

        return FALSE;
    }

    public function current()
    {
        if ($this->_current_row !== $this->internalRow && !$this->seek($this->_current_row)) {
            return null;
        }

        // Increment internal row for optimization assuming rows are fetched in order
        $this->internalRow++;

        if ($this->_as_object === true) {
            // Return an stdClass
            return $this->_result->fetch_object();
        }

        if (is_string($this->_as_object)) {
            // Return an object of given class name
            return $this->_result->fetch_object($this->_as_object, (array)$this->_object_params);
        }
        // Return an array of the row
        return $this->_result->fetch_assoc();
    }

}
