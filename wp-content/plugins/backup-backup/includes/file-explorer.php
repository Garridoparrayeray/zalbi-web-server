<?php


namespace BMI\Plugin;



if (!defined('ABSPATH')) exit;

use BMI\Plugin\Backup_Migration_Plugin as BMP;
use BMI\Plugin\BMI_Logger as Logger;


/**
 * File_Queue is an interface that define the methods that should be implemented by the file container
 */
interface File_Queue{
    
    /**
     * Insert the file in the container
     * @param string $path
     * @return void
     */
    public function insert_file($path); 

    /**
     * Get the largest n files and directories
     * @param int $n
     * @return array of largest n files and directories [['path', 'type', 'size'], ...]
     */
    public function getLargest($n); // Get the largest n files and directories
}


/**
 * BMI_File_Queue is a priority queue that store the files and directories and sort them based on the size
 */
class BMI_File_Queue extends \SplPriorityQueue implements File_Queue{

    const DIR_TYPE = 0;
    const FILE_TYPE = 1;

    const PATH_INDEX = 0;
    const TYPE_INDEX = 1;
    const SIZE_INDEX = 2;


    const UNABLE_TO_READ = -1;

    public function __construct(){
        $this->setExtractFlags(\SplPriorityQueue::EXTR_DATA);
    }


    /**
     * Insert the file in the container determine the type of the file and size and insert it in array ['path', 'type', 'size']
     * @param string $path
     * @return void
     */
    public function insert_file($path){
        if (strpos($path, '..') !== false || strpos($path, '/.') !== false){
            return;
        }
        $path = BMP::fixSlashes($path);
        if (is_dir($path)){
            $size = $this->getDirSize($path);
            if ($size == self::UNABLE_TO_READ){
                Logger::debug('Unable to read the directory: ' . $path);
                return;
            }
            $this->insert([$path, self::DIR_TYPE, $size], $size);
        }else{
            $this->insert([$path, self::FILE_TYPE, filesize($path)], filesize($path));
        }
    }

    /**
     * Get the largest n files and directories
     * @param int $n
     * @return array of largest n files and directories [['path', 'type', 'size'], ...]
     */
    public function getLargest($n){
        $largest = [];
        for ($i = 0; $i < $n; $i++){
            if ($this->isEmpty()){
                break;
            }
            $largest[] = $this->extract();
        }
        return $largest;
    }



    /**
     * Get the size of the directory recursively using DirectoryIterator
     * @param string $path
     * @return int size of the directory
     */
    function getDirSize($path){
        $bytestotal = 0;
        $path = realpath($path);
        if($path!==false && $path!='' && file_exists($path)){
            try{
                new \DirectoryIterator($path);
            }catch(\Exception $e){
                return self::UNABLE_TO_READ;
            }
            foreach(new \DirectoryIterator($path) as $file){
                if($file->isFile()){
                    $bytestotal += $file->getSize();
                }
                else if(!$file->isDot() && $file->isDir()){
                    $bytestotal += $this->getDirSize($file->getPathname());
                }
            }
        }
        return $bytestotal;
    }

}


/**
 * BMI_File_Explorer is a class that provide the methods to scan the directory and get the list of largest files and directories
 * 
 * @package BMI\Plugin
 * @since 1.4.5
 * @version 1.0
 * @category Class
 * @see BMI_File_Queue
 * @method BMI_File_Queue scanDir($path) Get the list of largest 100 files and directories in the path
 * @method int isSub($dir, $path) Recursivly get if the directory is sub directory of given path or not and return the depth of the sub directory return -1 if not sub directory
 * 
 */
class BMI_File_Explorer {
    
    /**
    * Get the list of largest 100 files and directories in the path
    * don't include the ignored files and directories
    * @param string $path
    * @param array $ignored
    * @param File_Queue $queue
    * @return BMI_File_Queue of directories and files each element is an path and type and size [['html/wp-content/plugins', 'dir', 658745], ['html/wp-content/index.php', 'file', 0]], ...]
    * 
    */
    public static function scanDir($path, $ignored = [], $queue = null) {
        if ($queue == null){
            $queue = new BMI_File_Queue();
        }
        $handle = opendir($path);
        while (false !== ($entry = readdir($handle))) {
            
            $full_path = BMP::fixSlashes($path . '\\' . $entry);
            if (in_array($entry, $ignored) || in_array($full_path, $ignored) || $entry == '.' || $entry == '..') {
                continue;
            }
            $queue->insert_file($full_path);
            
        }
        return $queue;
    }

    /**
     * Recursivly get if the directory is sub directory of given path or not and return true or false
     * @param string $dir directory path
     * @param string $path path to check if the directory is sub directory of this path
     * @return int -1 if not sub directory, 0 is the same directory, depth of the sub directory
     */
    public static function isSub($dir, $path , $depth = 0){
        $path = str_replace('***ABSPATH***', ABSPATH, $path);
        $dir = str_replace('***ABSPATH***', ABSPATH, $dir);
        if (strpos($dir, $path) === false || strpos($dir, '..') !== false) {
            return -1;
        }

        if ($dir == $path) {
            return $depth;
        }

        return self::isSub(dirname($dir), $path, $depth + 1);
    }

    /**
     * Get the size of the directory recursively using DirectoryIterator
     * @param string $path
     * @return int size of the directory
     */
    public static function getDirSize($path){
        return (new BMI_File_Queue())->getDirSize($path);
    }
}