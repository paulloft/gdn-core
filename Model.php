<?php
namespace Garden;

/**
 * Model base class
 *
 *
 * @author PaulLoft <info@paulloft.ru>
 * @copyright 2014 Paulloft
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 */
class Model {

    public $table;
    public $primaryKey = 'id';
    public $allowedFields = [];

    /**
     * @var int current user id
     */
    public $userID;

    /**
     * @var bool if true getWhere() returns data with object
     */
    public $resultObject = false;

    /**
     * @var Db\Database\Query\Builder\Where
     */
    protected $_query;
    protected $_select = ['*'];
    protected $_allowedFields = [];

    private $_insertFields = [];
    private $_updateFields = [];
    private $_insupdFields = [];
    private $_deleteFields = [];

    private $_b_table;

    protected $validation;

    public $fieldDateInserted = 'dateInserted';
    public $fieldDateUpdated = 'dateUpdated';
    public $fieldUserUpdated = 'userUpdated';
    public $fieldUserInserted = 'userInserted';

    public $DBinstance;

    private static $instances;

    /**
     * Returns the application singleton or null if the singleton has not been created yet.
     * @return $this
     */
    public static function instance($table = null) {
        $class_name = get_called_class();

        $instance = $class_name === self::class && $table ? $table : $class_name;

        if (!self::$instances[$instance]) {
            self::$instances[$instance] = new $class_name($table);
        }

        return self::$instances[$instance];
    }

    /**
     * Class constructor. Defines the related database table name.
     * @param string $table table name
     */
    public function __construct($table = null)
    {
        if ($table !== null) {
            $this->setTable($table);
        }

        if (Gdn::authLoaded()) {
            $user = Gdn::auth()->user;
            $this->userID = val('id', $user);
        }

        $this->setFields($this->allowedFields);
    }

    /**
     * List of columns to be selected for the next query
     * @param array $columns
     * @return $this
     */
    public function select(array $columns)
    {
        $this->_select = $columns;
        return $this;
    }

    /**
     * Set using table
     * @param string $table table name
     */
    public function setTable($table = null)
    {
        $this->table = $this->_b_table = $table;
    }

    public function switchTable($table = false)
    {
        if (!$this->_b_table) {
            $this->_b_table = $this->table;
        }
        $this->table = $table ?: $this->_b_table;
    }

    /**
     * Get the data from the table based on its primary key
     *
     * @param int $id Element ID
     * @return array|object
     */
    public function getID($id)
    {
        $query = DB::select_array($this->_select)->from($this->table)->where($this->primaryKey, '=', $id)->limit(1);

        return $query->execute($this->DBinstance, $this->resultObject)->current();
    }

    /**
     * Get a dataset for the table.
     *
     * @param array $order Array('order' => 'direction')
     * @param int $limit
     * @param int $offset
     * @return Db\Database\Result
     */
    public function get(array $order = [], $limit = 0, $offset = 0)
    {
        return $this->getWhere([], $order, $limit, $offset);
    }

    /**
     * Get a dataset for the table with a where filter.
     *
     * @param array $where Array('field' => 'value') .
     * @param array $order Array('order' => 'direction')
     * @param int $limit
     * @param int $offset
     * @return Db\Database\Result
     */
    public function getWhere(array $where = [], array $order = [], $limit = 0, $offset = 0)
    {
        $this->_query = DB::select_array($this->_select)->from($this->table);

        $this->_where($where);

        foreach ($order as $field => $direction) {
            $this->_query->order_by($field, $direction);
        }

        if ($limit) {
            $this->_query->limit($limit);
            $this->_query->offset($offset);
        }

        return $this->_query->execute($this->DBinstance, $this->resultObject);
    }

    /**
     * get count of rows from table with a where filter.
     *
     * @param array $where Array('field' => 'value') .
     * @return int
     */

    public function getCount(array $where = [])
    {
        $count = DB::expr("COUNT({$this->primaryKey})");
        $this->_query = DB::select($count, 'count')->from($this->table);

        $this->_where($where);

        return $this->_query->execute($this->DBinstance, $this->resultObject)->get('count');
    }

    /**
     * Sets the definition of the field for the table
     *
     * @param array $fields fields array
     */
    public function setFields(array $fields)
    {
        $this->_allowedFields[$this->table] = $fields;
    }

    /**
     * Return the definition of the field for the table
     *
     * @return array
     */
    public function getFields()
    {
        return val($this->table, $this->_allowedFields, []);
    }

    /**
     * Added to the datebase POST data
     *
     * @param array $data inserted fields
     * @return int Record ID
     */
    public function insert(array $data)
    {
        $data = $this->insertDefaultFields($data);
        $data = $this->fixPostData($data);
        $columns = array_keys($data);

        $query = DB::insert($this->table, $columns)
            ->values($data)
            ->execute($this->DBinstance);

        return val(0, $query, false);
    }

