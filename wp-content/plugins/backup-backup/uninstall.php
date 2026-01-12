<?php

  // Namespace
  namespace BMI\Plugin\Uninstaller;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  // Get config file
  $configFile = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'backup-migration-config.php';

  if (!file_exists($configFile)) {
    return;
  }

  $config = file_get_contents($configFile);
  $config = json_decode(substr($config, 8), true);


  $deleteBackups = $config['OTHER:UNINSTALL:BACKUPS'];
  $deleteConfigs = $config['OTHER:UNINSTALL:CONFIGS'];


  if ($deleteBackups === 'true' || $deleteBackups === true) {
      $backupsPath = $config['STORAGE::LOCAL::PATH'];
      $backupsPath = $backupsPath . DIRECTORY_SEPARATOR . 'backups';

    if (file_exists($backupsPath) && is_dir($backupsPath)) {

      $files = scandir($backupsPath);
      for ($i = 0; $i < sizeof($files); ++$i) {

        $file = $backupsPath . DIRECTORY_SEPARATOR . $files[$i];
        if (is_file($file) && !in_array($files[$i], ['.', '..'])) {
          @unlink($file);
        }

      }

      $files = scandir($backupsPath);
      if (sizeof($files) <= 2) rmdir($backupsPath);

    }
  }

  if ($deleteConfigs === 'true' || $deleteConfigs === true) {
    $configFile = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'backup-migration-config.php';

    if (file_exists($configFile)) {
      @unlink($configFile);
    }

    global $wpdb;


    $free_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE '%bmi_%' OR option_name LIKE '%bmip_%' OR option_name LIKE '%bmi_pro_%'" );

    foreach( $free_options as $option ) {
        delete_option( $option->option_name );
    }
    
  }