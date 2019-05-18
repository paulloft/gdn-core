<?php

namespace Garden;

use function count;
use Garden\Exception;
use Garden\Helpers\Files;
use Garden\Helpers\Text;
use Garden\Traits\Instance;
use stdClass;
use function is_array;
use function is_string;

class Controller {

    /**
     * @var Form
     */
    public $form;

    // data storage
    protected $_data;
    protected $_view;
    protected $_controllerName;
    protected $_addonFolder = 'addons';
    protected $_addonName;

    protected $_templateBaseDir;

    // default view extention
    protected $_viewExt = 'tpl';

    /**
     * @var \Smarty
     */
    private $_smarty;

    use Instance;

    public function __construct()
    {
        $this->_addonName = $this->controllerInfo('addon');
    }

    /**
     * Automatically executed before the controller action. Can be used to set
     * class properties, do authorization checks, and execute other custom code.
     *
     */
    public function initialize()
    {
    }

    /**
     * Assign template data by key
     * @param array|string $key
     * @param mixed $value
     */
    public function setData($key, $value = null)
    {
        if (is_array($key)) {
            foreach ((array)$key as $k => $v) {
                $this->_data[$k] = $v;
            }
        } else {
            $this->_data[$key] = $value;
        }
    }

    /**
     * Returns assigned data by key
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public function data($key, $default = null)
    {
        return $this->_data[$key] ?? $default;
    }

    /**
     * Assigns the specified view
     * @param string $view name of view file
     * @param string $controllerName controller name
     * @param string $addonName addon name
     */
    public function setView($view = false, $controllerName = false, $addonName = false)
    {
        $this->_view = $view;
        $this->_controllerName = $controllerName;
        $this->_addonName = $addonName;
    }

    /**
     * Render template
     * @param string $view name of view file
     * @param string $controllerName controller name
     * @param string $addonName addon name
     */
    public function render($view = false, $controllerName = false, $addonName = false)
    {
        Event::fire('render_before');

        if ($this->renderType() === Request::RENDER_JSON) {
            Response::current()->setHeader('Content-Type', 'application/json');
            echo json_encode($this->_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $view = $view ?: $this->callerMethod();
            echo $this->fetchView($view, $controllerName, $addonName);
        }

        Event::fire('render_after');
    }

    /**
     * Returns generated html view content
     * @param string $view name of view file
     * @param string $controllerName controller name
     * @param string $addonName addon name
     * @return string html
     * @throws Exception\NotFound
     * @throws Exception\Error
     */
    public function fetchView($view, $controllerName = false, $addonName = false)
    {
        $viewPath = $this->getViewPath($view, $controllerName, $addonName);
        $realPath = realpath(PATH_ROOT . "/$viewPath");

        if (!$this->viewExists($view, $controllerName, $addonName)) {
            throw new Exception\NotFound('Page', "View template \"$view\" not found in $viewPath");
        }

        if (Text::strEnds($realPath, '.' . $this->_viewExt)) {
            try {
                $smarty = $this->smarty();
                $smarty->setTemplateDir(PATH_ROOT . "/{$this->_templateBaseDir}");
                $smarty->assign($this->_data);
                $view = $smarty->fetch($realPath);
            } catch (\Exception $e) {
                throw new Exception\Error($e);
            }
        } else {
            $view = Files::getInclude($realPath, $this->_data);
        }

        return $view;
    }

    /**
     * @param $view
     * @param bool $controllerName
     * @param bool $addonName
     * @return bool
     */
    public function viewExists($view, $controllerName = false, $addonName = false)
    {
        $viewPath = $this->getViewPath($view, $controllerName, $addonName);
        $realPath = realpath(PATH_ROOT . "/$viewPath");

        return is_file($realPath);
    }

    /**
     * Returns view absolute file path
     * @param string $view
     * @param string $controllerName
     * @param string $addonName
     * @return string
     */
    public function getViewPath($view, $controllerName = false, $addonName = false)
    {
        $addonName = ucfirst($addonName ?: $this->_addonName);
        $controllerName = $controllerName ?: $this->_controllerName;

        $addonFolder = $addonName ? "{$this->_addonFolder}/$addonName" : $this->controllerInfo('folder');
        $controllerName = $controllerName ?: $this->controllerInfo('controller');

        if (Text::strEnds($controllerName, 'controller')) {
            $controllerName = substr($controllerName, 0, -10);
        }

        $pathinfo = pathinfo($view);
        $dir = $pathinfo['dirname'];
        $filename = $pathinfo['filename'] ?? 'index';
        $ext = $pathinfo['extension'] ?? $this->_viewExt;

        $this->_templateBaseDir = "$addonFolder/Views/" . strtolower($controllerName) . "/$dir";

        return "$this->_templateBaseDir/$filename.$ext";
    }

    /**
     * Return lazyload Smarty object
     * @return \Smarty
     */
    public function smarty()
    {
        if ($this->_smarty === null) {
            $this->_smarty = new Smarty();
        }

        return $this->_smarty;
    }

    /**
     * Return current render type
     * @return string
     */
    public function renderType()
    {
        return Request::current()->renderType();
    }

    public function setRenderType($type)
    {
        Request::current()->setRenderType($type);
    }

    /**
     * Return current addon name
     * @return string
     */
    public function getAddonName()
    {
        return $this->_addonName;
    }

    /**
     * @param string|Model $model
     * @param array|stdClass|Db\Database\Result $data
     * @return Form
     */
    public function form($model = false, $data = false)
    {
        $tablename = is_string($model) ? $model : false;
        $this->form = new Form($tablename);

        if ($model instanceof Model) {
            $this->form->setModel($model);
            $this->form->setData($data);
        } elseif ($data !== false) {
            $this->form->setData($data);
        }

        $this->setData('gdn_form', $this->form);

        return $this->form;
    }

    /**
     * @return string
     */
    protected function callerMethod()
    {
        return Request::current()->getEnvKey('action');
    }

    /**
     * @param bool $key
     * @param bool $default
     * @return mixed
     */
    protected function controllerInfo($key = false, $default = null)
    {
        $className = static::class;

        if (!$result = Gdn::dirtyCache()->get($className)) {
            $space = explode('\\', $className);

            $result = false;
            if (count($space) >= 3) {
                $result = [
                    'addon' => $space[1],
                    'folder' => strtolower($space[0]) . "/{$space[1]}",
                    'controller' => array_pop($space)
                ];
            }

            Gdn::dirtyCache()->set($className, $result);
        }

        return $key ? ($result[$key] ?? $default) : $result;
    }


}
