<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden;

use Garden\Exception;
use Garden\Helpers\Arr;

/**
 * Contains functionality that allows addons to enhance or change an application's functionality.
 *
 * An addon can do the following.
 *
 * 1. Any classes that the addon defines in its root, /controllers, /library, /models, and /modules
 *    directories are made available.
 * 2. The addon can contain a bootstrap.php which will be included at the app startup.
 * 3. If the addon declares any classes ending in *Plugin then those plugins will automatically
 *    bind their event handlers. (also *Hooks)
 */
class Addons
{
    /// Constants ///
    const K_BOOTSTRAP = 'bootstrap'; // bootstrap path key
    const K_CONFIG = 'config'; // config path key
    const K_CLASSES = 'classes';
    const K_HOOKS = 'hooks';
    const K_DIR = 'dir';
    const K_INFO = 'info'; // addon info key

    const DIR_SETTINGS = 'Settings';
    const DIR_ASSETS = 'Assets';

    /// Properties ///

    /**
     * @var array An array that maps addon keys to full addon information.
     */
    protected static $all;

    /**
     * @var string The base directory where all of the addons are found.
     */
    protected static $baseDir;

    /**
     * @var array|null An array that maps class names to file paths.
     */
    protected static $classMap;

    /**
     * @var array An array that maps addon keys to full addon information for enabled addons.
     */
    protected static $enabled;

    /**
     * @var array An array of enabled addon keys.
     */
    protected static $enabledKeys;

    /**
     * @var bool Signals that the addon framework is in a shared environment and shouldn't use the enabled cache.
     */
    public static $sharedEnvironment;

    /**
     * Get all of the available addons or a single addon from the available list.
     *
     * @param string $addonKey If you supply an addon key then only that addon will be returned.
     * @param string $key Supply one of the Addons::K_* constants to get a specific key from the addon.
     * @return array Returns the addon with the given key or all available addons if no key is passed.
     */
    public static function all($addonKey = null, $key = null)
    {
        if (self::$all === null) {

            if (!self::$all = Gdn::cache('system')->get('addons-all')) {
                self::$all = self::scanAddons();
                Gdn::cache('system')->set('addons-all', self::$all);
            }
        }

        // The array should be built now return the addon.
        if ($addonKey === null) {
            return self::$all;
        }

        $addon = val(strtolower($addonKey), self::$all);
        if ($addon && $key) {
            return val($key, $addon);
        }

        if ($addon) {
            return $addon;
        }

        return null;
    }

    /**
     * An autoloader that will autoload a class based on which addons are enabled.
     *
     * @param string $classname The name of the class to load.
     */
    public static function autoload($classname)
    {
        list(, $path) = static::classMap($classname);
        if ($path) {
            require_once $path;
        }
    }

    /**
     * Gets the base directory for addons.
     *
     * @return string Returns the base directory for addons.
     */
    public static function baseDir()
    {
        if (self::$baseDir === null) {
            self::$baseDir = GDN_ADDONS;
        }

        return self::$baseDir;
    }

    /**
     * Sets the base directory for addons.
     *
     * @param string $value Pass a value to set the new base directory.
     */
    public static function setBaseDir(string $value)
    {
        self::$baseDir = rtrim($value, '/');
    }


    /**
     * Start up the addon framework.
     *
     * @param array $enabledAddons An array of enabled addons.
     * @throws \InvalidArgumentException
     */
    public static function bootstrap($enabledAddons = null)
    {
        // Load the addons from the config if they aren't passed in.
        if (!\is_array($enabledAddons)) {
            $enabledAddons = Config::get('addons', []);
        }

        // Reformat the enabled array into the form: array('addon_key' => 'addon_key')
        $enabledKeys = array_keys(array_change_key_case(array_filter($enabledAddons)));
        $enabledKeys = array_combine($enabledKeys, $enabledKeys);
        self::$enabledKeys = $enabledKeys;
        self::$classMap = null; // invalidate so it will rebuild

        // Enable the addon autoloader.
        spl_autoload_register([__CLASS__, 'autoload']);

        // Bind all of the addon plugin events now.
        foreach (self::enabled() as $addon) {
            foreach ($addon[self::K_HOOKS] as $className) {
                Event::bindClass($className);
            }
        }

        self::baseDir();

        Event::bind('bootstrap', function () {
            // Start each of the enabled addons.
            foreach (self::enabled() as $key => $value) {
                static::startAddon($key);
            }
        });
    }

