<?php
namespace Garden;

class Template extends Controller {

    // template file
    protected $template = 'template';
    protected $templateAddon;

    protected $_js  = [];
    protected $_css = [];
    protected $meta = [];

    /**
     * Add js library to template
     * @param string $src path to js file
     * @param string $addon addon name
     */
    public function addJs($src, $addon = null)
    {
        $this->addResurce('js', $src, $addon);
    }

    /**
     * Add css library to template
     * @param string $src path to css file
     * @param string $addon addon name
     */
    public function addCss($src, $addon = null)
    {
        $this->addResurce('css', $src, $addon);
    }

    /**
     * Add meta tag to template
     * @param string $name
     * @param string $content
     * @param string $http_equiv
     */
    public function meta($name, $content, $http_equiv = false)
    {
        $this->meta[$name] = [$content, $http_equiv];
    }

    /**
     * Specifies the name of the template
     * @param string $template file name of template
     * @param string $addonName addon name
     * @return string
     */
    public function template($template = false, $addonName = false)
    {
        if($template)  $this->template      = $template;
        if($addonName) $this->templateAddon = $addonName;

        return $this->template;
    }

    /**
     * Returns generated html template content
     * @param string $view name of view file
     * @param string $controllerName controller name
     * @param string $addonName addon name
     * @return string html
     * @throws Exception\NotFound
     */
    public function fetchTemplate($view = false, $controllerName = false, $addonFolder = false)
    {
        $view = $view ?: $this->callerMethod();
        $view = $this->fetchView($view, $controllerName, $addonFolder);

        $data = [
            'content'  => $view,
            'meta'     => $this->meta,
            'js'       => $this->_js,
            'css'      => $this->_css,

            'action'     => strtolower($this->callerMethod()),
            'addon'      => strtolower($this->controllerInfo('addon')),
            'controller' => strtolower($this->controllerInfo('controller')),
        ];

        $this->setData('gdn', $data);
        $this->setData('sitename', c('main.sitename'));

        $template = $this->fetchView($this->template, '/', $addonFolder?: $this->templateAddon);

        return $template;
    }

    /**
     * Render template
     * @param string $view name of view file
     * @param string $controllerName controller name
     * @param string $addonName addon name
     */
    public function render($view = false, $controllerName = false, $addonFolder = false)
    {
        Event::fire('render_before');

        $view = $view ?: $this->callerMethod();
        if ($this->renderType() === Request::RENDER_VIEW) {
            if ($this->_js) {
                Response::current()->headers('Ajax-Js', json_encode($this->_js));
            }
            if ($this->_css) {
                Response::current()->headers('Ajax-Css', json_encode($this->_css));
            }

            echo $this->fetchView($view, $controllerName, $addonFolder);
        } elseif ($this->renderType() === Request::RENDER_JSON) {
            Response::current()->headers('Content-Type', 'application/json');
            echo json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            echo $this->fetchTemplate($view, $controllerName, $addonFolder);
        }

        Event::fire('render_after');
    }

    protected function getResourcePath($resource, $src, $addon = null)
    {
        $addon = $addon !== null ? $addon : strtolower($this->getAddonName());
        $local = is_local_url($src);
        if ($local && $addon && file_exists(GDN_ADDONS.'/'.ucfirst($addon).'/Assets/'.$resource.'/'.$src)) {
            return "/assets/$addon/$resource/$src";
        } elseif (!$local || file_exists(PATH_PUBLIC.'/'.$src)) {
            return $src;
        } else {
            return false;
        }
    }

    protected function addResurce($resource, $src, $addon = false)
    {
        $src = $this->getResourcePath($resource, $src, $addon);

        if ($src) {
            $hash = hash('md4', $src);
            $this->{'_'.$resource}[$hash] = $src;
        }
    }
}