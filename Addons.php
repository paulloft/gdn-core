<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden;

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
class Addons {
    /// Constants ///
    const K_BOOTSTRAP = 'bootstrap'; // bootstrap path key
    const K_CONFIG    = 'config'; // config path key
    const K_CLASSES   = 'classes';
    const K_DIR       = 'dir';
    const K_INFO      = 'info'; // addon info key

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
     * @var array An array that maps class names to their fully namespaced class names.
     */
//    protected static $basenameMap;

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
     * @param string $addon_key If you supply an addon key then only that addon will be returned.
     * @param string $key Supply one of the Addons::K_* constants to get a specific key from the addon.
     * @return array Returns the addon with the given key or all available addons if no key is passed.
     */
    public static function all($addon_key = null, $key = null) {
        if (self::$all === null) {

            if(!self::$all = Gdn::cache('rough')->get('addons-all')) {
                self::$all = self::scanAddons();
                Gdn::cache('rough')->set('addons-all', self::$all);
            }
        }

        // The array should be built now return the addon.
        if ($addon_key === null) {
            return self::$all;
        } else {
            $addon = val(strtolower($addon_key), self::$all);
            if ($addon && $key) {
                return val($key, $addon);
            } elseif ($addon) {
                return $addon;
            } else {
                return null;
            }
        }
    }

    /**
     * An autoloader that will autoload a class based on which addons are enabled.
     *
     * @param string $classname The name of the class to load.
     */
    public static function autoload($classname) {
        list($fullClass, $path) = static::classMap($classname);
        if ($path) {
            require_once $path;
        }
    }

    /**
     * Gets/sets the base directory for addons.
     *
     * @param string $value Pass a value to set the new base directory.
     * @return string Returns the base directory for addons.
     */
    public static function baseDir($value = null) {
        if ($value !== null) {
            self::$baseDir = rtrim($value, '/');
        } elseif (self::$baseDir === null) {
            self::$baseDir = PATH_ADDONS;
        }

        return self::$baseDir;
    }


    /**
     * Start up the addon framework.
     *
     * @param array $enabled_addons An array of enabled addons.
     */
    public static function bootstrap($enabled_addons = null) {
        // Load the addons from the config if they aren't passed in.
        if (!is_array($enabled_addons)) {
            $enabled_addons = config('main', 'addons', array());
        }

        // Reformat the enabled array into the form: array('addon_key' => 'addon_key')
        $enabled_keys = array_keys(array_change_key_case(array_filter($enabled_addons)));
        $enabled_keys = array_combine($enabled_keys, $enabled_keys);
        self::$enabledKeys = $enabled_keys;
        self::$classMap = null; // invalidate so it will rebuild

        // Enable the addon autoloader.
        spl_autoload_register(array(get_class(), 'autoload'), true, false);

        // Bind all of the addon plugin events now.
        foreach (self::enabled() as $addon) {
            if (!isset($addon[self::K_CLASSES])) {
                continue;
            }
            foreach ($addon[self::K_CLASSES] as $className=>$path) {
                if (str_ends($className, 'hooks')) {
                    Event::bindClass($className);
                }
            }
        }

        self::baseDir();

        Event::bind('bootstrap', function () {
            $translations = Gdn::cache('rough')->get('translations');
            if ($translations) {
                Gdn::$translations = $translations;
            }
            // Start each of the enabled addons.
            foreach (self::enabled() as $key => $value) {
                static::startAddon($key);
            }

            if(!$translations) {
                Gdn::cache('rough')->set('translations', Gdn::$translations);
            }

        });
    }

    /**
     * A an array that maps class names to physical paths.
     *
     * @param string $classname An optional class name to get the path of.
     * @return array Returns an array in the form `[fullClassname, classPath]`.
     * If no {@link $classname} is passed then the entire class map is returned.
     * @throws \Exception Throws an exception if the class map is corrupt.
     */
    public static function classMap($classname = null) {
        if (self::$classMap === null) {
            // Loop through the enabled addons and grab their classes.
            $class_map = array();
            foreach (static::enabled() as $addon) {
                if (isset($addon[self::K_CLASSES])) {
                    $class_map = array_replace($class_map, $addon[self::K_CLASSES]);
                }
            }
            self::$classMap = $class_map;
        }

        // Now that the class map has been built return the result.
        if ($classname !== null) {
            $basename = trim($classname, '\\');

            $row = val($basename, self::$classMap);

            if ($row === null) {
                return ['', ''];
            } elseif (is_string($row)) {
                return [$classname, $row];
            } elseif (is_array($row)) {
                return  $row;
            } else {
                return ['', ''];
            }
        } else {
            return self::$classMap;
        }
    }

