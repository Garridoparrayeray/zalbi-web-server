<?php

// Namespace
namespace BMI\Plugin;

// Uses
use BMI\Plugin\Backup_Migration_Plugin as BMP;
use BMI\Plugin\BMI_Logger as Logger;
use BMI\Plugin\BMI_Pro_Core;
use BMI\Plugin\BMProAjax as BMProAjax;
use BMI\Plugin\Scanner\BMI_BackupsScanner as Backups;
use BMI\Plugin\External\BMI_External_BackupBliss as BackupBliss;
use BMI\Plugin\External\BMI_External_Dropbox as Dropbox;
use BMI\Plugin\External\BMI_External_GDrive as GDrive;
use BMI\Plugin\External\BMI_External_FTP as FTP;
use BMI\Plugin\External\BMI_External_S3 as S3;
use BMI\Plugin\Dashboard as Dashboard;

// Exit on direct access
if (!defined('ABSPATH')) exit;

/**
 * Ajax Offline (unauthorized) Handler for BMI
 */
class BMI_Ajax_Offline
{

  public $post;
  public $backupbliss;
  public $dropbox;
  public $gdrive;
  public $ftp;
  public $aws;
  public $wasabi;
  public $proajax;


  public $dropboxStatus = false;
  public $gdriveStatus = false;
  public $ftpStatus = false;
  public $awsStatus = false;
  public $wasabiStatus = false;
  
  public function __construct($post)
  {

    // $POST is sanitized by BMI Basic Version
    // Do not call this class anywhere else [!]
    $this->post = $post;

    // Active offline ajax premium side
    if (defined('BMI_PRO_INC')) {
      if (BMI_DEBUG)
        Logger::error("PREMIUM CHECK");

      require_once BMI_PRO_INC . 'ajax_offline.php';
      $this->proajax = new BMI_Ajax_Offline_Premium($post);
    }

    $isDropboxEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::DROPBOX');
    $isGDriveEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::GDRIVE');
    $isFtpEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::FTP');
    $isAWSEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::AWS');
    $isWasabiEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::WASABI');
    
    $isEnabledDropbox = ($isDropboxEnabled === true || $isDropboxEnabled === 'true') && $this->getDropboxConnectionStatus();
    $isEnabledGdrive = ($isGDriveEnabled === true || $isGDriveEnabled === 'true') && $this->getGDriveConnectionStatus();
    $isEnabledFtp = ($isFtpEnabled === true || $isFtpEnabled === 'true') && $this->getFtpConnectionStatus();
    $isEnabledAWS = ($isAWSEnabled === true || $isAWSEnabled === 'true') && $this->getAWSConnectionStatus();
    $isEnabledWasabi = ($isWasabiEnabled === true || $isWasabiEnabled === 'true') && $this->getWasabiConnectionStatus();

    if ($isEnabledDropbox) {
      require_once BMI_INCLUDES . '/external/dropbox.php';
      $this->dropbox = new Dropbox();
    }

    if ($isEnabledGdrive) {
      require_once BMI_INCLUDES . '/external/google-drive.php';
      $this->gdrive = new GDrive();
    }

    if ($isEnabledFtp) {
      require_once BMI_INCLUDES . '/external/ftp.php';
      $this->ftp = new FTP();
    }

    if ($isEnabledAWS) {
      require_once BMI_INCLUDES . '/external/s3.php';
      $this->aws = new S3('aws');
    }

    if ($isEnabledWasabi) {
      require_once BMI_INCLUDES . '/external/s3.php';
      $this->wasabi = new S3('wasabi');
    }

    require_once BMI_INCLUDES . '/external/backupbliss.php';
    $this->backupbliss = new BackupBliss();

    if (is_user_logged_in() && current_user_can('manage_options')) {
        if ($this->post['f'] == 'check-not-uploaded-backups') {

          $this->checkForBackupsToUpload();

          if ($this->proajax)
            $this->proajax->checkForBackupsToUpload();

          BMP::res(['status' => 'success']);
        }
    }

    if ($this->post['f'] == 'refresh') {
        BMP::res($this->keepAliveUnAuthorizedRefresh());
    }
    
  }

