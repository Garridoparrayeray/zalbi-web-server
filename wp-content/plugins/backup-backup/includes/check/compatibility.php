<?php
/**
 * This PHP code is designed to check the compatibility of the server environment with certain requirements.
 * It uses the Strategy Design Pattern to encapsulate the different compatibility checks into separate classes.
 */

// Namespace
namespace BMI\Plugin\Checker;

if (!defined('ABSPATH')) exit;

// Use
use BMI\Plugin\Backup_Migration_Plugin as BMP;



/**
 * The Comparision interface defines the method for comparing a value with a recommendation.
 */
interface Comparision {

    /**
     * Compare the value with the recommendation.
     * @param mixed $value The value to compare.
     * @param mixed $recommendation The recommendation to compare with.
     * @return bool True if the value is compatible with the recommendation, false otherwise.
     */
    public function compare($value, $recommendation);
}

/**
 * The Version_Comparision class implements the Comparision interface for version comparisons.
 */
class Version_Comparision implements Comparision {

    protected $operator;

    public function __construct($operator = '>=') {
        $this->operator = $operator;
    }


    public function compare($value, $recommendation) {
        return version_compare($value, $recommendation, $this->operator);
    }
}

/**
 * The String_Comparision class implements the Comparision interface for string comparisons.
 */
class String_Comparision implements Comparision {
    public function compare($value, $recommendation)
    {
        return strpos($value, $recommendation) !== false;
    }
}

/**
 * The In_Comparision class implements the Comparision interface for checking if a value is in a set of recommendations.
 */
class In_Comparision implements Comparision {
    public function compare($value, $recommendation)
    {
        foreach ($recommendation as $rec) {
            if (strpos($value, $rec) !== false) {
                return true;
            }
        }
        return false;
    }
}

/**
 * The Int_Comparision class implements the Comparision interface for integer comparisons.
 */
class Int_Comparision implements Comparision {
    public function compare($value, $recommendation)
    {
        if (is_string($value)) {
            $value = intval($value);
            $recommendation = intval($recommendation);
        }
        return $value >= $recommendation;
    }
}

/**
 * The Bool_Comparision class implements the Comparision interface for boolean comparisons.
 */
class Bool_Comparision implements Comparision {
    public function compare($value, $recommendation)
    {
        return boolval($value) === boolval($recommendation);
    }
}

/**
 * The Compatibility_Attribute abstract class defines the structure for a compatibility attribute.
 * It uses a Comparision object to compare a system value with a recommendation.
 */
abstract class Compatibility_Attribute {

    /**
     * The key to access the system value.
     * @var string
     */
    protected $key;

    /**
     * The recommendation to compare with.
     * @var mixed
     */
    protected $recommendation;

    /**
     * The Comparision object to use for the comparison.
     * @var Comparision
     */
    protected $comparision;

    /**
     * The error message for the compatibility check.
     * @var string
     */
    protected $error_message;

    /**
     * Constructor
     * @param mixed $recommendation The recommendation to compare with.
     * @param string|null $key The key to access the system value. (if null, the compatability check will be determined by the checkValue method)
     * @param Comparision $comparision The Comparision object to use for the comparison.
     */
    public function __construct($recommendation, $key, $comparision)
    {
        $this->recommendation = $recommendation;
        $this->key = $key;
        $this->comparision = $comparision;
    }

    /**
     * Check if the system value is compatible with the recommendation.
     * @param array $system The system information.
     * @return bool True if the system value is compatible with the recommendation, false otherwise.
     */
    public function isCompatible($system){
        if ($this->keyExists($system)) {
            return $this->comparision->compare($system[$this->key], $this->recommendation);
        }
        return false;
    }

    /**
     * Check if the key exists in the system information.
     * @param array $system The system information.
     * @return bool True if the key exists, false otherwise.
     */
    protected function keyExists($system) {
        return isset($system[$this->key]);
    }

    /**
     * Set the error message for the compatibility check.
     * @param string $message The error message.
     */
    function setErrorMessage($message){
        $this->error_message = $message;
    }
    