    /**
     * A an array that maps class names to physical paths.
     *
     * @param string $classname An optional class name to get the path of.
     * @return array Returns an array in the form `[fullClassname, classPath]`.
     * If no {@link $classname} is passed then the entire class map is returned.
     */
    public static function classMap($classname = null)
    {
        if (self::$classMap === null) {
            // Loop through the enabled addons and grab their classes.
            $classMap = [[]];
            foreach (static::enabled() as $addon) {
                if (isset($addon[self::K_CLASSES])) {
                    $classMap[] = $addon[self::K_CLASSES];
                }
            }

            $classMap = array_replace(...$classMap);
            self::$classMap = $classMap;
        }

        // Now that the class map has been built return the result.
        if ($classname !== null) {
            $basename = trim($classname, '\\');

            $row = val($basename, self::$classMap);

            if ($row === null) {
                return ['', ''];
            }

            if (\is_string($row)) {
                return [$classname, $row];
            }

            if (\is_array($row)) {
                return $row;
            }

            return ['', ''];
        }

        return self::$classMap;
    }

    /**
     * Get all of the enabled addons or a single addon from the enabled list.
     *
     * @param string $addonKey If you supply an addon key then only that addon will be returned.
     * @param string $key Supply one of the Addons::K_* constants to get a specific key from the addon.
     * @return array Returns the addon with the given key or all enabled addons if no key is passed.
     */
    public static function enabled($addonKey = null, $key = null)
    {
        // Lazy build the enabled array.
        if (self::$enabled === null) {
            if (self::$all !== null || self::$sharedEnvironment) {
                // Build the enabled array by filtering the all array.
                self::$enabled = [];
                $allAddons = self::all();
                foreach ($allAddons as $row) {
                    if (isset($key, self::$enabledKeys)) {
                        self::$enabled[$key] = $row;
                    }
                }
            // Build the enabled array by walking the addons.
            } elseif (!self::$enabled = Gdn::cache('system')->get('addons-enabled')) {
                self::$enabled = self::scanAddons(null, self::$enabledKeys);
                Gdn::cache('system')->set('addons-enabled', self::$enabled);
            }
        }

        // The array should be built now return the addon.
        if ($addonKey === null) {
            return self::$enabled;
        }

        $addon = val(strtolower($addonKey), self::$enabled);
        if ($addon && $key) {
            return val($key, $addon);
        }

        if ($addon) {
            return $addon;
        }

        return null;
    }

    /**
     * Return the info array for an addon.
     *
     * @param string $addonKey The addon key.
     * @return array|null Returns the addon's info array or null if the addon wasn't found.
     */
    public static function info($addonKey)
    {
        $addonKey = strtolower($addonKey);

        // Check the enabled array first so that we don't load all addons if we don't have to.
        if (isset(self::$enabledKeys[$addonKey])) {
            return static::enabled($addonKey, self::K_INFO);
        }

        return static::all($addonKey, self::K_INFO);
    }

    /**
     * Scan an addon directory for information.
     *
     * @param string $dir The addon directory to scan.
     * @param array &$addons The addons array.
     * @param array $enabled An array of enabled addons or null to scan all addons.
     * @return array Returns an array in the form [addonKey, addonInfo].
     */
    protected static function scanAddonRecursive($dir, &$addons, $enabled = null)
    {
        $dir = rtrim($dir, '/');
        $addonKey = strtolower(basename($dir));

        // Scan the addon if it is enabled.
        if ($enabled === null || in_array($addonKey, $enabled, true)) {
            list($addonKey, $addon) = static::scanAddon($dir);
        } else {
            $addon = null;
        }

        // Add the addon to the collection array if one was supplied.
        if ($addon !== null) {
            $addons[$addonKey] = $addon;
        }

        return [$addonKey, $addon];
    }

    /**
     * Scan an individual addon directory and return the information about that addon.
     *
     * @param string $dir The path to the addon.
     * @return array An array in the form of `[$addon_key, $addon_row]` or `[$addon_key, null]` if the directory doesn't
     * represent an addon.
     */
    protected static function scanAddon($dir)
    {
        $dir = rtrim($dir, '/');
        $addon_key = strtolower(basename($dir));
        $settings = $dir . '/' . self::DIR_SETTINGS;

        // Look for the addon info array.
        $infoPath = $settings . '/about.json';
        if (file_exists($infoPath)) {
            $info = json_decode(file_get_contents($infoPath), true) ?: [];
        } else {
            $info = [];
        }

        Arr::touch('name', $info, $addon_key);
        Arr::touch('version', $info, '0.0');

        $bootstrap = self::checkFile($settings, self::K_BOOTSTRAP);
        $config = self::checkFile($settings, self::K_CONFIG);

        // Scan hooks directory
        $hooksFiles = glob("$dir/Hooks/*.php");
        $hooks = [];
        foreach ($hooksFiles as $path) {
            $classname = static::getFileClass($path);
            if ($classname) {
                $hooks[] = $classname;
            }
        }

        $addon = [
            self::K_BOOTSTRAP => $bootstrap,
            self::K_CONFIG => $config,
            self::K_HOOKS => $hooks,
            self::K_DIR => $dir,
            self::K_INFO => $info
        ];

        return [$addon_key, $addon];
    }

