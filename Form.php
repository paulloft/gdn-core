<?php
namespace Garden;

class Form
{
    /**
     * @var string form method
     */
    public $method = 'post';

    /**
     * @var string default css class for input
     */
    public $inputClass = 'form-control';

    /**
     * @var \Garden\Model
     */
    protected $model;
    protected $validation;
    protected $data;
    protected $formValues;

    private $hiddenInputs = [];
    private $inputs;
    private $errors = [];

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
     * @param object $model table model
     * @param array|object $dataset
     */
    public function setModel($model, $dataset = false)
    {
        $this->model = $model;

        if ($dataset !== false)
            $this->setData($dataset);
    }

    /**
     * return model primary key
     * @return bool|mixed
     */
    public function primaryKey()
    {
        return $this->model ? val('primaryKey', $this->model) : false;
    }

    /**
     * set form data
     * @param array|object $data
     */
    public function setData($data)
    {
        if (is_object($data)) {
            if ($data instanceof Db\Database\Result) {
                $this->data = $data->current();
            } elseif ($data instanceof \StdClass) {
                $this->data = (array)$data;
            }
        } elseif (is_array($data)) {
            $this->data = $data;
        }

        $primaryKey = $this->primaryKey();

        if ($primaryKey) {
            $id = val($primaryKey, $this->data, null);
            $this->addHidden($primaryKey, $id, true);
        }

    }

    /**
     * return form validation class
     * @return Validation
     */
    public function validation()
    {
        if ($this->model && $this->model instanceof Model) {
            return $this->model->validation();
        }

        if (!$this->validation) {
            $this->validation = new Validation($this->model);
        }

        return $this->validation;
    }

    /**
     * Check the form submission
     * @return bool
     */
    public function submitted()
    {
        $method = strtoupper($this->method);
        $postValues = is_array($this->getFormValues()) && count($this->getFormValues()) > 0;

        if ($method === Request::METHOD_GET) {
            return count(Gdn::request()->getQuery()) > 0 || $postValues;
        } else {
            return Gdn::request()->isPost() && $postValues;
        }
    }

    /**
     * Check the form submission and data integrity
     * @return bool
     */
    public function submittedValid()
    {
        if ($this->submitted()) {
            if ($this->checkValidData()) {
                return true;
            } else {
                $this->addError(t('The data obtained from these different form'));
            }
        }

        return false;
    }

    /**
     * return form field value
     * @param string $name field name
     * @param string $default default value if field not exists
     * @return mixed
     */
    public function getFormValue($name, $default = '')
    {
        return valr($name, $this->getFormValues(), $default);
    }

    private $magicQuotes;

    /**
     * return form values
     * @return array|mixed
     */
    public function getFormValues()
    {
        if (!is_array($this->formValues)) {
            $this->magicQuotes = get_magic_quotes_gpc();

            $this->formValues = [];
            $var = '_'.strtoupper($this->method);
            $formData = val($var, $GLOBALS, []);

            $this->formValues = $this->clearFormData($formData);
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
        } else {
            return valr($name, $this->data, $default);
        }
    }

    /**
     * return form values or if form submitted return post data
     * @return array|mixed
     */
    public function getValues()
    {
        if ($this->submitted()) {
            return $this->getFormValues();
        } else {
            return $this->data;
        }
    }

    /**
     * set form field value
     * @param string $name field value
     * @param mixed $value
     */
    public function setFormValue($name, $value)
    {
        $this->getFormValues();
        $this->formValues[$name] = trim($value);
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
     * @return bool|void
     */
    public function valid()
    {
        $data = $this->getFormValues();
        $data = $this->fixPostData($data);
        return $this->validation()->validate($data);
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

        $html = array();
        foreach ($errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                if (is_array($error)) {
                    $errField = val(0, $error);
                    $error = val(1, $error);
                } else {
                    $errField = t(($this->model ? val('table', $this->model, 'form') : 'form') . '.' . $field, $field);
                }
                if ($text) {
                    $html[] = $errField . ' ' . $error;
                } else {
                    $html[] = '<strong>' . $errField . ':</strong> ' . $error;
                }
            }
        }

        foreach ($this->errors as $error) {
            $html[] = $error;
        }

        if ($text) {
            return implode('; ', $html);
        } else {
            return sprintf(t('form_html_error_wrapper', '<div class="alert alert-danger">%s</div>'), implode('<br>', $html));
        }
    }

    /**
     * save form data
     * @return int id record
     */
    public function save()
    {
        $result = false;

        if ($this->valid()) {
            $post = $this->getFormValues();

            if ($this->model && $this->model instanceof Model) {
                $id = array_extract($this->model->primaryKey, $post);
                $post = $this->fixPostData($post);
                try {
                    $result = $this->model->save($post, $id);
                } catch (\Exception $e) {
                    if (c('main.debug', true)) {
                        $this->addError(t_sprintf('form_save_error_debug', $e->getMessage()));
                    } else {
                        $this->addError(t('form_save_error'));
                    }

                    $result = false;
                }
            } else {
                $result = $post;
            }
        }

        Response::current()->headers('Form-Error', !$result);

        return $result;
    }

    /**
     * generate form secure key from your $data
     * @param array $post
     * @return string md5 hash
     */
    public function generateSecureKey($data)
    {
        $keys = array_keys($data);
        return SecureString::instance()->encode($keys, ['aes256' => c('main.hashsalt')]);
    }

    /**
     * return posted form secure key
     * @return string
     */
    public function getSecureKey()
    {
        $var = '_'.strtoupper($this->method);
        $formData = val($var, $GLOBALS, []);

        return val('secureKey', $formData);
    }