    /**
     * Get the error message for the compatibility check.
     * @return string The error message.
     */
    function getErrorMessage(){
        return $this->error_message;
    }
}

/**
 * The KeepAlive_Timeout class extends Compatibility_Attribute to check the KeepAlive timeout compatibility.
 * Note: The checkValue method is not implemented yet.
 */
class KeepAlive_Timeout extends Compatibility_Attribute {

    function __construct($recommendation, $key, $comparision)
    {
        parent::__construct($recommendation, $key, $comparision);
        $this->error_message = __("The KeepAlive is not compatible. The recommended value is %s1.", 'backup-backup');
        $this->error_message= str_replace(
            ['%s1'],
            [$this->recommendation],
            $this->error_message
        );
    }
    // public function isCompatible($system) {
    //     //TODO: implement the check        
    // }
   
}


class CURL_Enabled extends Compatibility_Attribute {

    function __construct($recommendation, $key, $comparision)
    {
        parent::__construct($recommendation, $key, $comparision);
        $this->error_message = __("The CURL is not enabled. It is recommended to enable it.", 'backup-backup');
    }

    public function isCompatible($system) {
        return $this->comparision->compare(System_Info::is_curl_work(), $this->recommendation);

    }
}

class PHP_CLI_Enabled extends Compatibility_Attribute {

    function __construct($recommendation, $key, $comparision)
    {
        parent::__construct($recommendation, $key, $comparision);
        $this->error_message = __("The PHP CLI is not active. It is recommended to enable it.", 'backup-backup');
    }

    // return false if php_cli is not enabled and user use default backup method
    public function isCompatible($system) {
        return $this->comparision->compare(System_Info::is_php_cli_runnable(), $this->recommendation);
    }
}

class Disk_Space extends Compatibility_Attribute {

    protected $available_space;

    public function __construct($recommendation, $key, $comparision)
    {
        parent::__construct($recommendation, $key, $comparision);
        $this->available_space = $this->getDiskAvaialbleSpace();
        $this->error_message = __("The minimum required disk space is %s1, while the available space is %s2.", 'backup-backup');
        $this->error_message = str_replace(
            ['%s1', '%s2'],
            [BMP::humanSize(intval($this->recommendation)), BMP::humanSize(intval($this->available_space))],
            $this->error_message
        );
    }
    public function isCompatible($system) {
        return $this->comparision->compare(intval($this->available_space), intval($this->recommendation));
    }

    public function getDiskAvaialbleSpace(){

        return $this->getDiskAvaialbleSpaceByHARDWay();
    }

    public function getDiskAvaialbleSpaceByHARDWay() {

        $file = BMI_BACKUPS . '/' . '.space_check';
        try {
            $size = $this->recommendation;
            $fh = fopen($file, 'w');
            while($size > 0){
                $chunk = 1024;
                fputs($fh, str_pad('', min($chunk, $size)));
                $size -= $chunk;
            }
            fclose($fh);

            $fs = filesize($file);
            @unlink($file);

            return $fs;


        } catch (\Exception $e) {
            if (file_exists($file)){
                $fileSize  = filesize($file);
                unlink($file);
                return $fileSize;
            }

        } catch (\Throwable $e) {
            if (file_exists($file)){
                $fileSize = filesize($file);
                unlink($file);
                return $fileSize;
            } 

        }
        return false;
      
    }
}

class Normal_Attribute extends Compatibility_Attribute{
    
    function __construct($recommendation, $key, $comparision, $error_message = '')
    {
        parent::__construct($recommendation, $key, $comparision);
        $this->error_message = $error_message != '' ? $error_message : __("The %s1 is not compatible. The recommended value is %s2.", 'backup-backup');
        $recommendation = $this->recommendation;
        if (is_array($recommendation)) {
            $recommendation = implode('/', $recommendation);
        }
        $this->error_message= str_replace(
            ['%s1', '%s2'],
            [$this->key, $recommendation],
            $this->error_message
        );
        
    }
}

