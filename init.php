<?php
/**
 * KoolMinify loader
 *
 * @author Elze Kool
 * @copyright Elze Kool, Kool Software en Webdevelopment
 *
 * @package KoolMinify
 **/

// Add Autoloader mappings
$autoloader = KoolDevelop\AutoLoader::getInstance();
$autoloader->addMapping('\\Console', dirname(__FILE__) . DS . 'console');
$autoloader->addVendor('KoolMinify');

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
function __min($filename) {        
    return \KoolMinify\Loader::getInstance()->getMinifiedFilename($filename);
}

?>