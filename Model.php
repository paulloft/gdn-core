<?php

namespace Garden;

use Garden\Db\DB;

/**
 * Model base class
 *
 * @author PaulLoft <info@paulloft.ru>
 * @copyright 2014 Paulloft
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 */
class Model {

    protected $table;
    protected $primaryKey = 'id';
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
     * @var \Garden\Db\Database\Query\Builder\Select
     */
    protected $_query;
    protected $_select = ['*'];
    protected $_allowedFields;

    private $_insertFields = [];
    private $_updateFields = [];
    private $_insupdFields = [];
    private $_deleteFields = [];

    /**
     * @var Validation
     */
    protected $_validation;

    public $fieldCreatedDate = 'created_at';
    public $fieldUpdatedDate = 'updated_at';
    public $fieldCreatedUser = 'created_by';
    public $fieldUpdatedUser = 'updated_by';

    public $DBinstance;
    public $triggers = true;

    private static $instances;

    /**
     * Returns the application singleton or null if the singleton has not been created yet.
     * @return $this
     */
    public static function instance($table = null, $primaryKey = null)
    {
        $class_name = get_called_class();

        $instance = $class_name === self::class && $table ? $table : $class_name;

        if (!self::$instances[$instance]) {
            self::$instances[$instance] = new $class_name($table, $primaryKey);
        }

        return self::$instances[$instance];
    }

