<?php

// Namespace
namespace BMI\Plugin\External;

// Use
use BMI\Plugin\Backup_Migration_Plugin as BMP;
use BMI\Plugin\BMI_Logger as Logger;
use BMI\Plugin\BMI_Pro_Core;
use BMI\Plugin\BMProAjax as BMProAjax;
use BMI\Plugin\Progress\BMI_MigrationProgress as MigrationProgress;
use BMI\Plugin\Scanner\BMI_BackupsScanner as Backups;
use BMI\Plugin\Dashboard as Dashboard;
use function BMI\Plugin\Dashboard\bmi_get_config;

// Exit on direct access
if (!defined('ABSPATH')) {
  exit;
}

/**
 * BMI_External_FTP
 */
class BMI_External_FTP
{
  private $ftp_access_username = false;
  private $ftp_access_password = false;
  private $ftp_access_host = false;
  private $ftp_access_dir = false;
  private $ftp_access_port = false;

  public function __construct()
  {
    // Update FTP config
    $this->ftp_access_host = get_option('bmi_pro_ftp_host');
    $this->ftp_access_username = get_option('bmi_pro_ftp_username');
    $this->ftp_access_password = get_option('bmi_pro_ftp_password');
    $this->ftp_access_dir = get_option('bmi_pro_ftp_backup_dir');
    $this->ftp_access_port = get_option('bmi_pro_ftp_port');

    // Delete files
    add_action('bmi_premium_remove_backup_file', [&$this, 'deleteFtpDriveBackup']);
    add_action('bmi_premium_remove_backup_json_file', [&$this, 'deleteFtpJson']);
  }

  private function _custom_ftp_list($ftp_connection, $directory) {
    // Get the list of files and directories
    $file_list = ftp_nlist($ftp_connection, $directory);

    $result = [];

    if ($file_list === false) {
        return $result; // Error occurred during listing
    }

    foreach ($file_list as $file) {
        $item_path = $file;
        
        // Get the file size
        $size = ftp_size($ftp_connection, $item_path);
        
        // Only include files (ignore directories)
        if ($size >= 0) { // Size >= 0 indicates a file
            $result[] = [
                'name' => basename($file),
                'size' => $size,
                'type' => 'file'
            ];
        }
    }

    return $result;
  }

  public function get_host() {
    return $this->ftp_access_host;
  }

  public function get_user_name() {
    return $this->ftp_access_username;
  }

  public function get_password() {
    return $this->ftp_access_password;
  }

  public function get_dir() {
    return $this->ftp_access_dir;
  }

  public function get_port() {
    return $this->ftp_access_port;
  }

  /**
   * ftpConnect - Connects to FTP Server
   *
   * @return bool|\FTP\Connection
   */
  public function ftpConnect()
  {
    $ftp_server = $this->ftp_access_host;
    $ftp_username = $this->ftp_access_username;
    $ftp_password = $this->ftp_access_password;
    $ftp_port = $this->ftp_access_port;

    if (!$ftp_server || !$ftp_username || !$ftp_password) {
      return false;
    } 

    if (!function_exists('ftp_connect')) {
      return false;
    }

    $ftp_conn = ftp_connect($ftp_server, $ftp_port);

    if (!$ftp_conn) {
      return false;
    }

    $login = ftp_login($ftp_conn, $ftp_username, $ftp_password);
    if ($login) {
      ftp_pasv($ftp_conn, true);
    } else {
      ftp_close($ftp_conn);
      return false;
    }
    
    return $ftp_conn;
  }

  /**
   * checkForBackupsToUpload - Will check for backups that requires to be in sync with cloud
   *
   * @return string[]
   */
  public function checkForBackupsToUpload()
  {
    $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::FTP');
    if (!($isEnabled === true || $isEnabled === 'true')) {
      update_option('bmip_to_be_uploaded', ['current_upload' => [], 'queue' => []]);
      return [];
    }

    require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'scanner' . DIRECTORY_SEPARATOR . 'backups.php';

    // Upload Object
    $requiresUpload = get_option('bmip_to_be_uploaded', [
       'current_upload' => [],
       'queue' => [],
       'failed' => []
    ]);

    // Local Backups
    $backups = new Backups();
    $backupsAvailable = $backups->getAvailableBackups("local");
    $localBackups = $backupsAvailable['local'];
    $localBackups = array_reverse($localBackups);

    // FTP Drive
    $ftpFailed = false;
    $ftpBackups = $this->getFtpBackups();

    if ($ftpBackups && isset($ftpBackups['data'])) {
      $ftpParsed = $this->parseFtpFiles($ftpBackups['data']);
    } else {
      return ['status' => 'error']; //Don't requeue FTP if the fetching fails
    }

    $backupsFiles = isset($ftpParsed['zipFiles']) ? $ftpParsed['zipFiles'] : [];
    $manifestFiles = isset($ftpParsed['jsonFiles']) ? $ftpParsed['jsonFiles'] : [];   
    $availableManifests = array_column($manifestFiles, 'name');
    $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);