/**
 * Check if any incompatible plugins are active and ask the user to temporarily deactivate them.
 */
class Incompatible_Plugins extends Compatibility_Attribute {
    protected $incompatiblePlugins;

    public function __construct($incompatiblePlugins, $key, $comparison)
    {
        parent::__construct($incompatiblePlugins, $key, $comparison);
        $this->incompatiblePlugins = $incompatiblePlugins;
    }

    public function isCompatible($system) {
        // incompatiblePlugins = ['plugin1', 'plugin2', ...]
        $activePlugins = $system['wp_active_plugins_info']; // [ ['name' => 'Plugin 1', 'version' => '1.0', 'slug' => 'plugin1'], ... ]
        $incompatibleActivePlugins = array_filter($activePlugins, function($plugin) {
            return in_array($plugin['slug'], $this->incompatiblePlugins);
        });
        
        $isCompatible = empty($incompatibleActivePlugins);

        $this->error_message = $this->generateErrorMessage($incompatibleActivePlugins);
        return $isCompatible;
    }

    protected function generateErrorMessage($incompatibleActivePlugins) {
        $count = count($incompatibleActivePlugins);

        if ($count === 0) {
            return '';
        }

        $pluginList = $this->formatPluginList($incompatibleActivePlugins);

        if ($count === 1) {
            return sprintf(
                __("We've detected that the plugin %s is currently active and may interfere with our process. Please temporarily deactivate it and try again.", 'backup-backup'),
                $pluginList
            );
        }

        return sprintf(
            __("We've detected that the following plugins are currently active and may interfere with our process: %s. Please temporarily deactivate them and try again.", 'backup-backup'),
            $pluginList
        );
    }

    protected function formatPluginList($incompatibleActivePlugins) {
        $plugins = array_map(function($plugin) {
            return "<strong>". $plugin['name'] ."</strong>";
        }, $incompatibleActivePlugins);

        if (count($plugins) > 1) {
            $lastPlugin = array_pop($plugins);
            return implode(', ', $plugins) . ' ' . __('and', 'backup-backup') . ' ' . $lastPlugin;
        }

        return reset($plugins);
    }
}


/**
 * The Compatibility class is used to add compatibility strategies and check the compatibility of the system.
 */
class Compatibility {
    private $attrs = [];
    protected $errors = [];
    protected $mainReasonFound = false;
    protected $system_info;

    protected $for;

    /**
     * Constructor to initialize the system information and add default compatibility strategies.
     */
    public function __construct($for = 'backup') {
        require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'check' . DIRECTORY_SEPARATOR . 'system_info.php';
        $system = new System_Info();
        $this->system_info = $system->to_array();
        $this->for = $for;
        $this->addDefaultStrategies();
        $this->addMoreRecommendations();
    }

    /**
     * Add default compatibility strategies based on the type of operation.
     */
    public function addDefaultStrategies() {
        $this->addStrategy(new Normal_Attribute('5.5', 'mysql_version', new Version_Comparision(), __("MySQL version is not compatible. recommended to use version 5.5+.", 'backup-backup')));
        $this->addStrategy(new Normal_Attribute(['Apache', 'Nginx'], 'web_server_name', new In_Comparision(), __("We recommend using Apache/Nginx server type.", 'backup-backup')));
        $this->addStrategy(new Normal_Attribute('8.0', 'php_version_full', new Version_Comparision('<'), __("Your site is on PHP 8+, and some of your plugins are only compatible with older PHP versions, causing issues during the backup creation. Either disable those plugins or temporarily change to an earlier PHP version.", 'backup-backup')));
        $this->addStrategy(new Incompatible_Plugins(['wordfence', 'security-ninja'], null, null));

        $max_execution_time = new Normal_Attribute('300', 'php_max_execution_time', new Int_Comparision());
        $max_execution_time_error = __("PHP max execution time is %s1. The recommended value is %s2.", 'backup-backup');
        $max_execution_time_error = str_replace(
            ['%s1', '%s2'],
            [$this->system_info['php_max_execution_time'], '300'],
            $max_execution_time_error
        );
        $max_execution_time->setErrorMessage($max_execution_time_error);
        $this->addStrategy($max_execution_time);
        

        if ($this->for == 'backup') {
            $this->addBackupDefaultStrategies();
        } else if ($this->for == 'migration') {
            $this->addMigrationDefaultStrategies();
        }
    }

