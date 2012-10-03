<?php
/**
 * KoolMinify Console Task
 *
 * @author Elze Kool
 * @copyright Elze Kool, Kool Software en Webdevelopment
 *
 * @package KoolMinify
 **/

namespace Console;

/**
 * KoolMinify Console Task
 *
 * @author Elze Kool
 * @copyright Elze Kool, Kool Software en Webdevelopment
 *
 * @package KoolMinify
 **/
class KoolMinifyTask implements \KoolDevelop\Console\ITask
{
    /**
     * Default command
     *
     * @return void
     */
    public function index() {
        $this->execute(APP_PATH);
    }

    /**
	 * Base Path
	 *
	 * @var string
	 */
	private $basepath;

	/**
	 * Regular Expression of files to parse
	 *
	 * @var string
	 */
	private $files_to_parse = '/^[^\.]{1}(.*)\.php$/';

	/**
	 * Regular Expression of files to skip
	 *
	 * @var string
	 */
	private $files_to_exclude = '/^$/';

	/**
	 * Regular Expression of folders to parse
	 *
	 * @var string
	 */
	private $dirs_to_parse = '/^[^\.]{1}(.*)$/';

	/**
	 * Regular Expression of files to skip
	 * @var string
	 */
	private $dirs_to_exclude = '/^(tests|public_html)(.*)/';

	/**
	 * Template generation start
	 *
	 * @param string $basepath Base Path
	 *
	 * @return void
	 */
	private function execute($basepath) {
		$this->basepath = $basepath;
		$matches = $this->processFolder('/');
    
        $parsed = array();
        
        foreach($matches as $match) {
            
            // Get filename, removing starting and ending quotes
            $filename = stripslashes(substr($match['filename'], 1, -1));                        
            if (substr($filename,0, 1) != '/') {
                $filename = '/' . $filename;
            }
            
            if (in_array($filename, $parsed)) {
                continue;
            }            
            
            $parsed[] = $filename;
            
            // Check if file is a JS file and not already minified
            if (preg_match('/\.js$/', $filename) == 0) {
                continue;
            } else if (preg_match('/\.min\.js$/', $filename) != 0) {
                continue;
            }
            
            // Check if file exists
            $full_filename = APP_PATH . DS . 'public_html' . str_replace('/', DS , $filename);
            if (!file_exists($full_filename)) {
                continue;
            }
            
            // Minify code
            $code = $this->_minify(file_get_contents($full_filename));
            
            if (!empty($code)) {
                $minified_filename = substr($full_filename, 0, -3) . '.min.js';
                file_put_contents($minified_filename, $code);                
                echo $minified_filename . "\n";            
            }
            
        }
        
	}
    
    /**
     * Minify Code using Google Closure Compile
     * 
     * @param string $code Code to minify
     * 
     * @return string Minified code
     */
    private function _minify($code) {
        
        $url = 'http://closure-compiler.appspot.com/compile';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
            'js_code' => $code,
            'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
            'output_format' => 'text',
            'output_info' => 'compiled_code'
        )));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch); 
        
        return $response;
    } 
    
    

	/**
	 * Proces folder and return found matches
	 *
	 * @param string $folder Folder
	 *
	 * @return array Matches
	 */
	private function processFolder($folder) {

		$matches = array();

		if (!is_dir($this->basepath . $folder)) {
			throw new \InvalidArgumentException(__f("Folder does not exsist.", 'koolminify'));
		}

		if (false !== ($handle = opendir($this->basepath . $folder))) {
			while (($folder_item = readdir($handle)) !== false) {
				if (is_dir($this->basepath . $folder . $folder_item) AND (preg_match($this->dirs_to_parse, $folder_item) > 0)  AND (preg_match($this->dirs_to_exclude, $folder_item) == 0)) {
					$foldermatches = $this->processFolder($folder . $folder_item . '/');
					$matches = array_merge($matches, $foldermatches);
				} else if (is_file($this->basepath . $folder . $folder_item) AND (preg_match($this->files_to_parse, $folder_item) > 0)  AND (preg_match($this->files_to_exclude, $folder_item) == 0)) {
					$filematches = $this->processFile($folder . $folder_item);
					$matches = array_merge($matches, $filematches);
				}
			}
		}

		return $matches;
	}

	/**
	 * Proces file and return found matches
	 *
	 * @param string $filename Filename
     * 
	 * @return array Matches
	 */
	private function processFile($filename) {

		if (!is_file($this->basepath . $filename)) {
			throw new \InvalidArgumentException(__f("File does not exsist.",'koolminify'));
		}

		echo $filename . "\n";

		if (strlen($file_contents = file_get_contents($this->basepath . $filename)) > 0) {
			$tokens = token_get_all($file_contents);
			if (count($tokens) > 0) {
				return $this->parseTokens($tokens, $filename);
			}
		}

		return array();
	}


	/**
	 * Proces function parameters
	 *
	 * @param int     $offset Offset of function
	 * @param mixed[] $tokens Tokens
	 *
	 * @return parameters
	 */
	private function parseFunction($offset, &$tokens) {

		$depth = -1;
		$pos = $offset;
		$strings = array();

		while (!(($tokens[$pos] == ')') AND ($depth == 0))) {

			$token = $tokens[$pos];
			$pos++;

			if ($token == '(') {
				$depth++;
				continue;
			} else if ($token == ')') {
				$depth--;
				continue;
			} else if ($depth == 0) {
				if (is_array($token) AND ($token[0] == T_CONSTANT_ENCAPSED_STRING)) {
					$strings[] = $token[1];
				}
			}
			if ($pos == count($tokens)) {
				break;
			}
		}

		return $strings;
	}

	/**
	 * Loop trough tokens and return array of matches
	 *
	 * @param mixed[] $tokens   Tokens
	 * @param string  $filename Filename for reference
     * 
	 * @return array Matches
	 */
	private function parseTokens(&$tokens, $filename) {

        // Functions with number of required parameters
        $parseFunctions = array(
            '__min' => 1
        );
        
		$matches = array();

		$tokenCount = count($tokens);

		for($i = 0; $i < ($tokenCount - 3); $i++) {

			if ((@$tokens[$i][0] == T_STRING)) {
				if ((@$tokens[$i+1] == '(')) {

					if (isset($parseFunctions[$tokens[$i][1]])) {
						$params = $this->parseFunction($i, $tokens);
						$required = $parseFunctions[$tokens[$i][1]];

                        if (count($params) >= $required) {
                            $matches[] = array(
                                'type' => $tokens[$i][1],
                                'ref' => $filename,
                                'filename' => $params[0]
                            );
                        }
                        
					}


				}
			}
		}

		// Geef resultaten terug
		return $matches;

	}


}