    foreach($localBackups as $name => $details) {
      $md5 = $details[7];
      if (isset($uploadedBackupStatus[$md5]) && isset($uploadedBackupStatus[$md5]['ftp'])) {
        continue;
      }
      $isBackupNotExists = !in_array($md5 . '.json', $availableManifests) || !in_array($name, array_column($backupsFiles, 'name'));
      if ($isBackupNotExists && !(isset($requiresUpload['current_upload']['task']) && $requiresUpload['current_upload']['task'] == 'ftp_' . $md5)) {
        $requiresUpload['queue']['ftp_' . $md5] = [
           'name' => $name,
           'md5' => $md5,
           'json' => $md5 . '.json'
        ];

        //As it gets queued again remove any failed tasks
        if (isset($requiresUpload['failed']['ftp_' . $md5])) unset($requiresUpload['failed']['ftp_' . $md5]);
      }
    }

    update_option('bmip_to_be_uploaded', $requiresUpload);
    return ['status' => 'success'];
  }

  /**
   * parseFtpFiles - Parses FTp Drive output files
   *
   * @param object $files FTP Files of Return
   *
   * @return array of parsed files
   */
  public function parseFtpFiles(&$files)
  {
    $login_result = $this->ftpConnect();
    if ($login_result === false) {
      return [];
    }

    $parsedFiles = [];
    $zipFiles = [];
    $jsonFiles = [];
    foreach ($files as $index => $file) {
      
      if ($file['type'] !== 'file') continue;

      $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    
      if (in_array($ext, ['zip', 'gz', 'tar'])) {
        $zipFiles[] = $file;
      } else if ($ext === 'json') {
        $jsonFiles[] = $file;
      }
    }

    return compact('zipFiles', 'jsonFiles');
  }

  /**
   * getFtpBackups - Return list of FTP backups and their MD5s
   *
   * @return array|bool status
   */
  public function getFtpBackups()
  {
    // connect and login to FTP server
    // Update FTP config
    $ftpConnect = $this->ftpConnect();

    if ($ftpConnect !== false) {
      //Get detail files
      $fileList = $this->_custom_ftp_list($ftpConnect, $this->ftp_access_dir);
      ftp_close($ftpConnect);
      return ['status' => 'success', 'data' => $fileList];
    }
    return false;
  }

  /**
   * uploadManifestFile - It will upload manifest file into BMI directory on FTP
   *
   * @return array status
   */
  private function uploadManifestFile($mdf, $manifestPath)
  {
    $ftpConnect= $this->ftpConnect();

    if ($ftpConnect !== false) {
      ftp_chdir($ftpConnect, $this->ftp_access_dir);
      if (ftp_put($ftpConnect, $mdf . '.json', $manifestPath, FTP_ASCII)) {
        ftp_close($ftpConnect);
        return ['status' => 'success', 'data' => 'ok'];
      } else {
        ftp_close($ftpConnect);
        return ['status' => 'error', 'data' => 'error'];
      }
    }
    return ['status' => 'error', 'data' => 'error'];
  }

  /**
   * getFTPFileContents - Gets file body by filename ftp
   *
   * @return array status
   */
  public function getFtpFileContents($fileName)
  {
    $ftpConnect = $this->ftpConnect();
    if ($ftpConnect === false){
      return ['status' => 'error', 'data' => 'error'];
    }

    ftp_chdir($ftpConnect, $this->ftp_access_dir);

    $temp_file = 'temp.json';

    $file = ftp_get($ftpConnect, BMI_BACKUPS . DIRECTORY_SEPARATOR . $temp_file, $fileName, FTP_BINARY);
    if ($file) {
      $file = file_get_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . $temp_file);
    }
    ftp_close($ftpConnect);
    return ['status' => 'success', 'data' => $file];
  }

  /**
   * getFtpDriveFileMeta - Gets file meta data by FTP file ID
   *
   * @return array|bool status
   */
  public function getFtpDriveFileMeta($fileName)
  {
    $ftpConnect = $this->ftpConnect();
    if ($ftpConnect !== false) {
      $fileList = $this->_custom_ftp_list($ftpConnect, $this->ftp_access_dir);
      foreach($fileList as $file) {
        if($file['name'] === $fileName) {
          ftp_close($ftpConnect);
          return ['status' => 'success', 'data' => $file];
        }
      }
      ftp_close($ftpConnect);
    }
    return false;
  }
  
  /**
   * deleteFtpDriveBackup - Deletes Backup from FTP
   *
   * @return bool status
   */
  public function deleteFtpDriveBackup($md5)
  {
    $files = $this->getFtpBackups();

    if (isset($files['status']) && $files['status'] === 'success') {
      $files = $files['data'];
      foreach ($files as $index => $file) {
        if ($file['name'] === $md5 . '.json') {
          $content =  $this->getFtpFileContents($file['name']);
          if (!$content['data']) {
            return false;
          }

          $data =  json_decode($content['data']);
          if (!isset($data->name)) {
            return false;
          }

          $this->deleteFileFtp($data->name);
        }
      }
      return true;
    }
    return false;
  }

  /**
   * deleteFtpJson - Deletes JSON manifest from FTP
   *
   * @return bool status
   */
  public function deleteFtpJson($fileName)
  {
    $files = $this->getFtpBackups();
    if (isset($files['status']) && $files['status'] === 'success') {
      $files = $files['data'];
      foreach ($files as $index => $file) {
        if ($file['name'] === $fileName) {
          $this->deleteFileFtp($fileName);
          return true;
        }
      }
    }
    return false;
  }

  public function getFtpDriveFileContents($fileName, $startRange, $endRange) {
    $remote_file = $this->ftp_access_dir . DIRECTORY_SEPARATOR . $fileName;

    $ftp_username = urlencode($this->ftp_access_username);
    $ftp_password = urlencode($this->ftp_access_password);
    $ftp_port = $this->ftp_access_port;
    $ftp_server = $this->ftp_access_host;

    $ch = curl_init();

    $url = "ftp://{$ftp_username}:{$ftp_password}@{$ftp_server}:{$ftp_port}/{$remote_file}";

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_RANGE, "$startRange-$endRange");
    curl_setopt($ch, CURLOPT_NOPROGRESS, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $data = curl_exec($ch);

    if(curl_errno($ch)) {
        $error_message = curl_error($ch);
        curl_close($ch);
        error_log('cURL error: ' . $error_message);
        return false;
    }

    curl_close($ch);

    return ['status' => 'success', 'data' => $data];
  }

  /**
   * uploadFtpDriveFiles - It will upload particular file into BMI directory on FTP
   *
   * @return array status
   */
  public function uploadFtpDriveFiles($uploadURL, $filePath, $manifestPath, $md5, $batch, $bytesPerRequest)
  {
    if (!file_exists($filePath)) {
      update_option('bmip_to_be_uploaded', [
         'current_upload' => [],
         'queue' => [],
         'failed' => []
      ]);

      return ['status' => 'error'];
    }

    set_transient('bmip_upload_ongoing', '1', 31);

    $batchNumber = intval($batch);
    $maxLength = filesize($filePath);

    $toBeUploaded = get_option('bmip_to_be_uploaded', false);

    //Check Exist file in ftp
    $exist = $this->isExist(basename($filePath), $maxLength);

    
    if ($exist) {
      $manifestRes = $this->uploadManifestFile($md5, $manifestPath);
      if ($manifestRes['status'] === 'success') {
        $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);
        if (!isset($uploadedBackupStatus[$md5])) {
          $uploadedBackupStatus[$md5] = [];
        }
        $uploadedBackupStatus[$md5]['ftp'] = true;
        update_option('bmi_uploaded_backups_status', $uploadedBackupStatus);
      }

      $task = $toBeUploaded['current_upload']['task'];
      $toBeUploaded['current_upload'] = [];
      if (!isset($toBeUploaded['failed'])) {
        $toBeUploaded['failed'] = [];
      }

      if (isset($toBeUploaded['failed'][$task])) {
        unset($toBeUploaded['failed'][$task]);
      }

      update_option('bmip_to_be_uploaded', $toBeUploaded);
      return ['status' => 'success', 'data' => []];
    }

    $chunkSize = 256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024);

    $chunkOffset = (($batchNumber - 1) * $chunkSize);
    $rangeEnd = (($chunkSize * $batchNumber) - 1);

    if ($rangeEnd >= $maxLength) {
      $rangeEnd = $maxLength - 1;
    }
    if (($chunkSize + $chunkOffset) >= $maxLength) {
      $chunkSize = $rangeEnd - $chunkOffset + 1;
    }
    $nextShouldStartAt = $rangeEnd + 1;

    if ($stream = fopen($filePath, 'r')) {
      if ($maxLength > $chunkOffset) {
        $binaryData = stream_get_contents($stream, $chunkSize, $chunkOffset);
      }
      fclose($stream);
    }
    

    $ftpConnect = $this->ftpConnect();

    if ($ftpConnect !== false) {
      // Login to FTP server

      // Change directory to FTP directory
      ftp_chdir($ftpConnect, $this->ftp_access_dir);

      // Open local file for reading
      $localFile = fopen($filePath, 'ab');
      $remote_file = basename($filePath);
      $user = $this->ftp_access_username;
      $pass = $this->ftp_access_password;
      $ftp_host = $this->ftp_access_host;
      $ftpDirectory = $this->ftp_access_dir;
      $ftp_port = $this->ftp_access_port;

      $remote_handle = fopen("ftp://$user:$pass@$ftp_host:$ftp_port/$ftpDirectory/$remote_file", 'ab');

      if ($localFile) {
        fseek($localFile, $chunkOffset);
        $upload = fwrite($remote_handle, $binaryData);

        // Check if upload was successful
        if ($upload) {

          $task = $toBeUploaded['current_upload']['task'];
          if (!isset($toBeUploaded['failed'])) {
            $toBeUploaded['failed'] = [];
          }

          if (isset($toBeUploaded['failed'][$task])) {
            unset($toBeUploaded['failed'][$task]);
          }

          // All is good
          if ($toBeUploaded) {
            $toBeUploaded['current_upload']['batch'] = intval($batch) + 1;
            $toBeUploaded['current_upload']['progress'] = number_format(($rangeEnd / $maxLength) * 100, 2) . '%';
            update_option('bmip_to_be_uploaded', $toBeUploaded);
          }

          // Check finished
          if ($chunkSize + $chunkOffset >= $maxLength) {

            $manifestRes = $this->uploadManifestFile($md5, $manifestPath);
            if ($manifestRes['status'] === 'success') {
              $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);
              if (!isset($uploadedBackupStatus[$md5])) {
                $uploadedBackupStatus[$md5] = [];
              }
              $uploadedBackupStatus[$md5]['ftp'] = true;
              update_option('bmi_uploaded_backups_status', $uploadedBackupStatus);
            }

            $task = $toBeUploaded['current_upload']['task'];
            $toBeUploaded['current_upload'] = [];
            if (!isset($toBeUploaded['failed'])) {
              $toBeUploaded['failed'] = [];
            }

            if (isset($toBeUploaded['failed'][$task])) {
              unset($toBeUploaded['failed'][$task]);
            }

            update_option('bmip_to_be_uploaded', $toBeUploaded);
          }
        } else {
          $this->errorFtp($toBeUploaded, 'Error uploading file to FTP server!');
        }

        // Close local file
        fclose($localFile);
      } else {
        $this->errorFtp($toBeUploaded, 'Error opening local file!');
      }

      ftp_close($ftpConnect);
    } else {
      $this->errorFtp($toBeUploaded, 'Error connecting to FTP server!');
    }
    delete_transient('bmip_upload_ongoing');
    return ['status' => 'success', 'data' => []];
  }

  public function errorFtp($toBeUploaded, $message)
  {
    Logger::error('[BMI PRO] Error during file upload (FTP) Message:' . $message . '!');

    $task = $toBeUploaded['current_upload']['task'];
    // Requeueing is handled globally
    // $toBeUploaded['queue'][$task] = [
    //    'name' => $toBeUploaded['current_upload']['name'],
    //    'md5' => $toBeUploaded['current_upload']['md5'],
    //    'json' => $toBeUploaded['current_upload']['json']
    // ];

    $toBeUploaded['current_upload'] = [];
    if (!isset($toBeUploaded['failed'])) {
      $toBeUploaded['failed'] = [];
    }
    if (isset($toBeUploaded['failed'][$task])) {
      $toBeUploaded['failed'][$task]++;
    } else {
      $toBeUploaded['failed'][$task] = 1;
    }

    update_option('bmip_to_be_uploaded', $toBeUploaded);
  }

  public function uploadFTPDriveFile($fileName)
  {
    // Check enable FTP option
    $isFtpEnable = Dashboard\bmi_get_config('STORAGE::EXTERNAL::FTP');

    if ($isFtpEnable !== true && $isFtpEnable !== 'true') {
      return ['status' => 'error'];
    }

    $toBeUploaded = get_option('bmip_to_be_uploaded', false);

    $storageLocalPath = sanitize_text_field(bmi_get_config('STORAGE::LOCAL::PATH'));

    $backupFile = $storageLocalPath . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $fileName['filename'];

    $ftpConnect = $this->ftpConnect();
    if ($ftpConnect === false) {
      return ['status' => 'error'];
    }

    $fileSize = filesize($backupFile);

    $latest = BMI_BACKUPS . '/latest.log';
    $latest_progress = BMI_BACKUPS . '/latest_progress.log';

    if ($upload = ftp_nb_put($ftpConnect, DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $fileName['filename'], $backupFile, FTP_BINARY)) {
      while ($upload !== FTP_FINISHED && $upload !== FTP_FAILED) {
        $bytesUploaded = ftp_size($ftpConnect, $fileName['filename']);

        if ($bytesUploaded > 0 && $fileSize > 0) {
          $progress = ($bytesUploaded / $fileSize) * 100;

          error_log("Upload progress: " . round($progress, 2) . "%\n");

          if ($toBeUploaded) {
//            $toBeUploaded['current_upload']['batch'] = intval($batch) + 1;
            $toBeUploaded['current_upload']['progress'] = round($progress, 2) . '%';
            update_option('bmip_to_be_uploaded', $toBeUploaded);
          }

          $progress1 = fopen($latest_progress, 'w');

          if (!$progress1){
            update_option('bmip_to_be_uploaded', [
               'current_upload' => [],
               'queue' => [],
               'failed' => []
            ]);

            ftp_close($ftpConnect);
            return ['status' => 'error'];
          }
          fwrite($progress1, $progress);
          fclose($progress1);

          Logger::append('Step', "Upload progress: " . round($progress, 2) . "%\n");
        }

        $upload = ftp_nb_continue($ftpConnect);
      }

      ftp_close($ftpConnect);
      if ($upload === FTP_FAILED) {
        return ['status' => 'error'];
      } else {
        return true;
      }
    }
    return ['status' => 'error'];

  }

  private function deleteFileFtp($fileName)
  {
    $remote_file = $fileName;
    $ftpConnect = $this->ftpConnect();

    if ($ftpConnect === false) {
      return false;
    }

    ftp_chdir($ftpConnect, $this->ftp_access_dir);

    if (ftp_size($ftpConnect, $remote_file) !== -1) {
      if (ftp_delete($ftpConnect, $remote_file)) {
        ftp_close($ftpConnect);
        return true;
      } else {
        ftp_close($ftpConnect);
        return false;
      }
    } else {
      ftp_close($ftpConnect);
      return false;
    }
  }

  /**
   * Check file is exist in the ftp
   *
   * @param $fileName
   * @param $fileSize
   * @return bool|void
   */
  private function isExist($fileName, $fileSize)
  {
    $ftpConnect = $this->ftpConnect();
    if ($ftpConnect === false) {
      return false;
    }

    $contents_on_server = ftp_nlist($ftpConnect,$this->ftp_access_dir);

    foreach ($contents_on_server as $fileOnServer) {
      if ($fileName === basename($fileOnServer)) {
        if (ftp_size($ftpConnect, $fileOnServer) >= $fileSize) {
          ftp_close($ftpConnect);
          return true;
        }
      }
    }
    ftp_close($ftpConnect);
    return false;
  }
}
