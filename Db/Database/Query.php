<?php 
namespace Garden\Db\Database;
use Garden\Db\Database;
use Garden\Gdn;
/**
 * Database query wrapper.  See [Parameterized Statements](database/query/parameterized) for usage and examples.
 *
 * @package    Kohana/Database
 * @category   Query
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Query {

    // Query type
    protected $_type;

    // Execute the query during a cache hit
    protected $_force_execute = FALSE;

    // Cache lifetime
    protected $_lifetime = NULL;

    // SQL statement
    protected $_sql;

    // Quoted query parameters
    protected $_parameters = array();

    // Return results as associative arrays or objects
    protected $_as_object = FALSE;

    // Parameters for __construct when using object results
    protected $_object_params = array();

    /**
     * Creates a new SQL query of the specified type.
     *
     * @param   integer  $type  query type: Database::SELECT, Database::INSERT, etc
     * @param   string   $sql   query string
     * @return  void
     */
    public function __construct($type, $sql)
    {
        $this->_type = $type;
        $this->_sql = $sql;
    }

    /**
     * Return the SQL query string.
     *
     * @return  string
     */
    public function __toString()
    {
        try {
            // Return the SQL string
            return $this->compile(Database::instance());
        } catch (\Exception $e) {
            return \Exception::text($e);
        }
    }

    /**
     * Get the type of the query.
     *
     * @return  integer
     */
    public function type()
    {
        return $this->_type;
    }

    /**
     * Enables the query to be cached for a specified amount of time.
     *
     * @param   integer  $lifetime  number of seconds to cache, 0 deletes it from the cache
     * @param   boolean  whether or not to execute the query during a cache hit
     * @return  $this
     * @uses    Cache::$cache_life
     */
    public function cached($lifetime = NULL, $force = FALSE)
    {
        if ($lifetime === NULL) {
            // Use the global setting
            $lifetime = Gdn::cache()->lifetime;
        }

        $this->_force_execute = $force;
        $this->_lifetime = $lifetime;

        return $this;
    }

    /**
     * Returns results as associative arrays
     *
     * @return  $this
     */
    public function as_assoc()
    {
        $this->_as_object = FALSE;

        $this->_object_params = array();

        return $this;
    }

    /**
     * Returns results as objects
     *
     * @param   string  $class  classname or TRUE for stdClass
     * @param   array   $params
     * @return  $this
     */
    public function as_object($class = TRUE, array $params = NULL)
    {
        $this->_as_object = $class;

        if ($params) {
            // Add object parameters
            $this->_object_params = $params;
        }

        return $this;
    }

    /**
     * Set the value of a parameter in the query.
     *
     * @param   string   $param  parameter key to replace
     * @param   mixed    $value  value to use
     * @return  $this
     */
    public function param($param, $value)
    {
        // Add or overload a new parameter
        $this->_parameters[$param] = $value;

        return $this;
    }

    /**
     * Bind a variable to a parameter in the query.
     *
     * @param   string  $param  parameter key to replace
     * @param   mixed   $var    variable to use
     * @return  $this
     */
    public function bind($param, & $var)
    {
        // Bind a value to a variable
        $this->_parameters[$param] =& $var;

        return $this;
    }

    /**
     * Add multiple parameters to the query.
     *
     * @param   array  $params  list of parameters
     * @return  $this
     */
    public function parameters(array $params)
    {
        // Merge the new parameters in
        $this->_parameters = $params + $this->_parameters;

        return $this;
    }

    /**
     * Compile the SQL query and return it. Replaces any parameters with their
     * given values.
     *
     * @param   mixed  $db  Database instance or name of instance
     * @return  string
     */
    public function compile($db = NULL)
    {
        if ( ! is_object($db)) {
            // Get the database instance
            $db = Database::instance($db);
        }

        // Import the SQL locally
        $sql = $this->_sql;

        if ( ! empty($this->_parameters)) {
            // Quote all of the values
            $values = array_map(array($db, 'quote'), $this->_parameters);

            // Replace the values in the SQL
            $sql = strtr($sql, $values);
        }

        return $sql;
    }

    /**
     * Execute the current query on the given database.
     *
     * @param   mixed    $db  Database instance or name of instance
     * @param   string   result object classname, TRUE for stdClass or FALSE for array
     * @param   array    result object constructor arguments
     * @return  object   Database_Result for SELECT queries
     * @return  mixed    the insert id for INSERT queries
     * @return  integer  number of affected rows for all other queries
     */
    public function execute($db = NULL, $as_object = NULL, $object_params = NULL)
    {
        if ( ! is_object($db)) {
            // Get the database instance
            $db = Database::instance($db);
        }

        if ($as_object === NULL) {
            $as_object = $this->_as_object;
        }

        if ($object_params === NULL) {
            $object_params = $this->_object_params;
        }

        // Compile the SQL query
        $sql = $this->compile($db);

        if ($this->_lifetime !== NULL AND $this->_type === Database::SELECT) {
            // Set the cache key based on the database instance name and SQL
            $cache_key = 'Database::query("'.$db.'", "'.$sql.'")';

            // Read the cache first to delete a possible hit with lifetime <= 0
            if (($result = Gdn::cache()->get($cache_key)) !== NULL AND ! $this->_force_execute) {
                // Return a cached result
                return new Database\Result\Cached($result, $sql, $as_object, $object_params);
            }
        }

        // Execute the query
        $result = $db->query($this->_type, $sql, $as_object, $object_params);

        if (isset($cache_key) AND $this->_lifetime > 0) {
            // Cache the result array
            Gdn::cache()->set($cache_key, $result->as_array(), $this->_lifetime);
        }

        return $result;
    }

}