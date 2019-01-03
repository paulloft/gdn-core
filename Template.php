<?php
namespace Garden;

use Garden\Helpers\Validate;

class Template extends Controller {

    // template file
    protected $template = 'template';
    protected $addon;

    protected $resources = [];
    protected $meta = [];

    /**
     * Set page title
     * @param string $title
     */
    public function title($title)
    {
        $this->setData('title', Translate::get($title));
    }

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
        if($template)  {
            $this->template = $template;
        }

        if($addonName) {
            $this->addon = $addonName;
        }

        return $this->template;
    }

    /**
     * @return array
     */
    protected function getPageData()
    {
        $sitename = Config::get('main.sitename');
        $separator = Config::get('main.titleSeparator', '-');
        $title = $this->data('title');

        return [
            'h1'       => $title,
            'title'    => strip_tags($title.' '.$separator.' '.$sitename),
            'meta'     => $this->meta,
            'js'       => val('js', $this->resources, []),
            'css'      => val('css', $this->resources, []),

            'action'     => strtolower($this->callerMethod()),
            'addon'      => strtolower($this->controllerInfo('addon')),
            'controller' => strtolower($this->controllerInfo('controller'))
        ];
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

        $data = $this->getPageData();
        $data['content'] = $view;

        $this->setData('gdn', $data);
        $this->setData('sitename', Config::get('main.sitename'));

        return $this->fetchView($this->template, '/', $addonFolder?: $this->addon);
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

        switch ($this->renderType()) {
            case Request::RENDER_VIEW:
                echo $this->fetchView($view, $controllerName, $addonFolder);
                break;

            case Request::RENDER_JSON:
                parent::render();
                break;

            default:
                echo $this->fetchTemplate($view, $controllerName, $addonFolder);
                break;
        }

        Event::fire('render_after');
    }

    protected function getCacheVersion()
    {
        $version = Gdn::cache()->get('assetsVersion');
        if (!$version) {
            $version = md5(time());
            Gdn::cache()->set('assetsVersion', $version, 0);
        }

        return $version;
    }

    /**
     * @param $resource
     * @param $src
     * @param string $addon
     * @return bool|string
     */
    protected function getResourcePath($resource, $src, $addon = null)
    {
        $addon = $addon ?? strtolower($this->getAddonName());
        $local = Validate::localUrl($src);
        $version = (strpos($src, '?') === false ? '?' : '&') . 'v=' . $this->getCacheVersion();
        if ($local && $addon && file_exists(GDN_ADDONS . '/' . ucfirst($addon) . '/Assets/' . $resource . '/' . $src)) {
            return "/assets/$addon/$resource/$src$version";
        }

        if (!$local || file_exists(PATH_PUBLIC . '/' . $src)) {
            return $src.$version;
        }

        return false;
    }

    protected function addResurce($resource, $src, $addon = false)
    {
        $src = $this->getResourcePath($resource, $src, $addon);

        if ($src) {
            $hash = hash('md4', $src);
            $this->resources[$resource][$hash] = $src;
        }
    }
}