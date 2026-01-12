<?php


namespace BMI\Plugin;
use BMI\Plugin\Traits\LoggerTrait;
use BMI\Plugin\Dashboard as Dashboard;
use BMI\Plugin\Checker\System_Info as SI;


require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'traits' . DIRECTORY_SEPARATOR . 'logger-trait.php';


/**
 * Class BMI_BackupMethodManager
 * 
 * This class is responsible for managing the backup method used by the plugin.
 * It able to change the backup method during runtime or globally by changing the plugin settings.
 * 
 * @package BMI\Plugin
 * @since 1.4.6
 * 
 */
class BMI_BackupMethodManager {

    use LoggerTrait;

    private $method;
    private $error;
    private $oldMethod;
    
    /**
     * BMI_BackupMethodManager constructor.
     * 
     * It determines the current backup method used by the plugin.
     */
    public function __construct() {
        $this->method = $this->determineBackupMethod();
    }
    
    /**
     * Determine the current backup method used by the plugin.
     * 
     * @return string|null The current backup method used by the plugin.
     */
    private function determineBackupMethod() {
        if (Dashboard\bmi_get_config('OTHER:EXPERIMENT:TIMEOUT')) {
            return BMI_METHOD_CURL;
        } elseif (Dashboard\bmi_get_config('OTHER:USE:TIMEOUT:NORMAL')) {
            return BMI_METHOD_DEFAULT;
        } elseif (Dashboard\bmi_get_config('OTHER:EXPERIMENT:TIMEOUT:HARD')) {
            return BMI_METHOD_BROWSER;
        }
        return null;
    }
    
    /**
     * Change the backup method used by the plugin.
     * 
     * @param string $method The new backup method to use.
     * @param bool $global Whether to change the backup method globally or not. (Will force change the method globally if method is cURL or browser-method)
     * @return bool True if the backup method was changed successfully, false otherwise.
     */
    public function changeBackupMethod($method, $global = true) {
        if (!in_array($method, [BMI_METHOD_CURL, BMI_METHOD_DEFAULT, BMI_METHOD_BROWSER])) {
            return false;
        }
    
        if ($this->method === $method) {
            return true;
        }
    
        if (false === $this->isMethodChangable($method)) {
            // $this->log("Changing backup to {$method} method is not possible");
            return false;
        }
    
        if ($method === BMI_METHOD_BROWSER || $method === BMI_METHOD_CURL || $global) {
            // $this->log("Changing backup to {$method} method with global settings");
            if (!$this->applyMethodSettings($method, true)) {
                // $this->log("Changing backup to {$method} method failed");
                return false;
            }
        }

        if ($this->applyMethodSettings($method)) {
            $this->log("Backup method changed to {$method}");
            $this->oldMethod = $this->method;
            $this->method = $method;
            return true;
        }
    
        return false;
    }
    
    /**
     * Check if the backup method can be changed to the specified method.
     * 
     * @param string $method The backup method to check.
     * @return bool
     */
    public function isMethodChangable($method) {
        switch ($method) {
            case BMI_METHOD_CURL:
                require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'check' . DIRECTORY_SEPARATOR . 'system_info.php';
                return SI::is_curl_work();
            case BMI_METHOD_DEFAULT:
            case BMI_METHOD_BROWSER:
                return true;
            default:
                return false;
        }
    }
    
    /**
     * Apply the settings for the specified backup method.
     * 
     * @param string $method The backup method to apply settings for.
     * @param bool $global Whether to apply the settings globally or not.
     * @return bool True if the settings were applied successfully, false otherwise.
     */
    private function applyMethodSettings($method, $global = false) {
        if ($global) {
            $settings = [
                BMI_METHOD_CURL => ['OTHER:EXPERIMENT:TIMEOUT' => true, 'OTHER:USE:TIMEOUT:NORMAL' => false, 'OTHER:EXPERIMENT:TIMEOUT:HARD' => false],
                BMI_METHOD_DEFAULT => ['OTHER:EXPERIMENT:TIMEOUT' => false, 'OTHER:USE:TIMEOUT:NORMAL' => true, 'OTHER:EXPERIMENT:TIMEOUT:HARD' => false],
                BMI_METHOD_BROWSER => ['OTHER:EXPERIMENT:TIMEOUT' => false, 'OTHER:USE:TIMEOUT:NORMAL' => false, 'OTHER:EXPERIMENT:TIMEOUT:HARD' => true],
            ];
            
        } else {
            $settings = [
                BMI_METHOD_CURL => ['bmi_legacy_version' => false, 'bmi_function_normal' => false, 'bmi_legacy_hard_version' => true],
                BMI_METHOD_DEFAULT => ['bmi_legacy_version' => true, 'bmi_function_normal' => true, 'bmi_legacy_hard_version' => true],
                BMI_METHOD_BROWSER => ['bmi_legacy_version' => true, 'bmi_function_normal' => false, 'bmi_legacy_hard_version' => false],
            ];
        }
    
        return $this->configureTimeouts($settings[$method], $global);
    }
    
    /**
     * Configure the settings for the specified backup method.
     * 
     * @param array $settings The settings to configure.
     * @param bool $global Whether to configure the settings globally or not.
     * @return bool
     */
    private function configureTimeouts($settings, $global) {
        $this->configureConstantFilters($settings);
        return $global ? $this->configurePluginSettings($settings) : true;
    }

    /**
     * Configure the plugin settings.
     * 
     * @param array $settings The settings to configure.
     * @return bool
     */
    private function configurePluginSettings($settings) {
        $result = true;
        foreach ($settings as $key => $value) {
            if (!Dashboard\bmi_set_config($key, $value)) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Configure the constant filters.
     * 
     * @param array $settings The settings to configure.
     * @return bool always return true.
     */
    private function configureConstantFilters($settings) {
        foreach ($settings as $filterName => $returnValue) {
            add_filter($filterName, '__return_' . ($returnValue ? 'true' : 'false'));
        }
        return true;
    }
    
    /**
     * Get the current backup method used by the plugin.
     * 
     * @return string The current backup method used by the plugin.
     */
    public function currentMethod() {
        return $this->method;
    }

    /**
     * Get the old backup method used by the plugin.
     * 
     * @return string|null The old backup method used by the plugin.
     */
    public function oldMethod() {
        return $this->oldMethod;
    }

    /**
     * Temporary disable cli during current request.
     * 
     * @param bool $global Whether to disable the cli globally or not.
     * @return bool
     */
    public function disableCLI($global = true) {
        add_filter('bmi_cli_enabled', '__return_false');
        if ($global) {
            return Dashboard\bmi_set_config('OTHER:CLI:DISABLE', true);
        }
        return true;
    }

}