  public function getWasabiConnectionStatus() {
    require_once BMI_INCLUDES . '/external/s3.php';

    $s3 = new S3('wasabi');
    $status = $s3->verifyConnection();
    if (isset($status['result']) && $status['result'] == 'connected') {
      $this->wasabiStatus = true;
      return true;
    } else {
      return false;
    }
  }


  public function getDropboxConnectionStatus() {
    require_once BMI_INCLUDES . '/external/dropbox.php';

    $dropbox = new Dropbox();
    $status = $dropbox->verifyConnection();
    if (isset($status['result']) && $status['result'] == 'connected') {
        $this->dropboxStatus = true;
      return true;
    } else {
      return false;
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
      $this->gdriveStatus = true;
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
  private function verifyGDriveConnection($forceGetNewAccessToken = false)
  {

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
        'force_refresh' => $forceGetNewAccessToken || ( get_transient('bmi_pro_access_token') && get_transient('bmip_gd_issue') === 'auth_error'),
        'redirect_uri' => $baseurl
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
          if (get_transient('bmip_gd_issue') === 'auth_error' && get_transient('bmi_pro_access_token')) {
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

  public function getAWSConnectionStatus() {
    require_once BMI_INCLUDES . '/external/s3.php';

    $s3 = new S3('aws');
    $status = $s3->verifyConnection();
    if (isset($status['result']) && $status['result'] == 'connected') {
      $this->awsStatus = true;
      return true;
    } else {
      return false;
    }
  }

  public function getFtpConnectionStatus()
  {

    $status = $this->verifyFtpConnection();
    if (isset($status['result']) && $status['result'] == 'connected') {
      $this->ftpStatus = true;
      return true;
    } else {
      return false;
    }
  }

  private function verifyFtpConnection()
  {
    require_once BMI_INCLUDES . '/external/ftp.php';

    $ftp = new FTP();
    $conn_id = $ftp->ftpConnect();

    $res = false;
    if ($conn_id !== false) {
      $res = 'connected';
    }

    return ['status' => 'success', 'result' => $res];
  }

  public function getBackupBlissConnectionStatus()
  {

    $res = $this->backupbliss->getSecret();
    return $res !== false;

  }

  public function checkForBackupsToUpload() {
    $toBeUploaded = $this->fetchToBeUploaded();

    $task = $toBeUploaded['current_upload'];
    $queue = $toBeUploaded['queue'];

    //If there's no task or queue present, then check for backups to upload
    if (sizeof($task) == 0 && sizeof($queue) == 0) {
        $this->backupbliss->checkForBackupsToUpload();
        if ($this->dropbox && $this->dropboxStatus) $this->dropbox->checkForBackupsToUploadToDropbox();
        if ($this->gdrive && $this->gdriveStatus) $this->gdrive->checkForBackupsToUpload();
        if ($this->ftp && $this->ftpStatus) $this->ftp->checkForBackupsToUpload();
        if ($this->aws && $this->awsStatus) $this->aws->checkForBackupsToUpload();
        if ($this->wasabi && $this->wasabiStatus) $this->wasabi->checkForBackupsToUpload();

        //Check for backups premium
        if ($this->proajax)
          $this->proajax->checkForBackupsToUpload();
    }

    //Remove failed tasks if the local backup is deleted
    if (isset($toBeUploaded['failed'])) {
      // Local Backups
      require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'scanner' . DIRECTORY_SEPARATOR . 'backups.php';
      $backups = new Backups();
      $backupsAvailable = $backups->getAvailableBackups("local");
      $localBackups = $backupsAvailable['local'];
      $localBackups = array_reverse($localBackups);
      
      $failed = $toBeUploaded['failed'];
      foreach($failed as $failed_task => $failed_count) {
        $data = explode("_", $failed_task);
      

        if (count($data) == 2) {
          $cloudtype = $data[0];
          $md5 = $data[1];
          
          $md5s = array_map(function($backup) { return $backup[7]; }, $localBackups);

          if (!in_array($md5, $md5s)) {
            unset($toBeUploaded["failed"][$failed_task]);
            update_option('bmip_to_be_uploaded', $toBeUploaded);
          }
        }
      }
    }
  }

  /**
   * keepAliveUnAuthorizedRefresh - Unauthorized Keep Alive Request
   * DO NOT RESPONSE WITH ANY SENSITIVE DATA, ONLY SUCCESS OR FAIL
   * THIS CAN BE ACCESSED BY ANYONE WITHOUT ANY AUTH
   *
   * @return string[] success/fail
   */
  public function keepAliveUnAuthorizedRefresh()
  {
    //Atomic locking to prevent race conditions
    $lock_file = BMI_CONFIG_DIR . DIRECTORY_SEPARATOR . '.keep_alive.lock';

    // Open the lock file
    $fp = fopen($lock_file, 'c');

    // Try to acquire an exclusive lock
    if (flock($fp, LOCK_EX | LOCK_NB)) {
        if (BMI_DEBUG)
          Logger::error("Lock acquired.");

        $ret = $this->keepAliveUnAuthorizedRefreshExec();

        // Release the lock
        flock($fp, LOCK_UN);
        if (BMI_DEBUG)
          Logger::error("Lock released.");
        return $ret;
    } else {
        return ['status' => 'success']; // Lock is already held
    }
  }

  private function removeCurrentTask($toBeUploaded) {
    $toBeUploaded["current_upload"] = []; //Removes the current ttask
    update_option("bmip_to_be_uploaded", $toBeUploaded);

    return ['status' => 'no_tasks'];
  }

  private function fetchToBeUploaded() {
    //Get the option without any caching when used with get_option which prevvents stale data from being retreived.
    //This is implemented after observing and debugging the issue that sometimes the same batch is uploaded again causing issues.
    global $wpdb;
    $bmip_to_be_uploaded = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s", 'bmip_to_be_uploaded' ) );
    if ($bmip_to_be_uploaded !== null) {
      $toBeUploaded = maybe_unserialize($bmip_to_be_uploaded);
      if (!isset($toBeUploaded['current_upload']))
        $toBeUploaded['current_upload'] = [];
    } else {
      $toBeUploaded = [
        'current_upload' => [],
        'queue' => [],
        'failed' => []
      ];
    }

    return $toBeUploaded;
  }

  private function checkIfBackupCanBeUploaded($type, $taskname) {
    
    $backupPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $taskname;
    $backupSize = file_exists($backupPath) ? filesize($backupPath) : -1;

    switch($type) {

      case "backupbliss": {
        $storageInfo = $this->backupbliss->getStorageInfo();

        if ($storageInfo["used_space_percent"] > 80 && $storageInfo["used_space_percent"] <= 100) {
          $error_message_notice = 'It seems you already used more than 80% of your space. <a href="'.BMI_AUTHOR_URI . 'pricing'.'">Get more storage now.</a>';
    
          $this->backupbliss->showNotice("storage_warn", $error_message_notice, 60 * 60);
        } elseif($storageInfo["used_space_percent"] > 100) {
          $error_message_notice = 'You’re using more space than allowed. No new backups will be moved to your storage and some of the <b>existing backups will be deleted very soon</b>. ';
  
          $this->backupbliss->showNotice("upload_issue_space", $error_message_notice, 60 * 60);
        } else {
          $this->backupbliss->removeNotice("storage_warn");
          $this->backupbliss->removeNotice("upload_issue_space");
        }

        if (!$this->getBackupBlissConnectionStatus()) {
          return false;
        }

        

        if (!$this->backupbliss->getNotice("upload_issue_space")) {

          if (isset($storageInfo["remaining_space"]))
          {
            $remaining = $storageInfo["remaining_space"];


            if ($backupSize != -1)
            {
              if ($remaining < $backupSize)
              {
                $error_message_notice = 'Moving backups to your storage is failing or will fail because you don’t have enough space.';

                add_option("bmip_backupbliss_required_space", $backupPath);
                $this->backupbliss->showNotice("upload_issue_space", $error_message_notice, 60 * 60);
                //Triggering the server, so that an alert also get sent
                $this->backupbliss->initiateUploadSession($backupPath);
                return false;
              }
            }
            
          }
          else {
            Logger::error("[BMI] Couldn't fetch quota from BackupBliss!");
          }
        }

        if ($this->backupbliss->getNotice("upload_issue_space")) {
          return false;
        }

        break;
      }

      case "dropbox": {
        if (!$this->dropbox || !$this->dropboxStatus) return false;
        if (in_array(get_transient('bmip_dropbox_issue'), ['rate_limit', 'not_enough_memory', 'insufficient_space', 'auth_error_disconnected'])) {
          $issue = get_transient('bmip_dropbox_issue');
          switch($issue){
            case 'auth_error_disconnected':
              delete_option($this->dropbox->dropboxAuthCodeOption);
              delete_option($this->dropbox->dropboxId);
              delete_transient($this->dropbox->dropboxAccessToken);
              return false;
            case 'not_enough_memory':
              if (BMP::getAvailableMemoryInBytes() >= 16 * 1024 * 1024) {
                delete_transient('bmip_dropbox_issue');
                return true;
              }
              break;
            case 'insufficient_space':
              $spaceUsage = $this->dropbox->getSpaceUsage();
              if ($spaceUsage === false) {
                return false;
              }
      
              $requiredSpace = get_option('bmip_dropbox_required_space', 0);
              $requiredSpace = intval($requiredSpace);
              $availableSpace = $spaceUsage['allocation']['allocated'] - $spaceUsage['used'];

              if ($backupSize != -1 && $availableSpace >= $backupSize)
                return true; //Allow backup to be uploaded if the backup size is within the storage limit
              
          
              if ($availableSpace >= $requiredSpace) {
                delete_transient('bmip_dropbox_issue');
                return true;
              }
              break;
          }

          return false;
        }

        break;
      }

      case "gdrive": {
        if (get_transient('bmip_gd_issue') === 'auth_error_disconnected') {
          delete_option('bmi_pro_gd_client_id');
          delete_option('bmi_pro_gd_token');
          delete_transient('bmi_pro_access_token');
          return false;
        }
        if (!$this->gdrive || !$this->gdriveStatus) return false;
        if (get_transient('bmip_display_quota_issues')) {
          $requiredSpace = get_option('bmip_gd_required_space', 0);
          $gdriveStorage = $this->gdrive->getGoogleDriveAvailableStorage();

          if ($backupSize != -1 && $gdriveStorage >= $backupSize)
            return true; //Allow backup to be uploaded if the backup size is within the storage limit

          if ($requiredSpace < $gdriveStorage && $requiredSpace != 0) {
            delete_transient('bmip_display_quota_issues');
            delete_option('bmip_gd_required_space');
            delete_option('bmip_to_be_uploaded');
          } else {
            return false;
          }
        }

        break;
      }

      case "ftp": {
        if (!$this->ftp || !$this->ftpStatus) return false;

        break;
      }

      case "aws": {
        if (!$this->aws || !$this->awsStatus) return false;
        $issue = $this->aws->getIssue()['issue'];
        switch ($issue){
          case 'rate_limit':
            return false;
          case 'disconnected':
          case 'forbidden':
            $this->aws->restartUploadProcess();
            return false;
          default:
            return true;
        }
      }

      case "wasabi": {
        if (!$this->wasabi || !$this->wasabiStatus) return false;
        $issue = $this->wasabi->getIssue()['issue'];
        switch ($issue){
          case 'rate_limit':
            return false;
          case 'disconnected':
          case 'forbidden':
            $this->wasabi->restartUploadProcess();
            return false;
          default:
            return true;
        }
      }
    }

    if ($this->proajax)
      return $this->proajax->checkIfBackupCanBeUploaded($type, $backupSize);

    return true;
  }

  private function _removeTasksFromDeactivatedClouds($cltype, $toBeUploaded, $task, $queue, $failed) {
    if (sizeof($task) > 0 && isset($task['task'])) {
      $taskname = $task['task'];
      $type = explode('_', $taskname)[0];
      if ($type == $cltype)
        $task = [];
    }

    if (sizeof($queue) > 0) {
      $tasks = array_keys($queue);
      foreach($tasks as $taskname) {
        $type = explode('_', $taskname)[0];
        if ($type == $cltype)
          unset($queue[$taskname]);
      }
    }

    if (sizeof($failed) > 0) {
      $tasks = array_keys($failed);
      foreach($tasks as $taskname) {
        $type = explode('_', $taskname)[0];
        if ($type == $cltype)
          unset($failed[$taskname]);
      }
    }

    $toBeUploaded['current_upload'] = $task;
    $toBeUploaded['queue'] = $queue;
    $toBeUploaded['failed'] = $failed;
    update_option("bmip_to_be_uploaded", $toBeUploaded);
  }

  public function getDeactivatedClouds() {
    $deactivatedClouds = [];
    if (!$this->ftpStatus) $deactivatedClouds[] = "ftp";
    if (!$this->getBackupBlissConnectionStatus()) $deactivatedClouds[] = "backupbliss";
    if (!$this->dropboxStatus) $deactivatedClouds[] = "dropbox";
    if (!$this->gdriveStatus) $deactivatedClouds[] = "gdrive";

    if ($this->proajax)
      $deactivatedClouds = array_merge($this->proajax->getDeactivatedClouds(), $deactivatedClouds);

    return $deactivatedClouds;
  }
  
  public function keepAliveUnAuthorizedRefreshExec() {

    $isOnGoing = get_transient('bmip_upload_ongoing');
    if ($isOnGoing === '1') return ['status' => 'success']; //Returning success so that the auto pinger will keep on pinging


    $toBeUploaded = $this->fetchToBeUploaded();

    

    $task = $toBeUploaded['current_upload'];
    $queue = $toBeUploaded['queue'];
    $failed = isset($toBeUploaded['failed']) ? $toBeUploaded['failed'] : [];

    foreach ($this->getDeactivatedClouds() as $cloudType)
      $this->_removeTasksFromDeactivatedClouds($cloudType, $toBeUploaded, $task, $queue, $failed);

    
    //Check for uploads 
    if (get_transient('bmip_check_for_backups_to_upload') !== "wait") {
      set_transient("bmip_check_for_backups_to_upload", "wait", 10);
      $this->checkForBackupsToUpload();
      //Refresh variables after checking for backups to upload
      $toBeUploaded = $this->fetchToBeUploaded();
      $task = $toBeUploaded['current_upload'];
      $queue = $toBeUploaded['queue'];
    }

    $shouldBeQueued = false;

    if (sizeof($task) > 0 && isset($task['task'])) {
      $taskname = $task['task'];
      $type = explode('_', $taskname)[0];


      if (!$this->checkIfBackupCanBeUploaded($type, $task['name'])) {
        $this->removeCurrentTask($toBeUploaded);
        $type = null; //Set type as null so that no actions will be taken
        $shouldBeQueued = true; //Set it to queue the next task
      }

      // BackupBliss
      if ($type == 'backupbliss') {

        if (!isset($task['uploadSession'])) {

          $backupPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $task['name'];
          $manifestPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $task['json'];
          $uploadSession = $this->backupbliss->initiateUploadSession($backupPath);
          if (!$uploadSession)
          {
            $this->removeCurrentTask($toBeUploaded);
            return ['status' => 'success'];
          }

          $availableMemory = BMP::getAvailableMemoryInBytes();
          $bytesPerRequest = intval($availableMemory / 4);

          $toBeUploaded['current_upload']['bytesPerRequest'] = $bytesPerRequest;
          $toBeUploaded['current_upload']['uploadSession'] = $uploadSession;
          $toBeUploaded['current_upload']['manifestPath'] = $manifestPath;
          $toBeUploaded['current_upload']['backupPath'] = $backupPath;
          $toBeUploaded['current_upload']['batch'] = 1;

          update_option('bmip_to_be_uploaded', $toBeUploaded);

          if (!file_exists($backupPath)) delete_option('bmip_to_be_uploaded');
          return ['status' => 'success'];
        } else {

          if (!file_exists($task['backupPath'])) {
            delete_option('bmip_to_be_uploaded');
            return ['status' => 'success'];
          }

          $this->backupbliss->uploadFile($task['uploadSession'], $task['backupPath'], $task['manifestPath'], $task['md5'], $task['batch'], $task['bytesPerRequest']);
          return ['status' => 'success'];
        }
      }
      // Dropbox Process
      elseif ($type == 'dropbox') {

        $sessionId = isset($task['sessionId']) ? $task['sessionId'] : '';
        $offset = isset($task['offset']) ? $task['offset'] : 0;
        $backupName = isset($task['name']) ? $task['name'] : '';
        $md5 = isset($task['md5']) ? $task['md5'] : '';

        $dropbox = new Dropbox();
        $result = $dropbox->uploadDropboxBackup($sessionId, $backupName, $offset, $md5);
        switch ($result['status']) {
          case 'success':
            $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);
            if (!isset($uploadedBackupStatus[$md5])) {
              $uploadedBackupStatus[$md5] = [];
            }
            $uploadedBackupStatus[$md5]['dropbox'] = true;
            update_option('bmi_uploaded_backups_status', $uploadedBackupStatus);
            $toBeUploaded['current_upload'] = [];
            if (isset($toBeUploaded['failed']) && isset($toBeUploaded['failed'][$taskname])) unset($toBeUploaded['failed'][$taskname]);
            break;
          case 'error':
            Logger::error('[BMI PRO] Could not upload ' . $backupName . ' to Dropbox as an error occurred: ' . $result['error']);

            if ($result['error'] == 'internal_file_not_found') {
              return $this->dropbox->restartUploadprocess();
            }

            if (!isset($toBeUploaded['failed'])) $toBeUploaded['failed'] = [];
            if (isset($toBeUploaded['failed'][$taskname])) $toBeUploaded['failed'][$taskname]++;
            else $toBeUploaded['failed'][$taskname] = 1;
            break;
          case 'continue':
            $offset = isset($result['offset']) ? $result['offset'] : 0;
            $sessionId = isset($result['sessionId']) ? $result['sessionId'] : '';
            if ($offset != 0 ) $toBeUploaded['current_upload']['offset'] = $offset;
            if ($sessionId != '') $toBeUploaded['current_upload']['sessionId'] = $sessionId;
            $fileSize = filesize(BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupName);
            $toBeUploaded['current_upload']['progress'] = round(($offset / $fileSize) * 100) . '%';

            // remove from failed
            if (isset($toBeUploaded['failed']) && isset($toBeUploaded['failed'][$taskname])) unset($toBeUploaded['failed'][$taskname]);
            break;
        }

        delete_transient("bmip_upload_ongoing");
        update_option('bmip_to_be_uploaded', $toBeUploaded);
        return ['status' => 'success'];
      }
      // Google Drive Process
      elseif ($type == 'gdrive') {

        if (!isset($task['uploadURL'])) {

          $backupPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $task['name'];
          $manifestPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $task['json'];
          $uploadURL = $this->gdrive->createUploadGoogleDriveURL($backupPath, $manifestPath);

          $availableMemory = BMP::getAvailableMemoryInBytes();
          $bytesPerRequest = intval($availableMemory / 4);

          $toBeUploaded['current_upload']['bytesPerRequest'] = $bytesPerRequest;
          $toBeUploaded['current_upload']['uploadURL'] = $uploadURL['uploadURL'];
          $toBeUploaded['current_upload']['manifestPath'] = $manifestPath;
          $toBeUploaded['current_upload']['backupPath'] = $backupPath;
          $toBeUploaded['current_upload']['batch'] = 1;

          update_option('bmip_to_be_uploaded', $toBeUploaded);

          if (!file_exists($backupPath)) delete_option('bmip_to_be_uploaded');
          return ['status' => 'success'];
        } else {

          if (!file_exists($task['backupPath'])) {
            delete_option('bmip_to_be_uploaded');
            return ['status' => 'success'];
          }

          $this->gdrive->uploadGoogleDriveFile($task['uploadURL'], $task['backupPath'], $task['manifestPath'], $task['md5'], $task['batch'], $task['bytesPerRequest']);
          return ['status' => 'success'];
        }
      }
      // FTP Process
      elseif ($type == 'ftp') {

        if (!isset($task['uploadURL'])) {
          $backupPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $task['name'];
          $manifestPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $task['json'];
          $availableMemory = BMP::getAvailableMemoryInBytes();

          $bytesPerRequest = intval($availableMemory / 4);
          $toBeUploaded['current_upload']['bytesPerRequest'] = $bytesPerRequest;
          $toBeUploaded['current_upload']['uploadURL'] = get_option('bmi_pro_ftp_host');
          $toBeUploaded['current_upload']['manifestPath'] = $manifestPath;
          $toBeUploaded['current_upload']['backupPath'] = $backupPath;
          $toBeUploaded['current_upload']['batch'] = 1;

          update_option('bmip_to_be_uploaded', $toBeUploaded);

          if (!file_exists($backupPath)) delete_option('bmip_to_be_uploaded');
          return ['status' => 'success'];
        } else {

          if (!file_exists($task['backupPath'])) {
            delete_option('bmip_to_be_uploaded');
            return ['status' => 'success'];
          }
          $this->ftp->uploadFtpDriveFiles($task['uploadURL'], $task['backupPath'], $task['manifestPath'], $task['md5'], $task['batch'], $task['bytesPerRequest']);
          return ['status' => 'success'];
        }

      }
      // AWS
      elseif ($type == 'aws') {

        $uploadId = isset($task['uploadId']) ? $task['uploadId'] : '';
        $offset = isset($task['offset']) ? $task['offset'] : 0;
        $backupName = isset($task['name']) ? $task['name'] : '';
        $md5 = isset($task['md5']) ? $task['md5'] : '';

        $s3 = new S3('aws');
        
        $result = $s3->uploadBackup($uploadId, $backupName, $offset, $md5);
        switch ($result['status']) {
          case 'success':
            $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);
            if (!isset($uploadedBackupStatus[$md5])) {
              $uploadedBackupStatus[$md5] = [];
            }
            $uploadedBackupStatus[$md5]['aws'] = true;
            update_option('bmi_uploaded_backups_status', $uploadedBackupStatus);
            $toBeUploaded['current_upload'] = [];
            if (isset($toBeUploaded['failed']) && isset($toBeUploaded['failed'][$taskname])) unset($toBeUploaded['failed'][$taskname]);
            break;
          case 'error':
            Logger::error('[BMI PRO] Could not upload ' . $backupName . ' to AWS S3 as an error occurred: ' . $result['error']);

            $toBeUploaded['current_upload'] = [];
            if (!isset($toBeUploaded['failed'])) $toBeUploaded['failed'] = [];
            if (isset($toBeUploaded['failed'][$taskname])) $toBeUploaded['failed'][$taskname]++;
            else $toBeUploaded['failed'][$taskname] = 1;
            break;
          case 'continue':
            $offset = isset($result['offset']) ? $result['offset'] : 0;
            $uploadId = isset($result['uploadId']) ? $result['uploadId'] : '';
            if ($offset != 0 ) $toBeUploaded['current_upload']['offset'] = $offset;
            if ($uploadId != '') $toBeUploaded['current_upload']['uploadId'] = $uploadId;
            $fileSize = filesize(BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupName);
            $toBeUploaded['current_upload']['progress'] = round(($offset / $fileSize) * 100) . '%';

            // remove from failed
            if (isset($toBeUploaded['failed']) && isset($toBeUploaded['failed'][$taskname])) unset($toBeUploaded['failed'][$taskname]);
            break;
        }

        delete_transient("bmip_upload_ongoing");
        update_option('bmip_to_be_uploaded', $toBeUploaded);
        return ['status' => 'success'];
      }
      //Wasabi
      elseif ($type == 'wasabi') {

        $uploadId = isset($task['uploadId']) ? $task['uploadId'] : '';
        $offset = isset($task['offset']) ? $task['offset'] : 0;
        $backupName = isset($task['name']) ? $task['name'] : '';
        $md5 = isset($task['md5']) ? $task['md5'] : '';

        $s3 = new S3('wasabi');
        
        $result = $s3->uploadBackup($uploadId, $backupName, $offset, $md5);
        switch ($result['status']) {
          case 'success':
            $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);
            if (!isset($uploadedBackupStatus[$md5])) {
              $uploadedBackupStatus[$md5] = [];
            }
            $uploadedBackupStatus[$md5]['wasabi'] = true;
            update_option('bmi_uploaded_backups_status', $uploadedBackupStatus);
            $toBeUploaded['current_upload'] = [];
            if (isset($toBeUploaded['failed']) && isset($toBeUploaded['failed'][$taskname])) unset($toBeUploaded['failed'][$taskname]);
            break;
          case 'error':
            Logger::error('[BMI PRO] Could not upload ' . $backupName . ' to Wasabi as an error occurred: ' . $result['error']);

            $toBeUploaded['current_upload'] = [];
            if (!isset($toBeUploaded['failed'])) $toBeUploaded['failed'] = [];
            if (isset($toBeUploaded['failed'][$taskname])) $toBeUploaded['failed'][$taskname]++;
            else $toBeUploaded['failed'][$taskname] = 1;
            break;
          case 'continue':
            $offset = isset($result['offset']) ? $result['offset'] : 0;
            $uploadId = isset($result['uploadId']) ? $result['uploadId'] : '';
            if ($offset != 0 ) $toBeUploaded['current_upload']['offset'] = $offset;
            if ($uploadId != '') $toBeUploaded['current_upload']['uploadId'] = $uploadId;
            $fileSize = filesize(BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupName);
            $toBeUploaded['current_upload']['progress'] = round(($offset / $fileSize) * 100) . '%';

            // remove from failed
            if (isset($toBeUploaded['failed']) && isset($toBeUploaded['failed'][$taskname])) unset($toBeUploaded['failed'][$taskname]);
            break;
        }

        delete_transient("bmip_upload_ongoing");
        update_option('bmip_to_be_uploaded', $toBeUploaded);
        return ['status' => 'success'];
      }
      elseif ($this->proajax) {
        $ret = $this->proajax->processClouds($type, $task, $toBeUploaded, $taskname);
        if ($ret["status"] !== "no_tasks")
          return $ret;
      }

    } else {
      $shouldBeQueued = true;
    }

    if ($shouldBeQueued && sizeof($queue) > 0) {

      $tasks = array_keys($queue);
      if (sizeof($tasks) > 0) {

        $selectedTask = $tasks[0];
        $cloudType = explode("_", $selectedTask)[0];
        $toBeProcessed = $queue[$selectedTask];

        if ($this->checkIfBackupCanBeUploaded($cloudType, $toBeProcessed['name'])) {
          $toBeUploaded['current_upload'] = [
            'task' => $selectedTask,
            'name' => $toBeProcessed['name'],
            'md5' => $toBeProcessed['md5'],
            'json' => $toBeProcessed['json'],
            'progress' => '0%'
          ];
        } else {
          if (isset($toBeUploaded['failed']))
            $toBeUploaded['failed'][$selectedTask] = 1; //Mark the task as failed
        }
        
        unset($toBeUploaded['queue'][$selectedTask]);
        update_option('bmip_to_be_uploaded', $toBeUploaded);

        //Return success if there are more tasks in the queue, so auto pinger can ping rapidly
        return ['status' => sizeof($queue) > 0 ? 'success' : 'no_tasks'];
      } else return ['status' => 'no_tasks'];
    } else return ['status' => 'no_tasks'];

    return ['status' => 'no_tasks'];
  }
}
