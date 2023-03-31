<?php


namespace Garden\Renderers;

use Garden\Exception;
use Garden\Helpers\Files;
use Garden\Interfaces\Renderer;
use Garden\Request;
use Garden\Response;
use Garden\Traits\DataSetGet;
use function call_user_func;

class View implements Renderer
{

    use DataSetGet;

    /**
     * @var string default view extention
     */
    public static $defaultExtention = 'tpl';

    /**
     * Extention renderers
     *
     * @var array
     */
    private static $extentionRenderers = [];

    protected $view;
    protected $folder;

    private $controllerInfo = [];

    /**
     * View constructor.
     *
     * @param string $viewName
     * @param string $viewFolder
     * @param string $adddon
     */
    public function __construct(string $viewName = null, string $viewFolder = null, string $adddon = null)
    {
        $request = Request::current();
        $this->view = $viewName ?? (string)$request->getEnvKey('action');
        $viewFolder = $viewFolder ?? strtolower($request->getEnvKey('controller_name'));
        $adddon = ucfirst($adddon ?? (string)$request->getEnvKey('addon'));

        $this->folder = "/$adddon/Views/$viewFolder";
    }

    /**
     * @param Response $response
     * @return string
     * @throws Exception\NotFound
     */
    public function fetch(Response $response): string
    {
        $response->setContentType('text/html');
        $viewPath = $this->getViewPath();
        $realPath = realpath(GDN_ADDONS . $viewPath);

        if (!is_file($realPath)) {
            throw new Exception\NotFound('Page', "View template \"{$this->view}\" not found in $viewPath");
        }

        $ext = pathinfo($realPath, PATHINFO_EXTENSION);

        if (isset(self::$extentionRenderers[$ext])) {
            return call_user_func(self::$extentionRenderers[$ext], $realPath, $this->_data);
        }

        return Files::getInclude($realPath, $this->_data);
    }

    /**
     * Returns view absolute file path
     *
     * @param string $view
     * @param string $controllerName
     * @param string $addonName
     * @return string
     */
    public function getViewPath(): string
    {
        $pathinfo = pathinfo($this->view);
        $filename = $pathinfo['filename'] ?? 'index';
        $ext = $pathinfo['extension'] ?? self::$defaultExtention;

        return "{$this->folder}/{$filename}.$ext";
    }

    /**
     * Register extention renderer
     *
     * @param string $ext
     * @param callable $function
     */
    public static function registerExtRenderer(string $ext, callable $function)
    {
        self::$extentionRenderers[$ext] = $function;
    }
}