    /**
     * Get all of the enabled addons or a single addon from the enabled list.
     *
     * @param string $addon_key If you supply an addon key then only that addon will be returned.
     * @param string $key Supply one of the Addons::K_* constants to get a specific key from the addon.
     * @return array Returns the addon with the given key or all enabled addons if no key is passed.
     * @throws \Exception Throws an exception if {@link Addons::bootstrap()} hasn't been called yet.
     */
    public static function enabled($addon_key = null, $key = null) {
        // Lazy build the enabled array.
        if (self::$enabled === null) {
            // Make sure the enabled addons have been added first.
            if (self::$enabledKeys === null) {
                throw new \Exception("Addons::boostrap() must be called before Addons::enabled() can be called.", 500);
            }

            if (self::$all !== null || self::$sharedEnvironment) {
                // Build the enabled array by filtering the all array.
                self::$enabled = array();
                foreach (self::all() as $key => $row) {
                    if (isset($key, self::$enabledKeys)) {
                        self::$enabled[$key] = $row;
                    }
                }
            } else {
                // Build the enabled array by walking the addons.
                if(!self::$enabled = Gdn::cache('rough')->get('addons-enabled')) {
                    self::$enabled = self::scanAddons(null, self::$enabledKeys);
                    Gdn::cache('Rough')->set('addons-enabled', self::$enabled);
                }
            }
        }

        // The array should be built now return the addon.
        if ($addon_key === null) {
            return self::$enabled;
        } else {
            $addon = val(strtolower($addon_key), self::$enabled);
            if ($addon && $key) {
                return val($key, $addon);
            } elseif ($addon) {
                return $addon;
            } else {
                return null;
            }
        }
    }

    /**
     * Return the info array for an addon.
     *
     * @param string $addon_key The addon key.
     * @return array|null Returns the addon's info array or null if the addon wasn't found.
     */
    public static function info($addon_key) {
        $addon_key = strtolower($addon_key);

        // Check the enabled array first so that we don't load all addons if we don't have to.
        if (isset(self::$enabledKeys[$addon_key])) {
            return static::enabled($addon_key, self::K_INFO);
        } else {
            return static::all($addon_key, self::K_INFO);
        }
    }

    /**
     * Scan an addon directory for information.
     *
     * @param string $dir The addon directory to scan.
     * @param array &$addons The addons array.
     * @param array $enabled An array of enabled addons or null to scan all addons.
     * @return array Returns an array in the form [addonKey, addonInfo].
     */
    protected static function scanAddonRecursive($dir, &$addons, $enabled = null) {
        $dir = rtrim($dir, '/');
        $addonKey = strtolower(basename($dir));

        // Scan the addon if it is enabled.
        if ($enabled === null || in_array($addonKey, $enabled)) {
            list($addonKey, $addon) = static::scanAddon($dir);
        } else {
            $addon = null;
        }

        // Add the addon to the collection array if one was supplied.
        if ($addon !== null) {
            $addons[$addonKey] = $addon;
        }

        // Recurse.
        $addon_subdirs = array('/addons');
        foreach ($addon_subdirs as $addon_subdir) {
            if (is_dir($dir.$addon_subdir)) {
                static::scanAddons($dir.$addon_subdir, $enabled, $addons);
            }
        }

        return array($addonKey, $addon);
    }

    /**
     * Scan an individual addon directory and return the information about that addon.
     *
     * @param string $dir The path to the addon.
     * @return array An array in the form of `[$addon_key, $addon_row]` or `[$addon_key, null]` if the directory doesn't
     * represent an addon.
     */
    protected static function scanAddon($dir) {
        $dir = rtrim($dir, '/');
        $addon_key = strtolower(basename($dir));
        $settings = $dir.'/settings';

        // Look for the addon info array.
        $info_path = $settings.'/about.json';
        $info = false;
        if (file_exists($info_path)) {
            $info = json_decode(file_get_contents($info_path), true);
        }
        if (!$info) {
            $info = array();
        }
        array_touch('name', $info, $addon_key);
        array_touch('version', $info, '0.0');

        $settingsFiles = array(self::K_BOOTSTRAP, self::K_CONFIG);

        foreach ($settingsFiles as $file) {
            $$file = self::checkFile($dir.'/settings', $file);
        }

        // Scan the appropriate subdirectories  for classes.
        $subdirs = array('', '/library', '/modules', '/settings');
        $classes = array();
        foreach ($subdirs as $subdir) {
            // Get all of the php files in the subdirectory.
            $paths = glob($dir.$subdir.'/*.php');
            foreach ($paths as $path) {
                $decls = static::scanFile($path);
                foreach ($decls as $namespace_row) {
                    if (isset($namespace_row['namespace']) && $namespace_row) {
                        $namespace = rtrim($namespace_row['namespace'], '\\').'\\';
                        $namespace_classes = $namespace_row['classes'];

                        foreach ($namespace_classes as $class_row) {
                            $classes[$namespace.$class_row['name']] = $path;
                        }
                    } else {
                        $classes[$namespace_row[0]['name']] = $path;
                    }
                }
            }
        }

        $addon = array(
            self::K_BOOTSTRAP => $bootstrap,
            self::K_CONFIG    => $config,
            self::K_CLASSES   => $classes,
            self::K_DIR       => $dir,
            self::K_INFO      => $info
        );

        return array($addon_key, $addon);
    }

