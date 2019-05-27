<?php

namespace Garden;

use Exception;
use Garden\Helpers\Arr;
use Garden\Helpers\Date;
use Garden\Helpers\Object;
use Garden\Helpers\Text;
use stdClass;
use function count;
use function in_array;
use function is_array;
use function is_object;

class Form {
    /**
     * @var string form method
     */
    public $method = 'post';

    /**
     * @var string default css class for input
     */
    public $inputClass = 'form-control';

    /**
     * @var bool if true, ignored all fields that was not present when the form was initialized
     */
    public $protection = false;

    /**
     * @var Model
     */
    protected $model;
    protected $data = [];
    protected $formValues;
    /**
     * @var Validation
     */
    private $_validation;
    private $_valid;

    private $hiddenInputs = [];
    private $inputs;
    private $errors = [];

    const KEY_LIFE_TIME = 43200; //12 hours

    /**
     * Form constructor.
     * @param string $tablename table name for form model
     */
    public function __construct($tablename = false)
    {
        if ($tablename) {
            $model = new Model($tablename);
            $this->setModel($model);
        }
    }

    /**
     * set form model
     * @param Model $model table model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * return model primary key
     * @return bool|mixed
     */
    public function primaryKey()
    {
        return $this->model ? $this->model->getPrimaryKey() : false;
    }

    /**
     * set form data
     * @param array|stdClass|Db\Database\Result $data
     */
    public function setData($data)
    {
        if (is_object($data)) {
            if ($data instanceof Db\Database\Result) {
                $this->data = $data->current();
            } elseif ($data instanceof stdClass) {
                $this->data = (array)$data;
            }
        } elseif (is_array($data)) {
            $this->data = $data;
        }

        $primaryKey = $this->primaryKey();

        if ($primaryKey) {
            $id = $this->data[$primaryKey] ?? null;
            $this->addHidden($primaryKey, $id, true);
        }
    }

    /**
     * gets initial field form data
     * @param string $field
     * @param mixed $default
     */
    public function getDataField($field, $default = null)
    {
        return $this->data[$field] ?? $default;
    }

    /**
     * gets initial form data
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }


    /**
     * sets form value if field not exists in post (see force param)
     * @param string $field
     * @param string $value
     */
    public function setFormValue($field, $value)
    {
        $this->getFormValues();
        $this->formValues[$field] = trim($value);
    }

    /**
     * return form validation class
     * @return Validation
     */
    public function validation(): Validation
    {
        if (!$this->_validation) {
            if ($this->model && $this->model instanceof Model) {
                $this->_validation = $this->model->validation($this);
                $this->model->initFormValidation($this);
            } else {
                $this->_validation = new Validation($this->model);
            }
        }

        return $this->_validation;
    }

    /**
     * Check the form submission
     * @return bool
     */
    public function submitted(): bool
    {
        $method = strtoupper($this->method);

        if ($method === Request::METHOD_GET) {
            return (bool)Request::current()->getQuery('form-submitted', 0);
        }

        return Request::current()->getMethod() === $method;
    }

    /**
     * return form field value
     * @param string $name field name
     * @param string $default default value if field not exists
     * @return mixed
     */
    public function getFormValue($name, $default = '')
    {
        return Arr::path($this->getFormValues(), $name, $default);
    }

    private $magicQuotes;

    /**
     * return form values
     * @param bool $force
     * @return array|mixed
     */
    public function getFormValues($force = false)
    {
        if ($force || !is_array($this->formValues)) {
            $this->magicQuotes = get_magic_quotes_gpc();

            $this->formValues = [];
            $var = '_' . strtoupper($this->method);
            $formData = Arr::get($GLOBALS, $var, []);

            $this->formValues = $this->clearFormData($formData, $this->protection);
        }

        return $this->formValues;
    }