    protected static function checkFile($dir, $filename)
    {
        $file = $dir . '/' . $filename . '.php';
        return !file_exists($file) ? null : $file;
    }

    /**
     * Scan a directory for addons.
     *
     * @param string $dir The directory to scan.
     * @param array $enabled An array of enabled addons in the form `[addonKey => enabled, ...]`.
     * @param array &$addons The addons will fill this array.
     * @return array Returns all of the addons.
     */
    protected static function scanAddons($dir = null, $enabled = null, &$addons = null)
    {
        if (!$dir) {
            $dir = self::baseDir();
        }
        if ($addons === null) {
            $addons = [];
        }

        foreach (new \DirectoryIterator($dir) as $subdir) {
            if ($subdir->isDir() && !$subdir->isDot()) {
                static::scanAddonRecursive($subdir->getPathname(), $addons, $enabled);
            }
        }

        return $addons;
    }

    /**
     * Looks what classes and namespaces are defined in a file and returns the first found.
     *
     * @param string $src Path to file.
     * @return string Returns classname with namespace
     */
    public static function getFileClass(string $src): string
    {
        $contents = file_get_contents($src);
        $tokens = token_get_all($contents);

        $namespace = $class = '';
        $namespaceFound = $classFound = false;

        foreach ($tokens as $token) {
            if (\is_array($token) && $token[0] == T_NAMESPACE) {
                $namespaceFound = true;
            }

            if (\is_array($token) && $token[0] == T_CLASS) {
                $classFound = true;
            }

            if ($namespaceFound) {
                if (\is_array($token) && \in_array($token[0], [T_STRING, T_NS_SEPARATOR], true)) {
                    $namespace .= $token[1];
                } elseif ($token === ';') {
                    $namespaceFound = false;
                }
            }

            if ($classFound && \is_array($token) && $token[0] == T_STRING) {
                $class = $token[1];
                break;
            }
        }

        return $namespace ? $namespace . '\\' . $class : $class;
    }

    /**
     * Start an addon.
     *
     * This function does the following:
     *
     * 1. Make the addon available in the autoloader.
     * 2. Run the addon's bootstrap.php if it exists.
     *
     * @param string $addonKey The key of the addon to enable.
     * @return bool Returns true if the addon was enabled. False otherwise.
     */
    public static function startAddon($addonKey)
    {
        $addon = static::enabled($addonKey);
        if (!$addon) {
            return false;
        }

        $cache = Gdn::cache('system');
        // load config.
        if (!$cache->get('config-autoload')) {
            $configPath = val(self::K_CONFIG, $addon);
            if ($configPath) {
                Config::load($addonKey, $configPath, true);
            }
        }

        // load translations
        if (!$cache->get('translations')) {
            $locale = Config::get('main.locale', 'en_US');
            $localePath = val('dir', $addon);
            Translate::load("$localePath/Locales/$locale." . Translate::$defaultExtension);
        }

        // Run the class' bootstrap.
        $bootstrapPath = val(self::K_BOOTSTRAP, $addon);
        if ($bootstrapPath) {
            include_once $bootstrapPath;
        }

        return true;
    }

    /**
     * @param string $addonKey
     * @param string $assetPath
     * @throws Exception\NotFound
     */
    public static function renderAsset($addonKey, $assetPath)
    {
        $addon = self::enabled($addonKey);

        if (!$addon) {
            throw new Exception\NotFound();
        }

        $filePath = $addon['dir'] . '/' . self::DIR_ASSETS . '/' . str_replace('../', '/', $assetPath);

        if (file_exists($filePath)) {
            $pathinfo = pathinfo($filePath);

            switch ($pathinfo['extension']) {
                case 'css':
                    $mime = 'text/css';
                    break;

                case 'js':
                    $mime = 'application/javascript';
                    break;

                default:
                    $mime = mime_content_type($filePath);
                    break;
            }

            header('Content-Type: ' . $mime);

            $handle = fopen($filePath, 'rb');

            while (!feof($handle)) {
                echo fread($handle, (1024 * 1024));

                ob_flush();
                flush();
            }

            fclose($handle);

            exit;
        }

        throw new Exception\NotFound();
    }
}