    /**
     * Update record by ID
     *
     * @param int $id Element ID
     * @param array $data POST data
     */
    public function update($id, array $data)
    {
        $data = $this->updateDefaultFields($data);
        $data = $this->fixPostData($data);

        DB::update($this->table)
            ->set($data)
            ->where($this->primaryKey, '=', $id)
            ->execute($this->DBinstance);
        return true;
    }

    /**
     * Update record by filter
     *
     * @param array $where Array('field' => 'value')
     * @param array $data POST data
     */
    public function updateWhere(array $where, array $data)
    {
        $data = $this->updateDefaultFields($data);
        $data = $this->fixPostData($data);

        $this->_query = DB::update($this->table)->set($data);

        $this->_where($where);

        $this->_query->execute($this->DBinstance);
    }

    /**
     * Insert or update record
     * @param int $id update fields
     * @return int id record
     */
    public function insertOrUpdate($id, array $fields)
    {
        $result = $this->getID($id);

        if ($result) {
            $id = val($this->primaryKey, $result);
            $this->update($id, $fields);
        } else {
            $fields['id'] = $id;
            $id = $this->insert($fields);
        }

        return $id;
    }


    /**
     * Removes fields are not part of the table
     *
     * @param array $post
     * @return array
     */
    public function fixPostData(array $post)
    {
        $fields = $this->getFields() ?: $this->getTableFields();
        $post = $this->checkArray($post, $fields);
        $post = $this->setNullValues($post);

        return $post;
    }

    /**
     * Returne table columns
     *
     * @return array
     */
    public function getTableFields()
    {
        $cacheKey = 'table_columns_' . $this->table;
        $result = Gdn::cache()->get($cacheKey);

        if (!$result) {
            $structure = $this->getStructure();

            $result = [];
            foreach ($structure as $col) {
                $result[] = val('name', $col);
            }

            Gdn::cache()->set($cacheKey, $result);
        }

        return $result;
    }

    /**
     * save post data into table
     *
     * @param array $post POST data
     * @param int $id record ID
     * @return int inserted or updated record ID
     */
    public function save(array $post, $id = false)
    {
        if ($id) {
            $this->update($id, $post);
        } else {
            $id = $this->insert($post);
        }

        return $id;
    }

    /**
     * removes records of the $where condition
     * @param array $where
     */
    public function delete(array $where)
    {
        $this->_query = DB::delete($this->table);
        $this->_where($where);
        $this->_query->execute($this->DBinstance);
    }

    /**
     * Delete record by primary key
     *
     * @param $id
     */
    public function deleteID($id)
    {
        $this->delete([$this->primaryKey => $id]);
    }


    /**
     * enqueues addition data
     * @param array $fields array fields
     */
    public function insert_queue(array $fields = [])
    {
        $fields = $this->insertDefaultFields($fields);
        $fields = $this->fixPostData($fields);
        if (!empty($fields)) {
            $this->_insertFields[] = $fields;
        }
    }

    /**
     * enqueues update data
     * @param array $where
     * @param array $fields
     */
    public function update_queue(array $where = [], array $fields)
    {
        $fields = $this->updateDefaultFields($fields);
        $fields = $this->fixPostData($fields);
        if (!empty($fields)) {
            $this->_updateFields[] = ['fields' => $fields, 'where' => $where];
        }
    }

    /**
     * enqueues insert or update data
     * @param array $fields
     * @param array $where
     */
    public function insupd_queue(array $fields)
    {
        $fields = $this->updateDefaultFields($fields);
        $fields = $this->fixPostData($fields);
        if (!empty($fields)) {
            $this->_insupdFields[] = $fields;
        }
    }

    /**
     * enqueues delete data
     * @param array $where
     */
    public function delete_queue(array $where)
    {
        if (!empty($where)) {
            $this->_deleteFields[] = $where;
        }
    }

    /**
     * start all pending operations
     *
     * @param string $table
     */
    public function start_queue($table = false)
    {
        $sql = '';
        $table = $table ?: $this->table;

        foreach ($this->_updateFields as $update) {
            $fields = val('fields', $update);
            $where = val('where', $update);
            $this->_query = DB::update($table)->set($fields);
            $this->_where($where);
            $sql .= $this->_query->compile() . ";\n";
        }

        foreach ($this->_insertFields as $fields) {
            $columns = array_keys($fields);
            $sql .= DB::insert($table, $columns)->values($fields)->compile() . ";\n";
        }

        foreach ($this->_insupdFields as $fields) {
            $columns = array_keys($fields);
            $insert = DB::insert($table, $columns)->values($fields)->compile();
            $update = str_replace('  SET', '', DB::update()->set($fields)->compile());

            $sql .= "$insert ON DUPLICATE KEY $update;\n";
        }

        foreach ($this->_deleteFields as $delete) {
            $this->_query = DB::delete($table);
            $this->_where($delete);
            $sql .= $this->_query->compile() . ";\n";
        }

        if (empty($sql)) {
            return;
        }

        DB::query(null, $sql)->execute($this->DBinstance);

        $this->_updateFields = $this->_insertFields = $this->_deleteFields = [];
    }

