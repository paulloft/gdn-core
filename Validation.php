<?php

namespace Garden;

class Validation
{
    /**
     * @var Model
     */
    protected $model;
    protected $data;

    protected $rule = [];
    protected $errors = [];

    public $validated = false;

    public function __construct(Model $model = null)
    {
        $this->model = $model;
    }

    /**
     * Return table columns
     * @return array|bool
     */
    public function getStructure()
    {
        return $this->model ? $this->model->getStructure() : false;
    }

    /**
     * Return validation errors in an array
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * set data from validation
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * get data from validation
     * @param array $data
     */
    public function getData($key = false)
    {
        return $key ? val($key, $this->data) : $this->data;
    }

    /**
     * set user error for $field
     * @param string $field
     * @param string $error
     */
    public function addError($field, $error)
    {
        $this->errors[$field][] = $error;
    }

    /**
     * clear all errors
     */
    public function clearErrors()
    {
        $this->errors = [];
    }

    /**
     * set custom validation errors of array
     * @param array $errors
     */
    public function addValidationResult(array $errors)
    {
        foreach ($errors as $field => $error) {
            if (\is_array($error)) {
                foreach ($error as $inerror) {
                    $this->addError($field, $inerror);
                }
            } else {
                $this->addError($field, $error);
            }
        }
    }

    /**
     * Add validation rule for field
     * @param string $field
     * @param string|array $rule
     * @param mixed $params
     * @param string $message
     * @return $this
     */
    public function rule($field, $rule, $params = null, $message = false)
    {
        $this->rule[$field][] = [
            'type' => $rule,
            'params' => $params,
            'message' => $message
        ];

        return $this;
    }

    /**
     * validate data
     * @param array $data
     * @return bool
     */
    public function validate($data = false)
    {
        if (!$this->validated) {
            if ($data) {
                $this->setData($data);
            }

            if (!\is_array($this->data)) {
                return false;
            }

            if ($this->model) {
                $this->checkStructure();
            }

            $this->checkRules();
            $this->validated = true;
        }

        return empty($this->errors);
    }

    /**
     * check on an empty value
     * @param $value
     * @return bool
     */
    protected function isEmpty($value)
    {
        return $value === null || $value === '' || $value === false;
    }

    protected function checkStructure()
    {
        $structure = $this->getStructure();

        foreach ($structure as $field => $opt) {
            if (!array_key_exists($field, $this->data)) {
                continue;
            }

            $value = val($field, $this->data);
            if (\is_array($value)) {
                $this->errors[$field][] = t('validate_wrong_type_data');
                continue;
            }

            $value = trim($value);
            $length = (int)$opt->length;

            if (!empty($length)) {
                $len = mb_strlen($value);
                if ($len > $length) {
                    $this->errors[$field][] = t_sprintf('validate_max_length', $length);
                    continue;
                }
            }

            if (!$opt->autoIncrement && $opt->default === NULL && !$opt->allowNull && $this->isEmpty($value)) {
                $this->errors[$field][] = t('validate_not_empty');
                continue;
            }

            switch ($opt->dataType) {
                case 'int':
                case 'bigint':
                    if (!$this->isEmpty($value) && (filter_var($value, FILTER_VALIDATE_INT) === false)) {
                        $this->errors[$field][] = t('validate_int');
                        continue 2;
                    }
                    break;

                case 'double':
                    if (!\is_float($value) && !$this->isEmpty($value)) {
                        $this->errors[$field][] = t('validate_double');
                        continue 2;
                    }
                    break;

                case 'float':
                    if (!is_numeric($value) && !$this->isEmpty($value)) {
                        $this->errors[$field][] = t('validate_float');
                        continue 2;
                    }
                    break;

                case 'date':
                case 'datetime':
                case 'timestamp':
                    if (!$this->isEmpty($value) && !validate_sql_date($value)) {
                        $this->errors[$field][] = t('validate_sql_date');
                        continue 2;
                    }
                    break;

                default:
                    continue 2;
                    break;

            }
        }
    }

    protected function checkRules()
    {
        if (empty($this->rule)) {
            return true;
        }

        foreach ($this->rule as $field => $rules) {
            foreach ((array)$rules as $opt) {
                $type    = val('type', $opt);
                $message = val('message', $opt);
                $params  = val('params', $opt);

                $value = val($field, $this->data);
                $value = \is_array($value) ? array_map('trim', $value) : trim($value);

                if (\is_array($type)) {
                    $ruleFunc = [$type[0], $type[1]];
                    $type = $type[1];
                } elseif ($type instanceof \Closure) {
                    $ruleFunc = $type;
                }else {
                    $ruleFunc = 'validate_' . $type;
                }

                if ($type == 'unique') {
                    $params = [':id', $field, $this->model];
                }

                $params = $this->replaceParams($params, $this->data);

                if (($type == 'required' || $type === 'not_empty' || !$this->isEmpty($value)) && !$ruleFunc($value, $params)) {
                    if (\is_array($message)) {
                        $field = val(0, $message);
                        $message = val(1, $message);
                        $error = [t($field), vsprintf(t($message ?: 'validate_' . $type), $params)];
                    } else {
                        $error = vsprintf(t($message ?: 'validate_' . $type), $params);
                    }
                    $this->errors[$field][] = $error;
                }
            }
        }
    }

    protected function replaceParams($params, $data)
    {
        if (!$params) {
            return false;
        }

        if (\is_array($params)) {
            foreach ($params as $k => $param) {
                $params[$k] = $this->replaceParams($param, $data);
            }
            return $params;
        }

        if (str_begins($params, ':')) {
            $key = ltrim_substr($params, ':');
            return val($key, $data);
        }

        return $params;
    }
}