    /**
     * if form submitted return post field value or return form data
     * @param string $name field name
     * @param string $default default value if field not exists
     * @return mixed
     */
    public function getValue($name, $default = '')
    {
        if ($this->submitted()) {
            return $this->getFormValue($name, $default);
        }

        return Arr::path($this->data, $name, $default);
    }

    /**
     * return form values or if form submitted return post data
     * @return array|mixed
     */
    public function getValues()
    {
        if ($this->submitted()) {
            return $this->getFormValues();
        }

        return $this->data;
    }

    /**
     * unset form field value
     * @param string $name field value
     */
    public function unsetFormValue($name)
    {
        $this->getFormValues();
        unset($this->formValues[$name]);
    }

    /**
     * check data to valid
     * @return bool
     */
    public function valid(): bool
    {
        if ($this->_valid === null) {
            $data = $this->getFormValues();
            $data = $this->fixPostData($data);

            if ($this->protection && !$this->getSecureKey()) {
                $this->addError(Translate::get('Secure key is empty'));
            } elseif ($this->protection && empty($this->getSecureFields())) {
                $this->addError(Translate::get('Form session timeout'));
            }

            $this->_valid = $this->validation()->validate($data) && !count($this->errors);
        }

        return $this->_valid;
    }

    /**
     * Add custom error in validiation results
     * @param string $error
     */
    public function addError($error)
    {
        $this->errors[] = $error;
    }

    /**
     * get form errors
     * @param bool $text if true return errors in text else in html
     * @return bool|string
     */
    public function errors($text = false)
    {
        $errors = $this->validation()->errors();
        if (empty($errors) && empty($this->errors)) {
            return false;
        }

        $html = [];
        foreach ($errors as $field => $fieldErrors) {
            foreach ((array)$fieldErrors as $error) {
                if (is_array($error)) {
                    list($errField, $error) = $error;
                } else {
                    $errField = Translate::get(($this->model ? $this->model->getTable() : 'form') . '.' . $field, $field);
                }

                if ($text) {
                    $html[] = $errField . ' ' . $error;
                } else {
                    $html[] = Translate::getVsprintf('form_html_error', [$errField, $error], '%s: %s');
                }
            }
        }

        foreach ($this->errors as $error) {
            $html[] = $error;
        }

        if ($text) {
            return implode('; ', $html);
        }

        return Translate::getVsprintf('form_html_error_wrapper', [implode('<br>', $html)], '%s');
    }

    /**
     * save form data
     * @return mixed id record or false
     */
    public function save()
    {
        $result = false;

        if ($this->valid()) {
            $post = $this->getFormValues();

            if ($this->model && $this->model instanceof Model) {
                $id = Arr::extract($post, $this->model->getPrimaryKey());
                $post = $this->fixPostData($post);
                try {
                    $result = $this->model->save($post, $id);
                } catch (Exception $e) {
                    if (Config::get('main.debug', true)) {
                        $this->addError(Translate::getSprintf('form_save_error_debug', $e->getMessage()));
                    } else {
                        $this->addError(Translate::get('form_save_error'));
                    }

                    $result = false;
                }
            } else {
                $result = $post;
            }
        }

        return $result;
    }

    /**
     * generate form secure key from your $data
     * @param array $post
     * @return string md5 hash
     */
    public function generateSecureKey($data): string
    {
        $keys = array_keys($data);
        $key = md5(implode(';', $keys) . Config::get('main.hashsalt'));

        Gdn::cache()->set("form_$key", $keys, self::KEY_LIFE_TIME);

        return $key;
    }

    /**
     * return posted form secure key
     * @return string|null
     */
    public function getSecureKey()
    {
        $var = '_' . strtoupper($this->method);
        $formData = Arr::get($GLOBALS, $var, []);

        return $formData['secureKey'] ?? null;
    }

    /**
     * Gets initialized form fields
     * @param string $secureKey
     * @return array
     */
    public function getSecureFields($secureKey = null): array
    {
        if ($secureKey === null) {
            $secureKey = $this->getSecureKey();
        }

        if (!$secureKey) {
            return [];
        }

        return Gdn::cache()->get("form_$secureKey", []);
    }