    /**
     * Checks for a condition for uniqueness
     *
     * @param $where
     * @return bool
     */
    public function unique(array $where)
    {
        $query = DB::select(['COUNT("*")', 'total_count'])->from($this->table);

        foreach ($where as $field => $value) {
            $query->where($field, '=', $value);
        }

        return (bool)$query->execute($this->DBinstance, $this->resultObject)->get('total_count');
    }

    /**
     * Converting date fields to sql format
     * @param array $post
     * @param array $fields
     * @return array converted post data
     */
    public function convertPostDate($post, array $fields)
    {
        $fields = (array)$fields;

        foreach ($fields as $field) {
            $value = val($field, $post);

            if ($value !== false) {
                $new = date_convert($value, 'sql');
                $post[$field] = $new ?: null;
            }
        }

        return $post;
    }

    /**
     * @return Validation
     */
    public function validation()
    {
        if (!$this->validation) {
            $this->validation = new Validation($this);
        }

        return $this->validation;
    }

    /**
     * Get table columns
     * @return array
     */
    public function getStructure()
    {
        $cacheKey = 'table_structure_' . $this->table;
        $result = Gdn::cache()->get($cacheKey);

        if (!$result) {
            $result = Gdn::database()->list_columns($this->table);
            Gdn::cache()->set($cacheKey, $result);
        }

        return $result;
    }


    protected static $nullTypes = ['int', 'tinyint', 'bigint', 'float', 'datetime', 'date', 'time'];
    protected function setNullValues(array $post)
    {
        $structure = $this->getStructure();

        foreach ($post as $field => $value) {
            $type = valr($field.'.dataType', $structure);
            $default = valr($field.'.default', $structure);
            $allowNull = valr($field.'.allowNull', $structure);
            if ($allowNull && $value === '' && in_array($type, self::$nullTypes)) {
                $post[$field] = $default ?: null;
            }
        }

        return $post;
    }

    protected function insertDefaultFields(array $data)
    {
        if (!val($this->fieldDateInserted, $data)) {
            $data[$this->fieldDateInserted] = DB::expr('now()');
        }

        if (!val($this->fieldUserInserted, $data)) {
            $data[$this->fieldUserInserted] = $this->userID;
        }

        return $data;
    }

    protected function updateDefaultFields(array $data)
    {
        if (!val($this->fieldDateUpdated, $data)) {
            $data[$this->fieldDateUpdated] = DB::expr('now()');
        }

        if (!val($this->fieldUserUpdated, $data)) {
            $data[$this->fieldUserUpdated] = $this->userID;
        }

        return $data;
    }

    protected function _where($field, $value = null)
    {
        if (!is_array($field)) {
            $field = [$field => $value];
        }

        foreach ($field as $subField => $subValue) {
            if (is_array($subValue) && empty($subValue)) {
                continue;
            }

            $expr = $this->conditionExpr($subField, $subValue);
            $this->_query->where($expr[0], $expr[1], $expr[2]);
        }
        return $this;
    }

    /**
     * Clears the $array of extra
     *
     * @param array $array
     * @param array $fields POST data
     * @return array fixed array
     */
    protected function checkArray(array $array, $fields)
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (in_array($key, $fields)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * parses the field in the form array [field, operator, value] suitable for sql where() function
     * @param $field
     * @param $value
     * @return array [field, operator, value]
     */
    public function conditionExpr($field, $value)
    {
        // Try and split an operator out of $Field.
        $fieldOpRegex = "/(?:\s*(=|!=|<>|>|<|>=|<=)\s*$)|\s+(like|not\s+like)\s*$|\s+(?:(is)(\s+null)?|(is\s+not)(\s+null)?|(not\s+in))\s*$/i";
        $split = preg_split($fieldOpRegex, $field, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        if (count($split) > 1) {
            list($field, $op) = $split;

            if (count($split) > 2) {
                $value = null;
            }
        } else {
            $op = '=';
        }

        if ($op == '=' && $value === null) {
            // this is a special case where the value sql is checking for an is null operation.
            $op = 'is';
            $value = null;
        }

        if ($op != 'not in' && is_array($value)) {
            $op = 'in';
        }

        return [$field, $op, $value];
    }

}