    /**
     * Сhecks the integrity of the data came
     * @param array $post
     * @param array $secureKey
     * @return bool
     */
    public function checkValidData($post = false, $secureKey = false)
    {
        if (!$post) {
            $post = $this->getFormValues();
        }
        if (!$secureKey) {
            $secureKey = $this->getSecureKey();
        }

        if (!$post || !$secureKey) {
            return false;
        }

        $postFields = array_keys($post);
        $fields = SecureString::instance()->decode($secureKey, ['aes256' => c('main.hashsalt')]);

        $result = array_diff($postFields, $fields);

        return empty($result);
    }

    /**
     * html tag open form
     * @param array $attributes form attributes
     * @return string
     */
    public function open(array $attributes = [])
    {
        $return = '<form ';
        $currentPath = Gdn::request()->getPath();
        array_touch('action', $attributes, $currentPath);
        array_touch('method', $attributes, $this->method);
        $this->method = val('method', $attributes);

        $return .= $this->attrToString($attributes);

        $return .= '>';
        return $return;
    }

    /**
     * html tag close form
     * @return string
     */
    public function close()
    {
        $return = '';
        foreach ($this->hiddenInputs as $name => $value) {
            $return .= $this->input($name, 'hidden', ['value' => $value]);
        }

        $return .= $this->input('secureKey', 'hidden', ['value' => $this->generateSecureKey($this->inputs)]);
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
    public function input($name, $type = 'text', array $attributes = [])
    {
        if ($type !== 'radio' && $type !== 'checkbox' && $type !== 'hidden') {
            array_touch('class', $attributes, $this->inputClass);
        }

        $inputValue = val('value', $attributes);
        $correctName = $this->correctName($name);

        $attributes['name'] = $name;
        $attributes['type'] = $type;

        if ($type == 'radio' || $type == 'checkbox') {
            $value = $this->getValue($correctName);
            $checked = is_array($value) ? in_array($inputValue, $value) : $inputValue == $value;
            if ($inputValue !== false && $checked) {
                array_touch('checked', $attributes, 'checked');
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
    public function textarea($name, array $attributes = [])
    {
        array_touch('class', $attributes, $this->inputClass);
        array_touch('rows', $attributes, '5');

        $attributes['name'] = $name;
        $value = val('value', $attributes);
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
    public function checkbox($name, array $attributes = [])
    {
        array_touch('value', $attributes, 1);
        $defaultValue = array_extract('defaultValue', $attributes, null);

        $html = '<input type="hidden" name="' . $name . '" value="'.$defaultValue.'" />';
        $html .= $this->input($name, 'checkbox', $attributes);

        return $html;
    }

    /**
     * html tag input[type="radio"]
     * @param string $name field name
     * @param array $attributes radio attributes
     * @return string
     */
    public function radio($name, array $attributes = [])
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
    public function select($name, array $options = [], array $attributes = [])
    {
        array_touch('class', $attributes, $this->inputClass);
        $attributes['name'] = $name;

        $defaultName = array_extract('default_name', $attributes);
        $defaultValue = array_extract('default_value', $attributes);
        $keyValue = array_extract('key_value', $attributes);
        $keyName = array_extract('key_name', $attributes);

        $html = '<select ' . $this->attrToString($attributes) . '>';

        if ($defaultName) {
            $html .= '<option value="' . $defaultValue . '">' . $defaultName . '</option>';
        }

        $correctName = $this->correctName($name);
        $this->addInput($correctName);
        $fieldValue = $this->getValue($correctName);

        foreach ($options as $key => $option) {
            $optionValue = $keyName && $keyValue ? val($keyValue, $option) : $key;
            $optionName = $keyName && $keyValue ? val($keyName, $option) : $option;

            $selected = is_array($fieldValue) ? in_array($optionValue, $fieldValue) : $optionValue == $fieldValue;

            $html .= '<option value="' . $optionValue . '"' . ($selected ? ' selected' : '') . '>' . $optionName . '</option>';
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
        if ($this->submitted() && $forceValue === false) {
            $value = $this->getFormValue($name, $value);
        }

        $this->hiddenInputs[$name] = $value;
    }

    protected function _value($name, $value = false)
    {
        return format_form($value === false ? $this->getValue($name) : $value);
    }

    /**
     * return generated html attributes
     * @param $attributes
     * @return string
     */
    protected function attrToString($attributes)
    {
        return implode_assoc('" ', '="', $attributes) . '"';
    }

    /**
     * change date and time fields to sql format
     * @param array $post
     * @return array fixed $post data
     */
    protected function fixPostData($post)
    {
        if ($this->model instanceof Model) {
            $structure = $this->validation()->getStructure();
            foreach ($post as $field => $value) {
                // if (empty($value)) $value = null;
                if ($value && $options = val($field, $structure)) {
                    switch ($options->dataType) {
                        case 'time':
                            $post[$field] = date_convert($value, 'time');
                            break;
                        case 'date':
                        case 'datetime':
                        case 'timestamp':
                            $post[$field] = date_convert($value, 'sql');
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
    protected function clearFormData($formData)
    {
        unset($formData['secureKey']);

        foreach ($formData as $name => $value) {
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
        return str_replace(['[]','[', ']'], ['', '.', ''], $name);
    }

    protected function addInput($name)
    {
        $pos = strpos($name, '.');
        $arrName = $pos ? substr($name, 0, $pos) : $name;
        $this->inputs[$arrName] = $name;
    }
}