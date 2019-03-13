<?php
/**
 * @author paulloft
 * @license MIT
 */

namespace Garden\Helpers;


class Files {
    /**
     * Make sure that a directory exists.
     *
     * @param string $dir The name of the directory.
     * @param int $mode The file permissions on the folder if it's created.
     * @param bool $recursive
     * @throws \Exception Throws an exception when {@link $dir} is a file.
     * @category Filesystem Functions
     */
    public static function touchdir($dir, $mode = 0777, $recursive = true)
    {
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \Exception("The specified directory already exists as a file. ($dir)", 400);
            }

            return;
        }

        if (!mkdir($dir, $mode, $recursive) && !is_dir($dir)) {
            throw new \Exception("Failed to create directory. ($dir)", 400);
        }
    }

    /**
     * Isolated include file
     * @param $path
     * @param array $variables
     * @return string
     */
    public static function getInclude($path, array $variables = []): string
    {
        $func = function ($path, array $data) {
            ob_start();
            extract($data, EXTR_OVERWRITE);

            include $path;

            $result = ob_get_contents();
            ob_end_clean();

            return $result;
        };

        return $func($path, $variables);
    }
}