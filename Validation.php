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

    public function __construct($model = false)
    {
        $this->model = $model;
    }

    /**
     * Return table columns
     * @return array
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
            if (is_array($error)) {
                foreach ($error as $inerror) {
                    $this->addError($field, $inerror);
                }
            } else {
                $this->addError($field, $errors);
            }
        }
    }

    /**
     * Add validation rule for field
     * @param string $field
     * @param string $rule
     * @param mixed $params
     * @param string $message
     * @return $this
     */
    public function rule($field, $rule, $params = null, $message = false)
    {
        $this->rule[$field][] = array(
            'type' => $rule,
            'params' => $params,
            'message' => $message
        );

        return $this;
    }

    /**
     * validate data
     * @param array $data
     * @return bool|void
     */
    public function validate($data = false)
    {
        if (!$this->validated) {
            if ($data) {
                $this->setData($data);
            }

            if (!is_array($this->data)) {
                return;
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
        return $value === null || $value === '';
    }

    protected function checkStructure()
    {
        $structure = $this->getStructure();

        foreach ($structure as $field => $opt) {
            if(!array_key_exists($field, $this->data)) continue;

            $value = val($field, $this->data);
            if (is_array($value)) {
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
                    if (!$this->isEmpty($value) && !ctype_digit($value)) {
                        $this->errors[$field][] = t('validate_int');
                        continue 2;
                    }
                    break;

                case 'double':
                    if (!$this->isEmpty($value) && !is_float($value)) {
                        $this->errors[$field][] = t('validate_double');
                        continue 2;
                    }
                    break;

                case 'float':
                    if (!$this->isEmpty($value) && !is_numeric($value)) {
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
        if (empty($this->rule)) return true;

        foreach ($this->rule as $field => $rules) {
            foreach ($rules as $opt) {
                $type    = val('type', $opt);
                $message = val('message', $opt);
                $params  = val('params', $opt);

                $value = val($field, $this->data);
                $value = trim($value);

                if(is_array($type)) {
                    $ruleFunc = array($type[0], $type[1]);
                    $type = $type[1];
                } else {
                    $ruleFunc =  'validate_' . $type;
                }

                if(!$this->isEmpty($value) || $type === 'not_empty') {
                    if (!call_user_func($ruleFunc, $value, $params)) {
                        if (is_array($message)) {
                            $field = val(0, $message);
                            $message = val(1, $message);
                            $error = array(t($field), vsprintf(t($message ?: 'validate_'.$type), $params));
                        } else {
                            $error = vsprintf(t($message ?: 'validate_'.$type), $params);
                        }
                        $this->errors[$field][] = $error;
                    }
                }
            }
        }
    }
}