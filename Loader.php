<?php
/**
 * KoolMinify Loader
 *
 * @author Elze Kool
 * @copyright Elze Kool, Kool Software en Webdevelopment
 *
 * @package KoolMinify
 **/

namespace KoolMinify;

/**
 * KoolMinify Loader
 * 
 * Assists in loading minified JS files
 * 
 * @author Elze Kool
 * @copyright Elze Kool, Kool Software en Webdevelopment
 *
 * @package KoolMinify
 */
class Loader 
{

    /**
     * Loader instance
     * @var \KoolMinify\Loader
     */
    private static $Instance;
    
    /**
     * Get \KoolMinify\Loader instance
     *
     * @return \KoolMinify\Loader
     */
    public static function getInstance() {
        if (self::$Instance === null) {
            self::$Instance = new self();
        }
        return self::$Instance;
    }
    
    /**
     * Get filename of minified versions
     * 
     * Finds the minified version of a filename relative 
     * to the public_html (web root) path and returns it's filename.
     * 
     * @param string $filename Filename relative to public_html
     * 
     * @return string Filename of minified version, relative to public_html
     */
    public function getMinifiedFilename($filename) {   
        
        if (substr($filename,0, 1) != '/') {
            $filename = '/' . $filename;
        }
        
        // Check if file is a JS file and not already minified
        if (preg_match('/\.js$/', $filename) == 0) {
            return $filename;
        } else if (preg_match('/\.min\.js$/', $filename) != 0) {
            return $filename;
        }
        
        // Check if file exists
        $full_filename = APP_PATH . DS . 'public_html' . str_replace('/', DS , $filename);
        if (!file_exists($full_filename)) {
            return $filename;
        }        
        
        // Check if minified version exists
        $minified_filename = substr($full_filename, 0, -3) . '.min.js';        
        if (!file_exists($minified_filename)) {
            return $filename;
        }
        
        // Saveguard that minified version must be newer
        if (filemtime($full_filename) > filemtime($minified_filename)) {
            return $filename;
        }
        
        // Return filename
        return substr($filename, 0, -3) . '.min.js';
    }
    
}