    /**
     * Add default compatibility strategies for migration.
     */
    public function addMigrationDefaultStrategies() {
    }

    /**
     * Add default compatibility strategies for backup.
     */
    public function addBackupDefaultStrategies() {

        $this->addStrategy(new CURL_Enabled(true, null, new Bool_Comparision()));
    }

    /**
     * Add more recommendations based on verbose in log.
     * @return void
     */
    public function addMoreRecommendations() {
        // e.g.
        // $this->addRecommendation('missing space.', __("The disk space is not enough. Please free up some space.", 'backup-backup'));
        $requiredSpace = get_option('bmi_required_space', false);
        if (is_numeric($requiredSpace) && intval($requiredSpace) > 0) {
            $message = __("There is not enough free space on the server. Please secure more free space (%s1) and then try to run the process again.", 'backup-backup');
            $message = str_replace(
                ['%s1'],
                [BMP::humanSize(intval($requiredSpace))],
                $message
            );
            if ($this->addRecommendation('not_enough_space', $message)) {
                delete_option('bmi_required_space');
                $this->mainReasonFound = true;
            }
        }
        if ($this->addRecommendation('error_during_downloading_backup', __("Please upload the backup file manually to this site and start a Restoration. You can download and upload backups on the plugin screen “Manage & Restore Backups”.", 'backup-backup'))) {
            $this->mainReasonFound = true;
        }
        if ($this->for == 'backup') {
            $this->addBackupRecommendations();
        } else if ($this->for == 'migration') {
            $this->addMigrationRecommendations();
        }
    }

    public function addMigrationRecommendations() {
    }

    public function addBackupRecommendations() {
    }

    /**
     * Add recommendation based on vebose in log
     * @param string $verbose The verbose in log
     * @param string $the message to show
     * @return bool True if the the recommendation is added, false otherwise.
     */
    public function addRecommendation($verbose, $message) {
        $file = dirname(BMI_BACKUPS) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'latest' . ($this->for == 'backup' ? '' : '_migration') . '.log';
        $content = file_get_contents($file);
        $pattern = '#^\[VERBOSE\] \[[0-9-]+ [0-9:]+\] ' . $verbose . '#mi'; // e.g. [VERBOSE] [2021-12-31 23:59:59] missing space.
        if (preg_match($pattern, $content, $matches)) {
            array_push($this->errors, $message);
            return true;
        }
        return false;

    }
    /**
     * Add a compatibility strategy.
     * @param Compatibility_Attribute $strategy The compatibility strategy to add.
     */
    public function addStrategy( Compatibility_Attribute $strategy) {
        array_push($this->attrs, $strategy);
    }

    /**
     * Check the compatibility of the system.
     * @return array The errors found during the compatibility check.
     */
    public function check() {
        if ($this->mainReasonFound) {
            return $this->errors;
        }

        foreach ($this->attrs as $attribute) {
            if(!$attribute->isCompatible($this->system_info)) {
                array_push($this->errors, $attribute->getErrorMessage());
            }
        }
        return $this->errors;
    }

    /**
     * Get backup size from backup log.
     * @return int $bytes 
     */
    public function getBackupSize() {
        if(current_user_can('manage_options') && current_user_can('administrator')) {
            return BMP::getRecentSize() * 1.4;
        }
    }
    
    /**
     * Get whether a main reason for incompatibility was found.
     * @return bool True if a main reason was found, false otherwise.
     */
    public function mainReasonFound() {
        return $this->mainReasonFound;
    }
    
}

