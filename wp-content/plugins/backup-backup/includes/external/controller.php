<?php

  // Namespace
  namespace BMI\Plugin\External;

  // Use
  use BMI\Plugin\BMI_Logger as Logger;
  use BMI\Plugin\External\BMI_External_BackupBliss as BackupBliss;
  use BMI\Plugin\Dashboard as Dashboard;
  use BMI\Plugin\External\BMI_External_Storage_Premium as ExternalStoragePremium;
  use BMI\Plugin\External\BMI_External_Dropbox as Dropbox;
  use BMI\Plugin\External\BMI_External_GDrive as GDrive;
  use BMI\Plugin\External\BMI_External_FTP as FTP;
  use BMI\Plugin\External\BMI_External_S3 as S3;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

  /**
   * BMI_External_Storage
   */
  class BMI_External_Storage {

    public $backupbliss;
    public $dropbox;
    public $gdrive;
    public $ftp;
    public $aws;
    public $wasabi;

    public function __construct() {

        require_once BMI_INCLUDES . '/external/backupbliss.php';
        $this->backupbliss = new BackupBliss();

        require_once BMI_INCLUDES . '/external/dropbox.php';
        $this->dropbox = new Dropbox();
        
        require_once BMI_INCLUDES . '/external/google-drive.php';
        $this->gdrive = new GDrive();

        require_once BMI_INCLUDES . '/external/ftp.php';
        $this->ftp = new FTP();

        //S3 Includes
        require_once BMI_INCLUDES . '/external/s3.php';
        $this->aws = new S3('aws');
        $this->wasabi = new S3('wasabi');
    }

    public function getExternalBackups() {

      $backups = [];

      // Google Drive
      $backups['backupbliss'] = $this->getBackupBlissBackupsParsedForList();

      // Dropbox
      $backups['dropbox'] = $this->getDropboxBackupsParsedForList();
      
      // Google Drive
      $backups['gdrive'] = $this->getGoogleDriveBackupsParsedForList();

      //FTP
      $backups['FTP'] = $this->getFTPBackupsParsedForList();

      // AWS S3
      $backups['aws'] = $this->getAWSBackupsParsedForList();

      // Wasabi
      $backups['wasabi'] = $this->getWasabiBackupsParsedForList();

      if (defined('BMI_BACKUP_PRO') && defined('BMI_PRO_INC')) {
        $proPath = BMI_PRO_INC . 'external/controller.php';
        if (file_exists($proPath)) {
          require_once $proPath;

          $externalStorage = new ExternalStoragePremium();
          $external = $externalStorage->getExternalBackups();         
          $backups = array_merge($backups, $external);
        }
      }


      //Here we check and remove if there are any failed tasks but backups are successfully uploaded.

      $toBeUploaded = get_option('bmip_to_be_uploaded', [
        'current_upload' => [],
        'queue' => [],
        'failed' => []
      ]);

      if (isset($toBeUploaded['failed'])) {
        foreach($backups as $cloudName => $cloudBackups) {
          $cloudName = strtolower($cloudName);
          foreach($cloudBackups as $md5 => $backupDetails) {
            if (isset($toBeUploaded['failed'][$cloudName . "_" . $md5]))
              unset($toBeUploaded['failed'][$cloudName . "_" . $md5]);
          }
        }

        //Remove failed tasks if any of the cloud storages are disabled or empty
        $failed = $toBeUploaded['failed'];
        foreach($failed as $failed_task => $failed_count) {
          $data = explode("_", $failed_task);
         

          if (count($data) == 2) {
            $cloudtype = $data[0];
            $cloudtype = isset($backups[$cloudtype]) ? $cloudtype : strtoupper($cloudtype);
            if (isset($backups[$cloudtype]) && count($backups[$cloudtype]) == 0) {
              unset($toBeUploaded["failed"][$failed_task]); //Removes the failed task as there's no backups found or disabled.
            }
          }
        }

        update_option('bmip_to_be_uploaded', $toBeUploaded);
      }


      return $backups;

    }


    public function getAWSBackupsParsedForList()
    {
        $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::AWS');
        if (!($isEnabled === true || $isEnabled === 'true')) {
            return [];
        }

        $parsedBackups = [];
        $parsedAWSFiles = $this->aws->getParsedFiles();
        $backupsFileName = isset($parsedAWSFiles['zipFilesName']) ? $parsedAWSFiles['zipFilesName'] : [];
        $manifestFilesPath = isset($parsedAWSFiles['jsonFilesPath']) ? $parsedAWSFiles['jsonFilesPath'] : [];

        foreach ($manifestFilesPath as $manifestPath) {
            if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . basename($manifestPath))) {
                $manifest = file_get_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . basename($manifestPath));
                $manifest = json_decode($manifest, true);
            } else {
                $manifest = $this->aws->getFileContent($manifestPath);
                if (!$manifest) continue;
                file_put_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . basename($manifestPath), $manifest);
                $manifest = json_decode($manifest, true);
            }
            $md5 = pathinfo($manifestPath, PATHINFO_FILENAME);
            $backupName = $manifest['name'];
            if (!in_array($backupName, array_keys($backupsFileName))) continue; // Skip if the backup is not found
            $parsedBackups[$md5] = [];
            $parsedBackups[$md5][] = $manifest['name'];
            $parsedBackups[$md5][] = $manifest['date'];
            $parsedBackups[$md5][] = $manifest['files'];
            $parsedBackups[$md5][] = $manifest['manifest'];
            $parsedBackups[$md5][] = $backupsFileName[$backupName]['size'];
            $parsedBackups[$md5][] = $manifest['is_locked'];
            $parsedBackups[$md5][] = $manifest['cron'];
            $parsedBackups[$md5][] = $md5;
            $parsedBackups[$md5][] = $backupsFileName[$backupName]['id'];
            $parsedBackups[$md5][] = sanitize_text_field(isset($manifest['domain']) ? $manifest['domain'] : '');
        }
        return $parsedBackups;        
    }

    public function getWasabiBackupsParsedForList()
    {
        $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::WASABI');
        if (!($isEnabled === true || $isEnabled === 'true')) {
            return [];
        }

        $parsedBackups = [];
        $parsedWasabiFiles = $this->wasabi->getParsedFiles();
        $backupsFileName = isset($parsedWasabiFiles['zipFilesName']) ? $parsedWasabiFiles['zipFilesName'] : [];
        $manifestFilesPath = isset($parsedWasabiFiles['jsonFilesPath']) ? $parsedWasabiFiles['jsonFilesPath'] : [];

        foreach ($manifestFilesPath as $manifestPath) {
            if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . basename($manifestPath))) {
                $manifest = file_get_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . basename($manifestPath));
                $manifest = json_decode($manifest, true);
            } else {
                $manifest = $this->wasabi->getFileContent($manifestPath);
                if (!$manifest) continue;
                file_put_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . basename($manifestPath), $manifest);
                $manifest = json_decode($manifest, true);
            }
            $md5 = pathinfo($manifestPath, PATHINFO_FILENAME);
            $backupName = $manifest['name'];
            if (!in_array($backupName, array_keys($backupsFileName))) continue; // Skip if the backup is not found
            $parsedBackups[$md5] = [];
            $parsedBackups[$md5][] = $manifest['name'];
            $parsedBackups[$md5][] = $manifest['date'];
            $parsedBackups[$md5][] = $manifest['files'];
            $parsedBackups[$md5][] = $manifest['manifest'];
            $parsedBackups[$md5][] = $backupsFileName[$backupName]['size'];
            $parsedBackups[$md5][] = $manifest['is_locked'];
            $parsedBackups[$md5][] = $manifest['cron'];
            $parsedBackups[$md5][] = $md5;
            $parsedBackups[$md5][] = $backupsFileName[$backupName]['id'];
            $parsedBackups[$md5][] = sanitize_text_field(isset($manifest['domain']) ? $manifest['domain'] : '');
        }
        return $parsedBackups;
    }

    private function getGoogleDriveBackupsParsedForList() {

      $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::GDRIVE');
      if (!($isEnabled === true || $isEnabled === 'true')) {
        return [];
      }

      $backups = [];
      $googleDriveBackups = $this->gdrive->getGoogleDriveBackups();
      if ($googleDriveBackups && is_object($googleDriveBackups['data']) && isset($googleDriveBackups['data']->files)) {
        $backups = $this->gdrive->parseGoogleDriveFiles($googleDriveBackups['data']->files);
      } else $backups = [];

      $parsedBackups = [];
      foreach ($backups as $key => $value) {
        if (in_array(pathinfo($key, PATHINFO_EXTENSION), ['zip', 'tar', 'gz'])) {

          $md5 = $value['md5'];
          $size = $value['size'];
          $backupFileId = $value['id'];
          if (isset($backups['file_' . $md5 . '.json'])) {

            $localManifest = BMI_BACKUPS . DIRECTORY_SEPARATOR. $md5 . '.json';
            if (file_exists($localManifest)) {

              $manifest = file_get_contents($localManifest);
              $manifest = json_decode($manifest);

            } else {

              $manifestFileId = $backups['file_' . $md5 . '.json']['id'];
              $manifest = $this->gdrive->getGoogleDriveFileContents($manifestFileId);
              if (isset($manifest['status']) && isset($manifest['data']) && $manifest['status'] == 'success') {

                $manifest = $manifest['data'];
                file_put_contents($localManifest, json_encode($manifest));

              } else continue;

            }

            $parsedBackups[$md5] = [];
            $parsedBackups[$md5][] = $manifest->name;
            $parsedBackups[$md5][] = $manifest->date;
            $parsedBackups[$md5][] = $manifest->files;
            $parsedBackups[$md5][] = $manifest->manifest;
            $parsedBackups[$md5][] = $size;
            $parsedBackups[$md5][] = $manifest->is_locked;
            $parsedBackups[$md5][] = $manifest->cron;
            $parsedBackups[$md5][] = $md5;
            $parsedBackups[$md5][] = $backupFileId;
            $parsedBackups[$md5][] = sanitize_text_field($manifest->domain);
          }
        }
      }

      return $parsedBackups;
    }

    private function getFTPBackupsParsedForList()
    {
        $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::FTP');
        if (!($isEnabled === true || $isEnabled === 'true')) {
            return [];
        }

        $backups = [];
        $FtpBackups = $this->ftp->getFtpBackups();
        if ($FtpBackups && isset($FtpBackups['data'])) {
            $backups = $this->ftp->parseFtpFiles($FtpBackups['data']);
        } else $backups = [];
        $backupsFiles = isset($backups['zipFiles']) ? $backups['zipFiles'] : [];
        $manifestFiles = isset($backups['jsonFiles']) ? $backups['jsonFiles'] : [];   
        $parsedBackups = [];

        foreach ($manifestFiles as $manifestFile) {
          if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . basename($manifestFile['name']))) {
            $manifest = file_get_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . $manifestFile['name']);
            $manifest = json_decode($manifest, true);
          } else {
            $manifest = $this->ftp->getFtpFileContents($manifestFile['name']);
            if ($manifest['status'] !== 'success' || $manifest['data'] === '') continue;
            $manifest = $manifest['data'];

            file_put_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . $manifestFile['name'], $manifest);
            $manifest = json_decode($manifest, true);
          }
          $md5 = pathinfo($manifestFile['name'], PATHINFO_FILENAME);

          $backupName = $manifest['name'];
          if (!in_array($backupName, array_column($backupsFiles, 'name'))) continue; // Skip if the backup is not found
          $backupFile = array_values(array_filter($backupsFiles, function ($file) use ($backupName) {
            return $file['name'] === $backupName;
          }))[0];
          $parsedBackups[$md5] = [];
          $parsedBackups[$md5][] = $manifest['name'];
          $parsedBackups[$md5][] = $manifest['date'];
          $parsedBackups[$md5][] = $manifest['files'];
          $parsedBackups[$md5][] = $manifest['manifest'];
          $parsedBackups[$md5][] = $backupFile['size'];
          $parsedBackups[$md5][] = $manifest['is_locked'];
          $parsedBackups[$md5][] = $manifest['cron'];
          $parsedBackups[$md5][] = $md5;
          $parsedBackups[$md5][] = $backupFile['name'];
          $parsedBackups[$md5][] = sanitize_text_field(isset($manifest['domain']) ? $manifest['domain'] : '');
        }

      return $parsedBackups;
    }

    /**
     * Get Dropbox backups list
     *
     * @return array[] An array of backups, where each backup is an array of details keyed by MD5 hash
     *                 Each backup array contains the following elements:
     *                 [
     *                     0 => string   Backup name
     *                     1 => string   Backup date
     *                     2 => int      Number of files in backup
     *                     3 => string   Date of the backup
     *                     4 => int      Backup size in bytes
     *                     5 => string   Backup lock status ("unlocked" or "locked")
     *                     6 => bool     Whether the backup was created by cron
     *                     7 => string   Backup MD5 hash
     *                     8 => string   Backup file ID in Dropbox
     *                     9 => string   Backup domain used for tooltip in backups list
     *                 ]
     */
    public function getDropboxBackupsParsedForList()
    {
      $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::DROPBOX');
      if (!($isEnabled === true || $isEnabled === 'true') || $this->dropbox->verifyConnection()['result'] != 'connected') {
        return [];
      }

     $parsedBackups = [];
     $parsedDropboxFiles = $this->dropbox->getParsedFiles();
     $backupsFileName = isset($parsedDropboxFiles['zipFilesName']) ? $parsedDropboxFiles['zipFilesName'] : [];
     $manifestFilesPath = isset($parsedDropboxFiles['jsonFilesPath']) ? $parsedDropboxFiles['jsonFilesPath'] : [];

      foreach ($manifestFilesPath as $manifestPath) {
          if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . basename($manifestPath))) {
              $manifest = file_get_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . basename($manifestPath));
              $manifest = json_decode($manifest, true);
          } else {
            $manifest = $this->dropbox->getFileContent($manifestPath);
            if (!$manifest || $manifest == 'error') continue;
            file_put_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . basename($manifestPath), $manifest);
            $manifest = json_decode($manifest, true);
          }
          $md5 = pathinfo($manifestPath, PATHINFO_FILENAME);
          $backupName = $manifest['name'];
          if (!in_array($backupName, array_keys($backupsFileName))) continue; // Skip if the backup is not found
          $parsedBackups[$md5] = [];
          $parsedBackups[$md5][] = $manifest['name'];
          $parsedBackups[$md5][] = $manifest['date'];
          $parsedBackups[$md5][] = $manifest['files'];
          $parsedBackups[$md5][] = $manifest['manifest'];
          $parsedBackups[$md5][] = $backupsFileName[$backupName]['size'];
          $parsedBackups[$md5][] = $manifest['is_locked'];
          $parsedBackups[$md5][] = $manifest['cron'];
          $parsedBackups[$md5][] = $md5;
          $parsedBackups[$md5][] = $backupsFileName[$backupName]['id'];
          $parsedBackups[$md5][] = sanitize_text_field(isset($manifest['domain']) ? $manifest['domain'] : '');
      }
      return $parsedBackups;
  }

    private function getBackupBlissBackupsParsedForList() {


      $files = $this->backupbliss->parseFiles($this->backupbliss->getAllFiles());

      $parsedBackups = [];

      
      if ($files) {
        foreach ($files['manifests'] as $manifestFileName => $filedetail) {
          
          $localManifest = BMI_BACKUPS . DIRECTORY_SEPARATOR. $manifestFileName;

          if (file_exists($localManifest)) {

            $manifestData = file_get_contents($localManifest);
            $manifest = json_decode($manifestData);

          } else {

            $manifestData = $this->backupbliss->getFile($manifestFileName);
            if (is_array($manifestData) && $manifestData["file_data"]) {
              
              file_put_contents($localManifest, $manifestData["file_data"]);
              $manifest = json_decode($manifestData["file_data"]);

            } else continue;

          }

          if (!isset($manifest))
            continue;

          $md5 = pathinfo($manifestFileName, PATHINFO_FILENAME);
          $backupFileName = $manifest->name;

          if (!isset($files["backups"][$backupFileName]))
            continue;

          $parsedBackups[$md5] = [];
          $parsedBackups[$md5][] = $backupFileName;
          $parsedBackups[$md5][] = $manifest->date;
          $parsedBackups[$md5][] = $manifest->files;
          $parsedBackups[$md5][] = $manifest->manifest;
          $parsedBackups[$md5][] = $files["backups"][$backupFileName]["size"];
          $parsedBackups[$md5][] = $manifest->is_locked;
          $parsedBackups[$md5][] = $manifest->cron;
          $parsedBackups[$md5][] = $md5;
          $parsedBackups[$md5][] = $backupFileName;
          $parsedBackups[$md5][] = sanitize_text_field($manifest->domain);
        }
    }

      return $parsedBackups;
    }
  }