    protected static function checkFile($dir, $filename)
    {
        $file = $dir.'/'.$filename.'.php';
        return (!file_exists($file) ? null : $file);
    }

    /**
     * Scan a directory for addons.
     *
     * @param string $dir The directory to scan.
     * @param array $enabled An array of enabled addons in the form `[addonKey => enabled, ...]`.
     * @param array &$addons The addons will fill this array.
     * @return array Returns all of the addons.
     */
    protected static function scanAddons($dir = null, $enabled = null, &$addons = null) {
        if (!$dir) {
            $dir = self::baseDir();
        }
        if ($addons === null) {
            $addons = array();
        }

        /* @var \DirectoryIterator */
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
     * @param string $file Path to file.
     * @return array Returns an empty array if no classes are found or an array with namespaces and
     * classes found in the file.
     * @see http://stackoverflow.com/a/11114724/1984219
     */
    protected static function scanFile($file) {
        $classes = $nsPos = $final = array();
        $foundNamespace = false;
        $ii = 0;

        if (!file_exists($file)) {
            return array();
        }

        $er = error_reporting();
        error_reporting(E_ALL ^ E_NOTICE);

        $php_code = file_get_contents($file);
        $tokens = token_get_all($php_code);
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if (!$foundNamespace && $tokens[$i][0] == T_NAMESPACE) {
                $nsPos[$ii]['start'] = $i;
                $foundNamespace = true;
            } elseif ($foundNamespace && ($tokens[$i] == ';' || $tokens[$i] == '{')) {
                $nsPos[$ii]['end'] = $i;
                $ii++;
                $foundNamespace = false;
            } elseif ($i - 2 >= 0 && $tokens[$i - 2][0] == T_CLASS && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING) {
                if ($i - 4 >= 0 && $tokens[$i - 4][0] == T_ABSTRACT) {
                    $classes[$ii][] = array('name' => $tokens[$i][1], 'type' => 'ABSTRACT CLASS');
                } else {
                    $classes[$ii][] = array('name' => $tokens[$i][1], 'type' => 'CLASS');
                }
            } elseif ($i - 2 >= 0 && $tokens[$i - 2][0] == T_INTERFACE && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING) {
                $classes[$ii][] = array('name' => $tokens[$i][1], 'type' => 'INTERFACE');
            }
        }
        error_reporting($er);
        if (empty($classes)) {
            return [];
        }

        if (!empty($nsPos)) {
            foreach ($nsPos as $k => $p) {
                $ns = '';
                for ($i = $p['start'] + 1; $i < $p['end']; $i++) {
                    $ns .= $tokens[$i][1];
                }

                $ns = trim($ns);
                $final[$k] = array('namespace' => $ns, 'classes' => $classes[$k + 1]);
            }
            $classes = $final;
        }
        return $classes;
    }

    /**
     * Start an addon.
     *
     * This function does the following:
     *
     * 1. Make the addon available in the autoloader.
     * 2. Run the addon's bootstrap.php if it exists.
     *
     * @param string $addon_key The key of the addon to enable.
     * @return bool Returns true if the addon was enabled. False otherwise.
     */
    public static function startAddon($addon_key) {
        $addon = static::enabled($addon_key);
        if (!$addon) {
            return false;
        }

        $rough = Gdn::cache('rough');

        // load config.
        if (!$rough->get('config-autoload')) {
            if($config_path = val(self::K_CONFIG, $addon)) {
                Config::load($addon_key, $config_path);
            }
        }

        // load translations
        if (!$rough->get('translations')) {
            $locale = c('main.locale', 'en_US');
            $locale_path = val('dir', $addon)."/locale/$locale.php";

            if (file_exists($locale_path)) {
                $translations = include $locale_path;
                Gdn::$translations = array_merge(Gdn::$translations, $translations);
            }
        }

        // Run the class' bootstrap.
        if ($bootstrap_path = val(self::K_BOOTSTRAP, $addon)) {
            include_once $bootstrap_path;
        }

        return true;
    }
}