    /**
     * Class constructor. Defines the related database table name.
     * @param string $table table name
     */
    public function __construct($table = null, $primaryKey = null)
    {
        if ($table !== null) {
            $this->setTable($table);
        }

        if ($primaryKey !== null) {
            $this->setPrimaryKey($primaryKey);
        }

        $this->userID = Request::current()->getEnvKey('USER_ID');
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

    public function getTable()
    {
        return $this->table;
    }

    /**
     * Set using table
     * @param string $table table name
     */
    public function setTable($table = null)
    {
        $this->table = $table;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * Get the data from the table based on its primary key
     *
     * @param int $id Element ID
     * @return array|\stdClass
     */
    public function getID($id)
    {
        $query = DB::selectArray($this->_select)
            ->from($this->table)
            ->where($this->primaryKey, '=', $id)
            ->limit(1);

        return $query->execute($this->DBinstance, $this->resultObject)->current();
    }

    /**
     * Get a dataset for the table.
     *
     * @param array $order Array('order' => 'direction')
     * @param int $limit
     * @param int $offset
     * @return \Garden\Db\Database\Result
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
     * @return \Garden\Db\Database\Result
     */
    public function getWhere(array $where = [], array $order = [], $limit = 0, $offset = 0)
    {
        $this->_query = DB::selectArray($this->_select)->from($this->table);

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
        $this->_allowedFields = $fields;
    }

    /**
     * Return the definition of the field for the table
     *
     * @return array
     */
    public function getAllowedFields()
    {
        return $this->_allowedFields ?? $this->getTableFields();
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

        if (empty($data)) {
            return false;
        }

        $columns = array_keys($data);

        $query = DB::insert($this->table, $columns)
            ->values($data)
            ->execute($this->DBinstance);

        list($id) = $query;

        if ($this->triggers) {
            Event::fire('gdn_model_insert', $this, $id, $data);
        }

        return $id;
    }

    /**
     * Update record by ID
     *
     * @param int $id Element ID
     * @param array $data POST data
     * @return int number of rows affected
     */
    public function update($id, array $data)
    {
        $data = $this->updateDefaultFields($data);
        $data = $this->fixPostData($data);

        if (empty($data)) {
            return 0;
        }

        $rows = DB::update($this->table)
            ->set($data)
            ->where($this->primaryKey, '=', $id)
            ->execute($this->DBinstance);

        if ($this->triggers) {
            Event::fire('gdn_model_update', $this, $id, $data);
        }

        return (int)$rows;
    }

    /**
     * Update record by filter
     *
     * @param array $where Array('field' => 'value')
     * @param array $fields POST data
     * @return int number of rows affected
     */
    public function updateWhere(array $fields, array $where)
    {
        $fields = $this->updateDefaultFields($fields);
        $fields = $this->fixPostData($fields);

        $this->_query = DB::update($this->table)->set($fields);

        $this->_where($where);

        $rows = $this->_query->execute($this->DBinstance);

        if ($this->triggers) {
            Event::fire('gdn_model_updateWhere', $this, $where, $fields);
        }

        return (int)$rows;
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
        $allowedFields = $this->getAllowedFields();
        $post = array_intersect_key($post, array_flip($allowedFields));
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
            $result = array_column($structure, 'name');

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
     * @return int number of rows affected
     */
    public function delete(array $where)
    {
        if ($this->triggers) {
            Event::fire('gdn_model_deleteWhere', $this, $where);
        }

        $this->_query = DB::delete($this->table);
        $this->_where($where);
        $rows = $this->_query->execute($this->DBinstance);

        return (int)$rows;
    }

    /**
     * Delete record by primary key
     *
     * @param int|string $id
     * @return int number of rows affected
     */
    public function deleteID($id)
    {
        return (int)$this->delete([$this->primaryKey => $id]);
    }


    /**
     * enqueues addition data
     * @param array $fields array fields
     */
    public function queueInsert(array $fields = [])
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
    public function queueUpdate(array $fields, array $where = [])
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
     */
    public function queueInsertOrUpdate(array $fields)
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
    public function queueDelete(array $where)
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
    public function queueStart($table = null)
    {
        $sql = '';
        $table = $table ?? $this->table;

        foreach ($this->_updateFields as $update) {
            $this->_query = DB::update($table)->set($update['fields']);
            $this->_where($update['where']);
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

        $this->_updateFields = [];
        $this->_insertFields = [];
        $this->_deleteFields = [];
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

    protected function initValidation()
    {
        return new Validation($this);
    }

    /**
     * @return Validation
     */
    public function validation()
    {
        if (!$this->_validation) {
            $this->_validation = $this->initValidation();
        }

        return $this->_validation;
    }

    public function initFormValidation(Form $form)
    {
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
            $result = Gdn::database()->listColumns($this->table);
            Gdn::cache()->set($cacheKey, $result);
        }

        return $result;
    }


    protected static $nullTypes = ['int', 'tinyint', 'bigint', 'float', 'datetime', 'date', 'time'];

    protected function setNullValues(array $post)
    {
        $structure = $this->getStructure();

        foreach ($post as $field => $value) {
            $type = valr($field . '.dataType', $structure);
            $default = valr($field . '.default', $structure);
            $allowNull = valr($field . '.allowNull', $structure);
            if ($allowNull && $value === '' && \in_array($type, self::$nullTypes, true)) {
                $post[$field] = $default ?: null;
            }
        }

        return $post;
    }

    protected function insertDefaultFields(array $data)
    {
        if (!isset($data[$this->fieldCreatedDate])) {
            $data[$this->fieldCreatedDate] = DB::expr('now()');
        }

        if (!isset($data[$this->fieldCreatedUser])) {
            $data[$this->fieldCreatedUser] = $this->userID;
        }

        return $data;
    }

    protected function updateDefaultFields(array $data)
    {
        if (!isset($data[$this->fieldUpdatedDate])) {
            $data[$this->fieldUpdatedDate] = DB::expr('now()');
        }

        if (!isset($data[$this->fieldUpdatedUser])) {
            $data[$this->fieldUpdatedUser] = $this->userID;
        }

        return $data;
    }

    protected function _where($field, $value = null, $alias = null)
    {
        if (!\is_array($field)) {
            $field = [$field => $value];
        }

        foreach ($field as $subField => $subValue) {
            if (\is_array($subValue) && empty($subValue)) {
                continue;
            }

            if ($alias && !strpos($subField, '.')) {
                $subField = $alias . '.' . $subField;
            }

            $expr = $this->conditionExpr($subField, $subValue);
            $this->_query->where($expr[0], $expr[1], $expr[2]);
        }
        return $this;
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

        if (\count($split) > 1) {
            list($field, $op) = $split;

            if (\count($split) > 2) {
                $value = null;
            }
        } else {
            $op = '=';
        }

        if ($op === '=' && $value === null) {
            // this is a special case where the value sql is checking for an is null operation.
            $op = 'is';
            $value = null;
        }

        if ($op !== 'not in' && \is_array($value)) {
            $op = 'in';
        }

        return [$field, $op, $value];
    }
}