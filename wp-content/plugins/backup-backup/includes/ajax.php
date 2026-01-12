<?php

  // Namespace
  namespace BMI\Plugin;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  // Uses
  use BMI\Plugin\Backup_Migration_Plugin as BMP;
  use BMI\Plugin\BMI_Logger as Logger;
  use BMI\Plugin\Checker\BMI_Checker as Checker;
  use BMI\Plugin\Checker\System_Info as SI;
  use BMI\Plugin\CRON\BMI_Crons as Crons;
  use BMI\Plugin\Dashboard as Dashboard;
  use BMI\Plugin\Extracter\BMI_Extracter as Extracter;
  use BMI\Plugin\Progress\BMI_MigrationProgress as MigrationProgress;
  use BMI\Plugin\Progress\BMI_ZipProgress as Progress;
  use BMI\Plugin\Progress\BMI_StagingProgress as StagingProgress;
  use BMI\Plugin\Scanner\BMI_BackupsScanner as Backups;
  use BMI\Plugin\Scanner\BMI_FileScanner as Scanner;
  use BMI\Plugin\Zipper\BMI_Zipper as Zipper;
  use BMI\Plugin\PHPCLI\Checker as PHPCLICheck;
  use BMI\Plugin\External\BMI_External_Storage as ExternalStorage;
  use BMI\Plugin\External\BMI_External_Storage_Premium as ExternalStoragePremium;
  use BMI\Plugin\Staging\BMI_Staging_TasteWP as StagingTasteWP;
  use BMI\Plugin\Staging\BMI_StagingLocal as StagingLocal;
  use BMI\Plugin\Heart\BMI_Backup_Heart as Bypasser;
  use BMI\Plugin\Staging\BMI_Staging as Staging;
  use BMI\Plugin\Checker\Compatibility as Compatibility;
  use BMI\Plugin\External\BMI_External_BackupBliss as BackupBliss;
  use BMI\Plugin\BMI_File_Explorer as File_Explorer;
  use BMI\Plugin\External\BMI_External_Dropbox as Dropbox;
  use BMI\Plugin\External\BMI_External_GDrive as GDrive;
  use BMI\Plugin\External\BMI_External_FTP as FTP;
  use BMI\Plugin\External\BMI_External_S3 as S3;

  /**
   * Ajax Handler for BMI
   */
  class BMI_Ajax {

    private $gdrive_access_token = false;
    public $post;
    public $zip_progress;
    public $migration_progress;
    public $lock_cli;
    public $lastCurlCode;

    public $total_size_for_backup = 0;
    public $total_size_for_backup_in_mb = 0;
    public $total_excluded_size_for_backup = 0;
    public $ignoredDirectoriesSize = 0;

    public function __construct($initializedWithCLI = false) {

      // Initialize CRON if wasn't done earlier
      $this->shareDomainForAutoCron();

      // Return if it's not post
      if (empty($_POST)) {
        $this->post = ['f' => 'unknown_method'];
        return;
      }

      // Sanitize User Input
      $this->post = BMP::sanitize($_POST);

      if (!isset($this->post['f'])) {
        if (is_object($this->post) || is_array($this->post)) $this->post['f'] = 'unknown_method';
        else $this->post = ['f' => 'unknown_method'];
      }
      
      // Check nonce for non PHP CLI usage (ignore while self requested via previously verified nonce to PHP CLI)
      if (check_ajax_referer('backup-migration-ajax', 'nonce', false) === false && $initializedWithCLI === false) {
        return wp_send_json_error(['reason' => 'not authorized request']);
      }

      // Log Handler Call (Verbose)
      Logger::debug(__("Running POST Function: ", 'backup-backup') . $this->post['f']);

      // Create backup folder
      if (!file_exists(BMI_BACKUPS)) {
        mkdir(BMI_BACKUPS, 0755, true);
      }

      // Create background logs file
      $backgroundLogsPath = BMI_CONFIG_DIR . DIRECTORY_SEPARATOR . 'background-errors.log';
      if (!file_exists($backgroundLogsPath)) {
        @touch($backgroundLogsPath);
      }
      
      if (!isset($this->post['f'])) {
        return;
      }

      // Handle User Request If Known And Sanitize Response
      if ($this->post['f'] == 'scan-directory') {
        BMP::res($this->dirSize());
      } elseif ($this->post['f'] == 'create-backup') {
        BMP::res($this->prepareAndMakeBackup());
      } elseif ($this->post['f'] == 'reset-latest') {
        BMP::res($this->resetLatestLogs());
      } elseif ($this->post['f'] == 'get-current-backups') {
        BMP::res($this->getBackupsList());
      } elseif ($this->post['f'] == 'restore-backup') {
        BMP::res($this->restoreBackup());
      } elseif ($this->post['f'] == 'is-running-backup') {
        BMP::res($this->isRunningBackup());
      } elseif ($this->post['f'] == 'stop-backup') {
        BMP::res($this->stopBackup());
      } elseif ($this->post['f'] == 'download-backup') {
        BMP::res($this->handleQuickMigration());
      } elseif ($this->post['f'] == 'migration-locked') {
        BMP::res($this->isMigrationLocked());
      } elseif ($this->post['f'] == 'upload-backup') {
        BMP::res($this->handleChunkUpload());
      } elseif ($this->post['f'] == 'delete-backup') {
        BMP::res($this->removeBackupFile());
      } elseif ($this->post['f'] == 'save-storage') {
        BMP::res($this->saveStorageConfig());
      } elseif ($this->post['f'] == 'save-file-config') {
        BMP::res($this->saveFilesConfig());
      } elseif ($this->post['f'] == 'save-other-options') {
        BMP::res($this->saveOtherOptions());
      } elseif ($this->post['f'] == 'store-config') {
        BMP::res($this->saveStorageTypeConfig());
      } elseif ($this->post['f'] == 'unlock-backup') {
        BMP::res($this->toggleBackupLock(true));
      } elseif ($this->post['f'] == 'lock-backup') {
        BMP::res($this->toggleBackupLock(false));
      } elseif ($this->post['f'] == 'get-dynamic-names') {
        BMP::res($this->getDynamicNames());
      } elseif ($this->post['f'] == 'reset-configuration') {
        BMP::res($this->resetConfiguration());
      } elseif ($this->post['f'] == 'get-site-data') {
        BMP::res($this->getSiteData());
      } elseif ($this->post['f'] == 'send-test-mail') {
        BMP::res($this->sendTestMail());
      } elseif ($this->post['f'] == 'calculate-cron') {
        BMP::res($this->calculateCron());
      } elseif ($this->post['f'] == 'dismiss-error-notice') {
        BMP::res($this->dismissErrorNotice());
      } elseif ($this->post['f'] == 'fix_uname_issues') {
        BMP::res($this->fixUnameFunction());
      } elseif ($this->post['f'] == 'revert_uname_issues') {
        BMP::res($this->revertUnameProcess());
      } elseif ($this->post['f'] == 'continue_restore_process') {
        BMP::res($this->continueRestoreProcess());
      } elseif ($this->post['f'] == 'htaccess-litespeed') {
        BMP::res($this->fixLitespeed());
      } elseif ($this->post['f'] == 'force-backup-to-stop') {
        BMP::res($this->forceBackupToStop());
      } elseif ($this->post['f'] == 'force-restore-to-stop') {
        BMP::res($this->forceRestoreToStop());
      } elseif ($this->post['f'] == 'staging-local-name') {
        BMP::res($this->checkStagingLocalName());
      } elseif ($this->post['f'] == 'staging-start-local-creation') {
        BMP::res($this->startLocalStagingCreation());
      } elseif ($this->post['f'] == 'staging-local-creation-process') {
        BMP::res($this->localStagingCreationProcess());
      } elseif ($this->post['f'] == 'staging-tastewp-creation-process') {
        BMP::res($this->tastewpStagingCreation());
      } elseif ($this->post['f'] == 'staging-rename-display') {
        BMP::res($this->stagingRename());
      } elseif ($this->post['f'] == 'staging-prepare-login') {
        BMP::res($this->stagingPrepareLogin());
      } elseif ($this->post['f'] == 'staging-delete-permanently') {
        BMP::res($this->stagingDelete());
      } elseif ($this->post['f'] == 'staging-get-updated-list') {
        BMP::res($this->stagingSitesGetList());
      } elseif ($this->post['f'] == 'send-troubleshooting-logs') {
        BMP::res($this->sendTroubleshootingDetails());
      } elseif ($this->post['f'] == 'log-sharing-details') {
        BMP::res($this->logSharing());
      } elseif ($this->post['f'] == 'get-latest-backup') {
        BMP::res($this->getLatestBackupFile());
      } elseif ($this->post['f'] == 'front-end-ajax-error') {
        BMP::res($this->frontEndAjaxError());
      } elseif ($this->post['f'] == 'backup-browser-method') {
        BMP::res($this->backupBrowserMethodHandler());
      } elseif ($this->post['f'] == 'debugging') {
        BMP::res($this->debugging());
      } elseif ($this->post['f'] == 'check-disk-space') {
        BMP::res($this->checkDiskSpace());
      } elseif ($this->post['f'] == 'check-comptability') {
        BMP::res($this->checkCompatibility());
      } elseif ($this->post['f'] == 'clean-up-after-error'){
        BMP::res($this->cleanUpAfterError());
      } elseif ($this->post['f'] == 'clicked-on-plugin-review') {
        BMP::res($this->clickedOnPluginReview());
      } elseif ($this->post['f'] == 'keep-dropbox-connection') {
        BMP::res($this->keepDropboxToken());
      } elseif ($this->post['f'] == 'get-dropbox-token') {
        BMP::res($this->getDropboxToken());
      } elseif ($this->post['f'] == 'disconnect-dropbox') {
        BMP::res($this->disconnectDropboxToken());
      } elseif ($this->post['f'] == 'verify-dropbox-connection') {
        BMP::res($this->verifyDropboxConnection());
      } elseif ($this->post['f'] == 'download-dropbox-backup') {
        BMP::res($this->downloadCloudBackupV2());
      } elseif ($this->post['f'] == 'dismiss-dropbox-notice') {
        BMP::res($this->dismissDropboxNotice());
      } elseif ($this->post['f'] == 'get-gdrive-token') {
        BMP::res($this->getGDriveToken());
      } elseif ($this->post['f'] == 'keep-gdrive-connection') {
        BMP::res($this->keepGDriveToken());
      } elseif ($this->post['f'] == 'verify-gdrive-connection') {
        BMP::res($this->verifyGDriveConnection());
      } elseif ($this->post['f'] == 'disconnect-gdrive') {
        BMP::res($this->disconnectGDriveToken());
      } elseif ($this->post['f'] == 'get-ftp-config') {
        BMP::res($this->connectToConfig());
      } elseif ($this->post['f'] == 'disconnect-ftp') {
        BMP::res($this->disconnectFtp());
      } elseif ($this->post['f'] == 'save-aws-config') {
        BMP::res($this->saveAWSConfig());
      } elseif ($this->post['f'] == 'disconnect-aws') {
        BMP::res($this->disconnectAWS());
      } elseif ($this->post['f'] == 'verify-aws-connection') {
        BMP::res($this->verifyAWSConnection());
      } elseif ($this->post['f'] == 'save-wasabi-config') {
        BMP::res($this->saveWasabiConfig());
      } elseif ($this->post['f'] == 'disconnect-wasabi') {
        BMP::res($this->disconnectWasabi());
      } elseif ($this->post['f'] == 'verify-wasabi-connection') {
        BMP::res($this->verifyWasabiConnection());
      } elseif ($this->post['f'] == 'manually-enqueue-upload') {
        BMP::res($this->manuallyEnqueueUpload());
      } 
      elseif (substr($this->post['f'], 0, 3) === "bb-") {
        require_once BMI_INCLUDES . '/external/backupbliss.php';
        $backupBliss = new BackupBliss();
        BMP::res($backupBliss->process(substr($this->post['f'], 3), $this->post));
      } elseif ($this->post['f'] == 'check-not-uploaded-backups') {
        do_action('bmi_ajax_offline', $this->post);
      } elseif($this->post['f'] == 'download-cloud-backup') {
        if (isset($this->post['storage']) && ($this->post['storage'] == 'backupbliss' || 
        $this->post['storage'] == 'googledrive' || $this->post['storage'] == 'ftp'))
          BMP::res($this->downloadCloudBackup());
        //Forward it to premium plugin for other cloud downloads
        elseif (has_action('bmi_premium_ajax')) {
          do_action('bmi_premium_ajax', $this->post);
        }
      }


      //If none of the action matches it executes premium ajax if it exists
      elseif (has_action('bmi_premium_ajax')) {
        do_action('bmi_premium_ajax', $this->post);
      }

    }
  
  /**
   * getFtpConfig
   *
   * @return string[] Token
   */
  private function connectToConfig()
  {
    // Safely retrieve POST values
    $host = isset($this->post['bmip-ftp-host']) ? trim($this->post['bmip-ftp-host']) : false;
    $dir = isset($this->post['bmip-ftp-backup-dir']) ? trim($this->post['bmip-ftp-backup-dir']) : '';
    $port = isset($this->post['bmip-ftp-host-port']) && is_numeric($this->post['bmip-ftp-host-port']) ? (int)trim($this->post['bmip-ftp-host-port']) : 21;
    $password = isset($this->post['bmip-ftp-password']) ? trim($this->post['bmip-ftp-password']) : false;
    $userName = isset($this->post['bmip-ftp-username']) ? trim($this->post['bmip-ftp-username']) : false;
    
    if (!$host) {
      return ['status' => 'error', 'msg' => 'FTP Host is required and cannot be empty', 'errors' => 1];
    }

    if (!$userName) {
      return ['status' => 'error', 'msg' => 'FTP Username is required and cannot be empty', 'errors' => 1];
    }

    if (!$password) {
      return ['status' => 'error', 'msg' => 'FTP Password is required and cannot be empty', 'errors' => 1];
    }

    if ($dir[0] !== '/') { 
      $dir = '/' . $dir; 
    }


    if (!function_exists('ftp_connect')) {
      return [
        'msg' => "FTP functions are not available on your server. Please make sure the FTP extension for PHP is installed and enabled.",
        'status' => 'error',
        'errors' => 1
      ];
    }

    $ftp = ftp_connect($host, $port, 10);
    if (!$ftp) {
      return [
        'msg' => "Could not connect to FTP server at $host on port $port. Please check the hostname and port.",
        'status' => 'error',
        'errors' => 1
      ];
    }
    
    $login_result = @ftp_login($ftp, $userName, $password);
    if (!$login_result) {
      ftp_close($ftp);
      return [
        'msg' => 'Invalid FTP username or password. Please check your credentials.',
        'status' => 'error',
        'errors' => 1
      ];
    }
    
    ftp_pasv($ftp, true);
    
    // Try to change to the directory or create it if not found
    if (!@ftp_chdir($ftp, $dir)) {
      if (!@ftp_mkdir($ftp, $dir)) {
        ftp_close($ftp);
        return [
          'msg' => "The backup directory '$dir' does not exist and could not be created. Please ensure you have the correct permissions to create directories on the FTP server.",
          'status' => 'error',
          'errors' => 1
        ];
      }
    }
    
    // Change into the backup directory
    if (!@ftp_chdir($ftp, $dir)) {
      ftp_close($ftp);
      return [
        'msg' => "Unable to navigate to the backup directory '$dir'. Please ensure it exists and has the correct permissions.",
        'status' => 'error',
        'errors' => 1
      ];
    }
    
    // Permission check: Upload and download a temporary file
    $testFile = 'bmi_ftp_test_' . uniqid() . '.txt';
    $localTempPath = BMI_TMP . DIRECTORY_SEPARATOR . $testFile;
    file_put_contents($localTempPath, "Permission check");
    
    $upload = @ftp_put($ftp, $testFile, $localTempPath, FTP_ASCII);
    $download = false;
    
    if ($upload) {
      // Try to download back to confirm read access
      $downloadPath = $localTempPath . '_download';
      $download = @ftp_get($ftp, $downloadPath, $testFile, FTP_ASCII);
    }
    
    // Cleanup
    $delete = @ftp_delete($ftp, $testFile);

    @unlink($localTempPath);
    if (isset($downloadPath) && file_exists($downloadPath)) {
      @unlink($downloadPath);
    }
    
    if (!$upload) {
      ftp_close($ftp);
      return [
        'msg' => "Connected successfully, but upload permission check failed. Unable to upload files in '$dir'.",
        'status' => 'error',
        'errors' => 1
      ];
    }

    if (!$download) {
      ftp_close($ftp);
      return [
        'msg' => "Connected successfully, but download permission check failed. Unable to download files from '$dir'.",
        'status' => 'error',
        'errors' => 1
      ];
    }

    if (!$delete) {
      ftp_close($ftp);
      return [
        'msg' => "Connected successfully, but delete permission check failed. Unable to delete files in '$dir'.",
        'status' => 'error',
        'errors' => 1
      ];
    }
    
    // Store the config and close connection
    Dashboard\bmi_set_config('STORAGE::EXTERNAL::FTP', "true");
    
    update_option('bmi_pro_ftp_host', $host);
    update_option('bmi_pro_ftp_username', $userName);
    update_option('bmi_pro_ftp_backup_dir', $dir);
    update_option('bmi_pro_ftp_port', $port);
    update_option('bmi_pro_ftp_password', $password);
    
    ftp_close($ftp);
    
    return [
      'status' => 'success',
      'errors' => 0
    ];
  }

  private function disconnectFtp()
  {
    delete_option('bmi_pro_ftp_host');
    delete_option('bmi_pro_ftp_backup_dir');
    delete_option('bmi_pro_ftp_port');
    delete_option('bmi_pro_ftp_username');
    delete_option('bmi_pro_ftp_password');
    Dashboard\bmi_set_config('STORAGE::EXTERNAL::FTP', "false");
    return ['status' => 'success'];
  }

  private function getDropboxToken()
  {

    $bytes = random_bytes(36);
    $token = bin2hex($bytes);

    update_option('bmip_dropbox', $token);
    return ['token' => $token];
  }

  private function keepDropboxToken()
  {

    $receivedToken = $this->post['receivedToken'];
    $receivedAuthCode = $this->post['receivedClientID'];

    $currentToken = get_option('bmip_dropbox', false);

    if ($currentToken === $receivedToken) {

      update_option('bmip_dropbox_auth_code', $receivedAuthCode);
      return ['status' => 'success'];
    } else {

      return ['status' => 'token_mismatch'];
    }
  }

  private function verifyDropboxConnection()
  {

    require_once BMI_INCLUDES . '/external/dropbox.php';

    $dropbox = new Dropbox();
    return $dropbox->verifyConnection();
  }

  private function disconnectDropboxToken()
  {
    require_once BMI_INCLUDES . '/external/dropbox.php';

    $dropbox = new Dropbox();
    $dropbox->disconnect();
    delete_option($dropbox->dropboxAuthCodeOption);
    delete_option($dropbox->dropboxId);
    delete_transient($dropbox->dropboxAccessToken);
    Dashboard\bmi_set_config('STORAGE::EXTERNAL::DROPBOX', 'false');

    return ['status' => 'success'];
  }


  private function downloadCloudBackupV2()
  {

    require_once BMI_INCLUDES . '/progress/migration.php';

    $secret = isset($this->post['secret']) ? $this->post['secret'] : false;
    $startRestoreProcess = isset($this->post['startRestoreProcess']) ? $this->post['startRestoreProcess'] : 'true';
    $lock = BMI_BACKUPS . '/.migration_lock';

    if (file_exists($lock) && (time() - filemtime($lock)) < 1) {
      $lockContent = file_get_contents($lock);
      if ($lockContent !== $secret) {
        return ['status' => 'msg', 'why' => __('Download process is currently running, please wait till it complete.', 'backup-backup'), 'level' => 'warning'];
      }
    }

    $externalStorage = null;
    switch($this->post['f']){
      case 'download-dropbox-backup':
        require_once BMI_INCLUDES . '/external/dropbox.php';
        $externalStorage = new Dropbox();
        break;
      default:
        return ['status' => 'error'];
    }
    $fileId = isset($this->post['fileId']) ? $this->post['fileId'] : false; // Required
    $md5 = isset($this->post['md5']) ? $this->post['md5'] : false; // Required
    $step = isset($this->post['step']) ? intval($this->post['step']) : 0; // Required
    $size = isset($this->post['size']) ? intval($this->post['size']) : false;
    $fileName = isset($this->post['filename']) ? $this->post['filename'] : false;
    $writePath = isset($this->post['writepath']) ? $this->post['writepath'] : false;
    $chunkSize = isset($this->post['chunksize'])  && (intval($this->post['chunksize']) != 0) ? intval($this->post['chunksize']) : BMP::getAvailableMemoryInBytes() / 4;
    $migration = new MigrationProgress(($step === 0) ? false : true);


    $migration->start();

    if ($step === 0) {
      $migration->log((__('Backup & Migration version: ', 'backup-backup') . BMI_VERSION));
      $migration->log(__('Creating lock file', 'backup-backup'));
      $secret = $this->randomString();
      file_put_contents($lock, $secret);

      $migration->log('Download intialized', 'INFO');
      $migration->log('Getting backup details from cloud...', 'STEP');

      $backupDetails = $externalStorage->getFileMeta($fileId);
      if (!isset($backupDetails['name'])) $backupDetails['name'] = $fileId;


      if ($backupDetails == false) {
        $migration->log('It seem like I was unable to get backup details from cloud.', 'ERROR');
        $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
        if (file_exists($lock)) @unlink($lock);

        $migration->log('error_during_downloading_backup', 'verbose');
        $migration->log('#002', 'END-CODE');
        $migration->end();

        return ['status' => 'error'];
      }

      $manifest = BMI_BACKUPS . DIRECTORY_SEPARATOR . $md5 . '.json';
      $manifestContent = $externalStorage->getFileContent($md5 . '.json');
      if ($manifestContent == false) {
        $migration->log('It seem like I was unable to get backup manifest from cloud.', 'ERROR');
        $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
        if (file_exists($lock)) @unlink($lock);

        $migration->log('error_during_downloading_backup', 'verbose');
        $migration->log('#002', 'END-CODE');
        $migration->end();

        return ['status' => 'error'];
      }
      file_put_contents($manifest, $manifestContent);


      $size = intval($backupDetails['size']);
      $fileName = $backupDetails['name'];

      $migration->log('Backup details received!', 'SUCCESS');
      $migration->log('Backup original name: ' . $fileName, 'INFO');
      $migration->log('Starting download process...', 'STEP');

      $availableMemory = BMP::getAvailableMemoryInBytes();
      $bytesPerRequest = intval($availableMemory / 4);


      $migration->log('Single batch will use up to: ' . $bytesPerRequest . ' bytes (~' . intval($bytesPerRequest / 1024 / 1024 / 2) . ' MBs)', 'INFO');

      $fileIterator = 2;
      $extension = pathinfo($fileName, PATHINFO_EXTENSION);
      $fileName = pathinfo($fileName, PATHINFO_FILENAME);
      if ($extension == 'gz') {
        $fileName = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = 'tar.gz';
      }

      $backupDestinationPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $fileName . '.' . $extension;
      $finalName = $fileName . '.' . $extension;

      while (file_exists($backupDestinationPath)) {
        $backupDestinationPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $fileName . '-' . $fileIterator . '.' . $extension;
        $fileIterator++;
      }

      $originalFilename = $finalName;

      $backupDestinationPath .= '.crdownload';
    } else {
      $bytesPerRequest = intval($chunkSize);
      $backupDestinationPath = $writePath;
      $originalFilename = $fileName;
    }

    $totalBatches = ceil($size / (256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024 / 2)));

    if ($totalBatches <= $step) {
      $migration->log('Verifying MD5 checksum of downloaded file...', 'STEP');

      rename($backupDestinationPath, str_replace('.crdownload', '', $backupDestinationPath));
      $backupDestinationPath = str_replace('.crdownload', '', $backupDestinationPath);

      $local_md5 = md5_file($backupDestinationPath);
      if (file_exists($backupDestinationPath) && $local_md5 == $md5) {


        $migration->log('Downloaded MD5: ' . $local_md5, 'INFO');
        $migration->log('Expected MD5: ' . $md5, 'INFO');
        $migration->log('File MD5 checksum is correct!', 'SUCCESS');
      } else {

        $migration->log('File MD5 checksum is NOT correct!', 'ERROR');
        $migration->log('Downloaded MD5: ' . $local_md5, 'ERROR');
        $migration->log('Expected MD5: ' . $md5, 'ERROR');
        $migration->log('Downloaded file path: ' . $backupDestinationPath, 'ERROR');
        $migration->log('File exist?: ' . (file_exists($backupDestinationPath) ? "Yes" : "No?"), 'ERROR');
        $migration->log('For security reasons, I will remove the file and stop the process...', 'ERROR');
        $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
        if (file_exists($lock)) @unlink($lock);

        $migration->log('error_during_downloading_backup', 'verbose');
        $migration->log('#002', 'END-CODE');
        $migration->end();

        if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
        return ['status' => 'error'];
      }

      $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
      if (file_exists($lock)) @unlink($lock);
      if ($startRestoreProcess == 'true') {
        $migration->log('Download process finished!', 'SUCCESS');
        $migration->log('Requesting restoration process...', 'STEP');

        $migration->log('#205', 'END-CODE');
      } else {
        $migration->log('Download process finished!', 'SUCCESS');
        $migration->log('#206', 'END-CODE');
      }

      $migration->progress(100);
      $migration->end();

      return ['status' => 'success', 'finished' => 'true', 'filename' => $originalFilename];
    } else {

      $chunkSize = 256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024 / 2);
      $startRange = ($step * $chunkSize);
      if ($step !== 0) $startRange = $startRange + 1;
      $endRange = (($step + 1) * $chunkSize);
      if ($endRange > $size) $endRange = $size;
      $currentRange = $startRange . '-' . $endRange;
      $percentage = intval(($endRange / $size) * 100);

      $contents = $externalStorage->getFileContent($fileId, $currentRange);

      if ($contents == false) {

        $migration->log('It seem like I was unable to get backup content from cloud.', 'ERROR');
        $migration->log('For security reasons, I will remove the file (if exist) and stop the process...', 'ERROR');
        $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
        if (file_exists($lock)) @unlink($lock);

        $migration->log('error_during_downloading_backup', 'verbose');
        $migration->log('#002', 'END-CODE');
        $migration->end();

        if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
        return ['status' => 'error'];
      }

      if ((is_dir(dirname($backupDestinationPath)) && file_exists($backupDestinationPath)) || $step === 0) {

        $backupFile = fopen($backupDestinationPath, 'ab');
        fwrite($backupFile, $contents);
        unset($contents);
        fclose($backupFile);
      } else {

        $migration->log('File is not writable or directory does not exist.', 'ERROR');
        $migration->log('File: ' . basename($backupDestinationPath), 'ERROR');
        $migration->log('Dirname: ' . dirname($backupDestinationPath), 'ERROR');
        $migration->log('For security reasons, I will remove the file and stop the process...', 'ERROR');
        $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
        if (file_exists($lock)) @unlink($lock);

        $migration->log('error_during_downloading_backup', 'verbose');
        $migration->log('#002', 'END-CODE');
        $migration->end();

        if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
        return ['status' => 'error'];
      }

      $migration->log('Download progress (' . ($step + 1) . '/' . $totalBatches . '): ' . $endRange . '/' . $size . ' (' . $percentage . '%)', 'INFO');
      $migration->progress($percentage);
      $migration->end();

      return [
        'status' => 'success',
        'size' => $size,
        'md5' => $md5,
        'finished' => 'false',
        'originalFilename' => $originalFilename,
        'writepath' => $backupDestinationPath,
        'chunksize' => $bytesPerRequest,
        'secret' => $secret
      ];
    }
  }

  /**
   * getGDriveToken - Generates client sided auth token
   *
   * @return string Token
   */
  private function getGDriveToken()
  {

    $bytes = random_bytes(36);
    $token = bin2hex($bytes);
    $backupDirectoryPath = $this->post['backupDirectoryPath'];
    if (!preg_match("/^[a-zA-Z0-9\_\ \-\.]+$/", $backupDirectoryPath)) {
      return ['status' => 'msg', 'why' => __('Entered directory name does not match allowed characters (Google Drive).', 'backup-backup'), 'level' => 'warning'];
    }

    if (strlen(trim($backupDirectoryPath)) < 3) {
      return ['status' => 'msg', 'why' => __('Entered directory name is too short, min 3 characters (Google Drive).', 'backup-backup'), 'level' => 'warning'];
    }

    if (strlen(trim($backupDirectoryPath)) > 48) {
      return ['status' => 'msg', 'why' => __('Entered directory name is too long, max 48 characters (Google Drive).', 'backup-backup'), 'level' => 'warning'];
    }

    update_option('bmi_pro_gd_token', $token);
    Dashboard\bmi_set_config('STORAGE::EXTERNAL::GDRIVE::DIRNAME', $backupDirectoryPath);

    return [ 'status' => 'success', 'token' => $token];
  }

  /**
   * keepGDriveToken - Saves Client Token for GDrive API - BMI API communication
   *
   * @return json status
   */
  private function keepGDriveToken()
  {

    $receivedToken = $this->post['receivedToken'];
    $receivedClientID = $this->post['receivedClientID'];

    $currentToken = get_option('bmi_pro_gd_token', false);

    if ($currentToken === $receivedToken) {

      update_option('bmi_pro_gd_client_id', $receivedClientID);
      return ['status' => 'success'];
    } else {

      return ['status' => 'token_mismatch'];
    }
  }

  /**
   * getGDriveConnectionStatus - Returns Connection Status for PHP
   *
   * @return boolean true on connected | false on disconnected
   */
  public function getGDriveConnectionStatus()
  {

    $status = $this->verifyGDriveConnection();
    if (isset($status['result']) && $status['result'] == 'connected') {
      return true;
    } else {
      return false;
    }
  }

  /**
   * verifyGDriveConnection - Checks if the GDrive is still granted and tokens are not expired
   *
   * @return json rtoken
   */
  private function verifyGDriveConnection()
  {

    // Install Drive Keys
    $tempKeyDriveFile = BMI_TMP . DIRECTORY_SEPARATOR . 'driveKeys.php';
    if (file_exists($tempKeyDriveFile)) {

      $driveKeys = file_get_contents($tempKeyDriveFile);

      if (strpos($driveKeys, "\n") !== false) {

        $lines = explode("\n", $driveKeys);

        if (sizeof($lines) == 4) {
          $pro_gd_token = substr($lines[1], 2);
          $pro_gd_client_id = substr($lines[2], 2);

          if (function_exists('wp_load_alloptions')) {
            wp_load_alloptions(true);
          }
          delete_option('bmi_pro_gd_token');
          delete_option('bmi_pro_gd_client_id');
          if (function_exists('wp_load_alloptions')) {
            wp_load_alloptions(true);
          }
          update_option('bmi_pro_gd_token', $pro_gd_token);
          update_option('bmi_pro_gd_client_id', $pro_gd_client_id);
        }
      }

      if (strpos(site_url(), 'tastewp') !== false) {
        if (function_exists('wp_load_alloptions')) {
          wp_load_alloptions(true);
        }

        update_option('__tastewp_redirection_performed', true);
        update_option('auto_smart_tastewp_redirect_performed', 1);
        update_option('tastewp_auto_activated', true);
        update_option('__tastewp_sub_requested', true);
      }

      unlink($tempKeyDriveFile);
    }

    $baseurl = home_url();
    if (substr($baseurl, 0, 4) != 'http') {
      if (is_ssl()) $baseurl = 'https://' . home_url();
      else $baseurl = 'http://' . home_url();
    }

    $client_token = get_option('bmi_pro_gd_client_id', '');
    $site_token = get_option('bmi_pro_gd_token', '');

    if (strlen($site_token) < 60 || strlen($client_token) < 60) {
      return ['status' => 'success', 'result' => 'disconnected'];
    }

    $url = 'https://authentication.backupbliss.com/v1/gdrive/verify';
    $response = wp_remote_post($url, array(
      'method' => 'POST',
      'timeout' => 15,
      'redirection' => 2,
      'httpversion' => '1.0',
      'blocking' => true,
      'body' => array(
        'client_id' => get_option('bmi_pro_gd_client_id', ''),
        'site_token' => get_option('bmi_pro_gd_token', ''),
        'redirect_uri' => $baseurl,
        'force_refresh' => get_transient('bmip_gd_issue') === 'auth_error' && get_transient('bmi_pro_access_token') !== false
      )
    ));

    $res = 'disconnected';
    if (is_wp_error($response)) {
      $error_message = $response->get_error_message();
      Logger::error('[BMI PRO] Something went wrong during GDrive connection verification:' . $error_message);
      return ['status' => 'error', 'result' => 'disconnected'];
    } else {
      $result = json_decode($response['body']);
      if (isset($result->status)) {
        if (isset($result->expiration) && isset($result->access_token)) {
          $expiresInSeconds = intval($result->expiration) - intval(microtime(true));
          $accessToken = $result->access_token;
          set_transient('bmi_pro_access_token', $accessToken, $expiresInSeconds);
        }

        if ($result->status == 'disconnected') {
          $res = 'disconnected';
          if (get_transient('bmip_gd_issue') === 'auth_error' && get_transient('bmi_pro_access_token') !== false) {
            set_transient('bmip_gd_issue', 'auth_error_disconnected');
            delete_transient('bmi_pro_access_token');
          }
        }
        if ($result->status == 'connected'){
          $res = 'connected';
          if (in_array(get_transient('bmip_gd_issue'), ['auth_error', 'auth_error_disconnected'])) delete_transient('bmip_gd_issue');
        }
        if ($result->status == 'error') $res = 'disconnected';
      }
      return ['status' => 'success', 'result' => $res];
    }
  }

  /**
   * removeGDriveConnection - Removed GDrive connection from BMI API
   *
   * @return json rtoken
   */
  private function removeGDriveConnection()
  {

    $baseurl = home_url();
    if (substr($baseurl, 0, 4) != 'http') {
      if (is_ssl()) $baseurl = 'https://' . home_url();
      else $baseurl = 'http://' . home_url();
    }

    $client_token = get_option('bmi_pro_gd_client_id', '');
    $site_token = get_option('bmi_pro_gd_token', '');

    if (strlen($site_token) < 60 || strlen($client_token) < 60) {
      return ['status' => 'success'];
    }

    $url = 'https://authentication.backupbliss.com/v1/gdrive/disconnect';
    $response = wp_remote_post($url, array(
      'method' => 'POST',
      'timeout' => 15,
      'redirection' => 2,
      'httpversion' => '1.0',
      'blocking' => true,
      'body' => array(
        'client_id' => get_option('bmi_pro_gd_client_id', ''),
        'site_token' => get_option('bmi_pro_gd_token', ''),
        'redirect_uri' => $baseurl
      )
    ));

    if (is_wp_error($response)) {
      $error_message = $response->get_error_message();
      Logger::error('[BMI PRO] Something went wrong during GDrive removal process:' . $error_message);
      return ['status' => 'error'];
    }
  }

  /**
   * disconnectGDriveToken - Removes connection with GDrive API
   *
   * @return json status
   */
  private function disconnectGDriveToken()
  {

    $this->removeGDriveConnection();
    delete_option('bmi_pro_gd_client_id');
    delete_option('bmi_pro_gd_token');
    delete_transient('bmi_pro_access_token');
    Dashboard\bmi_set_config('STORAGE::EXTERNAL::GDRIVE', 'false');

    return ['status' => 'success'];
  }

  private function dismissDropboxNotice()
  {
    update_option('bmip_dropbox_dismiss_issue', true);
    return ['status' => 'success'];
  }

  public function saveAWSConfig()
  {
    $accessKey = isset($this->post['access-key']) ? $this->post['access-key'] : '';
    $secretKey = isset($this->post['secret-key']) ? $this->post['secret-key'] : '';
    $bucket = isset($this->post['bucket']) ? $this->post['bucket'] : '';
    $sse = isset($this->post['sse']) ? $this->post['sse'] : '';
    $storageClass = isset($this->post['storage-class']) ? $this->post['storage-class'] : 'STANDARD';
    $path = isset($this->post['path']) ? $this->post['path'] : '';
    $path = trim($path, '/');
    $region = isset($this->post['region']) ? $this->post['region'] : '';

    // VALIDATE INPUTS
    if (empty($accessKey) || empty($secretKey) || empty($bucket) || empty($region)) {
      return ['status' => 'error', 'msg' => __('Please fill all the required fields.', 'backup-backup')];
    }

    if (!preg_match('/^[a-zA-Z0-9-]*$/', $bucket)) {
      return ['status' => 'error', 'msg' => __('Bucket name can only contain letters, numbers and hyphens.', 'backup-backup')];
    }

    if (!in_array($storageClass, ['STANDARD', 'STANDARD_IA', 'REDUCED_REDUNDANCY'])) {
      return ['status' => 'error', 'msg' => __('Invalid storage class.', 'backup-backup')];
    }

    if (!in_array($sse, ['AES256', ''])) {
      return ['status' => 'error', 'msg' => __('Invalid server-side encryption.', 'backup-backup')];
    }

    if (!in_array($region, [
      'us-east-1','us-east-2','us-west-1','us-west-2','af-south-1','ap-east-1','ap-south-1','ap-northeast-3','ap-northeast-2','ap-southeast-1','ap-southeast-2','ap-northeast-1','ca-central-1','eu-central-1','eu-west-1','eu-west-2','eu-south-1','eu-west-3','eu-north-1','me-south-1','sa-east-1'
    ])) {
      return ['status' => 'error', 'msg' => __('Invalid region.', 'backup-backup')];
    }

    // Test the connection
    require_once BMI_INCLUDES . '/external/s3.php';
    $s3 = new S3('aws');

    $testConnection = $s3->testConnection( $accessKey, $secretKey, $bucket, $region, $path, $storageClass, $sse);

    if ($testConnection['status'] == 'error') {
      return ['status' => 'error', 'msg' => $testConnection['error']];
    }

    Dashboard\bmi_set_config('STORAGE::EXTERNAL::AWS', 'true');
    update_option('bmip_aws_access_key', $accessKey);
    update_option('bmip_aws_secret_key', $secretKey);
    update_option('bmip_aws_bucket', $bucket);
    update_option('bmip_aws_storage_class', $storageClass);
    update_option('bmip_aws_path', $path);
    update_option('bmip_aws_sse', $sse);
    set_transient('bmip_aws_connection_status', true, HOUR_IN_SECONDS);
    update_option('bmip_aws_region', $region);

    return ['status' => 'success'];
  }

  public function disconnectAWS()
  {

    require_once BMI_INCLUDES . '/external/s3.php';
    $s3 = new S3('aws');
    $s3->disconnect();

    return ['status' => 'success'];
  }

  /**
   * Verify AWS S3 connection
   *
   * @return array
   */
  private function verifyAWSConnection()
  {
      require_once BMI_INCLUDES . '/external/s3.php';
      $s3 = new S3('aws');
      $status = $s3->verifyConnection();
      if ($status['result'] == 'connected') {
        return [
          'status' => 'success',
          'result' => 'connected',
          'configs' => $s3->retrieveS3Configs(),
        ];
      }
      return [
          'status' => 'success',
          'result' => 'disconnected',
      ];
  }

    /**
     * Save the selected files, directories and tables for restore process
     * required post data:
     * - access-key
     * - secret-key
     * - bucket
     * - region
     * - path (OPTIONAL)
     * 
     * 
     * @return array{msg: mixed, status: string|array{msg: string, status: string}|array{status: string}}
     */
    public function saveWasabiConfig()
  {
      $accessKey = isset($this->post['access-key']) ? $this->post['access-key'] : '';
      $secretKey = isset($this->post['secret-key']) ? $this->post['secret-key'] : '';
      $bucket = isset($this->post['bucket']) ? $this->post['bucket'] : '';
      $path = isset($this->post['path']) ? $this->post['path'] : '';
      $path = trim($path, '/');
      $region = isset($this->post['region']) ? $this->post['region'] : '';
  
      // VALIDATE INPUTS
      if (empty($accessKey) || empty($secretKey) || empty($bucket) || empty($region)) {
          return ['status' => 'error', 'msg' => __('Please fill all the required fields.', 'backup-backup')];
      }
        
      // Test the connection
      require_once BMI_INCLUDES . '/external/s3.php';
      $s3 = new S3('wasabi');
  
      $testConnection = $s3->testConnection($accessKey, $secretKey, $bucket, $region, $path);
  
      if ($testConnection['status'] == 'error') {
          return ['status' => 'error', 'msg' => $testConnection['error']];
      }
  
      Dashboard\bmi_set_config('STORAGE::EXTERNAL::WASABI', 'true');
      update_option('bmip_wasabi_access_key', $accessKey);
      update_option('bmip_wasabi_secret_key', $secretKey);
      update_option('bmip_wasabi_bucket', $bucket);
      update_option('bmip_wasabi_path', $path);
      update_option('bmip_wasabi_sse', '');
      update_option('bmip_wasabi_storage_class', 'STANDARD');
      set_transient('bmip_wasabi_connection_status', true, HOUR_IN_SECONDS);
      update_option('bmip_wasabi_region', $region);
  
      return ['status' => 'success'];
  }
  
  public function disconnectWasabi()
  {
      require_once BMI_INCLUDES . '/external/s3.php';
      $s3 = new S3('wasabi');
      $s3->disconnect();
  
      return ['status' => 'success'];
  }
  
  private function verifyWasabiConnection()
  {
      require_once BMI_INCLUDES . '/external/s3.php';
      $s3 = new S3('wasabi');
      $status = $s3->verifyConnection();
      if ($status['result'] == 'connected') {
          return [
              'status' => 'success',
              'result' => 'connected',
              'configs' => $s3->retrieveS3Configs(),
          ];
      }
      return [
          'status' => 'success',
          'result' => 'disconnected',
      ];
  }

    /**
   * shareDomainForAutoCron - Allows our API to keep scheduled backups on time
   *
   * @return json rtoken
   */
  private function shareDomainForAutoCron()
  {

    $cron_shared = get_option('bmi_cron_new_domain_done', false);
    if ($cron_shared) return 0;

    $baseurl = home_url();
    if (substr($baseurl, 0, 4) != 'http') {
      if (is_ssl()) $baseurl = 'https://' . home_url();
      else $baseurl = 'http://' . home_url();
    }

    $url = 'https://authentication.backupbliss.com/v1/crons/connect';
    $response = wp_remote_post($url, array(
      'method' => 'POST',
      'timeout' => 15,
      'redirection' => 2,
      'httpversion' => '1.0',
      'blocking' => true,
      'body' => array('site' => $baseurl)
    ));

    if (!is_wp_error($response)) {
      $response = json_decode($response['body'], true);
      if (isset($response['status']) && $response['status'] === 'success') {
        update_option('bmi_cron_new_domain_done', true);
      }

      return 0;
    }

    return 0;
  }

  /**
   * randomString - Generates "random" string
   *
   * @return string "random"
   */
  private function randomString($length = 64)
  {

    $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $str = "";

    for ($i = 0; $i < $length; ++$i) {

      $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }

    return $str;
  }

  /**
   * downloadCloudBackup - Downloads Cloud Backup to Local Storage
   *
   * @return json status
   */
  private function downloadCloudBackup()
  {

    $secret = false;
    if (isset($this->post['secret'])) $secret = $this->post['secret'];

    $lock = BMI_BACKUPS . '/.migration_lock';
    if (file_exists($lock) && (time() - filemtime($lock)) < 1) {
      $lockContent = file_get_contents($lock);
      if ($lockContent !== $secret) {
        return ['status' => 'msg', 'why' => __('Download process is currently running, please wait till it complete.', 'backup-backup'), 'level' => 'warning'];
      }
    }

    require_once BMI_INCLUDES . '/progress/migration.php';

    $step = intval($this->post['step']);
    $storage = $this->post['storage'];
    $startRestoreProcess = isset($this->post['startRestoreProcess']) ? $this->post['startRestoreProcess'] : 'true';

    $clearFile = ($step === 0) ? false : true;
    $migration = new MigrationProgress($clearFile);
    $migration->start();

    if ($storage == 'backupbliss') {

      require_once BMI_INCLUDES . '/external/backupbliss.php';
      $backupbliss = new BackupBliss();

      $backupDetails = false;
      $fileId = $this->post['fileId'];

      if ($step === 0 || (!isset($this->post['size']) || $this->post['size'] == false || !is_numeric($this->post['size']))) {

        $migration->log((__('Backup & Migration version: ', 'backup-backup') . BMI_VERSION));
        $migration->log(__('Creating lock file', 'backup-backup'));
        $secret = $this->randomString();
        file_put_contents($lock, $secret);

        $migration->log('Download intialized', 'INFO');
        $migration->log('Getting backup details from BackupBliss...', 'STEP');
        $backupDetails = $backupbliss->getFileDetailByName($fileId);

        if (!$backupDetails) {

          $migration->log("Couldn't fetch backup details from cloud.", 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('error_during_downloading_backup', 'verbose');
          $migration->log('#002', 'END-CODE');
          $migration->end();

          return ['status' => 'error'];
        }

        $size = intval($backupDetails['size']);
        $originalFilename = $backupDetails['name'];

        $migration->log('Backup details received!', 'SUCCESS');
        $migration->log('Backup original name: ' . $originalFilename, 'INFO');
        $migration->log('Starting download process...', 'STEP');

        $availableMemory = BMP::getAvailableMemoryInBytes();
        $bytesPerRequest = intval($availableMemory / 4);

        $migration->log('Single batch will use up to: ' . $bytesPerRequest . ' bytes (~' . intval($bytesPerRequest / 1024 / 1024 / 2) . ' MBs)', 'INFO');

        $fileIterator = 2;
        $originalFilenameInfo = pathinfo($originalFilename);
        $extension = $originalFilenameInfo['extension'];
        $originalFilename = $originalFilenameInfo['filename'];
        if ($originalFilenameInfo['extension'] == 'gz') {
          $originalFilename = pathinfo($originalFilename, PATHINFO_FILENAME);
          $extension = 'tar.gz';
        }
        
        $backupDestinationPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $originalFilename . '.' . $extension;
        $finalName = $originalFilename . '.' . $extension;

        while (file_exists($backupDestinationPath)) {
          $backupDestinationPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $originalFilename . '-' . $fileIterator . '.' . $extension;
          $finalName = $originalFilename . '-' . $fileIterator . '.' . $extension;
          $fileIterator++;
        }

        $originalFilename = $finalName;

        $backupDestinationPath .= '.crdownload';

      } else {

        $size = intval($this->post['size']);
        $originalFilename = $this->post['filename'];
        $backupDestinationPath = $this->post['writepath'];
        $bytesPerRequest = intval($this->post['chunksize']);
      }

      $md5 = $this->post['md5'];

      $totalBatches = ceil($size / (256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024 / 2)));

      if ($totalBatches <= $step) {

        $migration->log('Download process finished!', 'SUCCESS');
        $migration->log('Verifying MD5 checksum of downloaded file...', 'STEP');

        rename($backupDestinationPath, str_replace('.crdownload', '', $backupDestinationPath));
        $backupDestinationPath = str_replace('.crdownload', '', $backupDestinationPath);
  

        $local_md5 = hash_file('md5', $backupDestinationPath);
        if (file_exists($backupDestinationPath) && $local_md5 == $md5) {

          $migration->log('Downloaded MD5: ' . $local_md5, 'INFO');
          $migration->log('Expected MD5: ' . $md5, 'INFO');
          $migration->log('File MD5 checksum is correct!', 'SUCCESS');
        } else {

          $migration->log('File MD5 checksum is NOT correct!', 'ERROR');
          $migration->log('Downloaded MD5: ' . $local_md5, 'ERROR');
          $migration->log('Expected MD5: ' . $md5, 'ERROR');
          $migration->log('Downloaded file path: ' . $backupDestinationPath, 'ERROR');
          $migration->log('File exist?: ' . (file_exists($backupDestinationPath) ? "Yes" : "No?"), 'ERROR');
          $migration->log('For security reasons, I will remove the file and stop the process...', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('error_during_downloading_backup', 'verbose');
          $migration->log('#002', 'END-CODE');
          $migration->end();

          if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
          return ['status' => 'error'];
        }

        $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
        if (file_exists($lock)) @unlink($lock);
        if ($startRestoreProcess == 'true') {
          $migration->log('Requesting restoration process...', 'STEP');

          $migration->log('#205', 'END-CODE');
        } else {
          $migration->log('Download process finished!', 'SUCCESS');
          $migration->log('#206', 'END-CODE');
        }
        $migration->progress(100);
        $migration->end();

        return ['status' => 'success', 'finished' => 'true', 'filename' => $originalFilename];
      } else {

        $chunkSize = 256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024 / 2);
        $startRange = ($step * $chunkSize);
        if ($step !== 0) $startRange = $startRange + 1;
        $endRange = (($step + 1) * $chunkSize);
        if ($endRange > $size) $endRange = $size;
        $percentage = intval(($endRange / $size) * 100);

        $data = $backupbliss->getFile($fileId, $startRange, $endRange);

        if (!$data["file_detail"] || !$data["file_data"]) {

          $migration->log("Couldn't fetch backup file from cloud.", 'ERROR');
          $migration->log('For security reasons, I will remove the file (if exist) and stop the process...', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('error_during_downloading_backup', 'verbose');
          $migration->log('#002', 'END-CODE');
          $migration->end();

          if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
          return ['status' => 'error'];
        }

        if ((is_dir(dirname($backupDestinationPath)) && file_exists($backupDestinationPath)) || $step === 0) {

          $backupFile = fopen($backupDestinationPath, 'ab');
          fwrite($backupFile, $data['file_data']);
          fclose($backupFile);
        } else {

          $migration->log('File is not writable or directory does not exist.', 'ERROR');
          $migration->log('File: ' . basename($backupDestinationPath), 'ERROR');
          $migration->log('Dirname: ' . dirname($backupDestinationPath), 'ERROR');
          $migration->log('For security reasons, I will remove the file and stop the process...', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('error_during_downloading_backup', 'verbose');
          $migration->log('#002', 'END-CODE');
          $migration->end();

          if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
          return ['status' => 'error'];
        }

        $migration->log('Download progress (' . ($step + 1) .  '/' . $totalBatches . '): ' . $endRange . '/' . $size . ' (' . $percentage . '%)', 'INFO');
        $migration->progress($percentage);
        $migration->end();

        return [
          'status' => 'success',
          'size' => $size,
          'md5' => $md5,
          'finished' => 'false',
          'originalFilename' => $originalFilename,
          'writepath' => $backupDestinationPath,
          'chunksize' => $bytesPerRequest,
          'secret' => $secret
        ];
      }
    }

    if ($storage == 'googledrive') {

      require_once BMI_INCLUDES . '/external/google-drive.php';
      $gdrive = new GDrive();

      $backupDetails = false;
      $fileId = $this->post['fileId'];

      if ($step === 0 || (!isset($this->post['size']) || $this->post['size'] == false || !is_numeric($this->post['size']))) {

        $migration->log((__('Backup & Migration version: ', 'backup-backup') . BMI_VERSION));
        $migration->log(__('Creating lock file', 'backup-backup'));
        $secret = $this->randomString();
        file_put_contents($lock, $secret);

        $migration->log('Download intialized', 'INFO');
        $migration->log('Getting backup details from Google Drive...', 'STEP');
        $backupDetails = $gdrive->getGoogleDriveFileMeta($fileId);

        if ($backupDetails == false || !isset($backupDetails['data']) || $backupDetails['data'] == false) {

          $migration->log('It seem like I was unable to get backup details from cloud.', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('error_during_downloading_backup', 'verbose');
          $migration->log('#002', 'END-CODE');
          $migration->end();

          return ['status' => 'error'];
        }

        $size = intval($backupDetails['data']->size);
        $md5 = $backupDetails['data']->md5Checksum;
        $originalFilename = $backupDetails['data']->originalFilename;

        $migration->log('Backup details received!', 'SUCCESS');
        $migration->log('Backup original name: ' . $originalFilename, 'INFO');
        $migration->log('Starting download process...', 'STEP');

        $availableMemory = BMP::getAvailableMemoryInBytes();
        $bytesPerRequest = intval($availableMemory / 4);

        $migration->log('Single batch will use up to: ' . $bytesPerRequest . ' bytes (~' . intval($bytesPerRequest / 1024 / 1024 / 2) . ' MBs)', 'INFO');

        $fileIterator = 2;
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $originalFilename = pathinfo($originalFilename, PATHINFO_FILENAME);
        if ($extension == 'gz') {
          $originalFilename = pathinfo($originalFilename, PATHINFO_FILENAME);
          $extension = 'tar.gz';
        }
        $backupDestinationPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $originalFilename . '.' . $extension;
        $finalName = $originalFilename . '.' . $extension;

        while (file_exists($backupDestinationPath)) {
          $backupDestinationPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $originalFilename . '-' . $fileIterator . '.' . $extension;
          $finalName = $originalFilename . '-' . $fileIterator . '.' . $extension;
          $fileIterator++;
        }

        $originalFilename = $finalName;

        $backupDestinationPath .= '.crdownload';

      } else {

        $size = intval($this->post['size']);
        $md5 = $this->post['md5'];
        $originalFilename = $this->post['filename'];
        $backupDestinationPath = $this->post['writepath'];
        $bytesPerRequest = intval($this->post['chunksize']);
      }

      $totalBatches = ceil($size / (256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024 / 2)));

      if ($totalBatches <= $step) {

        $migration->log('Download process finished!', 'SUCCESS');
        $migration->log('Verifying MD5 checksum of downloaded file...', 'STEP');

        rename($backupDestinationPath, str_replace('.crdownload', '', $backupDestinationPath));
        $backupDestinationPath = str_replace('.crdownload', '', $backupDestinationPath);
  

        $local_md5 = md5_file($backupDestinationPath);
        if (file_exists($backupDestinationPath) && $local_md5 == $md5) {

          $migration->log('Downloaded MD5: ' . $local_md5, 'INFO');
          $migration->log('Expected MD5: ' . $md5, 'INFO');
          $migration->log('File MD5 checksum is correct!', 'SUCCESS');
        } else {

          $migration->log('File MD5 checksum is NOT correct!', 'ERROR');
          $migration->log('Downloaded MD5: ' . $local_md5, 'ERROR');
          $migration->log('Expected MD5: ' . $md5, 'ERROR');
          $migration->log('Downloaded file path: ' . $backupDestinationPath, 'ERROR');
          $migration->log('File exist?: ' . (file_exists($backupDestinationPath) ? "Yes" : "No?"), 'ERROR');
          $migration->log('For security reasons, I will remove the file and stop the process...', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('error_during_downloading_backup', 'verbose');
          $migration->log('#002', 'END-CODE');
          $migration->end();

          if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
          return ['status' => 'error'];
        }

        $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
        if (file_exists($lock)) @unlink($lock);
        if ($startRestoreProcess == 'true') {
          $migration->log('Requesting restoration process...', 'STEP');

          $migration->log('#205', 'END-CODE');
        } else {
          $migration->log('Download process finished!', 'SUCCESS');
          $migration->log('#206', 'END-CODE');
        }

        $migration->progress(100);
        $migration->end();

        return ['status' => 'success', 'finished' => 'true', 'filename' => $originalFilename];
      } else {

        $chunkSize = 256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024 / 2);
        $startRange = ($step * $chunkSize);
        if ($step !== 0) $startRange = $startRange + 1;
        $endRange = (($step + 1) * $chunkSize);
        if ($endRange > $size) $endRange = $size;
        $currentRange = $startRange . '-' . $endRange;
        $percentage = intval(($endRange / $size) * 100);

        $contents = $gdrive->getGoogleDriveFileContents($fileId, $currentRange);

        if ($contents == false || !isset($contents['data']) || $contents['data'] == false) {

          $migration->log('It seem like I was unable to get backup content from cloud.', 'ERROR');
          $migration->log('For security reasons, I will remove the file (if exist) and stop the process...', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('error_during_downloading_backup', 'verbose');
          $migration->log('#002', 'END-CODE');
          $migration->end();

          if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
          return ['status' => 'error'];
        }

        if ((is_dir(dirname($backupDestinationPath)) && file_exists($backupDestinationPath)) || $step === 0) {

          $backupFile = fopen($backupDestinationPath, 'ab');
          fwrite($backupFile, $contents['data']);
          fclose($backupFile);
        } else {

          $migration->log('File is not writable or directory does not exist.', 'ERROR');
          $migration->log('File: ' . basename($backupDestinationPath), 'ERROR');
          $migration->log('Dirname: ' . dirname($backupDestinationPath), 'ERROR');
          $migration->log('For security reasons, I will remove the file and stop the process...', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('error_during_downloading_backup', 'verbose');
          $migration->log('#002', 'END-CODE');
          $migration->end();

          if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
          return ['status' => 'error'];
        }

        $migration->log('Download progress (' . ($step + 1) .  '/' . $totalBatches . '): ' . $endRange . '/' . $size . ' (' . $percentage . '%)', 'INFO');
        $migration->progress($percentage);
        $migration->end();

        return [
          'status' => 'success',
          'size' => $size,
          'md5' => $md5,
          'finished' => 'false',
          'originalFilename' => $originalFilename,
          'writepath' => $backupDestinationPath,
          'chunksize' => $bytesPerRequest,
          'secret' => $secret
        ];
      }
    }

    if ($storage == 'ftp') {

      require_once BMI_INCLUDES . '/external/ftp.php';
      $ftp = new FTP();

      $backupDetails = false;
      $fileId = $this->post['fileId'];

      if ($step === 0 || (!isset($this->post['size']) || $this->post['size'] == false || !is_numeric($this->post['size']))) {

        $migration->log((__('Backup & Migration version: ', 'backup-backup') . BMI_VERSION));
        $migration->log(__('Creating lock file', 'backup-backup'));
        $secret = $this->randomString();
        file_put_contents($lock, $secret);

        $migration->log('Download intialized', 'INFO');
        $migration->log('Getting backup details from FTP...', 'STEP');
        $backupDetails = $ftp->getFtpDriveFileMeta($fileId);

        if ($backupDetails == false || !isset($backupDetails['data']) || $backupDetails['data'] == false) {

          $migration->log('It seem like I was unable to get backup details from cloud.', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('#002', 'END-CODE');
          $migration->end();

          return ['status' => 'error'];
        }

        $size = intval($backupDetails['data']['size']);

        $originalFilename = $backupDetails['data']['name'];

        $migration->log('Backup details received!', 'SUCCESS');
        $migration->log('Backup original name: ' . $originalFilename, 'INFO');
        $migration->log('Starting download process...', 'STEP');

        $availableMemory = BMP::getAvailableMemoryInBytes();
        $bytesPerRequest = intval($availableMemory / 4);

        $migration->log('Single batch will use up to: ' . $bytesPerRequest . ' bytes (~' . intval($bytesPerRequest / 1024 / 1024 / 2) . ' MBs)', 'INFO');

        $fileIterator = 2;
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $originalFilename = pathinfo($originalFilename, PATHINFO_FILENAME);
        if ($extension == 'gz') {
          $originalFilename = pathinfo($originalFilename, PATHINFO_FILENAME);
          $extension = 'tar.gz';
        }
        $backupDestinationPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $originalFilename . '.' . $extension;
        $finalName = $originalFilename . '.' . $extension;

        while (file_exists($backupDestinationPath)) {
          $backupDestinationPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $originalFilename . '-' . $fileIterator . '.' . $extension;
          $finalName = $originalFilename . '-' . $fileIterator . '.' . $extension;
          $fileIterator++;
        }

        $originalFilename = $finalName;

        $backupDestinationPath .= '.crdownload';

      } else {

        $size = intval($this->post['size']);
        $md5 = $this->post['md5'];
        $originalFilename = $this->post['filename'];
        $backupDestinationPath = $this->post['writepath'];
        $bytesPerRequest = intval($this->post['chunksize']);
      }

      $totalBatches = ceil($size / (256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024 / 2)));
      $md5 = $this->post['md5'];

      if ($totalBatches <= $step) {
        $migration->log('Download process finished!', 'SUCCESS');
        $migration->log('Verifying MD5 checksum of downloaded file...', 'STEP');

        rename($backupDestinationPath, str_replace('.crdownload', '', $backupDestinationPath));
        $backupDestinationPath = str_replace('.crdownload', '', $backupDestinationPath);  

        $local_md5 = md5_file($backupDestinationPath);
        if (file_exists($backupDestinationPath) && $local_md5 == $md5) {

          $migration->log('Downloaded MD5: ' . $local_md5, 'INFO');
          $migration->log('Expected MD5: ' . $md5, 'INFO');
          $migration->log('File MD5 checksum is correct!', 'SUCCESS');
        } else {

          $migration->log('File MD5 checksum is NOT correct!', 'ERROR');
          $migration->log('Downloaded MD5: ' . $local_md5, 'ERROR');
          $migration->log('Expected MD5: ' . $md5, 'ERROR');
          $migration->log('Downloaded file path: ' . $backupDestinationPath, 'ERROR');
          $migration->log('File exist?: ' . (file_exists($backupDestinationPath) ? "Yes" : "No?"), 'ERROR');
          $migration->log('For security reasons, I will remove the file and stop the process...', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('#002', 'END-CODE');
          $migration->end();

          if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
          return ['status' => 'error'];
        }

        $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
        if (file_exists($lock)) @unlink($lock);
        if ($startRestoreProcess == 'true') {
          $migration->log('Requesting restoration process...', 'STEP');

          $migration->log('#205', 'END-CODE');
        } else {
          $migration->log('Download process finished!', 'SUCCESS');
          $migration->log('#206', 'END-CODE');
        }
        $migration->progress(100);
        $migration->end();

        return ['status' => 'success', 'finished' => 'true', 'filename' => $originalFilename];
      } else {
        $chunkSize = 256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024 / 2);
        $startRange = ($step * $chunkSize);
        if ($step !== 0) $startRange = $startRange + 1;
        $endRange = (($step + 1) * $chunkSize);
        if ($endRange > $size) $endRange = $size;
        $percentage = intval(($endRange / $size) * 100);

        $contents = $ftp->getFtpDriveFileContents($fileId, $startRange, $endRange);
        // wp_send_json($contents);
        if ($contents == false || !isset($contents['data']) || $contents['data'] == false) {

          $migration->log('It seem like I was unable to get backup content from cloud.', 'ERROR');
          $migration->log('For security reasons, I will remove the file (if exist) and stop the process...', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('#002', 'END-CODE');
          $migration->end();

          if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
          return ['status' => 'error'];
        }

        if ((is_dir(dirname($backupDestinationPath)) && file_exists($backupDestinationPath)) || $step === 0) {

          $backupFile = fopen($backupDestinationPath, 'ab');
          fwrite($backupFile, $contents['data']);
          fclose($backupFile);
        } else {

          $migration->log('File is not writable or directory does not exist.', 'ERROR');
          $migration->log('File: ' . basename($backupDestinationPath), 'ERROR');
          $migration->log('Dirname: ' . dirname($backupDestinationPath), 'ERROR');
          $migration->log('For security reasons, I will remove the file and stop the process...', 'ERROR');
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);

          $migration->log('#002', 'END-CODE');
          $migration->end();

          if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
          return ['status' => 'error'];
        }

        $migration->log('Download progress (' . ($step + 1) . '/' . $totalBatches . '): ' . $endRange . '/' . $size . ' (' . $percentage . '%)', 'INFO');
        $migration->progress($percentage);
        $migration->end();

        return [
          'status' => 'success',
          'size' => $size,
          'md5' => $md5,
          'finished' => 'false',
          'originalFilename' => $originalFilename,
          'writepath' => $backupDestinationPath,
          'chunksize' => $bytesPerRequest,
          'secret' => $secret
        ];
      }
    }

    if ($storage == 's3'){
      
      require_once BMI_INCLUDES . '/external/s3.php';
      $provider = $this->post['provider'];
      $s3 = new S3($provider);
  
      $backupDetails = false;
      $fileId = $this->post['fileId'];
      $md5 = $this->post['md5'];
  
      if ($step === 0 || (!isset($this->post['size']) || $this->post['size'] == false || !is_numeric($this->post['size']))) {
          $migration->log((__('Backup & Migration version: ', 'backup-backup') . BMI_VERSION));
          $migration->log(__('Creating lock file', 'backup-backup'));
          $secret = $this->randomString();
          file_put_contents($lock, $secret);
  
          $migration->log('Download initialized', 'INFO');
          $migration->log('Getting backup details from S3...', 'STEP');
          $backupDetails = $s3->getFileMeta($fileId);
  
          if ($backupDetails == false) {
              $migration->log('It seems like I was unable to get backup details from cloud.', 'ERROR');
              $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
              if (file_exists($lock)) @unlink($lock);
  
              $migration->log('error_during_downloading_backup', 'verbose');
              $migration->log('#002', 'END-CODE');
              $migration->end();
  
              return ['status' => 'error'];
          }
  
          $size = intval($backupDetails['size']);
          $originalFilename = $fileId;
  
          $migration->log('Backup details received!', 'SUCCESS');
          $migration->log('Backup original name: ' . $originalFilename, 'INFO');
          $migration->log('Starting download process...', 'STEP');
  
          $availableMemory = BMP::getAvailableMemoryInBytes();
          $bytesPerRequest = intval($availableMemory / 4);
  
          $migration->log('Single batch will use up to: ' . $bytesPerRequest . ' bytes (~' . intval($bytesPerRequest / 1024 / 1024 / 2) . ' MBs)', 'INFO');
  
          $fileIterator = 2;

          $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
          $originalFilename = pathinfo($originalFilename, PATHINFO_FILENAME);
          if ($extension == 'gz') {
              $originalFilename = pathinfo($originalFilename, PATHINFO_FILENAME);
              $extension = 'tar.gz';
          }

          $backupDestinationPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $originalFilename . '.' . $extension;
          $finalName = $originalFilename . '.' . $extension;
  
          while (file_exists($backupDestinationPath)) {
              $backupDestinationPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $originalFilename . '-' . $fileIterator . '.' . $extension;
              $finalName = $originalFilename . '-' . $fileIterator . '.' . $extension;
              $fileIterator++;
          }
  
          $originalFilename = $finalName;
          $backupDestinationPath .= '.crdownload';
  
      } else {
          $size = intval($this->post['size']);
          $md5 = $this->post['md5'];
          $originalFilename = $this->post['filename'];
          $backupDestinationPath = $this->post['writepath'];
          $bytesPerRequest = intval($this->post['chunksize']);
      }
  
      $totalBatches = ceil($size / (256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024 / 2)));
  
      if ($totalBatches <= $step) {
          $migration->log('Download process finished!', 'SUCCESS');
          $migration->log('Verifying MD5 checksum of downloaded file...', 'STEP');
  
          rename($backupDestinationPath, str_replace('.crdownload', '', $backupDestinationPath));
          $backupDestinationPath = str_replace('.crdownload', '', $backupDestinationPath);
  
          $local_md5 = md5_file($backupDestinationPath);
          if (file_exists($backupDestinationPath) && $local_md5 == $md5) {
              $migration->log('Downloaded MD5: ' . $local_md5, 'INFO');
              $migration->log('Expected MD5: ' . $md5, 'INFO');
              $migration->log('File MD5 checksum is correct!', 'SUCCESS');
          } else {
              $migration->log('File MD5 checksum is NOT correct!', 'ERROR');
              $migration->log('Downloaded MD5: ' . $local_md5, 'ERROR');
              $migration->log('Expected MD5: ' . $md5, 'ERROR');
              $migration->log('Downloaded file path: ' . $backupDestinationPath, 'ERROR');
              $migration->log('File exist?: ' . (file_exists($backupDestinationPath) ? "Yes" : "No?"), 'ERROR');
              $migration->log('For security reasons, I will remove the file and stop the process...', 'ERROR');
              $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
              if (file_exists($lock)) @unlink($lock);
  
              $migration->log('error_during_downloading_backup', 'verbose');
              $migration->log('#002', 'END-CODE');
              $migration->end();
  
              if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
              return ['status' => 'error'];
          }
  
          $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
          if (file_exists($lock)) @unlink($lock);
          if ($startRestoreProcess == 'true') {
              $migration->log('Requesting restoration process...', 'STEP');
  
              $migration->log('#205', 'END-CODE');
          } else {
              $migration->log('Download process finished!', 'SUCCESS');
              $migration->log('#206', 'END-CODE');
          }
  
          $migration->progress(100);
          $migration->end();
  
          return ['status' => 'success', 'finished' => 'true', 'filename' => $originalFilename];
      } else {
          $chunkSize = 256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024 / 2);
          $startRange = ($step * $chunkSize);
          if ($step !== 0) $startRange = $startRange + 1;
          $endRange = (($step + 1) * $chunkSize);
          if ($endRange > $size) $endRange = $size;
          $percentage = intval(($endRange / $size) * 100);
  
          $contents = $s3->getFileContent($fileId, strval($startRange) . '-' . strval($endRange));
  
          if ($contents == false) {
              $migration->log('It seems like I was unable to get backup content from cloud.', 'ERROR');
              $migration->log('For security reasons, I will remove the file (if exist) and stop the process...', 'ERROR');
              $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
              if (file_exists($lock)) @unlink($lock);
  
              $migration->log('error_during_downloading_backup', 'verbose');
              $migration->log('#002', 'END-CODE');
              $migration->end();
  
              if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
              return ['status' => 'error'];
          }
  
          if ((is_dir(dirname($backupDestinationPath)) && file_exists($backupDestinationPath)) || $step === 0) {
              $backupFile = fopen($backupDestinationPath, 'ab');
              fwrite($backupFile, $contents);
              fclose($backupFile);
          } else {
              $migration->log('File is not writable or directory does not exist.', 'ERROR');
              $migration->log('File: ' . basename($backupDestinationPath), 'ERROR');
              $migration->log('Dirname: ' . dirname($backupDestinationPath), 'ERROR');
              $migration->log('For security reasons, I will remove the file and stop the process...', 'ERROR');
              $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
              if (file_exists($lock)) @unlink($lock);
  
              $migration->log('error_during_downloading_backup', 'verbose');
              $migration->log('#002', 'END-CODE');
              $migration->end();
  
              if (file_exists($backupDestinationPath)) @unlink($backupDestinationPath);
              return ['status' => 'error'];
          }
  
          $migration->log('Download progress (' . ($step + 1) . '/' . $totalBatches . '): ' . $endRange . '/' . $size . ' (' . $percentage . '%)', 'INFO');
          $migration->progress($percentage);
          $migration->end();
  
          return [
              'status' => 'success',
              'size' => $size,
              'md5' => $md5,
              'finished' => 'false',
              'originalFilename' => $originalFilename,
              'writepath' => $backupDestinationPath,
              'chunksize' => $bytesPerRequest,
              'secret' => $secret
          ];
      }
    }

    if (file_exists($lock)) @unlink($lock);
    return ['status' => 'error'];
  }

    public function siteURL() {
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
      $domainName = $_SERVER['HTTP_HOST'];

      return $protocol . $domainName;
    }

    public function checkIfPHPCliExist(&$logger) {

      $shouldContinue = apply_filters('bmi_cli_enabled', true);
      if ($shouldContinue === false) {
        $logger->log(__('PHP CLI is disabled manually, plugin will omit all PHP CLI steps.', 'backup-backup'), 'warn');
        return false;
      }


      if (defined('BMI_CLI_ENABLED')) {
        $cliEnabled = apply_filters('bmi_cli_enabled', BMI_CLI_ENABLED);
        if ($cliEnabled === false) {
          $logger->log(__('PHP CLI is disabled manually, plugin will omit all PHP CLI steps.', 'backup-backup'), 'warn');
          return false;
        }
      }

      $logger->log(__('Looking for PHP CLI executable file.', 'backup-backup'), 'step');
      require_once BMI_INCLUDES . '/cli/php_cli_finder.php';
      $checker = new PHPCLICheck();
      $result = $checker->findPHP();

      if ($result === false) {

        if (!defined('BMI_CLI_ENABLED')) define('BMI_CLI_ENABLED', false);
        if (!defined('BMI_CLI_EXECUTABLE')) define('BMI_CLI_EXECUTABLE', false);
        if ($checker->ini_disabled === true) {
          $logger->log(__('PHP CLI is disabled in your php.ini file, the process may be unstable.', 'backup-backup'), 'warn');
        } else {
          $logger->log(__('Could not find proper PHP CLI executable, this process may be unstable.', 'backup-backup'), 'warn');
        }

        return false;

      } else {

        if (!defined('BMI_CLI_ENABLED')) define('BMI_CLI_ENABLED', apply_filters('bmi_cli_enabled', true));
        if (!defined('BMI_CLI_EXECUTABLE')) define('BMI_CLI_EXECUTABLE', $result['executable']);

        $logger->log(__('PHP CLI Filename: ', 'backup-backup') . basename($result['executable']), 'info');
        $logger->log(__('PHP CLI Version: ', 'backup-backup') . $result['version'] . ' ' . $result['brand'], 'info');
        $logger->log(__('PHP CLI Memory limit: ', 'backup-backup') . $result['memory'], 'info');
        $logger->log(__('PHP CLI Execution limit: ', 'backup-backup') . $result['max_exec'], 'info');
        $logger->log(__('We properly detected PHP CLI executable file.', 'backup-backup'), 'success');

        return $result;

      }

    }

    public function getDatabaseSize() {

      global $wpdb;
      $prefix = $wpdb->prefix;

      $sql = "SELECT SUM(DATA_LENGTH + INDEX_LENGTH) AS `bytes` FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s;";
      $sql = $wpdb->prepare($sql, array(DB_NAME));

      $result = $wpdb->get_results($sql);
      return intval($result[0]->bytes);

    }

    public function dirSize() {

      // Folder
      $f = $this->post['folder'];

      // Bytes
      $bytes = 0;
      $excludedBytes = 0;

      $emptyVar = [ 'this_is_empty_array' ];
      $allowed = [ 'plugins', 'uploads', 'themes', 'contents_others', 'wordpress' ];

      if (in_array($f, $allowed)) {

        // Get list of staging sites for exclusion rules
        require_once BMI_INCLUDES . '/staging/controller.php';
        $staging = new Staging('..ajax..');
        $stagingSites = $staging->getStagingSites(true);

        $files = $this->scanFilesForBackup($emptyVar, $stagingSites, $f);
        $files = $this->parseFilesForBackup($files, $emptyVar, false, true);

        $bytes = $this->total_size_for_backup;
        $excludedBytes = $this->total_excluded_size_for_backup;
        set_transient('bmi_latest_size_' . $f, $bytes);
      } elseif ($f == 'database') {

        $bytes = $this->getDatabaseSize();
        set_transient('bmi_latest_size_' . $f, $bytes);
      }

      return [ 'bytes' => $bytes, 'excluded' => $excludedBytes, 'readable' => BMP::humanSize($bytes) ];

    }

    public function backupErrorHandler() {
      set_error_handler(function ($errno, $errstr, $errfile, $errline) {

        if (BMI_DEBUG) {
          error_log('BMI DEBUG ENABLED, HERE IS THE COMPLETE REPORT (ERROR HANDLER #1):');
          error_log(print_r($errno, true));
          error_log(print_r($errstr, true));
          error_log(print_r($errfile, true));
          error_log(print_r($errline, true));
        }

        if (strpos($errstr, 'deprecated') !== false) return;
        if (strpos($errstr, 'php_uname') !== false) return;
        if (strpos($errfile, 'backup-backup') === false && strpos($errfile, 'backup-migration') === false && $errno != E_ERROR) return;

        if ($errno != E_ERROR && $errno != E_CORE_ERROR && $errno != E_COMPILE_ERROR && $errno != E_USER_ERROR && $errno != E_RECOVERABLE_ERROR) {

          if (strpos($errfile, 'backup-backup') === false && strpos($errfile, 'backup-migration') === false) return;
          Logger::error(__('There was an error before request shutdown (but it was not logged to restore log)', 'backup-backup'));
          Logger::error(__('Error message: ', 'backup-backup') . $errstr);
          Logger::error(__('Error file/line: ', 'backup-backup') . $errfile . '|' . $errline);
          Logger::error(__('Error handler: ', 'backup-backup') . 'ajax#01' . '|' . $errno);
          return;

        }
        if (strpos($errfile, 'backup-backup') === false) {
          Logger::error(__("Restore process was not aborted because this error is not related to Backup Migration.", 'backup-backup'));
          $this->zip_progress->log(__("There was an error not related to Backup Migration Plugin.", 'backup-backup'), 'warn');
          $this->zip_progress->log(__("Message: ", 'backup-backup') . $errstr, 'warn');
          $this->zip_progress->log(__("Backup will not be aborted because of this.", 'backup-backup'), 'warn');
          return;
        }
        if (strpos($errstr, 'unlink(') !== false) {
          Logger::error(__("Restore process was not aborted due to this error.", 'backup-backup'));
          Logger::error(__('Error handler: ', 'backup-backup') . 'ajax#02' . '|' . $errno);
          Logger::error($errstr);
          return;
        }
        if (strpos($errfile, 'pclzip') !== false) {
          Logger::error(__("Restore process was not aborted due to this error.", 'backup-backup'));
          Logger::error(__('Error handler: ', 'backup-backup') . 'ajax#03' . '|' . $errno);
          Logger::error($errstr);
          return;
        }
        if (strpos($errstr, 'rename(') !== false) {
          Logger::error(__("Restore process was not aborted due to this error.", 'backup-backup'));
          Logger::error(__('Error handler: ', 'backup-backup') . 'ajax#04' . '|' . $errno);
          Logger::error($errstr);
          $this->zip_progress->log(__("Cannot move: ", 'backup-backup') . $errstr, 'warn');
          return;
        }

        $this->zip_progress->log(__("There was an error during backup:", 'backup-backup'), 'error');
        $this->zip_progress->log(__("Message: ", 'backup-backup') . $errstr, 'error');
        $this->zip_progress->log(__("File/line: ", 'backup-backup') . $errfile . '|' . $errline, 'error');
        $this->zip_progress->log(__('Unfortunately we had to remove the backup (if partly created).', 'backup-backup'), 'error');

        $backup = $GLOBALS['bmi_current_backup_name'];
        $backup_path = BMI_BACKUPS . DIRECTORY_SEPARATOR . $backup;
        if (file_exists($backup_path)) @unlink($backup_path);
        if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.running')) @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.running');
        if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.abort')) @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.abort');

        $this->zip_progress->log(__("Aborting backup...", 'backup-backup'), 'step');
        $this->zip_progress->log(__("#002", 'backup-backup'), 'end-code');
        $this->zip_progress->end();

        $GLOBALS['bmi_error_handled'] = true;
        BMP::res(['status' => 'error', 'error' => $errstr]);
        exit;

      }, E_ALL);
    }

    public function migrationErrorHandler() {
      set_exception_handler(function ($exception) {
        if (BMI_DEBUG) {
          error_log('BMI DEBUG ENABLED, HERE IS THE COMPLETE REPORT (EXCEPTION HANDLER #1):');
          error_log(print_r($exception, true));
        }

        $this->migration_progress->log(__("Restore exception: ", 'backup-backup') . $exception->getMessage(), 'warn');
        Logger::log(__("Restore exception: ", 'backup-backup') . $exception->getMessage());
      });
    }

    public function migrationExceptionHandler() {
      set_error_handler(function ($errno, $errstr, $errfile, $errline) {

        if (BMI_DEBUG) {
          error_log('BMI DEBUG ENABLED, HERE IS THE COMPLETE REPORT (ERROR HANDLER #2):');
          error_log(print_r($errno, true));
          error_log(print_r($errstr, true));
          error_log(print_r($errfile, true));
          error_log(print_r($errline, true));
        }

        if (strpos($errstr, 'deprecated') !== false) return;
        if (strpos($errstr, 'php_uname') !== false) return;
        if (strpos($errfile, 'backup-backup') === false && strpos($errfile, 'backup-migration' && $errno != E_ERROR) === false) return;

        if ($errno == E_NOTICE) return;
        if ($errno != E_ERROR && $errno != E_CORE_ERROR && $errno != E_COMPILE_ERROR && $errno != E_USER_ERROR && $errno != E_RECOVERABLE_ERROR) {
          if (strpos($errfile, 'backup-backup') === false && strpos($errfile, 'backup-migration') === false) return;
          Logger::error(__('There was an error before request shutdown (but it was not logged to restore log)', 'backup-backup'));
          Logger::error(__('Error message: ', 'backup-backup') . $errstr);
          Logger::error(__('Error file/line: ', 'backup-backup') . $errfile . '|' . $errline);
          Logger::error(__('Error handler: ', 'backup-backup') . 'ajax#05' . '|' . $errno);
          return;
        }

        Logger::error(__("There was an error/warning during restore process:", 'backup-backup'));
        Logger::error(__("Message: ", 'backup-backup') . $errstr);
        Logger::error(__("File/line: ", 'backup-backup') . $errfile . '|' . $errline);
        Logger::error(__('Error handler: ', 'backup-backup') . 'ajax#06' . '|' . $errno);

        if (strpos($errfile, 'backup-backup') === false) {
          Logger::error(__("Restore process was not aborted because this error is not related to Backup Migration.", 'backup-backup'));
          $this->migration_progress->log(__("There was an error not related to Backup Migration Plugin.", 'backup-backup'), 'warn');
          $this->migration_progress->log(__("Message: ", 'backup-backup') . $errstr, 'warn');
          $this->migration_progress->log(__("Backup will not be aborted because of this.", 'backup-backup'), 'warn');
          return;
        }
        if (strpos($errstr, 'unlink(') !== false) {
          Logger::error(__("Restore process was not aborted due to this error.", 'backup-backup'));
          Logger::error(__('Error handler: ', 'backup-backup') . 'ajax#07' . '|' . $errno);
          Logger::error($errstr);
          return;
        }
        if (strpos($errfile, 'pclzip') !== false) {
          Logger::error(__("Restore process was not aborted due to this error.", 'backup-backup'));
          Logger::error(__('Error handler: ', 'backup-backup') . 'ajax#08' . '|' . $errno);
          Logger::error($errstr);
          return;
        }
        if (strpos($errstr, 'rename(') !== false) {
          Logger::error(__("Restore process was not aborted due to this error.", 'backup-backup'));
          Logger::error(__('Error handler: ', 'backup-backup') . 'ajax#09' . '|' . $errno);
          Logger::error($errstr);
          $this->migration_progress->log(__("Cannot move: ", 'backup-backup') . $errstr, 'warn');
          return;
        }

        $this->migration_progress->log(__("There was an error during restore process:", 'backup-backup'), 'error');
        $this->migration_progress->log(__("Message: ", 'backup-backup') . $errstr, 'error');
        $this->migration_progress->log(__("File/line: ", 'backup-backup') . $errfile . '|' . $errline, 'error');

        if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.migration_lock')) @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . '.migration_lock');

        $this->migration_progress->log(__("Aborting restore process...", 'backup-backup'), 'step');

        if (isset($GLOBALS['bmi_current_tmp_restore']) && !empty($GLOBALS['bmi_current_tmp_restore'])) {

          $this->migration_progress->log(__("Cleaning up exported files...", 'backup-backup'), 'step');

          $tmp_unique = $GLOBALS['bmi_current_tmp_restore_unique'];
          $dir = $GLOBALS['bmi_current_tmp_restore'];
          $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
          $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);

          $this->migration_progress->log(__('Removing ', 'backup-backup') . iterator_count($files) . __(' files', 'backup-backup'), 'INFO');
          foreach ($files as $file) {
            if ($file->isDir()) {
              @rmdir($file->getRealPath());
            } else {
              @unlink($file->getRealPath());
            }
          }

          @rmdir($dir);

          $config_file = untrailingslashit(ABSPATH) . DIRECTORY_SEPARATOR . 'wp-config.' . $tmp_unique . '.php';
          if (file_exists($config_file)) @unlink($config_file);

        }

        $this->migration_progress->log(__("#002", 'backup-backup'), 'end-code');
        $this->migration_progress->end();

        $GLOBALS['bmi_error_handled'] = true;
        BMP::res(['status' => 'error', 'error' => $errstr]);
        exit;

      }, E_ALL);
    }

    public function backupExceptionHandler() {
      set_exception_handler(function ($exception) {
        if (BMI_DEBUG) {
          error_log('BMI DEBUG ENABLED, HERE IS THE COMPLETE REPORT (EXCEPTION HANDLER #2):');
          error_log(print_r($exception, true));
        }

        $this->zip_progress->log(__("Exception: ", 'backup-backup') . $exception->getMessage(), 'warn');
        Logger::log(__("Exception: ", 'backup-backup') . $exception->getMessage());
      });
    }

    public function resetLatestLogs() {

      // Restore htaccess
      BMP::revertLitespeed();
      BMP::fixLitespeed();

      // Check time if not bugged
      if (file_exists(BMI_BACKUPS . '/.running') && (time() - filemtime(BMI_BACKUPS . '/.running')) > 65) {
        if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
        if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');
      }

      // Check if backup is not in progress
      if (file_exists(BMI_BACKUPS . '/.running')) {
        return ['status' => 'msg', 'why' => __('Backup process already running, please wait till it complete.', 'backup-backup'), 'level' => 'warning'];
      }

      // Remove too large logs
      $completeLogsPath = BMI_CONFIG_DIR . DIRECTORY_SEPARATOR . 'complete_logs.log';
      if (file_exists($completeLogsPath) && (filesize($completeLogsPath) / 1024 / 1024) >= 3) {
        @unlink($completeLogsPath);
      }

      $backgroundLogsPath = BMI_CONFIG_DIR . DIRECTORY_SEPARATOR . 'background-errors.log';
      if (file_exists($backgroundLogsPath) && (filesize($backgroundLogsPath) / 1024 / 1024) >= 3) {
        @unlink($backgroundLogsPath);
      }

      @touch($completeLogsPath);
      @touch($backgroundLogsPath);

      // Require logs
      require_once BMI_INCLUDES . '/progress/zip.php';
      require_once BMI_INCLUDES . '/progress/migration.php';
      require_once BMI_INCLUDES . '/progress/staging.php';

      // Write initial
      $zip_progress = new Progress('', 0);
      $zip_progress->start();
      $zip_progress->log(__("Initializing backup...", 'backup-backup'), 'step');
      $zip_progress->progress('0/100');
      $zip_progress->end();

      // Write initial
      $migration = new MigrationProgress(false);
      $migration->start();
      $migration->log(__('Initializing restore process', 'backup-backup'), 'STEP');
      $migration->progress('0');
      $migration->end();

      // Write initial
      $staging = new StagingProgress(false);
      $staging->start();
      $staging->log(__('Preparing creation of staging site...', 'backup-backup'), 'STEP');
      $staging->progress('0');
      $staging->end();

      // Return done
      return ['status' => 'success'];
    }

    public function makeBackupName() {
      $name = Dashboard\bmi_get_config('BACKUP:NAME');

      $urlparts = parse_url(home_url());
      $domain = str_replace('.', '-', sanitize_text_field($urlparts['host']));

      $hash = BMP::randomString(16);
      $name = str_replace('%domain', $domain, $name);
      $name = str_replace('%hash', $hash, $name);
      $name = str_replace('%Y', date('Y'), $name);
      $name = str_replace('%M', date('M'), $name);
      $name = str_replace('%D', date('D'), $name);
      $name = str_replace('%d', date('d'), $name);
      $name = str_replace('%j', date('j'), $name);
      $name = str_replace('%m', date('m'), $name);
      $name = str_replace('%n', date('n'), $name);
      $name = str_replace('%Y', date('Y'), $name);
      $name = str_replace('%y', date('y'), $name);
      $name = str_replace('%a', date('a'), $name);
      $name = str_replace('%A', date('A'), $name);
      $name = str_replace('%B', date('B'), $name);
      $name = str_replace('%g', date('g'), $name);
      $name = str_replace('%G', date('G'), $name);
      $name = str_replace('%h', date('h'), $name);
      $name = str_replace('%H', date('H'), $name);
      $name = str_replace('%i', date('i'), $name);
      $name = str_replace('%s', date('s'), $name);
      $name = str_replace('%s', date('s'), $name);

      $i = 2;
      $tmpname = $name;

      while (file_exists($tmpname . '.zip')) {
        $tmpname = $name . '_' . $i;
        $i++;
      }

      $name = $tmpname . '.zip';

      if (has_filter('bmip_backup_name')) {
        $name = apply_filters('bmip_backup_name', $name);
      }

      $GLOBALS['bmi_current_backup_name'] = $name;
      return $name;
    }

    public function fixUnameFunction() {
      $file = trailingslashit(ABSPATH) . 'wp-admin/includes/class-pclzip.php';
      $backup = trailingslashit(ABSPATH) . 'wp-admin/includes/class-pclzip-backup.php';

      // Make backup
      if (!file_exists($backup)) {
        @copy($file, $backup);
      }

      // Replace deprecated php_uname function which is mostly disabled and cause errors
      $replace = file_get_contents($file);
      $replace = str_replace('php_uname()', '(DIRECTORY_SEPARATOR === "/" ? "linux" : "windows")', $replace);
      file_put_contents($file, $replace);
      return ['status' => 'success'];
    }

    public function revertUnameProcess() {
      $file = trailingslashit(ABSPATH) . 'wp-admin/includes/class-pclzip.php';
      $backup = trailingslashit(ABSPATH) . 'wp-admin/includes/class-pclzip-backup.php';
      if (file_exists($backup)) {
        if (file_exists($file)) @unlink($file);
        @copy($backup, $file);
      }
      return ['status' => 'success'];
    }

    public function isFunctionEnabled($func) {
      $disabled = explode(',', ini_get('disable_functions'));
      $isDisabled = in_array($func, $disabled);
      if (!$isDisabled && function_exists($func)) return true;
      else return false;
    }

    public function prepareAndMakeBackup($cron = false) {

      global $wp_version;

      $triggerLock = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.last_triggered';
      
      if ($this->isFunctionEnabled('ini_set')) {
        ini_set('display_errors', 1);
        ini_set('error_reporting', E_ALL);
        ini_set('log_errors', 1);
        ini_set('error_log', BMI_CONFIG_DIR . DIRECTORY_SEPARATOR . 'complete_logs.log');
      }

      // Double check for .space_check file
      if (file_exists(BMI_BACKUPS . '/.space_check')) @unlink(BMI_BACKUPS . '/.space_check');

      // Require File Scanner
      require_once BMI_INCLUDES . '/progress/zip.php';
      require_once BMI_INCLUDES . '/check/checker.php';

      // CLI Handler
      $cliHandler = trailingslashit(sanitize_text_field(BMI_INCLUDES)) . 'cli-handler.php';

      // Backup name
      if (defined('BMI_CLI_ARGUMENT') && !empty(BMI_CLI_ARGUMENT)) {
        $name = BMI_CLI_ARGUMENT;
      } else {
        $name = $this->makeBackupName();
      }

      // Progress & Logs
      $cliRunning = (defined('BMI_USING_CLI_FUNCTIONALITY') && BMI_USING_CLI_FUNCTIONALITY === true) ? true : false;
      $shouldResetLogs = !$cliRunning;
      if (defined('BMI_DOING_SCHEDULED_BACKUP_VIA_CLI')) {
        $cron = true;
        $shouldResetLogs = true;
      }

      $clearEndCodes = false;
      if (isset($this->post['preserveLogs']) && ($this->post['preserveLogs'] == 'true' || $this->post['preserveLogs'] === true)) {
        $shouldResetLogs = false;
        $clearEndCodes = true;
      }

      $zip_progress = new Progress($name, 100, 0, $cron, $shouldResetLogs, $clearEndCodes);
      $zip_progress->start();

      // PHP CLI Check
      $isCLI = false;
      $cli_lock = BMI_BACKUPS . '/.backup_lock_cli';
      $cli_lock_end = BMI_BACKUPS . '/.backup_lock_cli_end';
      $cli_failed_lock = BMI_BACKUPS . '/.backup_lock_cli_failed';

      if (!defined('BMI_USING_CLI_FUNCTIONALITY') || BMI_USING_CLI_FUNCTIONALITY === false) {

        $cli_result = $this->checkIfPHPCliExist($zip_progress);
        $functionNormal = apply_filters('bmi_function_normal', BMI_FUNCTION_NORMAL);
        if ($cli_result !== false && $functionNormal === true) {

          $res = null;
          if (defined('BMI_DOING_SCHEDULED_BACKUP')) {
            @exec(BMI_CLI_EXECUTABLE . ' -f "' . $cliHandler . '" bmi_backup_cron ' . $name . ' > /dev/null &', $res);
          } else {
            @exec(BMI_CLI_EXECUTABLE . ' -f "' . $cliHandler . '" bmi_backup ' . $name . ' > /dev/null &', $res);
          }
          $res = implode("\n", $res);

          sleep(3);

          if (file_exists($cli_lock_end) && (time() - filemtime($cli_lock_end)) < 10) {

            if (file_exists($cli_lock_end)) @unlink($cli_lock_end);
            if (file_exists($triggerLock)) @unlink($triggerLock);
            return ['status' => 'success', 'filename' => $name];
            exit;

          }

          if (!file_exists($cli_lock) || (time() - filemtime($cli_lock)) > 10) {

            if (!file_exists(BMI_BACKUPS . '/.abort') || (time() - filemtime(BMI_BACKUPS . '/.abort')) > 10) {

              $zip_progress->log(__("Something went wrong in PHP CLI process, backup will be continued with legacy methods.", 'backup-backup'), 'warn');
              if (file_exists($cli_lock)) @unlink($cli_lock);
              define('BMI_CLI_FAILED', true);
              touch($cli_failed_lock);

            } else {

              $zip_progress->log(__("Backup will not be continued due to manual abort by user.", 'backup-backup'), 'warn');
              if (file_exists($cli_lock)) @unlink($cli_lock);
              if (file_exists($triggerLock)) @unlink($triggerLock);
              return ['status' => 'msg', 'why' => __('Backup process aborted.', 'backup-backup'), 'level' => 'info'];

            }

          } else {

            return ['status' => 'background', 'filename' => $name];

          }

        } else {

          if ($functionNormal !== true) {
            $zip_progress->log(__("PHP CLI will not run due to user settings in plugin other options.", 'backup-backup'), 'warn');
          } else {
            $zip_progress->log(__("PHP CLI file cannot be executed due to unknown reason.", 'backup-backup'), 'warn');
          }

        }

      } else {

        if (defined('BMI_USING_CLI_FUNCTIONALITY') && BMI_USING_CLI_FUNCTIONALITY === true) {

          if (file_exists($cli_failed_lock) && (time() - filemtime($cli_failed_lock)) < 10) {
            exit;
          }

          $isCLI = true;
          $zip_progress->log(__("Backup via PHP CLI initialized successfully.", 'backup-backup'), 'success');
          touch($cli_lock);

        }

      }

      // Just in case (e.g. syntax error, we can close the file correctly)
      $GLOBALS['bmi_backup_progress'] = $zip_progress;

      // Logs
      $zip_progress->log(__("Initializing backup...", 'backup-backup'), 'step');
      $zip_progress->log((__("Backup & Migration version: ", 'backup-backup') . BMI_VERSION), 'info');
      $zip_progress->log(__("Site which will be backed up: ", 'backup-backup') . site_url(), 'info');
      $zip_progress->log(__("PHP Version: ", 'backup-backup') . PHP_VERSION, 'info');
      $zip_progress->log(__("WP Version: ", 'backup-backup') . $wp_version, 'info');
      $zip_progress->log(__("MySQL Version: ", 'backup-backup') . $GLOBALS['wpdb']->db_version(), 'info');
      $maxAllowedPackets = $GLOBALS['wpdb']->get_results("SHOW VARIABLES LIKE 'max_allowed_packet';");
      if (sizeof($maxAllowedPackets) > 0) {
        $zip_progress->log(__("MySQL Max Length: ", 'backup-backup') . $maxAllowedPackets[0]->Value, 'info');
      } else {
        $zip_progress->log(__("MySQL Max Length: ", 'backup-backup') . 'Unknown', 'info');
      }
      if (isset($_SERVER['SERVER_SOFTWARE']) && !empty($_SERVER['SERVER_SOFTWARE'])) {
        $zip_progress->log(__("Web server: ", 'backup-backup') . $_SERVER['SERVER_SOFTWARE'], 'info');
      } else {
        $zip_progress->log(__("Web server: Not available", 'backup-backup'), 'info');
      }
      $zip_progress->log(__("Max execution time (in seconds): ", 'backup-backup') . @ini_get('max_execution_time'), 'info');

      $zip_progress->log(__("Memory limit (server): ", 'backup-backup') . @ini_get('memory_limit'), 'info');
      if (defined('WP_MEMORY_LIMIT')) {
        $zip_progress->log(__("Memory limit (wp-config): ", 'backup-backup') . WP_MEMORY_LIMIT, 'info');
      }
      if (defined('WP_MAX_MEMORY_LIMIT')) {
        $zip_progress->log(__("Memory limit (wp-config admin): ", 'backup-backup') . WP_MAX_MEMORY_LIMIT, 'info');
      }

      if (defined('BMI_DB_MAX_ROWS_PER_QUERY')) {
        $zip_progress->log(__('Max rows per query (this site): ', 'backup-backup') . BMI_DB_MAX_ROWS_PER_QUERY, 'info');
      }

      $zip_progress->log(__("Checking if backup dir is writable...", 'backup-backup'), 'info');

      if (defined('BMI_DOING_SCHEDULED_BACKUP')) {
        $zip_progress->log(__("This process was initialized due to scheduled backup configuration...", 'backup-backup'), 'info');
        $zip_progress->log(__("Backup will be unlocked by default as it is not manual backup...", 'backup-backup'), 'info');
        $zip_progress->log('This log is triggered by SCHEDULED BACKUP and its part of automatic backup creation', 'verbose');
      }

      if (defined('BMI_BACKUP_PRO')) {
        if (BMI_BACKUP_PRO == 1) {
          $zip_progress->log(__("Premium plugin is enabled and activated", 'backup-backup'), 'info');
        } else {
          $zip_progress->log(__("Premium version is enabled but not active, using free plugin.", 'backup-backup'), 'warn');
        }
      }

      // Error handler
      $zip_progress->log(__("Initializing custom error handler", 'backup-backup'), 'info');
      $this->zip_progress = &$zip_progress;
      $this->backupErrorHandler();
      $this->backupExceptionHandler();

      // Checker
      $checker = new Checker($zip_progress);

      if (!is_writable(dirname(BMI_BACKUPS))) {

        // Abort backup
        $zip_progress->log(__("Backup directory is not writable...", 'backup-backup'), 'error');
        $zip_progress->log(__("Path: ", 'backup-backup') . BMI_BACKUPS, 'error');

        // Close backup
        if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
        if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');
        if ($isCLI === true && file_exists($cli_lock)) @unlink($cli_lock);

        // Log and close log
        $zip_progress->log('#002', 'END-CODE');
        $zip_progress->end();

        if ($isCLI === true) touch($cli_lock_end);
        $this->actionsAfterProcess();

        // Return error
        if (file_exists($triggerLock)) @unlink($triggerLock);
        if ($cron == true) return ['status' => 'success'];
        else return ['status' => 'error'];
      } else {
        $zip_progress->log(__("Yup it is writable...", 'backup-backup'), 'success');
      }

      if (!file_exists(BMI_BACKUPS)) @mkdir(BMI_BACKUPS, true);

      // Get list of staging sites for exclusion rules
      require_once BMI_INCLUDES . '/staging/controller.php';
      $staging = new Staging('..ajax..');
      $stagingSites = $staging->getStagingSites(true);

      // Get file names (huge list mostly)
      if (has_filter('bmip_backup_files')) {
        $files = apply_filters('bmip_backup_files', []);
      } else if ($fgwp = Dashboard\bmi_get_config('BACKUP:FILES') == 'true') {
        $zip_progress->log(__("Scanning files...", 'backup-backup'), 'step');
        $files = $this->scanFilesForBackup($zip_progress, $stagingSites);
        $files = $this->parseFilesForBackup($files, $zip_progress, $cron);
      } else {
        $zip_progress->log(__("Omitting files (due to settings)...", 'backup-backup'), 'warn');
        $files = [];
      }

      $zip_progress->log(str_replace('%s', $this->total_excluded_size_for_backup, __("Total size of excluded files: %s bytes", 'backup-backup')), 'info');
      $zip_progress->log("Total size of excluded files (bytes): " . $this->total_excluded_size_for_backup, 'verbose');

      // Check if there is enough space
      $bytes = intval($this->total_size_for_backup * 1.4);
      update_option('bmi_required_space', $bytes);
      $zip_progress->log(__("Checking free space, reserving...", 'backup-backup'), 'step');
      if ($this->total_size_for_backup_in_mb >= BMI_REV * 1000 && get_option('bmip_last', false) != '1') {

        // Abort backup
        $zip_progress->log(__("Aborting backup...", 'backup-backup'), 'step');
        $zip_progress->log(str_replace('%s', BMI_REV, __("Site weights more than %s GB.", 'backup-backup')), 'error');
        if (isset($this->post['f'])) {
          $zip_progress->log('Function: ' . print_r($this->post['f'], true), 'verbose');
        }

        if (isset($_SERVER)) {
          $zip_progress->log('REQUEST_URI: ' . $_SERVER['REQUEST_URI'], 'verbose');
          $zip_progress->log('REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD'], 'verbose');
        }

        if (!empty($this->post)) {
          $zip_progress->log(print_r($this->post, true), 'verbose');
        }

        // Close backup
        if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
        if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');
        if ($isCLI === true && file_exists($cli_lock)) @unlink($cli_lock);

        // Log and close log
        $zip_progress->log('#100', 'END-CODE');
        $zip_progress->end();

        if ($isCLI === true) touch($cli_lock_end);
        $this->actionsAfterProcess();

        // Return error
        if (file_exists($triggerLock)) @unlink($triggerLock);
        return ['status' => 'error', 'bfs' => true];
      }

      $isSpaceCheckDisabled = Dashboard\bmi_get_config('OTHER:BACKUP:SPACE:CHECKING');

      if ($isSpaceCheckDisabled) {

        $zip_progress->log(__("Free space checking is disabled by user in settings...", 'backup-backup'), 'warn');
        $zip_progress->log(__("Backup will continue, trusting there is enough space...", 'backup-backup'), 'warn');

      } else {

        if (!$checker->check_free_space($bytes)) {

          // Abort backup
          $zip_progress->log(__("Aborting backup...", 'backup-backup'), 'step');
          $zip_progress->log(__("There is no space for that backup, checked: ", 'backup-backup') . ($bytes) . __(" bytes", 'backup-backup'), 'error');
          $zip_progress->log('not_enough_space', 'verbose');

          // Close backup
          if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
          if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');
          if ($isCLI === true && file_exists($cli_lock)) @unlink($cli_lock);

          // Log and close log
          $zip_progress->log('#002', 'END-CODE');
          $zip_progress->end();

          if ($isCLI === true) touch($cli_lock_end);
          $this->actionsAfterProcess();

          // Return error
          if (file_exists($triggerLock)) @unlink($triggerLock);
          if ($cron == true) return ['status' => 'msg', 'why' => __('There is not enough space for backup, please free up ' . round($bytes / 1024 / 1024, 2) . ' MB of space.', 'backup-backup')];
          else return ['status' => 'error'];
        } else {
          $zip_progress->log(__("Confirmed, there is more than enough space, checked: ", 'backup-backup') . ($bytes) . __(" bytes", 'backup-backup'), 'success');
          $zip_progress->bytes = $this->total_size_for_backup;
        }

      }

      if (Dashboard\bmi_get_config('BACKUP:DATABASE') != 'true') {

        // $zip_progress->log(__("Database won't be backed-up due to user settings, omitting...", 'backup-backup'), 'info');
        // Commented as message will be shown in database backup module

      }

      // Log and set files length
      $zip_progress->log(__("Scanning done - found ", 'backup-backup') . sizeof($files) . __(" files...", 'backup-backup'), 'info');
      $zip_progress->files = sizeof($files);

      // Make Backup
      $zip_progress->log(__("Backup initialized...", 'backup-backup'), 'success');
      $zip_progress->log(__("Initializing archiving system...", 'backup-backup'), 'step');

        $resultCreateBackup = $this->createBackup($files, ABSPATH, $name, $zip_progress, $cron, $isCLI);
        do_action('bmp_created_backup',$resultCreateBackup);
        return $resultCreateBackup;

      $bckpres = $this->createBackup($files, ABSPATH, $name, $zip_progress, $cron, $isCLI);
      if (file_exists($triggerLock)) @unlink($triggerLock);
      if ($cron == true) return ['status' => 'success'];
      else return $bckpres;
    }

    public function fixLitespeed() {
      BMP::fixLitespeed();

      return ['status' => 'success'];
    }

    public function revertLitespeed() {
      BMP::revertLitespeed();

      return ['status' => 'success'];
    }

    public function createBackup($files, $base, $name, &$zip_progress, $cron = false, $isCLI = false) {

      // Require File Zipper
      require_once BMI_INCLUDES . '/zipper/zipping.php';

      // CLI locks
      $cli_lock = BMI_BACKUPS . '/.backup_lock_cli';
      $cli_lock_end = BMI_BACKUPS . '/.backup_lock_cli_end';
      $cli_failed_lock = BMI_BACKUPS . '/.backup_lock_cli_failed';

      // Backup name
      $backup_path = BMI_BACKUPS . '/' . $name;

      // Check time if not bugged
      if (file_exists(BMI_BACKUPS . '/.running') && (time() - filemtime(BMI_BACKUPS . '/.running')) > 65) {
        if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
        if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');
        if ($isCLI === true && file_exists($cli_lock)) @unlink($cli_lock);
        if ($isCLI === true && file_exists($cli_lock_end)) @unlink($cli_lock_end);
      }

      if ($isCLI === true) {
        if (file_exists($cli_failed_lock) && (time() - filemtime($cli_failed_lock)) < 10) {
          exit;
        }
      }

      // Mark as in progress
      if (!file_exists(BMI_BACKUPS . '/.running')) {
        touch(BMI_BACKUPS . '/.running');
        file_put_contents(BMI_BACKUPS . '/.running', $name);
        if ($isCLI === true) touch($cli_lock);
      } else {
        return ['status' => 'msg', 'why' => __('Backup process already running, please wait till it complete.', 'backup-backup'), 'level' => 'warning'];
      }

      // Initialized
      $zip_progress->log(__("Archive system initialized...", 'backup-backup'), 'success');

      // Make ZIP
      $zipper = new Zipper();
      $zippy = $zipper->makeZIP($files, $backup_path, $name, $zip_progress, $cron);
      if (!$zippy) {

        // Make sure it's open
        $zip_progress->start();

        // Abort backup
        $zip_progress->log(__("Aborting backup...", 'backup-backup'), 'step');

        // Close backup
        if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
        if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');
        if ($isCLI === true && file_exists($cli_lock)) @unlink($cli_lock);

        // Log and close log
        $zip_progress->log('#002', 'END-CODE');
        $zip_progress->end();

        if ($isCLI === true) touch($cli_lock_end);

        // Return error
        if (file_exists($backup_path)) @unlink($backup_path);

        $this->actionsAfterProcess();
        return ['status' => 'error'];
      }

      if (isset($zippy['status']) && $zippy['status'] == 'background') {
        return $zippy;
      }

      // Backup aborted
      if (file_exists(BMI_BACKUPS . '/.abort')) {

        // Make sure it's open
        $zip_progress->start();

        if (file_exists($backup_path)) @unlink($backup_path);
        if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
        if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');
        if ($isCLI === true && file_exists($cli_lock)) @unlink($cli_lock);

        // Log and close log
        $zip_progress->log(__("Backup process aborted.", 'backup-backup'), 'warn');
        $zip_progress->log('#002', 'END-CODE');
        $zip_progress->end();

        if ($isCLI === true) touch($cli_lock_end);
        Logger::log(__("Backup process aborted.", 'backup-backup'));

        $this->actionsAfterProcess();
        return ['status' => 'msg', 'why' => __('Backup process aborted.', 'backup-backup'), 'level' => 'info'];
      }

      if (!file_exists($backup_path) && !$cron) {

        // Make sure it's open
        $zip_progress->start();

        // Abort backup
        $zip_progress->log(__("Aborting backup...", 'backup-backup'), 'step');
        $zip_progress->log(__("There is no backup file...", 'backup-backup'), 'error');
        $zip_progress->log(__("We could not find backup file when it already should be here.", 'backup-backup'), 'error');
        $zip_progress->log(__("This error may be related to missing space. (filled during backup)", 'backup-backup'), 'error');
        $zip_progress->log(__("Path: ", 'backup-backup') . $backup_path, 'error');

        // Close backup
        if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
        if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');
        if ($isCLI === true && file_exists($cli_lock)) @unlink($cli_lock);

        // Log and close log
        $zip_progress->log('#002', 'END-CODE');
        $zip_progress->end();

        if ($isCLI === true) touch($cli_lock_end);
        $this->actionsAfterProcess();

        // Return error
        if ($cron == true) return ['status' => 'success'];
        else return ['status' => 'error'];
      }

      // End zip log
      $zip_progress->log(__("New backup created and its name is: ", 'backup-backup') . $name, 'success');
      $zip_progress->log('#001', 'END-CODE');
      $zip_progress->end();

      if ($isCLI === true) touch($cli_lock_end);

      // Unlink progress
      if (file_exists(BMI_BACKUPS . '/.running')) @unlink(BMI_BACKUPS . '/.running');
      if (file_exists(BMI_BACKUPS . '/.abort')) @unlink(BMI_BACKUPS . '/.abort');
      if ($isCLI === true && file_exists($cli_lock)) @unlink($cli_lock);

      // Return
      Logger::log(__("New backup created and its name is: ", 'backup-backup') . $name);

      $GLOBALS['bmi_error_handled'] = true;

      $this->actionsAfterProcess(true);
      return ['status' => 'success', 'filename' => $name, 'root' => plugin_dir_url(BMI_ROOT_FILE)];

    }

    public function continueRestoreProcess() {

      // BMI_RESTORE_SECRET

    }

    public function getBackupsList() {

      // Require File Scanner
      require_once BMI_INCLUDES . '/scanner/backups.php';

      // Get backups
      $backups = new Backups();
      $manifests = $backups->getAvailableBackups();

      // Return files
      return ['status' => 'success', 'backups' => $manifests];
    }

    public function sendTestMail() {

      $email = Dashboard\bmi_get_config('OTHER:EMAIL') != false ? Dashboard\bmi_get_config('OTHER:EMAIL') : get_bloginfo('admin_email');
      $subject = __('Backup Migration – Example email', 'backup-backup');
      $message = __('This is a test email sent by the Backup Migration plugin via Troubleshooting options!', 'backup-backup');

      try {

        if (wp_mail($email, $subject, $message)) return [ 'status' => 'success' ];
        else return ['status' => 'error'];

      } catch (\Exception $e) {

        return ['status' => 'error'];

      } catch (\Throwable $e) {

        return ['status' => 'error'];

      }

    }

    public function restoreBackup() {

      global $wp_version;

      if ($this->isFunctionEnabled('ini_set')) {
        ini_set('display_errors', 1);
        ini_set('error_reporting', E_ALL);
        ini_set('log_errors', 1);
        ini_set('error_log', BMI_CONFIG_DIR . DIRECTORY_SEPARATOR . 'complete_logs.log');
      }


      // Double check for .space_check file
      if (file_exists(BMI_BACKUPS . '/.space_check')) @unlink(BMI_BACKUPS . '/.space_check');

      // Require File Scanner
      require_once BMI_INCLUDES . '/zipper/zipping.php';
      require_once BMI_INCLUDES . '/extracter/extract.php';
      require_once BMI_INCLUDES . '/progress/migration.php';
      require_once BMI_INCLUDES . '/check/checker.php';

      // Make AutoLogin possible
      $ip = '127.0.0.1';
      if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
      } else {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
          $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if ($ip === false) {
          if (isset($_SERVER['REMOTE_ADDR'])) $ip = $_SERVER['REMOTE_ADDR'];
        }
      }
      $autoLoginMD = time() . '_' . $ip . '_' . '4u70L051n';

      // Progress & lock file
      $lock = BMI_BACKUPS . '/.migration_lock';
      $lock_cli = BMI_BACKUPS . '/.migration_lock_cli';
      $autologin_file = BMI_BACKUPS . '/.autologin';
      $lock_cli_end = BMI_BACKUPS . '/.migration_lock_ended';
      $progress = BMI_BACKUPS . '/latest_migration_progress.log';
      $cli_last_download = BMI_BACKUPS . '/.cli_download_last';

      $ignoreRunCheck = ((isset($this->post['ignoreRunning']) && $this->post['ignoreRunning'] == 'true') ? true : false);
      $isCLIRunning = (defined('BMI_USING_CLI_FUNCTIONALITY') && BMI_USING_CLI_FUNCTIONALITY === true) ? true : false;
      if ($isCLIRunning) $ignoreRunCheck = false;

      if (file_exists($lock) && (time() - filemtime($lock)) < 65 && !$ignoreRunCheck) {
        return ['status' => 'msg', 'why' => __('The restore process is currently running, please wait till it end or once the lock file expire.', 'backup-backup'), 'level' => 'warning'];
      }

      // Check if download was via CLI
      if ($this->post['file'] == '.cli_download' && file_exists($cli_last_download)) {
        $this->post['file'] = file_get_contents($cli_last_download);
        if (file_exists($cli_last_download)) @unlink($cli_last_download);
      }

      // Logs
      $migration = new MigrationProgress($this->post['remote']);
      $migration->start();

      if ($ignoreRunCheck) {

        $migration->mute();

      }

      // Check PHP CLI
      if ((!defined('BMI_USING_CLI_FUNCTIONALITY') || BMI_USING_CLI_FUNCTIONALITY === false) && (!defined('BMI_CLI_REQUEST') || BMI_CLI_REQUEST === false)) {

        $cli_result = $this->checkIfPHPCliExist($migration);

        if ($cli_result !== false) {

          $cliHandler = trailingslashit(sanitize_text_field(BMI_INCLUDES)) . 'cli-handler.php';
          $backupName = esc_attr($this->post['file']);
          $remoteType = 'false';
          if ($this->post['remote'] == 'true' || $this->post['remote'] === true) $remoteType = 'true';
          if (file_exists($lock_cli_end)) @unlink($lock_cli_end);

          $res = null;
          @exec(BMI_CLI_EXECUTABLE . ' -f "' . $cliHandler . '" bmi_restore ' . $backupName . ' ' . $remoteType . ' > /dev/null &', $res);
          $res = implode("\n", $res);

          sleep(3);

          if (file_exists($lock_cli_end) && (time() - filemtime($lock_cli_end)) < 10) {

            // Put autologin
            file_put_contents($autologin_file, $autoLoginMD);
            touch($autologin_file);

            return ['status' => 'cli', 'login' => explode('_', $autoLoginMD)[0], 'url' => site_url()];
            exit;

          }

          if (!file_exists($lock_cli) || (time() - filemtime($lock_cli)) > 10) {

            $progressFile = null;
            $migration->log(__('No response from PHP CLI - plugin will try to recover the migration with traditional restore.', 'backup-backup'), 'warn');
            if (file_exists($lock_cli)) @unlink($lock_cli);

          } else {

            $progressFile = null;

            // $migration->log(__('PHP CLI responded with correct code - we will continue via PHP CLI.', 'backup-backup'), 'info');
            // $migration->end();

            // Put autologin
            file_put_contents($autologin_file, $autoLoginMD);
            touch($autologin_file);

            return ['status' => 'cli', 'login' => explode('_', $autoLoginMD)[0], 'url' => site_url()];
            exit;

          }

        } else {

          if (file_exists($lock_cli)) @unlink($lock_cli);

        }

      } else {

        if (defined('BMI_USING_CLI_FUNCTIONALITY') && BMI_USING_CLI_FUNCTIONALITY === true) {
          $migration->log(__('PHP CLI: Restore process initialized, restoring...', 'backup-backup'), 'success');
          touch($lock_cli);
        } else {
          $migration->log(__('Restore process initialized, restoring (non-cli mode)...', 'backup-backup'), 'success');
        }

      }

      // Just in case (e.g. syntax error, we can close the file correctly)
      $GLOBALS['bmi_migration_progress'] = $migration;

      // Checker
      $checker = new Checker($migration);
      $zipper = new Zipper();

      // Handle remote
      if ($this->post['file']) {
        $migration->log(__('Restore process responded', 'backup-backup'), 'SUCCESS');
      }

      // Make lock file
      $migration->log(__('Locking migration process', 'backup-backup'), 'SUCCESS');
      touch($lock);

      // Initializing
      $migration->log(__('Initializing restore process', 'backup-backup'), 'STEP');
      $migration->log((__("Backup & Migration version: ", 'backup-backup') . BMI_VERSION), 'info');

      // Error handler
      $migration->log(__("Initializing custom error handler", 'backup-backup'), 'info');

      // Error handler
      $this->migration_progress = &$migration;
      $this->migrationErrorHandler();
      $this->migrationExceptionHandler();

      $homeURL = site_url();
      if (strlen($homeURL) <= 8) $homeURL = home_url();
      if (defined('WP_SITEURL') && strlen(WP_SITEURL) > 8) $homeURL = WP_SITEURL;

      $migration->log(__("Site which will be restored: ", 'backup-backup') . $homeURL, 'info');
      $migration->log(__("PHP Version: ", 'backup-backup') . PHP_VERSION, 'info');
      $migration->log(__("WP Version: ", 'backup-backup') . $wp_version, 'info');
      $migration->log(__("MySQL Version: ", 'backup-backup') . $GLOBALS['wpdb']->db_version(), 'info');
      $maxAllowedPackets = $GLOBALS['wpdb']->get_results("SHOW VARIABLES LIKE 'max_allowed_packet';");
      if (sizeof($maxAllowedPackets) > 0) {
        $migration->log(__("MySQL Max Length: ", 'backup-backup') . $maxAllowedPackets[0]->Value, 'info');
      } else {
        $migration->log(__("MySQL Max Length: ", 'backup-backup') . 'Unknown', 'info');
      }
      if (isset($_SERVER['SERVER_SOFTWARE']) && !defined('BMI_USING_CLI_FUNCTIONALITY')) {
        $migration->log(__("Web server: ", 'backup-backup') . $_SERVER['SERVER_SOFTWARE'], 'info');
      } else {
        $migration->log(__("Web server: Not available", 'backup-backup'), 'info');
      }
      $migration->log(__("Max execution time (in seconds): ", 'backup-backup') . @ini_get('max_execution_time'), 'info');

      $migration->log(__("Memory limit (server): ", 'backup-backup') . @ini_get('memory_limit'), 'info');
      if (defined('WP_MEMORY_LIMIT')) {
        $migration->log(__("Memory limit (wp-config): ", 'backup-backup') . WP_MEMORY_LIMIT, 'info');
      }
      if (defined('WP_MAX_MEMORY_LIMIT')) {
        $migration->log(__("Memory limit (wp-config admin): ", 'backup-backup') . WP_MAX_MEMORY_LIMIT, 'info');
      }

      if (defined('BMI_BACKUP_PRO')) {
        if (BMI_BACKUP_PRO == 1) {
          $migration->log(__("Premium plugin is enabled and activated", 'backup-backup'), 'info');
        } else {
          $migration->log(__("Premium version is enabled but not active, using free plugin.", 'backup-backup'), 'warn');
        }
      }

      $migration->log(__("Restore process initialized successfully.", 'backup-backup'), 'success');

      // Check file size
      $zippath = BMP::fixSlashes(BMI_BACKUPS) . DIRECTORY_SEPARATOR . $this->post['file'];
      if (!$ignoreRunCheck) {

        $manifest = $zipper->getZipFileContent($zippath, 'bmi_backup_manifest.json');
        $migration->log(__('Free space checking...', 'backup-backup'), 'STEP');
        $migration->log(__('Checking if there is enough amount of free space', 'backup-backup'), 'INFO');

        $isSpaceCheckDisabled = Dashboard\bmi_get_config('OTHER:BACKUP:SPACE:CHECKING');

        if ($isSpaceCheckDisabled) {
          $migration->log(__("Free space checking is disabled by user in settings...", 'backup-backup'), 'warn');
          $migration->log(__("Restore will continue, trusting there is enough space...", 'backup-backup'), 'warn');
        } else {
          if ($manifest) {
            if (isset($manifest->bytes) && $manifest->bytes) {
              $bytes = intval($manifest->bytes * 1.4);
              update_option('bmi_required_space', $bytes);
              if (file_exists(BMI_TMP . DIRECTORY_SEPARATOR . 'restore_parts.json')) {
                $restoreParts = json_decode(file_get_contents(BMI_TMP . DIRECTORY_SEPARATOR . 'restore_parts.json'));
                if (isset($restoreParts->size) && $restoreParts->size && $restoreParts->backupName == $this->post['file']) {
                  $bytes = intval($restoreParts->size * 1.4);
                }
              }
              if (!$checker->check_free_space($bytes)) {
                $migration->log(__('Cannot start migration process', 'backup-backup'), 'ERROR');
                $migration->log(__('Error: There is not enough space on the server, checked: ' . ($bytes) . ' bytes.', 'backup-backup'), 'ERROR');
                $migration->log("not_enough_space", 'verbose');
                $migration->log(__('Aborting...', 'backup-backup'), 'ERROR');
                $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');

                if (file_exists($lock)) @unlink($lock);
                $migration->log('#004', 'END-CODE');
                $migration->end();

                if ($isCLIRunning == true) touch($lock_cli_end);
                $this->actionsAfterProcess(false, 'migration');

                return ['status' => 'error'];
              } else {
                $migration->log(__('Confirmed, there is enough space on the device, checked: ' . ($bytes) . ' bytes.', 'backup-backup'), 'SUCCESS');
              }
            }
          } else {
            $migration->log(__('Cannot start migration process', 'backup-backup'), 'ERROR');
            $migration->log(__('Error: File may not exist, check file name and if it still exist', 'backup-backup'), 'ERROR');
            $migration->log(__('Error: Could not find manifest in backup, file may be broken', 'backup-backup'), 'ERROR');
            $migration->log(__('Error: Btw. because of this I also cannot check free space', 'backup-backup'), 'ERROR');
            $migration->log(__('Used path: ', 'backup-backup') . $zippath, 'ERROR');
            $migration->log(__('Aborting...', 'backup-backup'), 'ERROR');
            $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');

            if (file_exists($lock)) @unlink($lock);
            $migration->log('#003', 'END-CODE');
            $migration->end();

            if ($isCLIRunning == true) touch($lock_cli_end);
            $this->actionsAfterProcess(false, 'migration');

            return ['status' => 'error'];
          }
        }

      }

      if ($ignoreRunCheck) {

        $migration->unmute();

      }

      // New extracter
      $theTmpName = ((isset($this->post['tmpname'])) ? $this->post['tmpname'] : false);
      $options = ((isset($this->post['options'])) ? $this->post['options'] : []);
      $extracter = new Extracter($this->post['file'], $migration, $theTmpName, $isCLIRunning, $options);

      // Extract
      $theSecret = ((isset($this->post['secret'])) ? $this->post['secret'] : null);
      $isFine = $extracter->extractTo($theSecret);
      if (!$isFine) {
        $migration->log(__('Aborting...', 'backup-backup'), 'ERROR');
        $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');

        if (file_exists($lock)) @unlink($lock);
        $migration->log('#002', 'END-CODE');
        $migration->end();

        if ($isCLIRunning == true) touch($lock_cli_end);
        $this->actionsAfterProcess(false, 'migration');

        return ['status' => 'error'];
      }

      $migration->progress('100');
      $migration->log(__('Restore process completed', 'backup-backup'), 'SUCCESS');
      $migration->log(__('Finalizing restored files', 'backup-backup'), 'STEP');
      $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
      if (file_exists($lock)) @unlink($lock);

      $migration->log('#001', 'END-CODE');
      $migration->end();

      if ($isCLIRunning == true) touch($lock_cli_end);

      // Put autologin
      file_put_contents($autologin_file, $autoLoginMD);
      touch($autologin_file);

      $this->actionsAfterProcess(true, 'migration');
      return ['status' => 'success', 'login' => explode('_', $autoLoginMD)[0], 'url' => site_url()];
    }

    public function isRunningBackup() {
      $this->lock_cli = BMI_BACKUPS . '/.backup_cli_lock';

      // Ongoing processes
      $ongoing = get_option('bmip_to_be_uploaded', [
        'current_upload' => [],
        'queue' => [],
        'failed' => []
      ]);

      // Backup CLI running
      if (file_exists($this->lock_cli) && (time() - filemtime($this->lock_cli)) <= 3600) {
        return ['status' => 'msg', 'why' => __('Backup process already running, please wait till it complete.', 'backup-backup'), 'level' => 'warning', 'ongoing' => $ongoing];
      }

      if (file_exists(BMI_BACKUPS . '/.running') && (time() - filemtime(BMI_BACKUPS . '/.running')) <= 65) {
        return ['status' => 'msg', 'why' => __('Backup process already running, please wait till it complete.', 'backup-backup'), 'level' => 'warning', 'ongoing' => $ongoing];
      } else {
        return ['status' => 'success', 'ongoing' => $ongoing];
      }
    }

    public function stopBackup() {
      if (!file_exists(BMI_BACKUPS . '/.running')) {
        return ['status' => 'msg', 'why' => __('Backup process completed or is not running.', 'backup-backup'), 'level' => 'info'];
      } else {
        if (!file_exists(BMI_BACKUPS . '/.abort')) {
          touch(BMI_BACKUPS . '/.abort');
        }

        return ['status' => 'success'];
      }
    }

    public function isMigrationLocked() {
      $lock = BMI_BACKUPS . '/.migration_lock';
      $lock_cli = BMI_BACKUPS . '/.migration_lock_cli';
      $lock_cli_end = BMI_BACKUPS . '/.migration_lock_ended';

      if ((file_exists($lock) && (time() - filemtime($lock)) < 65) || (file_exists($lock_cli) && (time() - filemtime($lock_cli)) < 7200)) {

        return ['status' => 'msg', 'why' => __('Restore process is currently running, please wait till it complete.', 'backup-backup'), 'level' => 'warning'];

      } else {

        require_once BMI_INCLUDES . '/progress/migration.php';
        $progress = BMI_BACKUPS . '/latest_migration_progress.log';
        $shouldClearLogs = true;

        if (isset($this->post['clearLogs']) && $this->post['clearLogs'] == 'false') {
          $shouldClearLogs = false;
        }

        if ($shouldClearLogs === true) {
          if (file_exists($lock_cli_end) && (time() - filemtime($lock_cli_end)) > 10) {

            $migration = new MigrationProgress();
            $migration->start();
            $migration->log(__('Initializing restore process...', 'backup-backup'), 'STEP');
            $migration->end();

            file_put_contents($progress, '0');

          }
        }

        return ['status' => 'success'];

      }
    }

    public function downloadFile($url, $dest, $progress, $lock, &$logger) {
      $current_percentage = 0;
      $previous_logged = 0;
      $fp = fopen($dest, 'w+');

      $progressfile = $progress;
      $lockfile = $lock;

      $ch = curl_init(rawurldecode($url));
      curl_setopt($ch, CURLOPT_TIMEOUT, 0);

      curl_setopt($ch, CURLOPT_FILE, $fp);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

      curl_setopt($ch, CURLOPT_NOPROGRESS, false);
      curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $download_size, $downloaded) use (&$current_percentage, &$lockfile, &$progressfile, &$logger, &$previous_logged) {
        if ($download_size > 0) {
          $new_percentage = intval(($downloaded / $download_size) * 100);

          if (intval($current_percentage) != intval($new_percentage)) {
            $logger->progress($new_percentage);

            if ($current_percentage == 0 || ($new_percentage % 5 == 0) || $new_percentage > 99) {
              $logger->log(sprintf(__('Download progress: %s/%s MB (%s%%)', 'backup-backup'), round($downloaded / 1024 / 1024), round($download_size / 1024 / 1024), $new_percentage), 'INFO');
              $previous_logged = $new_percentage;
            }

            $current_percentage = $new_percentage;
          }
        }
      });

      curl_exec($ch);
      $this->lastCurlCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      $error_msg = false;
      if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        $curl_errno = curl_errno($ch);
        $fileSize = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        
        if ($curl_errno == CURLE_WRITE_ERROR || $curl_errno == CURLE_ABORTED_BY_CALLBACK) {
            $requiredSpace = $fileSize * 1.1; // Add 10% buffer
            update_option('bmi_required_space', $requiredSpace);
            $logger->log('not_enough_space', 'verbose');
        }
      }

      curl_close($ch);
      fclose($fp);

      if ($error_msg) {
        return $error_msg;
      } else {
        return false;
      }
    }

    public function handleQuickMigration() {
      $lock = BMI_BACKUPS . '/.migration_lock';
      if (file_exists($lock) && (time() - filemtime($lock)) < 65) {
        return ['status' => 'msg', 'why' => __('Download process is currently running, please wait till it complete.', 'backup-backup'), 'level' => 'warning'];
      }

      require_once BMI_INCLUDES . '/progress/migration.php';
      require_once BMI_INCLUDES . '/zipper/zipping.php';

      $migration = new MigrationProgress(true);
      $migration->start();

      $tmp_name = 'backup_' . time() . '.zip.part';

      // Missing URL parameter
      if (!isset($this->post['url'])) {
        wp_send_json_error();
      }

      if (defined('BMI_USING_CLI_FUNCTIONALITY') && BMI_USING_CLI_FUNCTIONALITY === true && defined('BMI_CLI_ARGUMENT')) {

        $url = BMI_CLI_ARGUMENT;

      } else {

        $url = $this->post['url'];
        $startRestoreProcess = isset($this->post['startRestoreProcess']) ? $this->post['startRestoreProcess'] : 'true';

        $url = trim(rawurlencode(sanitize_url($url, ['http', 'https']))); // or esc_attr but rawurlencode should be fine

        // Just why not {
        $url = str_replace(' ', '', $url);
        $url = str_replace('$', '%24', $url);
        $url = str_replace('`', '%60', $url);
        $url = str_replace('"', '%22', $url);
        $url = str_replace('\\', '%5C', $url);
        $url = str_replace('&amp;', '&', $url);
        // }

      }

      $dest = BMI_BACKUPS . '/' . $tmp_name;
      $progress = BMI_BACKUPS . '/latest_migration_progress.log';
      $cli_lock = BMI_BACKUPS . '/.cli_download_lock';

      if (!defined('BMI_USING_CLI_FUNCTIONALITY') || BMI_USING_CLI_FUNCTIONALITY === false) {

        $cli_result = $this->checkIfPHPCliExist($migration);
        if ($cli_result !== false) {

          $cliHandler = trailingslashit(sanitize_text_field(BMI_INCLUDES)) . 'cli-handler.php';

          $res = null;
          @exec(BMI_CLI_EXECUTABLE . ' -f "' . $cliHandler . '" bmi_quick_migration "' . $url . '" > /dev/null &', $res);
          $res = implode("\n", $res);

          sleep(2);
          if (file_exists($cli_lock) && (time() - filemtime($cli_lock)) < 10) {

            if (file_exists($cli_lock)) @unlink($cli_lock);
            return [ 'status' => 'cli_download' ];
            exit;

          }

        }

      } else {

        $migration->log(__('Downloading via PHP CLI', 'backup-backup'));
        touch($cli_lock);

      }

      $migration->log((__("Backup & Migration version: ", 'backup-backup') . BMI_VERSION));
      $migration->log(__('Creating lock file', 'backup-backup'));
      file_put_contents($lock, '');
      $migration->log(__('Initializing download process', 'backup-backup'), 'STEP');
      $downstart = microtime(true);
      $migration->log(__('Downloading initialized', 'backup-backup'), 'SUCCESS');
      $migration->log(__('Downloading remote file...', 'backup-backup'), 'STEP');
      $migration->log(__('Used URL: ', 'backup-backup') . rawurldecode($url), 'INFO');
      $fileError = $this->downloadFile($url, $dest, $progress, $lock, $migration);
      $migration->log(__('Unlocking migration', 'backup-backup'), 'INFO');
      if (file_exists($lock)) @unlink($lock);

      if ($fileError) {
        $migration->log(__('Removing downloaded file', 'backup-backup'), 'INFO');
        if (file_exists($dest)) @unlink($dest);
        $migration->log(__('Download error', 'backup-backup'), 'ERROR');

        if (strpos($fileError, 'Failed writing body') !== false) {
          $migration->log(__('Error: There is not enough space on the server', 'backup-backup'), 'ERROR');
          $migration->log("not_enough_space", 'verbose');
        } else {
          $migration->log(__('Error', 'backup-backup') . ': ' . $fileError, 'ERROR');
        }

  $migration->log('error_during_downloading_backup', 'verbose');
  $migration->log('error_during_downloading_backup', 'verbose');
  $migration->log('#002', 'END-CODE');
        return ['status' => 'error'];
      } else {
        $migration->log(__('Download completed (took: ', 'backup-backup') . (microtime(true) - $downstart) . 's)', 'SUCCESS');
        $migration->log(__('Looking for backup manifest', 'backup-backup'), 'STEP');
        $zipper = new Zipper();
        $content = $zipper->getZipFileContent($dest, 'bmi_backup_manifest.json');
        if ($content) {
          try {
            $i = 1;
            $name = $content->name;
            $prepared_name = $name;
            $migration->log(__('Manifest found remote name: ', 'backup-backup') . $name, 'SUCCESS');

            while (file_exists(BMI_BACKUPS . '/' . $prepared_name)) {
              $prepared_name = substr($name, 0, -4) . '_' . $i . '.zip';
              $i++;
            }

            rename($dest, BMI_BACKUPS . '/' . $prepared_name);
            $migration->log(__('Requesting restore process', 'backup-backup'), 'STEP');
            $migration->progress(0);
            file_put_contents(BMI_BACKUPS . '/' . '.cli_download_last', $prepared_name);
            if ($startRestoreProcess == 'true'){
              $migration->log('#205', 'END-CODE');
            } else {
              $migration->log('#206', 'END-CODE');
            }

            if (defined('BMI_USING_CLI_FUNCTIONALITY')) {
              $this->post['file'] = '.cli_download';
              $this->post['remote'] = true;
              return $this->restoreBackup();
            } else {
              return ['status' => 'success', 'name' => $prepared_name];
            }
          } catch (\Exception $e) {
            $migration->log(__('Error: ', 'backup-backup') . $e, 'ERROR');
            $migration->log(__('Removing downloaded file', 'backup-backup'), 'ERROR');
            if (file_exists($dest)) @unlink($dest);

            $migration->log('error_during_downloading_backup', 'verbose');
            $migration->log('error_during_downloading_backup', 'verbose');
            $migration->log('#002', 'END-CODE');
            return ['status' => 'error'];
          } catch (\Throwable $e) {
            $migration->log(__('Error: ', 'backup-backup') . $e, 'ERROR');
            $migration->log(__('Removing downloaded file', 'backup-backup'), 'ERROR');
            if (file_exists($dest)) @unlink($dest);

            $migration->log('error_during_downloading_backup', 'verbose');
            $migration->log('error_during_downloading_backup', 'verbose');
            $migration->log('#002', 'END-CODE');
            return ['status' => 'error'];

          }

        } else {

          // $migration->log(__('Error during manifest check: ', 'backup-backup') . print_r($content, true), 'ERROR');
          if ($this->lastCurlCode == '403') {
            $migration->log(__('Backup is not available to download (Error 403).', 'backup-backup'), 'ERROR');
            $migration->log(__('It is restricted by remote server configuration.', 'backup-backup'), 'ERROR');
          } elseif ($this->lastCurlCode == '423') {
            $migration->log(__('Backup is locked on remote site, please unlock remote downloading.', 'backup-backup'), 'ERROR');
            $migration->log(__('You can find the setting in "Where shall the backup(s) be stored?" section.', 'backup-backup'), 'ERROR');
          } elseif ($this->lastCurlCode == '200' || $this->lastCurlCode == '404') {
            $migration->log(__('Backup does not exist under provided URL.', 'backup-backup'), 'ERROR');
            $migration->log(__('Please confirm that you can download the backup file via provided URL.', 'backup-backup'), 'ERROR');
            $migration->log(__('...or the manifest file does not exist in the backup.', 'backup-backup'), 'ERROR');
            $migration->log(__('Missing manifest means that the backup is probably invalid.', 'backup-backup'), 'ERROR');
          } else {
            $migration->log(__('Manifest file does not exist', 'backup-backup'), 'ERROR');
            $migration->log(__('Downloaded backup may be incomplete (missing manifest)', 'backup-backup'), 'ERROR');
            $migration->log(__('...or provided URL is not a direct download of ZIP file.', 'backup-backup'), 'ERROR');
            $migration->log(__('Removing downloaded file', 'backup-backup'), 'ERROR');
          }

          if (file_exists($dest)) @unlink($dest);

          $migration->log('error_during_downloading_backup', 'verbose');
          $migration->log('error_during_downloading_backup', 'verbose');
          $migration->log('#002', 'END-CODE');
          return ['status' => 'error'];

        }
      }
    }

    public function handleChunkUpload() {
      require_once BMI_INCLUDES . '/uploader/chunks.php';
    }

    public function removeBackupFile() {
      $files = $this->post['filenames'];
      $deleteCloud = $this->post['deleteCloud'] === 'yes' ? true : false;
      $cloudDetails = $this->post['cloudDetails'];

      $md5_file_summary_path = BMI_BACKUPS . DIRECTORY_SEPARATOR. 'md5summary.php';
      $md5summary = [];

      if (file_exists($md5_file_summary_path)) {
        $md5summary = file_get_contents($md5_file_summary_path);
        $md5summary = substr($md5summary, 18, -2);
        if (is_serialized($md5summary)) {
          $md5summary = maybe_unserialize($md5summary);
        }
      }

      if ($deleteCloud) {
        //Initialize externall storages for backup deletion action to be initiated
        require_once BMI_INCLUDES . '/external/controller.php';
        new ExternalStorage();

        if (defined('BMI_BACKUP_PRO') && defined('BMI_PRO_INC')) {
          $proPath = BMI_PRO_INC . 'external/controller.php';
          if (file_exists($proPath)) {
            require_once $proPath;
            new ExternalStoragePremium();
          }
        }
      }

      try {
        if (is_array($files)) {
          for ($i = 0; $i < sizeof($files); $i++) {

            $removeByMD5 = false;
            $file = $files[$i];
            $file = preg_replace('/\.\./', '', $file);

            if (file_exists(BMI_BACKUPS . '/' . $file)) {

              if ($deleteCloud) {
                do_action('bmi_premium_remove_backup_file', md5_file(BMI_BACKUPS . '/' . $file));
              }

              unlink(BMI_BACKUPS . '/' . $file);

            } else if ($deleteCloud) $removeByMD5 = true;

            if (isset($md5summary[$file])) {
              $md5s = $md5summary[$file];

              for ($j = 0; $j < sizeof($md5s); ++$j) {
                $md5_file_path = BMI_BACKUPS . DIRECTORY_SEPARATOR . $md5s[$j] . '.json';
                if (file_exists($md5_file_path)) {
                  if ($deleteCloud) {
                    do_action('bmi_premium_remove_backup_json_file', $md5s[$j] . '.json');
                  }
                  unlink($md5_file_path);
                } else if ($deleteCloud) $removeByMD5 = true;
              }

              unset($md5summary[$file]);
            }

            if ($deleteCloud && $removeByMD5) {
              if (isset($cloudDetails[$file])) {
                do_action('bmi_premium_remove_backup_file', $cloudDetails[$file]['md5']);
                do_action('bmi_premium_remove_backup_json_file', $cloudDetails[$file]['md5'] . '.json');
              }
            }

          }
        }
      } catch (\Exception $e) {
        return ['status' => 'error', 'e' => $e];
      } catch (\Throwable $e) {
        return ['status' => 'error', 'e' => $e];
      }

      $cacheMd5String = "<?php exit; \$x = '" . serialize($md5summary) . "';";
      file_put_contents($md5_file_summary_path, $cacheMd5String);

      return ['status' => 'success'];
    }

    public function saveStorageConfig() {
      $dir_path = $this->post['directory']; // STORAGE::LOCAL::PATH
      $accessible = $this->post['access']; // STORAGE::DIRECT::URL
      $gdrivedirname = 'BACKUP_MIGRATION_BACKUPS'; // STORAGE::EXTERNAL::GDRIVE::DIRNAME // $this->post['gdrivedirname']
      $curr_path = Dashboard\bmi_get_config('STORAGE::LOCAL::PATH');

      $errors = 0;
      $created = false;

      if (!preg_match("/^[a-zA-Z0-9\_\-\/\.]+$/", $dir_path)) {
       return ['status' => 'msg', 'why' => __('Entered directory/path name does not match allowed characters (Local Storage).', 'backup-backup'), 'level' => 'warning'];
      }

      if (!is_string($dir_path) || $dir_path === '' || 
        !(preg_match('/^[A-Z]:[\/\\\\]/i', $dir_path) || strpos($dir_path, '/') === 0)) {
        return ['status' => 'msg', 'why' => __('Please enter full path to the directory (Local Storage).', 'backup-backup'), 'level' => 'warning'];
      }
      if (!file_exists($dir_path)) {
        $created = @mkdir($dir_path, 0755, true);
      }

      if (isset($this->post['backupbliss'])) {
        $backupblissenabled = $this->post['backupbliss'];
        if (!Dashboard\bmi_set_config('STORAGE::EXTERNAL::backupbliss', $backupblissenabled)) {
          $errors++;
        }
      }

      if (isset($this->post['dropbox'])) {
        $dropboxenabled = $this->post['dropbox'];
        if (!Dashboard\bmi_set_config('STORAGE::EXTERNAL::DROPBOX', $dropboxenabled)) {
          $errors++;
        }
      }

      if (isset($this->post['gdrive'])) {
        $gdriveenabled = $this->post['gdrive'];
        if (!Dashboard\bmi_set_config('STORAGE::EXTERNAL::GDRIVE', $gdriveenabled)) {
          $errors++;
        }

        if (isset($this->post['gdrivedirname'])) {
          $gdrivedirname = $this->post['gdrivedirname'];

          if (!preg_match("/^[a-zA-Z0-9\_\-\.]+$/", $gdrivedirname)) {
            return ['status' => 'msg', 'why' => __('Entered directory name does not match allowed characters (Google Drive).', 'backup-backup'), 'level' => 'warning'];
          }

          if (strlen(trim($gdrivedirname)) < 3) {
            return ['status' => 'msg', 'why' => __('Entered directory name is too short, min 3 characters (Google Drive).', 'backup-backup'), 'level' => 'warning'];
          }

          if (strlen(trim($gdrivedirname)) > 48) {
            return ['status' => 'msg', 'why' => __('Entered directory name is too long, max 48 characters (Google Drive).', 'backup-backup'), 'level' => 'warning'];
          }

          if (!Dashboard\bmi_set_config('STORAGE::EXTERNAL::GDRIVE::DIRNAME', $gdrivedirname)) {
            $errors++;
          }
        }
      }

      if (isset($this->post['ftp'])) {
        $ftpenabled = $this->post['ftp'];
        if (!Dashboard\bmi_set_config('STORAGE::EXTERNAL::FTP', $ftpenabled)) {
          $errors++;
        }

        if ($ftpenabled != "false"){
          if (isset($this->post['ftphostip'])) {
            $ftpiphost = $this->post['ftphostip'];
            update_option('bmi_pro_ftp_host', $ftpiphost);
          }

          if (isset($this->post['ftphostusername'])) {
            $ftpHostUsername = $this->post['ftphostusername'];
            update_option('bmi_pro_ftp_username', $ftpHostUsername);
          }

          if (isset($this->post['ftppassword'])) {
            $ftpHostPassword = $this->post['ftppassword'];
            if (!empty($ftpHostPassword) && is_string($ftpHostPassword) && strlen(trim($ftpHostPassword)) > 0) 
              update_option('bmi_pro_ftp_password', $ftpHostPassword);
          }

          if (isset($this->post['ftpport'])) {
            $ftpHostPort = $this->post['ftpport'];
            update_option('bmi_pro_ftp_port', $ftpHostPort);
          }

          if (isset($this->post['ftpdir'])) {
            $ftpHostDir = $this->post['ftpdir'];
            update_option('bmi_pro_ftp_backup_dir', $ftpHostDir);
          }
        } else {
          delete_option('bmi_pro_ftp_host');
          delete_option('bmi_pro_ftp_username');
          delete_option('bmi_pro_ftp_password');
        }

      } else {
        delete_option('bmi_pro_ftp_host');
        delete_option('bmi_pro_ftp_username');
        delete_option('bmi_pro_ftp_password');
      }

      if (isset($this->post['aws'])) {
        $s3enabled = $this->post['aws'];
        if (!Dashboard\bmi_set_config('STORAGE::EXTERNAL::AWS', $s3enabled)) {
          $errors++;
        }
      }

      if (isset($this->post['wasabi'])) {
        $wasabienabled = $this->post['wasabi'];
        if (!Dashboard\bmi_set_config('STORAGE::EXTERNAL::WASABI', $wasabienabled)) {
          $errors++;
        }
      }
      
      if (defined('BMI_BACKUP_PRO') && BMI_BACKUP_PRO === 1) {

        if (isset($this->post['onedrive'])) {
          $onedriveenabled = $this->post['onedrive'];
          if (!Dashboard\bmi_set_config('STORAGE::EXTERNAL::ONEDRIVE', $onedriveenabled)) {
            $errors++;
          }
        }

        if (isset($this->post['sftp'])) {
          $sftpenabled = $this->post['sftp'];
          if (!Dashboard\bmi_set_config('STORAGE::EXTERNAL::SFTP', $sftpenabled)) {
            $errors++;
          }
    
        }

      }

      if (is_writable($dir_path)) {
        if (!Dashboard\bmi_set_config('STORAGE::DIRECT::URL', $accessible)) {
          Logger::error('Backup Storage Direct Url Error');
          $errors++;
        }
        if (!Dashboard\bmi_set_config('STORAGE::LOCAL::PATH', esc_attr($dir_path))) {
          Logger::error('Backup Storage Local Path Error');
          $errors++;
        } else {
          $cur_dir = BMP::fixSlashes($curr_path);
          $new_dir = BMP::fixSlashes($dir_path);

          $backups_cur_dir = BMP::fixSlashes($curr_path) . DIRECTORY_SEPARATOR . 'backups';
          $backups_new_dir = BMP::fixSlashes($dir_path) . DIRECTORY_SEPARATOR . 'backups';

          $staging_cur_dir = BMP::fixSlashes($curr_path) . DIRECTORY_SEPARATOR . 'staging';
          $staging_new_dir = BMP::fixSlashes($dir_path) . DIRECTORY_SEPARATOR . 'staging';

          $tmp_cur_dir = BMP::fixSlashes($curr_path) . DIRECTORY_SEPARATOR . 'tmp';
          $tmp_new_dir = BMP::fixSlashes($dir_path) . DIRECTORY_SEPARATOR . 'tmp';

          update_option('BMI::STORAGE::LOCAL::PATH', $new_dir);

          if ($cur_dir != $new_dir) {

            if (!file_exists($new_dir)) @mkdir($new_dir, 0755, true);
            if (!file_exists($backups_new_dir)) @mkdir($backups_new_dir, 0755, true);
            if (!file_exists($staging_new_dir)) @mkdir($staging_new_dir, 0755, true);
            if (!file_exists($tmp_new_dir)) @mkdir($tmp_new_dir, 0755, true);

            $scanned_directory_staging = array_diff(scandir($staging_cur_dir), ['..', '.']);
            foreach ($scanned_directory_staging as $i => $file) {
              if (file_exists($staging_cur_dir . DIRECTORY_SEPARATOR . $file) && !is_dir($staging_cur_dir . DIRECTORY_SEPARATOR . $file)) {
                rename($staging_cur_dir . DIRECTORY_SEPARATOR . $file, $staging_new_dir . DIRECTORY_SEPARATOR . $file);
              }
            }

            $scanned_directory_tmp = array_diff(scandir($tmp_cur_dir), ['..', '.']);
            foreach ($scanned_directory_tmp as $i => $file) {
              if (file_exists($tmp_cur_dir . DIRECTORY_SEPARATOR . $file) && !is_dir($tmp_cur_dir . DIRECTORY_SEPARATOR . $file)) {
                rename($tmp_cur_dir . DIRECTORY_SEPARATOR . $file, $tmp_new_dir . DIRECTORY_SEPARATOR . $file);
              }
            }

            $scanned_directory_backups = array_diff(scandir($backups_cur_dir), ['..', '.']);
            foreach ($scanned_directory_backups as $i => $file) {
              if (file_exists($backups_cur_dir . DIRECTORY_SEPARATOR . $file) && !is_dir($backups_cur_dir . DIRECTORY_SEPARATOR . $file)) {
                rename($backups_cur_dir . DIRECTORY_SEPARATOR . $file, $backups_new_dir . DIRECTORY_SEPARATOR . $file);
              }
            }

            $scanned_directory = array_diff(scandir($cur_dir), ['..', '.']);
            foreach ($scanned_directory as $i => $file) {
              if (file_exists($cur_dir . DIRECTORY_SEPARATOR . $file) && !is_dir($cur_dir . DIRECTORY_SEPARATOR . $file)) {
                rename($cur_dir . DIRECTORY_SEPARATOR . $file, $new_dir . DIRECTORY_SEPARATOR . $file);
              }
            }

            if (file_exists($backups_cur_dir . DIRECTORY_SEPARATOR . '.htaccess')) @unlink($backups_cur_dir . DIRECTORY_SEPARATOR . '.htaccess');
            if (file_exists($backups_cur_dir . DIRECTORY_SEPARATOR . 'index.php')) @unlink($backups_cur_dir . DIRECTORY_SEPARATOR . 'index.php');
            if (file_exists($backups_cur_dir . DIRECTORY_SEPARATOR . 'index.html')) @unlink($backups_cur_dir . DIRECTORY_SEPARATOR . 'index.html');
            if (file_exists($backups_cur_dir)) @rmdir($backups_cur_dir);

            if (file_exists($staging_cur_dir . DIRECTORY_SEPARATOR . '.htaccess')) @unlink($staging_cur_dir . DIRECTORY_SEPARATOR . '.htaccess');
            if (file_exists($staging_cur_dir . DIRECTORY_SEPARATOR . 'index.php')) @unlink($staging_cur_dir . DIRECTORY_SEPARATOR . 'index.php');
            if (file_exists($staging_cur_dir . DIRECTORY_SEPARATOR . 'index.html')) @unlink($staging_cur_dir . DIRECTORY_SEPARATOR . 'index.html');
            if (file_exists($staging_cur_dir)) @rmdir($staging_cur_dir);

            if (file_exists($tmp_cur_dir . DIRECTORY_SEPARATOR . '.htaccess')) @unlink($tmp_cur_dir . DIRECTORY_SEPARATOR . '.htaccess');
            if (file_exists($tmp_cur_dir . DIRECTORY_SEPARATOR . 'index.php')) @unlink($tmp_cur_dir . DIRECTORY_SEPARATOR . 'index.php');
            if (file_exists($tmp_cur_dir . DIRECTORY_SEPARATOR . 'index.html')) @unlink($tmp_cur_dir . DIRECTORY_SEPARATOR . 'index.html');
            if (file_exists($tmp_cur_dir)) @rmdir($tmp_cur_dir);

            if (file_exists($cur_dir . DIRECTORY_SEPARATOR . 'complete_logs.log')) @unlink($cur_dir . DIRECTORY_SEPARATOR . 'complete_logs.log');
            if (file_exists($cur_dir)) @rmdir($cur_dir);

            if (is_dir($cur_dir) && file_exists($cur_dir)) {
              $left_files = array_diff(scandir($cur_dir), ['..', '.']);
              if (sizeof($left_files) == 0) {
                if (file_exists($cur_dir)) {
                  @rmdir($cur_dir);
                }
              }
            }

          }
        }
      } else {
        if ($created === true) {
          if (file_exists($dir_path)) @unlink($dir_path);
        }

        return ['status' => 'msg', 'why' => __('Entered path is not writable, cannot be used.', 'backup-backup'), 'level' => 'warning'];
      }

      return ['status' => 'success', 'errors' => $errors];
    }

    public function saveOtherOptions() {

      // Errors
      $invalid_email = __('Provided email addess is not valid.', 'backup-backup');
      $title_long = __('Your email title is too long, please change the title (max 64 chars).', 'backup-backup');
      $title_short = __('Your email title is too short, please use longer one (at least 3 chars).', 'backup-backup');
      $title_empty = __('Title field is required, please fill it.', 'backup-backup');
      $email_empty = __('Email field cannot be empty, please fill it.', 'backup-backup');
      $cli_no_exist = __('Path to executable that you provided for PHP CLI does not exist.', 'backup-backup');
      $db_query_too_low = __('The value for query amount cannot be smaller than 15.', 'backup-backup');
      $db_query_too_much = __('The value for query amount cannot be larger than 15000.', 'backup-backup');
      $db_sr_max_too_low = __('The value for search replace max page cannot be smaller than 10.', 'backup-backup');
      $db_sr_max_too_much = __('The value for search replace max page cannot be larger than 30000.', 'backup-backup');
      $fl_ex_max_too_low = __('The value for extraction limit cannot be smaller than 50.', 'backup-backup');
      $fl_ex_max_too_much = __('The value for extraction limit cannot be larger than 20000.', 'backup-backup');

      $email = sanitize_email(trim($this->post['email'])); // OTHER:EMAIL
      $email_title = sanitize_text_field(trim($this->post['email_title'])); // OTHER:EMAIL:TITLE
      $schedule_issues = $this->post['schedule_issues'] === 'true' ? true : false; // OTHER:EMAIL:NOTIS
      $experiment_timeout = $this->post['experiment_timeout'] === 'true' ? true : false; // OTHER:EXPERIMENT:TIMEOUT
      $experiment_timeout_hard = $this->post['experimental_hard_timeout'] === 'true' ? true : false; // OTHER:EXPERIMENT:TIMEOUT:HARD
      $php_cli_manual_path = isset($this->post['php_cli_manual_path']) ? trim($this->post['php_cli_manual_path']) : ''; // OTHER:CLI:PATH
      $php_cli_disable_others = $this->post['php_cli_disable_others'] === 'true' ? true : false; // OTHER:CLI:DISABLE
      $normal_timeout = $this->post['normal_timeout'] === 'true' ? true : false; // OTHER:USE:TIMEOUT:NORMAL
      $insecure_download = $this->post['download_technique'] === 'true' ? true : false; // OTHER:DOWNLOAD:DIRECT
      $db_query_size = isset($this->post['db_queries_amount']) ? trim($this->post['db_queries_amount']) : '2000'; // OTHER:DB:QUERIES
      $db_search_replace_max = isset($this->post['db_search_replace_max']) ? trim($this->post['db_search_replace_max']) : '300'; // OTHER:DB:SEARCHREPLACE:MAX
      $file_limit_extraction_max = isset($this->post['file_limit_extraction_max']) ? trim($this->post['file_limit_extraction_max']) : 'auto'; // OTHER:FILE:EXTRACT:MAX
      $db_restore_splitting = $this->post['bmi-restore-splitting'] === 'true' ? true : false; // OTHER:RESTORE:SPLITTING
      $db_restore_v3_engine = $this->post['bmi-db-v3-restore-engine'] === 'true' ? true : false; // OTHER:RESTORE:DB:V3

      $no_assets_b4_restore = $this->post['remove-assets-before-restore'] === 'true' ? true : false; // OTHER:RESTORE:BEFORE:CLEANUP
      $single_file_db_force = $this->post['bmi-db-single-file-backup'] === 'true' ? true : false; // OTHER:BACKUP:DB:SINGLE:FILE
      $db_batching_backup = $this->post['bmi-db-batching-backup'] === 'true' ? true : false; // OTHER:BACKUP:DB:BATCHING

      $bmi_disable_space_check = $this->post['bmi-disable-space-check-function'] === 'true' ? true : false; // OTHER:BACKUP:SPACE:CHECKING

      $uninstall_config = $this->post['uninstall_config'] === 'true' ? true : false; // OTHER:UNINSTALL:CONFIGS
      $uninstall_backups = $this->post['uninstall_backups'] === 'true' ? true : false; // OTHER:UNINSTALL:BACKUPS

      if ($experiment_timeout_hard === true) {
        $experiment_timeout = false;
      }

      if ($normal_timeout === true) {
        $experiment_timeout = false;
        $experiment_timeout_hard = false;
      }

      if (!is_numeric($db_query_size) || empty($db_query_size)) {
        $db_query_size = "2000";
      }

      if (!is_numeric($file_limit_extraction_max) || empty($file_limit_extraction_max)) {
        $file_limit_extraction_max = "auto";
      }

      if (!is_numeric($db_search_replace_max) || empty($db_search_replace_max)) {
        $db_search_replace_max = "300";
      }

      if (strlen($email) <= 0) {
        return ['status' => 'msg', 'why' => $email_empty, 'level' => 'warning'];
      }
      if (strlen($email_title) <= 0) {
        return ['status' => 'msg', 'why' => $title_empty, 'level' => 'warning'];
      }
      if (strlen($email_title) > 64) {
        return ['status' => 'msg', 'why' => $title_long, 'level' => 'warning'];
      }
      if (strlen($email_title) < 3) {
        return ['status' => 'msg', 'why' => $title_short, 'level' => 'warning'];
      }
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'msg', 'why' => $invalid_email, 'level' => 'warning'];
      }
      if ($php_cli_manual_path != '' && !file_exists($php_cli_manual_path)) {
        return ['status' => 'msg', 'why' => $cli_no_exist, 'level' => 'warning'];
      }
      if (intval($db_query_size) > 15000) {
        return ['status' => 'msg', 'why' => $db_query_too_much, 'level' => 'warning'];
      }
      if (intval($db_query_size) < 15) {
        return ['status' => 'msg', 'why' => $db_query_too_low, 'level' => 'warning'];
      }
      if (intval($db_search_replace_max) > 30000) {
        return ['status' => 'msg', 'why' => $db_sr_max_too_much, 'level' => 'warning'];
      }
      if (intval($db_search_replace_max) < 10) {
        return ['status' => 'msg', 'why' => $db_sr_max_too_low, 'level' => 'warning'];
      }
      if ($file_limit_extraction_max != 'auto' && intval($file_limit_extraction_max) > 20000) {
        return ['status' => 'msg', 'why' => $fl_ex_max_too_much, 'level' => 'warning'];
      }
      if ($file_limit_extraction_max != 'auto' && intval($file_limit_extraction_max) < 50) {
        return ['status' => 'msg', 'why' => $fl_ex_max_too_low, 'level' => 'warning'];
      }

      $error = 0;
      if (!Dashboard\bmi_set_config('OTHER:EMAIL', $email)) {
        Logger::error('Backup Other Email Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:EMAIL:TITLE', $email_title)) {
        Logger::error('Backup Other Email Title Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:EMAIL:NOTIS', $schedule_issues)) {
        Logger::error('Backup Other Email Notis Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:CLI:PATH', $php_cli_manual_path)) {
        Logger::error('Backup Other CLI Path Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:CLI:DISABLE', $php_cli_disable_others)) {
        Logger::error('Backup Other CLI Disable Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:EXPERIMENT:TIMEOUT', $experiment_timeout)) {
        Logger::error('Backup Other Experiment Timeout Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:EXPERIMENT:TIMEOUT:HARD', $experiment_timeout_hard)) {
        Logger::error('Backup Other Experiment Timeout Hard Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:USE:TIMEOUT:NORMAL', $normal_timeout)) {
        Logger::error('Backup Other Experiment Timeout Normal Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:RESTORE:DB:V3', $db_restore_v3_engine)) {
        Logger::error('Backup Other Restore DB V3 Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:DB:QUERIES', $db_query_size)) {
        Logger::error('Backup Other DB Queries Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:DB:SEARCHREPLACE:MAX', $db_search_replace_max)) {
        Logger::error('Backup Other DB Queries Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:FILE:EXTRACT:MAX', $file_limit_extraction_max)) {
        Logger::error('Backup Other File Extract Max Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:DOWNLOAD:DIRECT', $insecure_download)) {
        Logger::error('Backup Other Download Direct Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:UNINSTALL:CONFIGS', $uninstall_config)) {
        Logger::error('Backup Other Uninstall Configs Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:UNINSTALL:BACKUPS', $uninstall_backups)) {
        Logger::error('Backup Other Uninstall Backups Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:RESTORE:SPLITTING', $db_restore_splitting)) {
        Logger::error('Backup Other Restore Splitting Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:BACKUP:DB:SINGLE:FILE', $single_file_db_force)) {
        Logger::error('Backup Other Backup DB Single File Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:BACKUP:DB:BATCHING', $db_batching_backup)) {
        Logger::error('Backup Other Backup DB Batching Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:BACKUP:SPACE:CHECKING', $bmi_disable_space_check)) {
        Logger::error('Backup Other Backup Space Checking Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('OTHER:RESTORE:BEFORE:CLEANUP', $no_assets_b4_restore)) {
        Logger::error('Backup Other Restore Before Cleanup Error');
        $error++;
      }

      if (has_action('bmi_premium_other_options')) {
        do_action('bmi_premium_other_options', $this->post);
      }

      return ['status' => 'success', 'errors' => $error];
    }

    public function saveStorageTypeConfig() {

      // Errors
      $name_empty = __('Name is required, please fill the input.', 'backup-backup');
      $name_long = __('Your name is too long, please change the name.', 'backup-backup');
      $name_short = __('Your name is too short, please create longer one.', 'backup-backup');
      $name_space = __('Please, do not use spaces in file name.', 'backup-backup');
      $name_forbidden = __('Your name contains character(s) that are not allowed in file names: ', 'backup-backup');

      $forbidden_chars = ['/', '\\', '<', '>', ':', '"', "'", '|', '?', '*', '.', ';', '@', '!', '~', '`', ',', '#', '$', '&', '=', '+'];
      $name = trim($this->post['name']); // BACKUP:NAME
      $extensionType = trim($this->post['extension']); // BACKUP:EXTENSION:TYPE

      if (strlen($name) == 0) {
        return ['status' => 'msg', 'why' => $name_empty, 'level' => 'warning'];
      }
      if (strlen($name) > 40) {
        return ['status' => 'msg', 'why' => $name_long, 'level' => 'warning'];
      }
      if (strlen($name) < 3) {
        return ['status' => 'msg', 'why' => $name_short, 'level' => 'warning'];
      }
      if (strpos($name, ' ') !== false) {
        return ['status' => 'msg', 'why' => $name_space, 'level' => 'warning'];
      }

      if (defined('BMI_BACKUP_PRO') && BMI_BACKUP_PRO == 1) {
        if (!in_array($extensionType, ['.zip', '.tar.gz', '.tar'])) {
          return ['status' => 'msg', 'why' => $name_space, 'level' => 'warning'];
        }
      }

      for ($i = 0; $i < sizeof($forbidden_chars); ++$i) {
        $char = $forbidden_chars[$i];
        if (strpos($name, $char) !== false) {
          return ['status' => 'msg', 'why' => $name_forbidden . $char, 'level' => 'warning'];
        }
      }

      $error = 0;
      if (!Dashboard\bmi_set_config('BACKUP:NAME', $name)) {
        Logger::error('Backup Name Error');
        $error++;
      }
      
      if (defined('BMI_BACKUP_PRO') && BMI_BACKUP_PRO == 1) {
        if (!Dashboard\bmi_set_config('BACKUP:EXTENSION:TYPE', $extensionType)) {
          Logger::error('Backup Extension Type Error');
          $error++;
        }
      }

      return ['status' => 'success', 'errors' => $error];
    }

    public function saveFilesConfig() {
      $db_group = $this->post['database_group']; // BACKUP:DATABASE
      $files_group = $this->post['files_group']; // BACKUP:FILES

      $fgp = $this->post['files-group-plugins']; // BACKUP:FILES::PLUGINS
      $fgu = $this->post['files-group-uploads']; // BACKUP:FILES::UPLOADS
      $fgt = $this->post['files-group-themes']; // BACKUP:FILES::THEMES
      $fgoc = $this->post['files-group-other-contents']; // BACKUP:FILES::OTHERS
      $fgwp = $this->post['files-group-wp-install']; // BACKUP:FILES::WP

      $file_filters = $this->post['files_by_filters']; // BACKUP:FILES::FILTER
      $ffs = $this->post['ex_b_fs']; // BACKUP:FILES::FILTER:SIZE
      $ffsizemax = $this->post['BFFSIN']; // BACKUP:FILES::FILTER:SIZE:IN
      $ffn = $this->post['ex_b_names']; // BACKUP:FILES::FILTER:NAMES
      $ffp = $this->post['ex_b_fpaths']; // BACKUP:FILES::FILTER:FPATHS
      $ffd = $this->post['ex_b_dpaths']; // BACKUP:FILES::FILTER:DPATHS

      $dbeg = $this->post['db-exclude-tables-group']; // BACKUP:DATABASE:EXCLUDE
      $dbet = $this->post['db-excluded-tables']; // BACKUP:DATABASE:EXCLUDE:LIST

      $existant = [];
      $parsed = [];
      $ffnames = $this->post['dynamic-names']; // BACKUP:FILES::FILTER:NAMES:IN
      $ffpnames = array_unique($this->post['dynamic-fpaths-names']); // BACKUP:FILES::FILTER:FPATHS:IN
      $ffdnames = array_unique($this->post['dynamic-dpaths-names']); // BACKUP:FILES::FILTER:DPATHS:IN

      if (is_array($dbet) || is_object($dbet)) {
        if (sizeof($dbet) == 1 && $dbet[0] == 'empty') {
          $dbet = [];
        }
      }

      if ($dbeg === 'true' || $dbeg === true) $dbeg = true;
      else $dbeg = false;

      $max = sizeof($ffpnames);
      for ($i = 0; $i < $max; ++$i) {
        if (!is_string($ffpnames[$i]) || trim(strlen($ffpnames[$i])) <= 1) {
          array_splice($ffpnames, $i, 1);
          $i--;
          $max--;
        }
      }

      $max = sizeof($ffdnames);
      for ($i = 0; $i < $max; ++$i) {
        if (!is_string($ffdnames[$i]) || trim(strlen($ffdnames[$i])) <= 1) {
          array_splice($ffdnames, $i, 1);
          $i--;
          $max--;
        }
      }

      for ($i = 0; $i < sizeof($ffnames); ++$i) {
        $row = $ffnames[$i];
        $txt = array_key_exists('txt', $row) ? "" . $row['txt'] . "" : false;
        $pos = array_key_exists('pos', $row) ? $row['pos'] : false;
        $whr = array_key_exists('whr', $row) ? $row['whr'] : false;

        if ($txt === false || $pos === false || $whr === false) {
          continue;
        }
        if (trim(strlen($txt)) <= 0) {
          continue;
        }
        if (!in_array($pos, ["1", "2", "3"])) {
          continue;
        }
        if (!in_array($whr, ["1", "2"])) {
          continue;
        }
        if (in_array($txt . $pos . $whr, $existant)) {
          continue;
        } else {
          $existant[] = $txt . $pos . $whr;
        }

        $parsed[] = ['txt' => $txt, 'pos' => $pos, 'whr' => $whr];
      }

      if ($ffs == 'true' && !is_numeric($ffsizemax)) {
        return ['status' => 'msg', 'why' => __('Entred file size limit, is not correct number.', 'backup-backup'), 'level' => 'warning'];
      }

      $error = 0;
      if (!Dashboard\bmi_set_config('BACKUP:DATABASE', $db_group)) {
        Logger::error('Backup Database Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES', $files_group)) {
        Logger::error('Backup Files Error');
        $error++;
      }

      if (!Dashboard\bmi_set_config('BACKUP:FILES::PLUGINS', $fgp)) {
        Logger::error('Backup Files Plugins Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::UPLOADS', $fgu)) {
        Logger::error('Backup Files Uploads Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::THEMES', $fgt)) {
        Logger::error('Backup Files Themes Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::OTHERS', $fgoc)) {
        Logger::error('Backup Files Others Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::WP', $fgwp)) {
        Logger::error('Backup Files WP Error');
        $error++;
      }

      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER', $file_filters)) {
        Logger::error('Backup Files Filter Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER:SIZE', $ffs)) {
        Logger::error('Backup Files Filter Size Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER:NAMES', $ffn)) {
        Logger::error('Backup Files Names Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER:FPATHS', $ffp)) {
        Logger::error('Backup Files Fpaths Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER:DPATHS', $ffd)) {
        Logger::error('Backup Files Dpaths Error');
        $error++;
      }

      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER:SIZE:IN', $ffsizemax)) {
        Logger::error('Backup Files Filter Size In Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER:NAMES:IN', $parsed)) {
        Logger::error('Backup Files Filter Names In Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER:FPATHS:IN', $ffpnames)) {
        Logger::error('Backup Files Filter Fpaths In Error');
        $error++;
      }
      if (!Dashboard\bmi_set_config('BACKUP:FILES::FILTER:DPATHS:IN', $ffdnames)) {
        Logger::error('Backup Files Filter Dpaths In Error');
        $error++;
      }

      if (defined('BMI_BACKUP_PRO') && BMI_BACKUP_PRO == 1) {
        if (!Dashboard\bmi_set_config('BACKUP:DATABASE:EXCLUDE', $dbeg)) {
          Logger::error('Backup Files Filter Database Exclude Error');
          $error++;
        }
        if (!Dashboard\bmi_set_config('BACKUP:DATABASE:EXCLUDE:LIST', $dbet)) {
          Logger::error('Backup Files Filter Database Exclude List Error');
          $error++;
        }
      }

      if (has_action('bmip_smart_exclusion_options')){
        do_action('bmip_smart_exclusion_options', $this->post);
      }

      // return array('status' => 'msg', 'why' => __('Entred path is not writable or does not exist.', 'backup-backup'), 'level' => 'warning');

      return ['status' => 'success', 'errors' => $error];
    }

    public function scanFilesForBackup(&$progress, $stgSites = [], $fileCalcType = false) {
      require_once BMI_INCLUDES . '/scanner/files.php';
      require_once BMI_INCLUDES . '/file-explorer.php';
      $stagingSites = [];

      // Get all directory names of staging sites
      foreach ($stgSites as $index => $site) {

        // Convert every directory to their location path
        $stagingSites[] = '***ABSPATH***/' . $site['name'];

      }

      // Use filters?
      $is = Dashboard\bmi_get_config('BACKUP:FILES::FILTER') === 'true' ? true : false;

      // Get settings form config
      $fgp = Dashboard\bmi_get_config('BACKUP:FILES::PLUGINS');
      $fgt = Dashboard\bmi_get_config('BACKUP:FILES::THEMES');
      $fgu = Dashboard\bmi_get_config('BACKUP:FILES::UPLOADS');
      $fgoc = Dashboard\bmi_get_config('BACKUP:FILES::OTHERS');
      $fgwp = Dashboard\bmi_get_config('BACKUP:FILES::WP');
      $dpathsis = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:DPATHS') === 'true' ? true : false;
      $dpaths = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:DPATHS:IN');
      $dynamesis = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:NAMES') === 'true' ? true : false;
      $dynames = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:NAMES:IN');
      $dynparsed = [];

      $isSmartExclusion =defined("BMI_BACKUP_PRO") && BMI_BACKUP_PRO && Dashboard\bmi_get_config('SMART:EXCLUSION:ENABLED') == 'true' ? true : false;
      $isCacheExcluded = $isSmartExclusion && (Dashboard\bmi_get_config('SMART:EXCLUSION:CACHE') == 'true' ? true : false);
      $isDeactivePluginsExcluded = $isSmartExclusion && (Dashboard\bmi_get_config('SMART:EXCLUSION:DPLUGINS') == 'true' ? true : false);
      $isNotUsedThemesExcluded = $isSmartExclusion && (Dashboard\bmi_get_config('SMART:EXCLUSION:NUTHEMES') == 'true' ? true : false);
      $isDebugLogsExcluded = $isSmartExclusion && (Dashboard\bmi_get_config('SMART:EXCLUSION:DLOGS') == 'true' ? true : false);
      $isPostRevisionsExcluded = $isSmartExclusion &&(Dashboard\bmi_get_config('SMART:EXCLUSION:PREVISIONS') == 'true' ? true : false);


      if ($fileCalcType != false) {
        $fgp = ($fileCalcType == 'plugins') ? true : false;
        $fgt = ($fileCalcType == 'themes') ? true : false;
        $fgu = ($fileCalcType == 'uploads') ? true : false;
        $fgoc = ($fileCalcType == 'contents_others') ? true : false;
        $fgwp = ($fileCalcType == 'wordpress') ? true : false;
      }

      // Filter dynames to for smaller size
      if ($is && $dynamesis) {
        for ($i = 0; $i < sizeof($dynames); ++$i) {
          $s = $dynames[$i];
          if ($s->whr == '2') {
            $dynparsed[] = ['s' => $s->txt, 'w' => $s->pos, 'z' => strlen($s->txt)];
          }
        }
      }

      // Set exclusion rules
      $ignored_folders_default = [];
      if ($is && $dynamesis) {
        BMP::merge_arrays($ignored_folders_default, $dynparsed);
      }
      $ignored_folders = $ignored_folders_default;
      $ignored_paths_default = [BMI_CONFIG_DIR, BMI_ROOT_DIR];
      $ignored_paths_default[] = "***ABSPATH***/wp-content/ai1wm-backups";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/ai1wm-backups-old";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/mwp-download";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/uploads/wp-clone";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/updraft";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/ebwp-backups";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/cache/seraphinite-accelerator";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/backups-dup-pro";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/wpvividbackups";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/backup-guard";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/backuply";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/backups-dup-lite";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/uploads/backupbuddy_backups";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/uploads/wp-file-manager-pro";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/uploads/wp-file-manager";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/plugins/akeebabackupwp";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/uploads/jetbackup";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/uploads/backup-guard";
      $ignored_paths_default[] = "***ABSPATH***/wp-content/uploads/wp-migrate-db";

      $ignored_paths_default[] = "***ABSPATH***/wp-content/uploads/wp-staging";
      
      if ($isSmartExclusion && ($fileCalcType == false || $fileCalcType == 'database')) {
        if ($isCacheExcluded) {
          $ignored_paths_default = apply_filters('bmip_smart_exclusion_cache', $ignored_paths_default);
        }
        if ($isDeactivePluginsExcluded) {
          $ignored_paths_default = apply_filters('bmip_smart_exclusion_deactive_plugins', $ignored_paths_default);
        }
        if ($isNotUsedThemesExcluded) {
          $ignored_paths_default = apply_filters('bmip_smart_exclusion_not_used_themes', $ignored_paths_default);
        }
      }

      // Exclude cache directory permanently as it's just cache
      // $ignored_paths_default[] = "***ABSPATH***/wp-content/cache";
      // $ignored_paths_default[] = "***ABSPATH***/wp-content/cache_bak";
      // $ignored_paths_default[] = "***ABSPATH***/wp-content/uploads/cache";

      // Add staging sites to permanent exclusion rules
      for ($i = 0; $i < sizeof($stagingSites); ++$i) {
        $ignored_paths_default[] = $stagingSites[$i];
      }

      if (defined('BMI_PRO_ROOT_DIR')) $ignored_paths_default[] = BMI_PRO_ROOT_DIR;
      if ($is && $dpathsis) {
        foreach($dpaths as $dpath) {
          $dpath = str_replace('***ABSPATH***', untrailingslashit(ABSPATH), $dpath);
          $dpath = BMP::fixSlashes($dpath);
          if (is_dir($dpath)) {
            if (!$fileCalcType) $progress->log(__('Removing directory from backup (due to exclude rules): ', 'backup-backup') . $dpath, 'WARN');
            $ignored_folders_default[] = $dpath;
          }
          $ignored_paths_default[] = $dpath;
        }
      }
      $ignored_paths = $ignored_paths_default;

      // Fix slashes for current system (directories)
      for ($i = 0; $i < sizeof($ignored_paths); ++$i) {
        $ignored_paths[$i] = str_replace('***ABSPATH***', untrailingslashit(ABSPATH), $ignored_paths[$i]);
        $ignored_paths[$i] = BMP::fixSlashes($ignored_paths[$i]);
      }

      // WordPress Paths
      $plugins_path = BMP::fixSlashes(WP_PLUGIN_DIR);
      $themes_path = BMP::fixSlashes(dirname(get_template_directory()));
      $uploads_path = BMP::fixSlashes(wp_upload_dir()['basedir']);
      $wp_contents = BMP::fixSlashes(WP_CONTENT_DIR);
      $wp_install = BMP::fixSlashes(ABSPATH);

      // Getting plugins
      $sfgp = Scanner::equalFolderByPath($wp_install, $plugins_path, $ignored_folders);
      if ($fgp == 'true' && !$sfgp) {
        $plugins_path_files = Scanner::scanFilesGetNamesWithIgnoreFBC($plugins_path, $ignored_folders, $ignored_paths);
        foreach($ignored_paths as $dpath) {
          $isSub = File_Explorer::isSub($dpath, $plugins_path);
          if ($isSub != -1) {
            $this->ignoredDirectoriesSize += File_Explorer::getDirSize($dpath);
          }
        }
      }

      // Getting themes
      $sfgt = Scanner::equalFolderByPath($wp_install, $themes_path, $ignored_folders);
      if ($fgt == 'true' && !$sfgt) {
        $themes_path_files = Scanner::scanFilesGetNamesWithIgnoreFBC($themes_path, $ignored_folders, $ignored_paths);
        foreach($ignored_paths as $dpath) {
          $isSub = File_Explorer::isSub($dpath, $themes_path);
          if ($isSub != -1) {
            $this->ignoredDirectoriesSize += File_Explorer::getDirSize($dpath);
          }
        }
      }

      // Getting uploads
      $sfgu = Scanner::equalFolderByPath($wp_install, $uploads_path, $ignored_folders);
      if ($fgu == 'true' && !$sfgu) {
        $uploads_path_files = Scanner::scanFilesGetNamesWithIgnoreFBC($uploads_path, $ignored_folders, $ignored_paths);
        foreach($ignored_paths as $dpath) {
          $isSub = File_Explorer::isSub($dpath, $uploads_path);
          if ($isSub != -1) {
            $this->ignoredDirectoriesSize += File_Explorer::getDirSize($dpath);
          }
        }
      }

      // Ignore above paths
      $sfgoc = Scanner::equalFolderByPath($wp_install, $wp_contents, $ignored_folders);
      if ($fgoc == 'true' && !$sfgoc) {

        // Ignore common folders (already scanned)
        $content_folders = [$plugins_path, $themes_path, $uploads_path];
        BMP::merge_arrays($content_folders, $ignored_paths);

        // Getting other contents
        $wp_contents_files = Scanner::scanFilesGetNamesWithIgnoreFBC($wp_contents, $ignored_folders, $content_folders);

        foreach($ignored_paths as $dpath) {
          $isSub = File_Explorer::isSub($dpath, $wp_contents) != -1 && 
            File_Explorer::isSub($dpath, $plugins_path) == -1 &&
            File_Explorer::isSub($dpath, $themes_path) == -1 &&
            File_Explorer::isSub($dpath, $uploads_path) == -1;
          if ($isSub) {
            $this->ignoredDirectoriesSize += File_Explorer::getDirSize($dpath);
          }
        }
      }

      // Ignore contents path
      if ($fgwp == 'true') {

        // Ignore contents file
        $ignored_paths[] = $wp_contents;

        // Getting WP Installation
        $wp_install_files = Scanner::scanFilesGetNamesWithIgnoreFBC($wp_install, $ignored_folders, $ignored_paths);

        foreach($ignored_paths as $dpath) {
          $isSub = File_Explorer::isSub($dpath, $wp_install) != -1 &&
            File_Explorer::isSub($dpath, $wp_contents) == -1;
          if ($isSub) {
            $this->ignoredDirectoriesSize += File_Explorer::getDirSize($dpath);
          }
        }
      }

      // Concat all file paths
      $all_files = [];
      if ($fgp == 'true' && !$sfgp) {
        BMP::merge_arrays($all_files, $plugins_path_files);
        unset($plugins_path_files);
      }

      if ($fgt == 'true' && !$sfgt) {
        BMP::merge_arrays($all_files, $themes_path_files);
        unset($themes_path_files);
      }

      if ($fgu == 'true' && !$sfgu) {
        BMP::merge_arrays($all_files, $uploads_path_files);
        unset($uploads_path_files);
      }

      if ($fgoc == 'true' && !$sfgoc) {
        BMP::merge_arrays($all_files, $wp_contents_files);
        unset($wp_contents_files);
      }

      if ($fgwp == 'true') {
        BMP::merge_arrays($all_files, $wp_install_files);
        unset($wp_install_files);
      }

      return $all_files;
    }

    public function parseFilesForBackup(&$files, &$progress, $cron = false, $dirCalc = false) {

      $is = Dashboard\bmi_get_config('BACKUP:FILES::FILTER') === 'true' ? true : false;
      $acis = (Dashboard\bmi_get_config('BACKUP:FILES::FILTER:FPATHS') === 'true' && $is) ? true : false;
      $ac = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:FPATHS:IN');

      $abis = (Dashboard\bmi_get_config('BACKUP:FILES::FILTER:NAMES') === 'true' && $is) ? true : false;
      $ab = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:NAMES:IN');
      $abres = [];
      $acres = new \stdClass();

      $isSmartExclusion = defined("BMI_BACKUP_PRO") && BMI_BACKUP_PRO && Dashboard\bmi_get_config('SMART:EXCLUSION:ENABLED') == 'true' ? true : false;
      $isDebugLogsExcluded = $isSmartExclusion && (Dashboard\bmi_get_config('SMART:EXCLUSION:DLOGS') == 'true' ? true : false);

      // Local list of permanently blocked files
      if ($acis == false) {
        $acis = true;
        $ac = [
          '***ABSPATH***/wp-content/uploads/wpforms/.htaccess.cpmh3129', // Binary broken file of wpforms
          '***ABSPATH***/wp-content/uploads/gravity_forms/.htaccess.cpmh3129', // Binary broken file of wpforms
          '***ABSPATH***/.htaccess.cpmh3129', // Binary broken file of wpforms
          '***ABSPATH***/logs/traffic.html/.md5sums', // Binary broken file of wpforms
          '***ABSPATH***/wp-config.php', // Exclude wp-config.php permanently
          '***ABSPATH***/wp-content/backup-migration-config.php' // Exclude BMI CONFIG hardly
        ];
      } else {
        foreach ($ac as $key => $value) {
          $value = str_replace('***ABSPATH***', untrailingslashit(ABSPATH), $value);
          $value = BMP::fixSlashes($value);
          if (file_exists($value)) {
            if (!$dirCalc) $progress->log(__('Removing file from backup (due to exclude rules): ', 'backup-backup') . $value, 'WARN');
            $ac[$key] = $value;
          }
        }
        $ac[] = '***ABSPATH***/wp-content/uploads/wpforms/.htaccess.cpmh3129'; // Binary broken file of wpforms
        $ac[] = '***ABSPATH***/wp-content/uploads/gravity_forms/.htaccess.cpmh3129'; // Binary broken file of wpforms
        $ac[] = '***ABSPATH***/.htaccess.cpmh3129'; // Binary broken file of wpforms
        $ac[] = '***ABSPATH***/logs/traffic.html/.md5sums'; // Binary broken file of wpforms
        $ac[] = '***ABSPATH***/wp-config.php'; // Exclude wp-config.php permanently
        $ac[] = '***ABSPATH***/wp-content/backup-migration-config.php'; // Exclude BMI CONFIG hardly
      }

      if ($isDebugLogsExcluded) {
        $ac = apply_filters('bmip_smart_exclusion_debug_logs', $ac);
      }

      $temp_is = false;
      if ($is == false) {
        $temp_is = true;
      }

      if (($is && $acis) || $temp_is) {
        foreach ($ac as $key => $value) {
          $value = str_replace('***ABSPATH***', untrailingslashit(ABSPATH), $value);
          $value = BMP::fixSlashes($value);
          $acres->{$value} = 1;
        }
      }

      if ($is && $abis) {
        for ($i = 0; $i < sizeof($ab); ++$i) {
          $s = $ab[$i];
          if ($s->whr == '1') {
            $abres[] = ['s' => $s->txt, 'w' => $s->pos, 'z' => strlen($s->txt)];
          }
        }
      }

      $limitcrl = 64;
      $cliEnabled = false;
      if (defined('BMI_CLI_ENABLED')) $cliEnabled = apply_filters('bmi_cli_enabled', BMI_CLI_ENABLED);
      if ($dirCalc && $cliEnabled && !defined('BMI_CLI_FAILED')) $limitcrl = 128;
      $first_big = false;
      $sizemax = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:SIZE:IN');
      $usesize = (Dashboard\bmi_get_config('BACKUP:FILES::FILTER:SIZE') === 'true' && $is) ? true : false;
      if (!is_numeric($sizemax)) {
        $usesize = false;
        $sizemax = 99999;
      } else {
        $sizemax = intval($sizemax);
      }

      // If legacy === false it will use background process to bypass the timeout
      if ($dirCalc) {
        $legacy = true;
      } else {
        $legacyVersion = apply_filters('bmi_legacy_version', BMI_LEGACY_VERSION);
        $legacyHardVersion = apply_filters('bmi_legacy_hard_version', BMI_LEGACY_HARD_VERSION);
        $functionNormal = apply_filters('bmi_function_normal', BMI_FUNCTION_NORMAL);
        if (!defined('BMI_LEGACY_VERSION')) $legacy = true;
        else $legacy = $legacyVersion;
        if ($legacy && defined('BMI_LEGACY_HARD_VERSION') && !$legacyHardVersion) $legacy = $legacyHardVersion;
        $cliEnabled = false;
        if (defined('BMI_CLI_ENABLED')) $cliEnabled = apply_filters('bmi_cli_enabled', BMI_CLI_ENABLED);
        if (defined('BMI_FUNCTION_NORMAL') && $cliEnabled === true && $functionNormal === true && !defined('BMI_CLI_FAILED')) $legacy = false;
      }

      $total_size = 0;
      $excludedBytes = 0;
      $max = $sizemax * (1024 * 1024);
      $maxfor = sizeof($files);

      // Non-legacy variables
      if ($legacy === false) {
        $Hx = trailingslashit(WP_CONTENT_DIR);
        $Hz = trailingslashit(ABSPATH);
        $Hxs = strlen($Hx);
        $Hzs = strlen($Hz);
      }

      // Sort it by size
      if ($legacy === false) {
        usort($files, function ($a, $b) {
          $a = explode(',', $a);
          $last = sizeof($a) - 1;
          $sizea = intval($a[$last]);

          $b = explode(',', $b);
          $last = sizeof($b) - 1;
          $sizeb = intval($b[$last]);

          if ($sizea == $sizeb) return 0;
          if ($sizea < $sizeb) return -1;
          else return 1;
        });
      }

      // Process due to rules
      for ($i = 0; $i < $maxfor; ++$i) {

        // Remove size from path and get the size
        $files[$i] = explode(',', $files[$i]);
        $last = sizeof($files[$i]) - 1;
        $size = intval($files[$i][$last]);
        unset($files[$i][$last]);
        $files[$i] = implode(',', $files[$i]);

        if ($usesize && Scanner::fileTooLarge($size, $max)) {
          if (!$dirCalc) $progress->log(__("Removing file from backup (too large) ", 'backup-backup') . $files[$i] . ' (' . number_format(($size / 1024 / 1024), 2) . ' MB)', 'WARN');
          array_splice($files, $i, 1);
          $maxfor--;
          $i--;

          $excludedBytes += $size;
          continue;
        }

        if ($abis && Scanner::equalFolder(basename($files[$i]), $abres)) {
          if (!$dirCalc) $progress->log(__("Removing file from backup (due to exclude rules): ", 'backup-backup') . $files[$i], 'WARN');
          array_splice($files, $i, 1);
          $maxfor--;
          $i--;

          $excludedBytes += $size;
          continue;
        }

        if ($acis && property_exists($acres, $files[$i])) {
          if (!$dirCalc) $progress->log(__("Removing file from backup (due to path rules): ", 'backup-backup') . $files[$i], 'WARN');
          array_splice($files, $i, 1);
          $maxfor--;
          $i--;

          $excludedBytes += $size;
          continue;
        }

        // if ($size === 0) {
        //   array_splice($files, $i, 1);
        //   $maxfor--;
        //   $i--;
          
        //   $excludedBytes += $size;
        //   continue;
        // }

        if (strpos($files[$i], 'bmi-pclzip-') !== false || strpos($files[$i], 'backup-migration') !== false) {
          array_splice($files, $i, 1);
          $maxfor--;
          $i--;

          $excludedBytes += $size;
          continue;
        }

        if ($size > ($limitcrl * (1024 * 1024))) {
          if ($first_big === false) $first_big = $i;
          if (!$dirCalc) $progress->log(__("This file is quite big consider to exclude it, if backup fails: ", 'backup-backup') . $files[$i] . ' (' . BMP::humanSize($size) . ')', 'WARN');
        }

        $functionNormal = apply_filters('bmi_function_normal', BMI_FUNCTION_NORMAL);
        $cliEnabled = apply_filters('bmi_cli_enabled', defined('BMI_CLI_ENABLED') ? BMI_CLI_ENABLED : false);
        if (($legacy === false && ($functionNormal === false || ($functionNormal === true && $cliEnabled === true))) && (!defined('BMI_USING_CLI_FUNCTIONALITY') || BMI_USING_CLI_FUNCTIONALITY === false)) {
          $fx = strpos($files[$i], $Hx);
          $fz = strpos($files[$i], $Hz);

          if ($fx !== false) $files[$i] = substr_replace($files[$i], '@1@', $fx, $Hxs);
          else if ($fz !== false) $files[$i] = substr_replace($files[$i], '@2@', $fz, $Hzs);

          $files[$i] .= ',' . $size;
        }
        $total_size += $size;
      }

      if ($legacy === false && (!defined('BMI_USING_CLI_FUNCTIONALITY') || BMI_USING_CLI_FUNCTIONALITY === false)) {
        $list_file = BMI_TMP . DIRECTORY_SEPARATOR . 'files_latest.list';
        if (file_exists($list_file)) @unlink($list_file);
        $files_list = fopen($list_file, 'a');
        if ($first_big === false) fwrite($files_list, sizeof($files) . "_-1\r\n");
        else fwrite($files_list, sizeof($files) . '_' . $first_big . "\r\n");
        for ($i = 0; $i < sizeof($files); ++$i) {
          fwrite($files_list, $files[$i] . "\r\n");
        }
        fclose($files_list);
        $this->first_big = $first_big;
      }

      $this->total_excluded_size_for_backup = $excludedBytes + $this->ignoredDirectoriesSize;
      $this->total_size_for_backup = $total_size;
      $this->total_size_for_backup_in_mb = ($total_size / 1024 / 1024);

      return $files;
    }

    public function toggleBackupLock($unlock = false) {

      // Require lib
      require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'zipper' . DIRECTORY_SEPARATOR . 'zipping.php';

      // Backup name
      $filename = $this->post['filename'];

      // Init Zipper
      $zipper = new Zipper();

      // Path to Backup
      $path = BMI_BACKUPS . DIRECTORY_SEPARATOR . $filename;
      $path_dir = BMP::fixSlashes(dirname($path));

      // Check if file exists
      if (!file_exists($path)) {
        return ['status' => 'fail'];
      }

      // Check if directory is correct
      if ($path_dir != BMP::fixSlashes(BMI_BACKUPS)) {
        return ['status' => 'fail'];
      }

      // Toggle the lock
      $status = $zipper->lock_zip($path, $unlock);

      // Return the status
      return ['status' => ($status ? 'success' : 'fail')];
    }

    public function getDynamicNames() {
      $data = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:NAMES:IN');
      $fpdata = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:FPATHS:IN');
      $fddata = Dashboard\bmi_get_config('BACKUP:FILES::FILTER:DPATHS:IN');

      for ($i = 0; $i < sizeof($fpdata); ++$i) {
        $fpdata[$i] = BMP::fixSlashes($fpdata[$i]);
      }

      for ($i = 0; $i < sizeof($fddata); ++$i) {
        $fddata[$i] = BMP::fixSlashes($fddata[$i]);
      }

      return [
        'status' => 'success',
        'dynamic-fpaths-names' => $fpdata,
        'dynamic-dpaths-names' => $fddata,
        'data' => $data
      ];
    }

    public function resetConfiguration() {

      if (file_exists(BMI_CONFIG_PATH)) {
        @unlink(BMI_CONFIG_PATH);
      }

      delete_option('bmi_hotfixes');
      delete_option('bmip_to_be_uploaded');
      delete_option('bmi_pro_gd_client_id');
      delete_option('bmi_pro_gd_token');
      delete_option('bmi_pro_cron_domain_done');
      delete_option('BMI::STORAGE::LOCAL::PATH');

      // update_option('BMI_LOGS_SHARING_IS_ALLOWED', 'unknown');

      return ['status' => 'success'];

    }

    public function getSiteData() {
      require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'check' . DIRECTORY_SEPARATOR . 'system_info.php';
      $bmi = new SI();
      $bmi = $bmi->to_array();

      return ['status' => 'success', 'data' => $bmi];
    }

    public function calculateCron() {
      require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'cron' . DIRECTORY_SEPARATOR . 'handler.php';

      $minutes = [];
      $keeps = [];
      $days = [];
      $weeks = [];
      $hours = [];

      for ($i = 1; $i <= 28; ++$i) {
        $days[] = substr('0' . $i, -2);
      }
      for ($i = 1; $i <= 7; ++$i) {
        $weeks[] = $i . '';
      }
      for ($i = 0; $i <= 23; ++$i) {
        $hours[] = substr('0' . $i, -2);
      }
      for ($i = 0; $i <= 55; $i += 5) {
        $minutes[] = substr('0' . $i, -2);
      }
      for ($i = 1; $i <= 20; ++$i) {
        $keeps[] = $i . '';
      }

      $errors = 0;
      if (in_array($this->post['type'], ['month', 'week', 'day'])) {
        if (!Dashboard\bmi_set_config('CRON:TYPE', $this->post['type'])) {
          $errors++;
        }
      }
      if (in_array($this->post['day'], $days)) {
        if (!Dashboard\bmi_set_config('CRON:DAY', $this->post['day'])) {
          $errors++;
        }
      }
      if (in_array($this->post['week'], $weeks)) {
        if (!Dashboard\bmi_set_config('CRON:WEEK', $this->post['week'])) {
          $errors++;
        }
      }
      if (in_array($this->post['hour'], $hours)) {
        if (!Dashboard\bmi_set_config('CRON:HOUR', $this->post['hour'])) {
          $errors++;
        }
      }
      if (in_array($this->post['minute'], $minutes)) {
        if (!Dashboard\bmi_set_config('CRON:MINUTE', $this->post['minute'])) {
          $errors++;
        }
      }
      if (in_array($this->post['keep'], $keeps)) {
        if (!Dashboard\bmi_set_config('CRON:KEEP', $this->post['keep'])) {
          $errors++;
        }
      }

      if ($this->post['enabled'] === 'true') {
        $this->post['enabled'] = true;
      } else {
        $this->post['enabled'] = false;
      }

      if (!Dashboard\bmi_set_config('CRON:ENABLED', $this->post['enabled'])) {
        $errors++;
      }

      if ($errors === 0) {
        $time = Crons::calculate_date([
          'type' => $this->post['type'],
          'week' => $this->post['week'],
          'day' => $this->post['day'],
          'hour' => $this->post['hour'],
          'minute' => $this->post['minute']
        ], time());

        $file = BMI_TMP . DIRECTORY_SEPARATOR . '.plan';
        if (file_exists($file)) {
          $earlier = intval(file_get_contents($file));
        } else {
          $earlier = 0;
        }

        if (!wp_next_scheduled('bmi_do_backup_right_now') || $earlier === 0 || (abs($time - $earlier) >= 15)) {
          wp_clear_scheduled_hook('bmi_do_backup_right_now');
          if ($this->post['enabled'] === true) {
            wp_schedule_single_event($time, 'bmi_do_backup_right_now');
            file_put_contents($file, $time);
          }
        }

        return [
          'status' => 'success',
          'data' => date('Y-m-d H:i:s', $time),
          'currdata' => date('Y-m-d H:i:s')
        ];
      } else {
        return ['status' => 'error'];
      }
    }

    public function dismissErrorNotice() {
      $optionId = isset($this->post['option_id']) ? $this->post['option_id'] : '';
      if (in_array($optionId, ['backupbliss-issues', 'backupbliss-dismiss-upload-issue']))
      {
        require_once BMI_INCLUDES . '/external/backupbliss.php';
        $backupbliss = new BackupBliss();
      }

      switch ($optionId) {
        case 'email-issues': 
          delete_option('bmi_display_email_issues');
          break;
        case 'before-update-issues':
          delete_option('bmi_display_before_update_backup_issues');
          break;
        case 'aws-issues':
          update_option('bmip_aws_dismiss_issue', true);
          break;
        case 'wasabi-issues':
          update_option('bmip_wasabi_dismiss_issue', true);
          break;
        case 'sftp-issues':
          update_option('bmip_sftp_dismiss_issue', true);
          break;
        case 'gdrive-issues':
          delete_transient('bmip_gd_issue');
          break;
        case 'backupbliss-issues':
          $backupbliss->removeNotice("invalid_key");
          $backupbliss->removeNotice("invalid_permission");
          if ($backupbliss->getNotice("storage_warn"))
            $backupbliss->hideNotice("storage_warn", 60 * 60);
          if ($backupbliss->getNotice("upload_issue"))
            $backupbliss->hideNotice("upload_issue", 60); //Hide only for a minute
          break;
        case 'backupbliss-dismiss-upload-issue':
          $backupbliss->hideFailureWarnNotice(14 * 24 * 60 * 60); //14 days
          break;
        case 'security-plugin-warning':
          update_option('bmi_security_warning_dismiss', true);
        default:
            break;
      }
    }

    // recursive removal
    private function rrmdir($dir) {

      if (is_dir($dir)) {

        $objects = scandir($dir);
        foreach ($objects as $object) {

          if ($object != "." && $object != "..") {

            if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . DIRECTORY_SEPARATOR . $object)) {

              $this->rrmdir($dir . DIRECTORY_SEPARATOR . $object);

            } else {

              @unlink($dir . DIRECTORY_SEPARATOR . $object);

            }

          }

        }

        @rmdir($dir);

      } else {

        if (file_exists($dir) && is_file($dir)) {

          @unlink($dir);

        }

      }

    }

    public function forceBackupToStop() {

      $filesToBeRemoved = [];

      $tmp_dir = BMI_ROOT_DIR . DIRECTORY_SEPARATOR . 'tmp';
      if (!is_dir($tmp_dir)) @mkdir($tmp_dir, 0755, true);

      foreach (scandir($tmp_dir) as $filename) {

        if (in_array($filename, ['.', '..'])) continue;
        $path = BMI_ROOT_DIR . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $filename;
        $filesToBeRemoved[] = $path;

      }

      $allowedFiles = ['wp-config.php', '.htaccess', '.litespeed', '.default.json', 'driveKeys.php', 'dropboxKeys.php', '.autologin.php', '.migrationFinished', 'onedriveKeys.php', 'awsKeys.php', 'wasabiKeys.php', 'backupblissKeys.php', 'sftpKeys.php'];
      foreach (glob(BMI_TMP . DIRECTORY_SEPARATOR . '.*') as $filename) {

        $basename = basename($filename);

        if (in_array($basename, ['.', '..'])) continue;
        if (is_file($filename) && !in_array($basename, $allowedFiles)) {
          $filesToBeRemoved[] = $filename;
        }

      }

      foreach (glob(BMI_TMP . DIRECTORY_SEPARATOR . 'BMI-*', GLOB_ONLYDIR) as $filename) {

        $basename = basename($filename);

        if (in_array($basename, ['.', '..'])) continue;
        if (is_dir($filename) && !in_array($filename, $allowedFiles)) {
          $filesToBeRemoved[] = $filename;
        }

      }

      foreach (glob(BMI_TMP . DIRECTORY_SEPARATOR . 'bg-BMI-*', GLOB_ONLYDIR) as $filename) {

        $basename = basename($filename);

        if (in_array($basename, ['.', '..'])) continue;
        if (is_dir($filename) && !in_array($filename, $allowedFiles)) {
          $filesToBeRemoved[] = $filename;
        }

      }

      $filesToBeRemoved[] = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.backup_cli_lock';
      $filesToBeRemoved[] = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.backup_cli_lock_ended';
      $filesToBeRemoved[] = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.backup_cli_lock_end';
      $filesToBeRemoved[] = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.last_triggered';
      $filesToBeRemoved[] = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.running';
      $filesToBeRemoved[] = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.space_check';
      $filesToBeRemoved[] = BMI_TMP . DIRECTORY_SEPARATOR . 'db_tables';
      $filesToBeRemoved[] = BMI_TMP . DIRECTORY_SEPARATOR . 'bmi_backup_manifest.json';
      $filesToBeRemoved[] = BMI_TMP . DIRECTORY_SEPARATOR . 'files_latest.list';
      $filesToBeRemoved[] = BMI_TMP . DIRECTORY_SEPARATOR . 'currentBackupConfig.php';

      if (is_array($filesToBeRemoved) || is_object($filesToBeRemoved)) {
        foreach ((array) $filesToBeRemoved as $file) {
          $this->rrmdir($file);
        }
      }

      return ['status' => 'success'];

    }

    public function forceRestoreToStop() {

      $filesToBeRemoved = [];

      $themedir = get_theme_root();
      $tempTheme = $themedir . DIRECTORY_SEPARATOR . 'backup_migration_restoration_in_progress';
      $filesToBeRemoved[] = $tempTheme;

      $tmpDirectory = BMI_ROOT_DIR . DIRECTORY_SEPARATOR . 'tmp';
      if (!is_dir($tmpDirectory)) @mkdir($tmpDirectory, 0755, true);

      foreach (scandir($tmpDirectory) as $filename) {

        if (in_array($filename, ['.', '..'])) continue;
        $path = BMI_ROOT_DIR . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $filename;
        $filesToBeRemoved[] = $path;

      }

      foreach (glob(BMI_TMP . DIRECTORY_SEPARATOR . 'backup-migration_??????????') as $filename) {

        $basename = basename($filename);

        if (is_dir($filename) && !in_array($basename, ['.', '..'])) {
          $filesToBeRemoved[] = $filename;
        }

      }

      $allowedFiles = ['wp-config.php', '.htaccess', '.litespeed', '.default.json', 'driveKeys.php', 'dropboxKeys.php', '.autologin.php', '.migrationFinished', 'onedriveKeys.php','awsKeys.php', 'wasabiKeys.php', 'backupblissKeys.php', 'sftpKeys.php'];
      foreach (glob(BMI_TMP . DIRECTORY_SEPARATOR . '.*') as $filename) {

        $basename = basename($filename);

        if (in_array($basename, ['.', '..'])) continue;
        if (is_file($filename) && !in_array($basename, $allowedFiles)) {
          $filesToBeRemoved[] = $filename;
        }

      }

      foreach (glob(BMI_TMP . DIRECTORY_SEPARATOR . 'restore_scan_*') as $filename) {

        $basename = basename($filename);

        if (in_array($basename, ['.', '..'])) continue;
        if (is_file($filename) && !in_array($basename, $allowedFiles)) {
          $filesToBeRemoved[] = $filename;
        }

      }

      foreach (glob(untrailingslashit(ABSPATH) . DIRECTORY_SEPARATOR . 'wp-config.??????????.php') as $filename) {

        $basename = basename($filename);

        if (in_array($basename, ['.', '..'])) continue;
        if (is_file($filename) && !in_array($filename, $allowedFiles)) {
          $filesToBeRemoved[] = $filename;
        }

      }

      $filesToBeRemoved[] = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.migration_lock';
      $filesToBeRemoved[] = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.migration_lock_cli';
      $filesToBeRemoved[] = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.migration_lock_cli_end';
      $filesToBeRemoved[] = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.migration_lock_ended';
      $filesToBeRemoved[] = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.cli_download_last';
      $filesToBeRemoved[] = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.running';
      $filesToBeRemoved[] = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.space_check';
      $filesToBeRemoved[] = BMI_TMP . DIRECTORY_SEPARATOR . '.restore_secret';
      $filesToBeRemoved[] = BMI_TMP . DIRECTORY_SEPARATOR . '.table_map';

      if (is_array($filesToBeRemoved) || is_object($filesToBeRemoved)) {
        foreach ((array) $filesToBeRemoved as $file) {
          $this->rrmdir($file);
        }
      }

      return ['status' => 'success'];

    }

    public function sendTroubleshootingDetails($send_type = 'manual', $triggeredBy = false, $blocking = true) {

      global $table_prefix;

      require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'check' . DIRECTORY_SEPARATOR . 'system_info.php';
      $bmiSiteData = new SI();
      $bmiSiteData = $bmiSiteData->to_array();
      $bmiSiteData['database_size'] = $this->getDatabaseSize();
      $bmiSiteData['database_size_mb'] = BMP::humanSize($bmiSiteData['database_size']);
      $bmiSiteData['xhria'] = get_option('z__bmi_xhria', 'none');
      $bmiSiteData['current_table_prefix'] = $table_prefix;

      $wpconfigPath = ABSPATH . DIRECTORY_SEPARATOR . 'wp-config.php';
      if (file_exists($wpconfigPath)) {
        $bmiSiteData['is_wp_config_writable'] = is_writable($wpconfigPath) ? "yes" : "no";
      } else {
        $bmiSiteData['is_wp_config_writable'] = "file_does_not_exist?";
      }

      $latestBackupLogs = 'does_not_exist';
      $latestBackupProgress = 'does_not_exist';
      $latestRestorationLogs = 'does_not_exist';
      $latestRestorationProgress = 'does_not_exist';
      $latestStagingLogs = 'does_not_exist';
      $latestStagingProgress = 'does_not_exist';
      $currentPluginConfig = 'does_not_exist';
      $pluginGlobalLogs = 'does_not_exist';
      $backgroundErrors = 'does_not_exist';

      if (file_exists(BMI_BACKUPS . '/latest.log')) {
        $latestBackupLogs = file_get_contents(BMI_BACKUPS . '/latest.log');
      }

      if (file_exists(BMI_BACKUPS . '/latest_progress.log')) {
        $latestBackupProgress = file_get_contents(BMI_BACKUPS . '/latest_progress.log');
      }

      if (file_exists(BMI_BACKUPS . '/latest_migration.log')) {
        $latestRestorationLogs = file_get_contents(BMI_BACKUPS . '/latest_migration.log');
      }

      if (file_exists(BMI_BACKUPS . '/latest_migration_progress.log')) {
        $latestRestorationProgress = file_get_contents(BMI_BACKUPS . '/latest_migration_progress.log');
      }

      if (file_exists(BMI_STAGING . '/latest_staging.log')) {
        $latestStagingLogs = file_get_contents(BMI_STAGING . '/latest_staging.log');
      }

      if (file_exists(BMI_STAGING . '/latest_staging_progress.log')) {
        $latestStagingProgress = file_get_contents(BMI_STAGING . '/latest_staging_progress.log');
      }

      if (file_exists(BMI_CONFIG_PATH)) {
        $currentPluginConfig = substr(file_get_contents(BMI_CONFIG_PATH), 8);
      }

      $completeLogsPath = BMI_CONFIG_DIR . DIRECTORY_SEPARATOR . 'complete_logs.log';
      if (file_exists($completeLogsPath)) {
        $fileSize = filesize($completeLogsPath);
        if ($fileSize <= 65535) {
          $pluginGlobalLogs = file_get_contents($completeLogsPath);
        } else {
          $fp = fopen($completeLogsPath, 'rb');
          if ($fp) {
              $seekPos = max(0, $fileSize - 65509 ); // Read last 64KB
              fseek($fp, $seekPos, SEEK_SET);
              $lastBytes = fread($fp, 65509);
              fclose($fp);
            
              $pluginGlobalLogs = 'file_too_large, last 64KB:' . "\n" . $lastBytes;

              file_put_contents($completeLogsPath, $lastBytes);
          } else {
              $pluginGlobalLogs = 'could_not_open_file';
          }
        }
      }

      $backgroundLogsPath = BMI_CONFIG_DIR . DIRECTORY_SEPARATOR . 'background-errors.log';
      if (file_exists($backgroundLogsPath)) {
        if ((filesize($backgroundLogsPath) / 1024 / 1024) <= 4) {
          $backgroundErrors = file_get_contents($backgroundLogsPath);
        } else {
          @unlink($backgroundLogsPath);
          @touch($backgroundLogsPath);
          $backgroundErrors = 'file_too_large';
        }
      }

      $ifCLI = false;
      if (defined('BMI_USING_CLI_FUNCTIONALITY') && BMI_USING_CLI_FUNCTIONALITY === true) {
        $ifCLI = true;
      }

      $logsSourceFrontEnd = 'manual';
      if ($triggeredBy != false) {
        $logsSourceFrontEnd = $triggeredBy;
      }
      if (isset($this->post['source']) && in_array($this->post['source'], ['backup', 'migration', 'staging'])) {
        $logsSourceFrontEnd = $this->post['source'];
      }

      $latestBackupLogs = preg_replace('/\:\ ((.*)\.zip)/', ': *****.zip', $latestBackupLogs);
      $latestRestorationLogs = preg_replace('/backup\-id\=(.*)\.zip/', 'backup-id=[***redacted***].zip', $latestRestorationLogs);
      $latestStagingLogs = preg_replace('/\:\ ((.*)\.zip)/', ': *****.zip', $latestStagingLogs);

      $currentPluginConfig = json_decode($currentPluginConfig);
      unset($currentPluginConfig->{"OTHER:EMAIL"});
      $currentPluginConfig = json_encode($currentPluginConfig);

      $url = 'https://' . BMI_API_BACKUPBLISS_PUSH . '/v1' . '/push';
      $data = array(
        'method' => 'POST',
        'timeout' => 15,
        'blocking' => $blocking,
        'sslverify' => false,
        'send_type' => $send_type,
        'body' => array(
          'admin_url' => admin_url(),
          'home_url' => home_url(),
          'site_url' => get_site_url(),
          'is_multisite' => is_multisite() ? "yes" : "no",
          'is_abspath_writable' => is_writable(ABSPATH) ? "yes" : "no",
          'site_information' => $bmiSiteData,
          'latest_backup_logs' => $latestBackupLogs,
          'latest_backup_progress' => $latestBackupProgress,
          'latest_restoration_logs' => $latestRestorationLogs,
          'latest_restoration_progress' => $latestRestorationProgress,
          'latest_staging_logs' => $latestStagingLogs,
          'latest_staging_progress' => $latestStagingProgress,
          'current_plugin_config' => $currentPluginConfig,
          'plugin_global_logs' => $pluginGlobalLogs,
          'background_errors' => $backgroundErrors,
          'triggered_by' => $logsSourceFrontEnd,
          'is_defined' => defined('BMI_BACKUP_PRO') ? 'yes' : 'no',
          'is_cli' => $ifCLI
        )
      );

      $disabled_functions = explode(',', ini_get('disable_functions'));
      $vA = !in_array('curl_exec', $disabled_functions);
      $vB = !in_array('curl_init', $disabled_functions);
      $vC = !in_array('http_build_query', $disabled_functions);
      $vD = !in_array('stream_context_create', $disabled_functions);
      $vE = !in_array('file_get_contents', $disabled_functions);
      $vF = false;
      $response = false;

      if (function_exists('curl_version') && function_exists('curl_exec') && function_exists('curl_init') && $vA && $vB) {

        $response = wp_remote_post($url, $data);

      } else {

        if (ini_get('allow_url_fopen') == true && $vC && $vD && $vE) {

          $vF = true;
          $postdata = http_build_query($data['body']);

          $opts = [
            'ssl' => [ 'verify_peer_name' => false, 'verify_peer' => false ],
            'http' => [
              'method'  => 'POST',
              'header'  => 'Content-type: application/x-www-form-urlencoded',
              'content' => $postdata
            ]
          ];

          $context  = stream_context_create($opts);
          $result = file_get_contents($url, false, $context);

          $response = [ 'body' => $result ];

        }

      }

      if ($response === false || is_wp_error($response)) {
        $error_message = $response->get_error_message();
        Logger::error($error_message, 'backup-backup');
        return ['status' => 'fail'];
      } else {
        try {
          $body = json_decode($response['body']);
          if (isset($body->code)) {
            return ['status' => 'success', 'code' => sanitize_text_field($body->code)];
          } else {
            return ['status' => 'fail'];
          }
        } catch (\Exception $e) {
          Logger::error(print_r($e, true), 'backup-backup');
          return ['status' => 'fail'];
        } catch (\Throwable $t) {
          Logger::error(print_r($t, true), 'backup-backup');
          return ['status' => 'fail'];
        }
      }

    }

    public function actionsAfterProcess($success = false, $triggeredBy = 'backup') {

      $afterMigrationLock = BMI_TMP . DIRECTORY_SEPARATOR . '.migrationFinished';
      if ($success) {

        file_put_contents($afterMigrationLock, '');
        Logger::log("Process (" . $triggeredBy . ") finished successfully via ajax.php");

      } else {

        Logger::log("Process (" . $triggeredBy . ") finished with errors via ajax.php");
        if (file_exists($afterMigrationLock)) @unlink($afterMigrationLock);

      }

      if (file_exists(BMI_TMP . DIRECTORY_SEPARATOR . 'restore_parts.json')) @unlink(BMI_TMP . DIRECTORY_SEPARATOR . 'restore_parts.json');

      
      if (has_action('bmi_premium_after_process') || (defined('BACKUP_TRIGGERED_BY_URL') && BACKUP_TRIGGERED_BY_URL === true)){
        do_action('bmi_premium_after_process', $success, $triggeredBy, defined('BACKUP_TRIGGERED_BY_URL') && BACKUP_TRIGGERED_BY_URL === true);
      }

      BMP::handle_after_cron();

      return null;

      // REMOVED CODE:
      // $canShare = BMP::canShareLogsOrShouldAsk();
      // if ($canShare === 'allowed') {
      //
      //   $send_type = 'error';
      //   if ($success) $send_type = 'success';
      //   $this->sendTroubleshootingDetails($send_type, $triggeredBy, false);
      //
      // }

    }

    public function logSharing() {

      $type = $this->post['question'];

      if ($type == 'set_yes') {

        // $isOk = Dashboard\bmi_set_config('LOGS::SHARING', 'yes');
        // update_option('BMI_LOGS_SHARING_IS_ALLOWED', 'yes');
        return ['status' => 'success'];

      } else if ($type == 'set_no') {

        // $isOk = Dashboard\bmi_set_config('LOGS::SHARING', 'no');
        // update_option('BMI_LOGS_SHARING_IS_ALLOWED', 'no');
        return ['status' => 'success'];

      } else if ($type == 'is_allowed') {

        // $canShare = BMP::canShareLogsOrShouldAsk();
        // return ['status' => 'success', 'result' => $canShare];
        return ['status' => 'success', 'result' => 'not-allowed'];

      } else {

        return ['status' => 'fail'];

      }

    }

    public function getLatestBackupFile() {

      $dir = BMI_BACKUPS;
      $backupdir = array_diff(scandir($dir), ['..', '.']);
      $backups = [];
      foreach ($backupdir as $index => $name) {

        $ext = pathinfo($dir . DIRECTORY_SEPARATOR . $name, PATHINFO_EXTENSION);

        if (in_array($ext, ['zip', 'tar', 'gz'])) {
          $backups[] = [
            'cdate' => filemtime($dir . DIRECTORY_SEPARATOR . $name),
            'name' => $name
          ];
        }

      }

      usort($backups, function ($a, $b) {
        if (intval($a['cdate']) < intval($b['cdate'])) return 1;
        else return -1;
      });

      $backups = array_values($backups);

      if (sizeof($backups) > 0) {
        return $backups[0]['name'];
      } else {
        return '---';
      }

    }

    /**
     * isStagingSiteCreationOngoing - Checks if the process is ongoing or not
     *
     * @return {bool} true if the process is running false if its not
     */
    public function isStagingSiteCreationOngoing() {

      $staging_lock = BMI_STAGING . '/.staging_lock';
      if (file_exists($staging_lock) && (time() - filemtime($staging_lock)) <= 15) {
        return true;
      } else {
        return false;
      }

    }

    /**
     * checkStagingLocalName - Verifies name of staging site and checks if it's not currently running
     * Can be called for verification pre start or during start on initial request
     *
     * @param  {string} $name = false name of staging site if called without ajax
     * @return {array} with status/fail/progress data
     */
    public function checkStagingLocalName($name = false) {

      if ($name == false && isset($this->post['name'])) {
        $name = $this->post['name'];
      }

      $ongoing = __('Staging site creation is already ongoing, please wait and try again.', 'backup-backup');
      $empty = __('You have to provide some staging site name before process.', 'backup-backup');
      $toolong = __('Staging site name cannot be longer than 24 characters.', 'backup-backup');
      $invalid = __('Provided name contains prohibited characters.', 'backup-backup');
      $blacklisted = __('This name is not allowed to be used, please pick different one.', 'backup-backup');
      $exist = __('Seems like directory or staging site with that name already exist, pick different one.', 'backup-backup');
      $dashes = __('Name cannot start or end with dash or underscore.', 'backup-backup');

      if ($this->isStagingSiteCreationOngoing()) {
        return ['status' => 'fail', 'message' => $ongoing];
      }

      if (strlen($name) <= 0) {
        return ['status' => 'fail', 'message' => $empty];
      }

      if (!preg_match('/^[a-zA-Z0-9-_]+$/', $name)) {
        return ['status' => 'fail', 'message' => $invalid];
      }

      if (strlen($name) >= 24) {
        return ['status' => 'fail', 'message' => $toolong];
      }

      if (in_array($name[0], ['_', '-']) || in_array($name[strlen($name) - 1], ['_', '-'])) {
        return ['status' => 'fail', 'message' => $dashes];
      }

      $bannedNames = [
        'wp-content',
        'wp-admin',
        'wp-includes',
        'content',
        'admin',
        'includes',
        'tmp',
        '.well-known',
        'download',
        'downloads',
        'google',
        'temporary'
      ];

      if (strpos($name, '.') !== false) {
        return ['status' => 'fail', 'message' => $blacklisted];
      }

      if (in_array($name, $bannedNames)) {
        return ['status' => 'fail', 'message' => $blacklisted];
      }

      $path = trailingslashit(ABSPATH) . $name;
      if (file_exists($path) || is_dir($path)) {
        return ['status' => 'fail', 'message' => $exist];
      }

      return ['status' => 'success'];

    }

    /**
     * startLocalStagingCreation - Initials creation of staging site process
     *
     * @return {array} with status/fail/progress data
     */
    public function startLocalStagingCreation() {

      // Verification of state
      $name = $this->post['name'];
      $verification = $this->checkStagingLocalName($name);
      $staging_lock = BMI_STAGING . '/.staging_lock';

      // Fail in case of wrong data or state
      if (isset($verification['status']) && $verification['status'] != 'success') {
        return $verification;
      }

      // Update lock file to prevent double processes
      touch($staging_lock);

      // Include local staging site controller
      require_once BMI_INCLUDES . '/staging/local.php';
      $staging = new StagingLocal($name, true);

      // Append the return if staging process requires more batches
      if ($staging->continue == true) return $staging->continuationData;

      return [ 'status' => 'continue', 'data' => [ 'name' => $name ] ];

    }

    /**
     * stagingSitesGetList - Returns staging sites list
     *
     * @return {array} with staging sites data
     */
    public function stagingSitesGetList() {

      // Include local staging site controller
      require_once BMI_INCLUDES . '/staging/controller.php';
      $staging = new Staging('..ajax..');
      $sites = $staging->getStagingSites();

      return [ 'status' => 'success', 'sites' => $sites ];

    }

    /**
     * stagingRename - Renames display name
     *
     * @return {array} status
     */
    public function stagingRename() {

      $name = $this->post['name'];
      $newName = $this->post['new'];

      // Include local staging site controller
      require_once BMI_INCLUDES . '/staging/controller.php';
      $staging = new Staging('..ajax..');
      return $staging->rename($name, $newName);

    }

    /**
     * stagingPrepareLogin - Prepares login script
     *
     * @return {array} login credentials
     */
    public function stagingPrepareLogin() {

      $name = $this->post['name'];

      // Include local staging site controller
      require_once BMI_INCLUDES . '/staging/controller.php';
      $staging = new Staging('..ajax..');
      return $staging->prepareLogin($name);

    }

    /**
     * Handles secure backup via browser method
     */
    public function backupBrowserMethodHandler() {

       try {

        // Load bypasser
        require_once BMI_INCLUDES . '/backup-process.php';
        $request = new Bypasser(false, BMI_CONFIG_DIR, trailingslashit(WP_CONTENT_DIR), BMI_BACKUPS, trailingslashit(ABSPATH), plugin_dir_path(BMI_ROOT_FILE));

        // Handle request
        $request->handle_batch();
        $request->shutdown();
        return;

      } catch (\Exception $e) {

        error_log('There was an error with Backup Migration plugin: ' . $e->getMessage());
        Logger::error(__('Error handler: ', 'backup-backup') . 'ajax#01' . '|' . $e->getMessage());
        error_log(strval($e));

      } catch (\Throwable $t) {

        error_log('There was an error with Backup Migration plugin: ' . $t->getMessage());
        Logger::error(__('Error handler: ', 'backup-backup') . 'ajax#01' . '|' . $t->getMessage());
        error_log(strval($t));

      }

      return [ 'status' => 'error' ];

    }

    /**
     * Handles ajax error on browser side, keep alive timeout etc.
     *
     * @return array static success
     */
    public function frontEndAjaxError() {

      if ($this->post['call'] == 'create-backup') {
        require_once BMI_INCLUDES . '/progress/zip.php';
        $logger = new Progress('', 0, 0, false, false);
      } else if (in_array($this->post['call'], ['restore-backup', 'download-backup', 'continue_restore_process'])) {
        require_once BMI_INCLUDES . '/progress/migration.php';
        $logger = new MigrationProgress(true);
      } else if (in_array($this->post['call'], ['staging-start-local-creation', 'staging-local-creation-process', 'staging-tastewp-creation-process'])) {
        require_once BMI_INCLUDES . '/progress/staging.php';
        $logger = new StagingProgress(true);
      }

      if (isset($this->post['error'])) {

        Logger::error('Front End Ajax Error START');
        if (isset($logger)) $logger->log('Front End Ajax Error START', 'verbose');

        if (is_array($this->post['error'])) {

          $errors = $this->post['error'];
          foreach ($errors as $k => $val) {
            $error = sanitize_text_field(print_r($val, true));
            Logger::error($k . ' = ' . $error);
            if (isset($logger)) $logger->log($k . ' = ' . $error, 'verbose');
          }

        } else {

          $theError = sanitize_text_field(print_r($this->post->error, true));
          Logger::error('Front End Ajax Error: ' . $theError);
          if (isset($logger)) $logger->log($theError, 'verbose');

        }

        Logger::error('Front End Ajax Error END');
        if (isset($logger)) $logger->log('Front End Ajax Error END', 'verbose');

      } else {

        Logger::error('Front End Ajax Error was called, but no error included.');

      }

      if (isset($logger)) {
        $logger->log(__('Browser-side error detected, the process will try to restart with alternative methods, otherwise it will throw error window.', 'backup-backup'), 'error');
        $logger->log('Browser-side error detected, the process will try to restart with alternative methods, otherwise it will throw error window.', 'verbose');
      }

      return [ 'status' => 'success' ];

    }

    /**
     * stagingDelete - Removes the staging site
     *
     * @return {array} status
     */
    public function stagingDelete() {

      $name = $this->post['name'];

      // Include local staging site controller
      require_once BMI_INCLUDES . '/staging/controller.php';
      $staging = new Staging('..ajax..');
      return $staging->delete($name);

    }

    /**
     * localStagingCreationProcess - Method that can continue batching of Staging process
     *
     * @return {array} data that should be send back to this function as POST
     */
    public function localStagingCreationProcess() {

      // Get $name and declare lock file
      $name = $this->post['name'];
      $staging_lock = BMI_STAGING . '/.staging_lock';

      // Update lock file to prevent double processes
      touch($staging_lock);

      // Include local staging site controller
      require_once BMI_INCLUDES . '/staging/local.php';
      $staging = new StagingLocal($name);

      // Process handler
      if (isset($this->post['delete'])) {
        $staging->requestDelete();
      } else $staging->continueProcess();

      // Append the return if staging process requires more batches
      if ($staging->continue == true) return $staging->continuationData;

      // Send success if nothing went wrong which finishes the process
      if (file_exists($staging_lock)) @unlink($staging_lock);
      return ['status' => 'error'];

    }

    /**
     * tastewpStagingCreation - Initializes and declares staging site will
     *
     * @return {array} batching status
     */
    public function tastewpStagingCreation() {

      // Get $name and declare lock file
      $name = $this->post['name'];
      $backupName = isset($this->post['backupName']) ? $this->post['backupName'] : false;
      $initialize = isset($this->post['initialize']) ? $this->post['initialize'] : false;
      $staging_lock = BMI_STAGING . '/.staging_lock';

      // Fix var type
      if ($initialize === true || $initialize == 'true') $initialize = true;
      else $initialize = false;

      // Update lock file to prevent double processes
      touch($staging_lock);

      // Include TasteWP staging site controller
      require_once BMI_INCLUDES . '/staging/tastewp.php';

      // Process handler
      if (isset($this->post['delete'])) {
        $delete = true;
      } else $delete = false;

      // Make first handshake with TasteWP
      $staging = new StagingTasteWP($name, $initialize, $backupName, $delete);

      // Append the return if staging process requires more batches
      if ($staging->continue == true) return $staging->continuationData;

      // Send success if nothing went wrong which finishes the process
      if (file_exists($staging_lock)) @unlink($staging_lock);
      return ['status' => 'error'];

    }

    public function debugging() {

      require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'scanner' . DIRECTORY_SEPARATOR . 'backups.php';
      $backups = new Backups();
      $availableBackups = $backups->getAvailableBackups();
      $list = $availableBackups['local'];

      // $cron_list = [];
      // $cron_dates = [];
      // foreach ($list as $key => $value) {
      //   if ($list[$key][6] == true) {
      //     if ($list[$key][5] == 'unlocked') {
      //       $cron_list[$list[$key][1]] = $list[$key][0];
      //       $cron_dates[] = $list[$key][1];
      //     }
      //   }
      // }

      // usort($cron_dates, function ($a, $b) {
      //   return (strtotime($a) < strtotime($b)) ? -1 : 1;
      // });

      // $cron_dates = array_slice($cron_dates, 0, -(intval(Dashboard\bmi_get_config('CRON:KEEP'))));
      // foreach ($cron_dates as $key => $value) {
      //   $name = $cron_list[$cron_dates[$key]];
      //   $name = explode('#%&', $name)[1];
      //   Logger::log(__("Removing backup due to keep rules: ", 'backup-backup') . $name);
      //   @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . $name);
      // }

      if (isset($availableBackups['external']['gdrive'])) {
        $sortedMD5s = [];
        $gdrive = $availableBackups['external']['gdrive'];
        foreach ($gdrive as $md5 => $data) {
          if ($gdrive[$md5][6] == true && $gdrive[$md5][5] == 'unlocked') {
            $sortedMD5s[] = [$gdrive[$md5][1], $md5];
          }
        }

        usort($sortedMD5s, function ($a, $b) {
          return (strtotime($a[0]) < strtotime($b[0])) ? -1 : 1;
        });

        $gdrive_md5s = array_slice($sortedMD5s, 0, -(intval(Dashboard\bmi_get_config('CRON:KEEP'))));
        foreach ($sortedMD5s as $index => $data) {
          $md5 = $data[1];

        }
      }

      return ['availableBackups' => $availableBackups, '$gdrive' => $sortedMD5s];

    }

    public function checkCompatibility() {

      $for = isset($this->post['for']) ? $this->post['for'] : 'backup';

      require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'check' . DIRECTORY_SEPARATOR . 'compatibility.php';
      $compatibility = new Compatibility($for);
      $errors = $compatibility->check();
      return ['status' => 'success', 'data' => $errors, 'mainReasonFound' => $compatibility->mainReasonFound()];
    }

    public function clickedOnPluginReview() {
      update_option('bmi_review_clicked', time());
    }


    public function checkDiskSpace(){
      $file = BMI_BACKUPS . '/' . '.space_check';

      $backupSize = BMP::getRecentSize() * 1.4;

      try {
          $size = $backupSize;
          $fh = fopen($file, 'w');
          while($size > 0){
              $chunk = 1024;
              fputs($fh, str_pad('', min($chunk, $size)));
              $size -= $chunk;
          }
          fclose($fh);

          $fs = filesize($file);
          @unlink($file);

          return ['status' => 'enough-space'];


      } catch (\Exception $e) {
          if (file_exists($file)){
              $fileSize  = filesize($file);
              unlink($file);

              return ['status' => 'not-enough-space', 'data' => ['available' => BMP::humanSize(intval($fileSize)), 'required' => BMP::humanSize(intval($backupSize))]];
          }

      } catch (\Throwable $e) {
          if (file_exists($file)){
              $fileSize = filesize($file);
              unlink($file);
              return ['status' => 'not-enough-space', 'data' => ['available' => BMP::humanSize(intval($fileSize)), 'required' => BMP::humanSize(intval($backupSize))]];
          } 

      }    
   }

  public function cleanUpAfterError() {
    $runningFile = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.running';
    $spaceCheckFile = BMI_BACKUPS . DIRECTORY_SEPARATOR . '.space_check';

    if (file_exists($runningFile)){
      $backupName = file_get_contents($runningFile);
      if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupName)){
        $partialBackup = glob(BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupName . '.??????');
        if (is_array($partialBackup) && !empty($partialBackup)){
          foreach ($partialBackup as $file){
            @unlink($file);
          }
        }
        @unlink(BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupName);
      }
      @unlink($runningFile);
    }

    if (file_exists($spaceCheckFile)){
      @unlink($spaceCheckFile);
    }
  }

  function manuallyEnqueueUpload(){
    $type = isset($this->post['type']) ? $this->post['type'] : '';
    $md5 = isset($this->post['md5']) ? $this->post['md5'] : '';
    if (empty($type)) {
      return ['status' => 'error', 'msg' => __('Missing backup type.', 'backup-backup')];
    }

    $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);

    if (!isset($uploadedBackupStatus[$md5]) || !isset($uploadedBackupStatus[$md5][$type])) {
      Logger::error("Failed to enqueue backup for upload. Details: " . print_r([$md5, $type, $uploadedBackupStatus], true));
      return [
        'status' => 'error',
        'msg' => __('Something went wrong', 'backup-backup')
      ];
    }

    unset($uploadedBackupStatus[$md5][$type]);
    update_option('bmi_uploaded_backups_status', $uploadedBackupStatus);

    return ['status' => 'success', 'msg' => __('Backup will be enqueued for upload shortly.', 'backup-backup'), 'data' => ['type' => $type]];
  }

  }
