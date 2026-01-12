<?php

  // Namespace
  namespace BMI\Plugin\Scanner;

  // Use
  use BMI\Plugin\BMI_Logger AS Logger;
  use BMI\Plugin\Zipper\BMI_Zipper AS Zipper;
  use BMI\Plugin\Zipper\Zip AS Zip;
  use BMI\Plugin\External\BMI_External_Storage as ExternalStorage;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  /**
   * Main Backup Scanner Logic
   */
  class BMI_BackupsScanner {

    public function scanBackupDir($path) {

      $files = [];
      $dirs = new \DirectoryIterator($path);
      foreach ($dirs as $fileInfo) {

        if ($fileInfo->isDot()) continue;
        if ($fileInfo->isFile()) {
          if (in_array($fileInfo->getExtension(), ['zip', 'tar', 'tar.gz', 'gz', 'rar', '7zip', '7z'])) {

            $files[] = array(
              'filename' => $fileInfo->getFilename(),
              'path' => $path,
              'size' => $fileInfo->getSize()
            );

          } else if (strlen($fileInfo->getExtension()) == 6) { // Remove old partial backups e.g. abcdef.zip.g1wdas
            $extentions = explode('.', $fileInfo->getFilename());
            if (in_array($extentions[count($extentions) - 2],['zip', 'tar', 'tar.gz', 'gz', 'rar', '7zip', '7z'])) {
              if (!file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.running')) {
                @unlink($path . DIRECTORY_SEPARATOR . $fileInfo->getFilename());
              }
            }
          } else if ($fileInfo->getFilename() == '.space_check') {
            if (filemtime(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.space_check') < time() - 2 * MINUTE_IN_SECONDS) {
              @unlink($path . DIRECTORY_SEPARATOR . $fileInfo->getFilename());
            }
          }
        }

      }

      return $files;

    }

    public function removeOldCache() {
      $md5_file_summary_path = BMI_BACKUPS . DIRECTORY_SEPARATOR. 'md5summary.php';
      
      $md5summary = [];
      if (file_exists($md5_file_summary_path)) {
        $md5summary = file_get_contents($md5_file_summary_path);
        $md5summary = substr($md5summary, 18, -2);
        if (is_serialized($md5summary)) {
          $md5summary = maybe_unserialize($md5summary);
        }
      }
      
      foreach ($md5summary as $backupName => $md5files) {
        foreach ($md5files as $index => $md5) {
          if (!file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupName)) {
            if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . $md5 . '.json')) {
              @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . $md5 . '.json');
            }
            unset($md5summary[$backupName][$index]);
          }
        }
        
        if (sizeof($md5summary[$backupName]) == 0) {
          unset($md5summary[$backupName]);
        }
      }
      
      $cacheMd5String = "<?php exit; \$x = '" . serialize($md5summary) . "';";
      file_put_contents($md5_file_summary_path, $cacheMd5String);  
    }

    public function getManifestFromZip($zip_path, &$zipper) {

      if (!file_exists($zip_path)) return false;

      // Get manifest content
      $md5_file_summary_path = BMI_BACKUPS . DIRECTORY_SEPARATOR. 'md5summary.php';
      $zip_modification_time = filemtime($zip_path);
      $zip_name = basename($zip_path);

      $md5summary = [];
      if (file_exists($md5_file_summary_path)) {
        $md5summary = file_get_contents($md5_file_summary_path);
        $md5summary = substr($md5summary, 18, -2);
        if (is_serialized($md5summary)) {
          $md5summary = maybe_unserialize($md5summary);
        }
      }

      if (!is_array($md5summary)) {
        @unlink($md5_file_summary_path);
        $md5summary = [];
      }

      $md5s = [];
      $cached_md5 = false;
      if (isset($md5summary[$zip_name])) {

        $md5s = $md5summary[$zip_name];
        $latest = 0;
        $latest_md5 = false;
        for ($i = 0; $i < sizeof($md5s); ++$i) {
          $md5_file_path = BMI_BACKUPS . DIRECTORY_SEPARATOR . $md5s[$i] . '.json';
          if (file_exists($md5_file_path)) {
            $ftime = filemtime($md5_file_path);
            if ($ftime > $latest) {
              $latest = $ftime;
              $latest_md5 = $md5s[$i];
            }
          }
        }

        if ($latest_md5 != false) {
          $cached_md5 = $latest_md5;
        }

      } else {

        if ($zip_name) $md5summary[$zip_name] = [];
        else return false;

      }

      if ($cached_md5 != false) {
        $zip_md5 = $cached_md5;
        $md5_file_path = BMI_BACKUPS . DIRECTORY_SEPARATOR. $cached_md5 . '.json';
      } else {
        $zip_md5 = md5_file($zip_path);
        $md5_file_path = BMI_BACKUPS . DIRECTORY_SEPARATOR. $zip_md5 . '.json';
        if (!in_array($zip_md5, $md5summary[$zip_name])) {
          $md5summary[$zip_name][] = $zip_md5;
        }
      }

      $res = array();
      if (file_exists($md5_file_path)) {
        $manifest = json_decode(file_get_contents($md5_file_path));
      } else {
        $manifest = $zipper->getZipFileContent($zip_path, 'bmi_backup_manifest.json');
      }

      if ($manifest) {

        $res[] = $manifest->name . '#%&' . $zip_name;
        $res[] = $manifest->date;
        $res[] = $manifest->files;
        $res[] = $manifest->manifest;
        $res[] = @filesize($zip_path);
        if (!isset($manifest->is_locked)) {
          $manifest->is_locked = $zipper->is_locked_zip($zip_path) ? 'locked' : 'unlocked';
          $res[] = $manifest->is_locked;
        } else {
          $res[] = $manifest->is_locked;
        }
        $res[] = $manifest->cron;
        $res[] = $zip_md5;
        $res[] = sanitize_text_field($manifest->domain);

        if (!file_exists($md5_file_path)) {
          $manifest->abspath = $manifest->config->ABSPATH;
          $manifest->table_prefix = $manifest->config->table_prefix;
          unset($manifest->config);
          file_put_contents($md5_file_path, json_encode($manifest));
        } else touch($md5_file_path);

        $cacheMd5String = "<?php exit; \$x = '" . serialize($md5summary) . "';";
        file_put_contents($md5_file_summary_path, $cacheMd5String);

        return $res;

      } else return false;

    }

    public function getAvailableBackups($scope = "all") {

      // Require Universal Zip Library
      require_once BMI_INCLUDES . '/zipper/zipping.php';
      $zipper = new Zipper();

      // Scan for manifests
      $manifests = array();
      $backups = array();
      $external = array();
      $ongoing = get_option('bmip_to_be_uploaded', [
        'current_upload' => [],
        'queue' => [],
        'failed' => []
      ]);

      if (file_exists(BMI_BACKUPS))
        $backups = $this->scanBackupDir(BMI_BACKUPS);
      
      // $start = time();
      // $maxTime = ini_get('max_execution_time');
      $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);

      for ($i = 0; $i < sizeof($backups); ++$i) {
        
        // $filestart = time();
        
        $backup = $backups[$i];
        if (!file_exists($backup['path'])) continue;
        $path = $backup['path'] . '/' . $backup['filename'];
        
        $manifest = $this->getManifestFromZip($path, $zipper);
        if ($manifest) {
          $md5 = $manifest[7];
          if (isset($uploadedBackupStatus[$md5])) {
            $manifest[] = $uploadedBackupStatus[$md5];
          } else {
            $manifest[] = [];
          }
          $manifests[$backup['filename']] = $manifest;
        }
        else{
          if (!file_exists(BMI_BACKUPS . '/.running')) @unlink($path); // Prevents deletion of running backups
        }
        
        // $fileend = $filestart - time();
        // $totalTime = $start - time();
        
        // if ($totalTime + $fileend > $maxTime) break;
        
      }

      if ($scope == "local") {
        return [ 'local' => $manifests ];
      }
      
      require_once BMI_INCLUDES . '/external/controller.php';
      $externalStorage = new ExternalStorage();
      $external = $externalStorage->getExternalBackups();
      
      $this->removeOldCache();

      return [ 'local' => $manifests, 'external' => $external, 'ongoing' => $ongoing ];

    }

  }