    /**
     * Reset all form data
     */
    public function reset()
    {
        $this->errors = [];
        $this->_valid = null;
        $this->_validation = null;
        $this->formValues = null;
    }

    /**
     * html tag open form
     * @param array $attributes form attributes
     * @return string
     */
    public function open(array $attributes = []): string
    {
        $return = '<form ';
        $currentPath = Request::current()->getPath();
        Arr::touch($attributes, 'action', $currentPath);
        Arr::touch($attributes, 'method', $this->method);
        $this->method = $attributes['method'];

        $return .= $this->attrToString($attributes);

        if (strtoupper($this->method) === Request::METHOD_GET) {
            $this->addHidden('form-submitted', 1);
        }

        $return .= '>';
        return $return;
    }

    /**
     * html tag close form
     * @return string
     */
    public function close(): string
    {
        $return = '';
        foreach ($this->hiddenInputs as $name => $value) {
            $return .= $this->input($name, 'hidden', ['value' => $value]);
        }

        if ($this->protection) {
            $return .= $this->input('secureKey', 'hidden', ['value' => $this->generateSecureKey($this->inputs)]);
        }
        $this->inputs = [];

        $return .= '</form>';

        return $return;
    }

    /**
     * html tag input
     * @param string $name field name
     * @param string $type text|radio|checkbox|hidden|...
     * @param array $attributes input attributes
     * @return string
     */
    public function input($name, $type = 'text', array $attributes = []): string
    {
        if ($type !== 'radio' && $type !== 'checkbox' && $type !== 'hidden') {
            Arr::touch($attributes, 'class', $this->inputClass);
        }

        $inputValue = $attributes['value'] ?? null;
        $correctName = $this->correctName($name);

        $attributes['name'] = $name;
        $attributes['type'] = $type;

        if ($type === 'radio' || $type === 'checkbox') {
            $value = $this->getValue($correctName);
            $checked = is_array($value) ? in_array($inputValue, $value) : (string)$inputValue === (string)$value;
            if ($inputValue !== null && $checked) {
                Arr::touch($attributes, 'checked', 'checked');
            }
        } else {
            $attributes['value'] = $this->_value($correctName, $inputValue);
        }

        $this->addInput($correctName);

        return '<input ' . $this->attrToString($attributes) . ' />';
    }

    /**
     * html tag textarea
     * @param string $name field name
     * @param array $attributes textarea attributes
     * @return string
     */
    public function textarea($name, array $attributes = []): string
    {
        Arr::touch($attributes, 'class', $this->inputClass);
        Arr::touch($attributes, 'rows', '5');

        $attributes['name'] = $name;
        $value = $attributes['value'] ?? null;
        unset($attributes['type'], $attributes['value']);

        $correctName = $this->correctName($name);

        $this->addInput($correctName);

        $html = '<textarea ' . $this->attrToString($attributes) . '>';
        $html .= $this->_value($correctName, $value);
        $html .= '</textarea>';
        return $html;
    }

    /**
     * html tag input[type="checkbox"]
     * @param string $name field name
     * @param array $attributes checkbox attributes
     * @return string
     */
    public function checkbox($name, array $attributes = []): string
    {
        Arr::touch($attributes, 'value', 1);
        $defaultValue = Arr::extract($attributes, 'defaultValue');

        $html = '<input type="hidden" name="' . $name . '" value="' . $defaultValue . '" />';
        $html .= $this->input($name, 'checkbox', $attributes);

        return $html;
    }

    /**
     * html tag input[type="radio"]
     * @param string $name field name
     * @param array $attributes radio attributes
     * @return string
     */
    public function radio($name, array $attributes = []): string
    {
        return $this->input($name, 'radio', $attributes);
    }

