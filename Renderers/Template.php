<?php

namespace Garden\Renderers;

use Garden\Config;
use Garden\Helpers\Validate;
use Garden\Interfaces\Renderer;
use Garden\Request;
use Garden\Response;
use Garden\Traits\DataSetGet;
use Garden\Translate;

class Template implements Renderer {

    use DataSetGet;

    private $view;
    private $addon;
    private $template;
    private $folder;

    private $resources = [];
    private $meta = [];

    /**
     * Template constructor.
     * @param string $templateName
     * @param string $templateFolder
     * @param string|null $addonName
     */
    public function __construct(string $templateName, string $templateFolder = '', string $addonName = null)
    {
        $request = Request::current();
        $this->template = $templateName;
        $this->folder = $templateFolder;
        $this->addon = $addonName ?? (string)$request->getEnvKey('addon');
    }

    /**
     * @see Renderer::fetch()
     *
     * @param Response $response
     * @return string
     */
    public function fetch(Response $response): string
    {
        $request = Request::current();
        $sitename = Config::get('main.sitename');
        $separator = Config::get('main.title_separator', '-');
        $title = $this->_data['title'] ?? '';

        if ($this->view === null) {
            $this->view = new View();
        }

        $this->view->setDataArray($this->_data);

        $content = $this->view->fetch($response);

        $template = new View($this->template, $this->folder, $this->addon);
        $template->setDataArray([
            'h1' => $this->_data['h1'] ?? $title,
            'title' => strip_tags("$title $separator $sitename"),
            'sitename' => $sitename,
            'meta' => $this->meta,
            'js' => $this->resources['js'] ?? [],
            'css' => $this->resources['css'] ?? [],

            'action' => strtolower($request->getEnvKey('action')),
            'addon' => strtolower($request->getEnvKey('addon')),
            'controller' => strtolower($request->getEnvKey('controller_name')),
            'content' => $content,
        ]);

        return $template->fetch($response);
    }

    /**
     * Set page title
     *
     * @param string $title
     * @return self
     */
    public function setTitle($title): self
    {
        $this->_data['title'] = Translate::get($title);

        return $this;
    }

    /**
     * Add js library to template
     *
     * @param string $src path to js file
     * @param string $addon addon name
     * @return self
     */
    public function addJs(string $src, string $addon = null): self
    {
        $this->addResurce('js', $src, $addon);

        return $this;
    }

    /**
     * Add css library to template
     *
     * @param string $src path to css file
     * @param string $addon addon name
     * @return self
     */
    public function addCss(string $src, string $addon = null): self
    {
        $this->addResurce('css', $src, $addon);

        return $this;
    }

    /**
     * Add meta tag to template
     * @param string $name
     * @param string $content
     * @param string $httpEquiv
     * @return self
     */
    public function meta(string $name, string $content, string $httpEquiv = null): self
    {
        $this->meta[$name] = [$content, $httpEquiv];

        return $this;
    }

    /**
     * @param string $viewName
     * @param string $viewFolder
     * @param string $adddon
     * @return self
     */
    public function setView(string $viewName = null, string $viewFolder = null, string $adddon = null): self
    {
        $this->view = new View($viewName, $viewFolder, $adddon);

        return $this;
    }

    /**
     * @param string $resource
     * @param string $src
     * @param string|null $addon
     */
    protected function addResurce(string $resource, string $src, string $addon = null)
    {
        $src = $this->getResourcePath($resource, $src, $addon);

        if ($src) {
            $hash = hash('md4', $src);
            $this->resources[$resource][$hash] = $src;
        }
    }

    /**
     * @param string $resource
     * @param string $src
     * @param string $addon
     * @return string
     */
    protected function getResourcePath(string $resource, string $src, string $addon = null): string
    {
        $addon = $addon ?? strtolower(Request::current()->getEnvKey('addon'));
        $isLocal = Validate::localUrl($src);

        if (!$isLocal) {
            return $src;
        }

        if ($addon && file_exists(GDN_ADDONS . '/' . ucfirst($addon) . "/Assets/$resource/$src")) {
            return "/assets/$addon/$resource/$src";
        }

        if (file_exists(PATH_PUBLIC . "/$src")) {
            return $src;
        }

        return '';
    }
}