    /**
     * html tag select
     * @param string $name
     * @param array $options
     * @param array $attributes
     * @return string
     */
    public function select($name, array $options = [], array $attributes = []): string
    {
        Arr::touch($attributes, 'class', $this->inputClass);
        $attributes['name'] = $name;

        $defaultName = Arr::extract($attributes, 'default_name');
        $defaultValue = Arr::extract($attributes, 'default_value');
        $keyValue = Arr::extract($attributes, 'key_value');
        $keyName = Arr::extract($attributes, 'key_name');

        $html = '<select ' . $this->attrToString($attributes) . '>';

        if ($defaultName) {
            $html .= '<option value="' . $defaultValue . '">' . $defaultName . '</option>';
        }

        $correctName = $this->correctName($name);
        $this->addInput($correctName);
        $fieldValue = $this->getValue($correctName);

        foreach ($options as $key => $option) {
            $optionValue = $keyName && $keyValue ? $option[$keyValue] ?? '' : $key;
            $optionName = $keyName && $keyValue ? $option[$keyName] ?? '' : $option;

            $selected = is_array($fieldValue) ? in_array($optionValue, $fieldValue) : (string)$optionValue === (string)$fieldValue;

            $html .= '<option value="' . $optionValue . '" ' . ($selected ? 'selected' : '') . '>' . $optionName . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * add hidden field to form
     * @param string $name field name
     * @param mixed $value
     * @param bool $forceValue if true value will not changed after form submitted
     */
    public function addHidden($name, $value = null, $forceValue = false)
    {
        if ($forceValue === false && $this->submitted()) {
            $value = $this->getFormValue($name, $value);
        }

        if ($value !== null) {
            $this->data[$name] = $value;
        }

        $this->hiddenInputs[$name] = $value;
    }

    protected function _value($name, $value = null): string
    {
        return Text::safe($value ?? $this->getValue($name));
    }

    /**
     * return generated html attributes
     * @param array $attributes
     * @return string
     */
    protected function attrToString(array $attributes): string
    {
        foreach ($attributes as $attr => $value) {
            if ($attributes[$attr] === false) {
                unset($attributes[$attr]);
            }
        }

        return Arr::implodeAssoc('" ', '="', $attributes) . '"';
    }

    /**
     * change date and time fields to sql format
     * @param array $post
     * @return array fixed $post data
     */
    protected function fixPostData($post): array
    {
        if ($this->model instanceof Model) {
            $structure = $this->validation()->getStructure();
            foreach ($post as $field => $value) {
                $options = Object::val($structure, $field);
                if ($value && $options) {
                    switch ($options->dataType) {
                        case 'time':
                            $post[$field] = Date::create($value)->format(Date::FORMAT_TIME);
                            break;
                        case 'date':
                        case 'datetime':
                        case 'timestamp':
                            $post[$field] = Date::create($value)->toSql();
                            break;
                    }
                } else {
                    $post[$field] = $value;
                }
            }
        }

        return $post;
    }

    /**
     * removes extra spaces and quotes
     * @param array $formData
     * @return array $formData
     */
    protected function clearFormData($formData, $protected = false): array
    {
        unset($formData['secureKey'], $formData['form-submitted']);

        $secureFileds = $protected ? $this->getSecureFields() : [];

        foreach ($formData as $name => $value) {
            if ($protected && !in_array($name, $secureFileds)) {
                unset($formData[$name]);
                continue;
            }
            if (is_array($value)) {
                $formData[$name] = $this->clearFormData($value);
            } else {
                $value = trim($value);
                if ($this->magicQuotes) {
                    $value = stripcslashes($value);
                }
                $formData[$name] = $value;
            }
        }

        return $formData;
    }

    protected function correctName($name)
    {
        return str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    }

    protected function addInput($name)
    {
        $pos = strpos($name, '.');
        $arrName = $pos ? substr($name, 0, $pos) : $name;
        $this->inputs[$arrName] = $name;
    }
}