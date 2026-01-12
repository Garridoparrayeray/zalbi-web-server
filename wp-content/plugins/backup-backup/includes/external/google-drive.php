<?php

// Namespace
namespace BMI\Plugin\External;

// Use
use BMI\Plugin\Backup_Migration_Plugin as BMP;
use BMI\Plugin\BMI_Logger as Logger;
use BMI\Plugin\BMI_Pro_Core;
use BMI\Plugin\BMProAjax as BMProAjax;
use BMI\Plugin\Scanner\BMI_BackupsScanner as Backups;
use BMI\Plugin\Dashboard as Dashboard;

// Exit on direct access
if (!defined('ABSPATH')) {
  exit;
}

/**
 * BMI_External_GDrive
 */
class BMI_External_GDrive  {

  private $gdrive_access_token = false;

  public function __construct() {

    add_action('bmi_premium_remove_backup_file', [&$this, 'deleteGoogleDriveBackup']);
    add_action('bmi_premium_remove_backup_json_file', [&$this, 'deleteGoogleDriveJson']);

  }

  /**
   * checkForBackupsToUpload - Will check for backups that requires to be in sync with cloud
   *
   * @return string[]
   */
  public function checkForBackupsToUpload() {

    $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::GDRIVE');
    if (!($isEnabled === true || $isEnabled === 'true')) {
      return;
    }

    // Upload Object
    $requiresUpload = get_option('bmip_to_be_uploaded', [
      'current_upload' => [],
      'queue' => [],
      'failed' => []
    ]);

    // Local Backups
    require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'scanner' . DIRECTORY_SEPARATOR . 'backups.php';
    $backups = new Backups();
    $backupsAvailable = $backups->getAvailableBackups("local");
    $localBackups = $backupsAvailable['local'];
    $localBackups = array_reverse($localBackups);

    // Google Drive
    $gdriveFailed = false;
    $googleDriveBackups = $this->getGoogleDriveBackups();
    if ($googleDriveBackups && is_object($googleDriveBackups['data']) && isset($googleDriveBackups['data']->files)) {
      $googleDriveParsed = $this->parseGoogleDriveFiles($googleDriveBackups['data']->files);
    } else $gdriveFailed = true;

    $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);
    foreach ($localBackups as $name => $details) {

      $manifestName = $details[0];
      $md5 = $details[7];
      if (isset($uploadedBackupStatus[$md5]) && isset($uploadedBackupStatus[$md5]['gdrive'])) {
        continue;
      }

      // Google Drive
      if (!$gdriveFailed && !(isset($googleDriveParsed['md5_' . $md5]) && isset($googleDriveParsed['file_' . $md5 . '.json']))) {

        // File is not uploaded action required
        if (!isset($requiresUpload['queue']['gdrive_' . $md5])) {
          $isAnyTaskATM = isset($requiresUpload['current_upload']['task']);
          if (($isAnyTaskATM && $requiresUpload['current_upload']['task'] != 'gdrive_' . $md5) || !$isAnyTaskATM) {
            $requiresUpload['queue']['gdrive_' . $md5] = [
              'name' => $name,
              'md5' => $md5,
              'json' => $md5 . '.json'
            ];
          }
        }
      }
    }

    update_option('bmip_to_be_uploaded', $requiresUpload);
    return [ 'status' => 'success' ];

  }

  /**
   * parseGoogleDriveFiles - Parses Google Drive output files
   *
   * @param  object $files Google Drive Files of Return
   * @return array of parsed files
   */
  public function parseGoogleDriveFiles(&$files) {

    $parsedFiles = [];
    foreach ($files as $index => $file) {
      $parsedFiles['file_' . $file->name] = [
        'md5' => $file->md5Checksum,
        'originalName' => $file->originalFilename,
        'id' => $file->id,
        'size' => $file->size
      ];
      $parsedFiles['md5_' . $file->md5Checksum] = [
        'name' => $file->name,
        'originalName' => $file->originalFilename,
        'id' => $file->id,
        'size' => $file->size
      ];
    }

    return $parsedFiles;

  }

  /**
   * getGoogleDriveAccessToken - Generates Access Token for API communication
   *
   * @return json status
   */
  private function getGoogleDriveAccessToken( $forceGetNewAccessToken = false ) {

    $uri = home_url();
    if (substr($uri, 0, 4) != 'http') {
      if (is_ssl()) $uri = 'https://' . home_url();
      else $uri = 'http://' . home_url();
    }

    if ($this->gdrive_access_token != false) {
      return $this->gdrive_access_token;
    }

    $client_token = get_option('bmi_pro_gd_client_id', '');
    $site_token = get_option('bmi_pro_gd_token', '');

    if (strlen($site_token) < 60 || strlen($client_token) < 60) {
      return false;
    }

    $savedAccessToken = get_transient('bmi_pro_access_token');
    if ($savedAccessToken) return $savedAccessToken;

    $url = 'https://authentication.backupbliss.com/v1/gdrive/token';
    $response = wp_remote_post($url, array(
      'method' => 'POST',
      'timeout' => 15,
      'redirection' => 2,
      'httpversion' => '1.0',
      'blocking' => true,
      'body' => array(
        'client_id' => get_option('bmi_pro_gd_client_id', ''),
        'site_token' => get_option('bmi_pro_gd_token', ''),
        'force_refresh' => $forceGetNewAccessToken,
        'redirect_uri' => $uri
      )
    ));

    if (is_wp_error($response)) {
      $error_message = $response->get_error_message();
      Logger::error('[BMI PRO] Something went wrong during getting token:' . $error_message);
      return false;
    } else {
      $result = json_decode($response['body']);
      if (isset($result->expiration) && isset($result->access_token)) {
        $expiresInSeconds = intval($result->expiration) - intval(microtime(true));
        $accessToken = $result->access_token;
        set_transient('bmi_pro_access_token', $accessToken, $expiresInSeconds);
        if (in_array(get_transient('bmip_gd_issue'), ['auth_error', 'auth_error_disconnected'])) {
          delete_transient('bmip_gd_issue');
        }

        $this->gdrive_access_token = $accessToken;
        return $this->gdrive_access_token;
      }
      return false;
    }

  }

  /**
   * makeGoogleDriveAPICall - Makes Call to the Google Drive API GET
   *
   * @return json status
   */
  private function makeGoogleDriveAPICall($uri, $range = false) {

    $access_token = $this->getGoogleDriveAccessToken();

    if ($access_token === false || $access_token === 'false') {
      return 'error';
    }

    $headers = array(
      'Authorization: Bearer ' . rawurlencode($access_token)
    );

    if ($range != false) $headers[] = 'Range: bytes=' . $range;
    else $headers[] = 'Content-Type: application/json';

    $url = 'https://www.googleapis.com/drive/v3/' . $uri;
    $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_TIMEOUT, 30);
          curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
      $error_message = curl_error($ch);
      Logger::error('[BMI PRO] Something went wrong during getting file list/content/download:' . $error_message);
      return 'error';
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code == 401) {
      if (get_transient('bmi_pro_access_token') && get_transient('bmip_gd_issue') != 'auth_error_disconnected') set_transient('bmip_gd_issue', 'auth_error', HOUR_IN_SECONDS);
    }

    // Close the cURL session
    curl_close($ch);

    if ($range != false) return $response;
    else return json_decode($response);

  }

  /**
   * makeGoogleDriveAPICallDelete - Makes Call to the Google Drive API DELETE
   *
   * @return json status
   */
  private function makeGoogleDriveAPICallDelete($fileId) {

    $access_token = $this->getGoogleDriveAccessToken();

    if ($access_token === false || $access_token === 'false') {
      return 'error';
    }

    $url = 'https://www.googleapis.com/drive/v3/files/' . $fileId;
    $response = wp_remote_get($url, array(
      'method' => 'DELETE',
      'timeout' => 15,
      'redirection' => 5,
      'httpversion' => '1.0',
      'blocking' => true,
      'headers' => array(
        'Authorization' => 'Bearer ' . rawurlencode($access_token)
      )
    ));

    $http_code = wp_remote_retrieve_response_code($response);
    if ($http_code == 401) {
      if (get_transient('bmi_pro_access_token') && get_transient('bmip_gd_issue') != 'auth_error_disconnected') set_transient('bmip_gd_issue', 'auth_error', HOUR_IN_SECONDS);
    }

    if (is_wp_error($response)) {
      $error_message = $response->get_error_message();
      Logger::error('[BMI PRO] Something went wrong during getting file list:' . $error_message);
      return 'error';
    } else return json_decode($response['body']);

  }

  /**
   * makeGoogleDriveAPICallPost - Makes Call to the Google Drive API POST
   *
   * @return json status
   */
  private function makeGoogleDriveAPICallPost($uri, $postdata, $type = false) {

    $access_token = $this->getGoogleDriveAccessToken();
    if ($access_token === false || $access_token === 'false') {
      return 'error';
    }

    if ($type == 'upload') $url = 'https://www.googleapis.com/upload/drive/v3/' . $uri;
    else $url = 'https://www.googleapis.com/drive/v3/' . $uri;

    $response = wp_remote_post($url, array(
      'method' => 'post',
      'timeout' => 15,
      'redirection' => 5,
      'httpversion' => '1.0',
      'blocking' => true,
      'headers' => array(
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . rawurlencode($access_token)
      ),
      'body' => wp_json_encode($postdata)
    ));

    $http_code = wp_remote_retrieve_response_code($response);
    if ($http_code == 401) {
      if (get_transient('bmi_pro_access_token') && get_transient('bmip_gd_issue') != 'auth_error_disconnected') set_transient('bmip_gd_issue', 'auth_error', HOUR_IN_SECONDS);
    }

    if (is_wp_error($response)) {
      $error_message = $response->get_error_message();
      Logger::error('[BMI PRO] Something went wrong during getting file list:' . $error_message);
      return 'error';
    } else {
      if ($type == 'upload') return $response['headers']['location'];
      else return json_decode($response['body']);
    }

  }

  /**
   * makeGoogleDriveAPICallPutSingle - Makes Call to the Google Drive API PUT
   * supports only single request upload
   *
   * @return json status
   */
  private function makeGoogleDriveAPICallPutSingle($url, &$data) {

    $access_token = $this->getGoogleDriveAccessToken();
    if ($access_token === false || $access_token === 'false') {
      return 'error';
    }

    $response = wp_remote_post($url, array(
      'method' => 'PUT',
      'timeout' => 15,
      'redirection' => 5,
      'httpversion' => '1.0',
      'blocking' => true,
      'headers' => array(
        'Content-Type' => 'text/plain',
        'Content-Length' => strlen($data),
        'Authorization' => 'Bearer ' . rawurlencode($access_token)
      ),
      'body' => $data
    ));

    $http_code = wp_remote_retrieve_response_code($response);
    if ($http_code == 401) {
      if (get_transient('bmi_pro_access_token') && get_transient('bmip_gd_issue') != 'auth_error_disconnected') set_transient('bmip_gd_issue', 'auth_error', HOUR_IN_SECONDS);
    }

    if (is_wp_error($response)) {
      $error_message = $response->get_error_message();
      Logger::error('[BMI PRO] Something went wrong during PUT upload (single):' . $error_message);
      return 'error';
    } else {
      return $response;
    }

  }

  /**
   * makeGoogleDriveAPICallPut - Makes Call to the Google Drive API PUT
   *
   * @return json status
   */
  private function makeGoogleDriveAPICallPut($url, &$binaryData, $chunkSize, $chunkRange) {

    $access_token = $this->getGoogleDriveAccessToken();
    if ($access_token === false || $access_token === 'false') {
      return 'error';
    }

    $max = ini_get('max_execution_time') - 2;
    if ($max > 120) $max = 120;

    $response = wp_remote_post($url, array(
      'method' => 'PUT',
      'timeout' => $max,
      'redirection' => 5,
      'httpversion' => '1.0',
      'blocking' => true,
      'headers' => array(
        'Content-Type' => 'application/octet-stream',
        'Authorization' => 'Bearer ' . rawurlencode($access_token),
        'Content-Length' => $chunkSize,
        'Content-Range' => $chunkRange
      ),
      'body' => $binaryData
    ));

    $http_code = wp_remote_retrieve_response_code($response);
    if ($http_code == 401) {
      if (get_transient('bmi_pro_access_token') && get_transient('bmip_gd_issue') != 'auth_error_disconnected') set_transient('bmip_gd_issue', 'auth_error', HOUR_IN_SECONDS);
    }

    if (is_wp_error($response)) {
      $error_message = $response->get_error_message();
      Logger::error('[BMI PRO] Something went wrong during file upload (resumable):' . $error_message);
      return 'error';
    } else {
      return $response;
    }

  }

  /**
   * getBMIDirectoryID - Return list of Google Drive backups and their MD5s
   *
   * @return json status
   */
  private function getBMIDirectoryID() {

    $dirname = esc_attr(sanitize_text_field(Dashboard\bmi_get_config('STORAGE::EXTERNAL::GDRIVE::DIRNAME')));

    $search = rawurlencode("name = '" . $dirname . "' and trashed = false and mimeType = 'application/vnd.google-apps.folder'");
    $uri = 'files?corpora=user&orderBy=folder&q=' . $search;
    $api = $this->makeGoogleDriveAPICall($uri);

    if ($api == 'error') return [ 'status' => 'error1' ];
    if (!isset($api->files)) return [ 'status' => 'error2' ];

    if (sizeof($api->files) <= 0 && !isset($api->files[0])) {

      $directoryCreationData = [
        'mimeType' => 'application/vnd.google-apps.folder',
        'name' => $dirname,
        'parents' => ['root'],
      ];

      $api = $this->makeGoogleDriveAPICallPost('files', $directoryCreationData);
      $bmiAppDirectoryID = $api->id;

    } else {

      $bmiAppDirectoryID = $api->files[0]->id;

    }

    return $bmiAppDirectoryID;

  }

  /**
   * getGoogleDriveBackups - Return list of Google Drive backups and their MD5s
   *
   * @return json status
   */
  public function getGoogleDriveBackups() {

    $bmiAppDirectoryID = $this->getBMIDirectoryID();

    if (is_array($bmiAppDirectoryID)) return false;

    $search = rawurlencode("'" . $bmiAppDirectoryID . "'" . ' in parents and trashed = false');
    $uri = 'files?corpora=user&orderBy=folder&fields=files(md5Checksum,+originalFilename,+size,+mimeType,+name,+id)&q=' . $search;
    $api = $this->makeGoogleDriveAPICall($uri);

    return [ 'status' => 'success', 'data' => $api ];

  }

  /**
   * createUploadGoogleDriveURL - It will create resumable upload URL
   *
   * @return json status
   */
  public function createUploadGoogleDriveURL($backupPath, $manifestPath, $forManifest = false) {

    $bmiAppDirectoryID = $this->getBMIDirectoryID();

    $uri = 'files?uploadType=resumable';

    // Make file LINK for RESUMABLE upload
    $uploadBody = array();
    if ($forManifest === true) $uploadBody['name'] = basename($manifestPath);
    else $uploadBody['name'] = basename($backupPath);
    $uploadBody['parents'] = [$bmiAppDirectoryID];
    $uploadURL = $this->makeGoogleDriveAPICallPost($uri, $uploadBody, 'upload');

    return [ 'status' => 'success', 'uploadURL' => $uploadURL ];

  }

  /**
   * uploadManifestFile - It will upload manifest file into BMI directory on Google Drive
   *
   * @return array|false status
   */
  private function uploadManifestFile($manifestUploadURL, $manifestPath) {

    if (!file_exists($manifestPath)) {
      return false;
    }

    $contents = file_get_contents($manifestPath);
    $api = $this->makeGoogleDriveAPICallPutSingle($manifestUploadURL, $contents);

    return [ 'status' => 'success', 'data' => $api ];

  }

  /**
   * getGoogleDriveFileContents - Gets file body by Google Drive file ID
   *
   * @return json status
   */
  public function getGoogleDriveFileContents($fileId, $range = false) {

    $uri = 'files/' . sanitize_text_field($fileId) . '?alt=media';
    $api = $this->makeGoogleDriveAPICall($uri, $range);

    return [ 'status' => 'success', 'data' => $api ];

  }

  /**
   * getGoogleDriveFileMeta - Gets file meta data by Google Drive file ID
   *
   * @return json status
   */
  public function getGoogleDriveFileMeta($fileId) {

    $uri = 'files/' . sanitize_text_field($fileId) . '?fields=md5Checksum,originalFilename,size,mimeType,name,id';
    $api = $this->makeGoogleDriveAPICall($uri);

    return [ 'status' => 'success', 'data' => $api ];

  }

  /**
   * deleteGoogleDriveBackup - Deletes Backup from Google Drive
   *
   * @return json status
   */
  public function deleteGoogleDriveBackup($md5) {

    $files = $this->getGoogleDriveBackups();
    if (isset($files['status']) && $files['status'] == 'success') {
      $files = $files['data']->files;
      foreach ($files as $index => $file) {
        if ($file->md5Checksum == $md5) {
          $fileId = $file->id;
          $this->makeGoogleDriveAPICallDelete($fileId);
        }
      }
    }

  }

  /**
   * deleteGoogleDriveJson - Deletes JSON manifest from Google Drive
   *
   * @return json status
   */
  public function deleteGoogleDriveJson($fileName) {

    $files = $this->getGoogleDriveBackups();
    if (isset($files['status']) && $files['status'] == 'success') {
      $files = $files['data']->files;
      foreach ($files as $index => $file) {
        if ($file->originalFilename == $fileName) {
          $fileId = $file->id;
          $this->makeGoogleDriveAPICallDelete($fileId);
        }
      }
    }

  }

  /**
   * uploadGoogleDriveFile - It will upload particular file into BMI directory on Google Drive
   *
   * @return json status
   */
  public function uploadGoogleDriveFile($uploadURL, $filePath, $manifestPath, $md5, $batch, $bytesPerRequest) {

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

    $chunkSize = 256 * 1024 * 4 * intval($bytesPerRequest / 1024 / 1024);

    $chunkOffset = (($batchNumber - 1) * $chunkSize);
    $rangeEnd = (($chunkSize * $batchNumber) - 1);

    if ($rangeEnd >= $maxLength) $rangeEnd = $maxLength - 1;
    if (($chunkSize + $chunkOffset) >= $maxLength) $chunkSize = $rangeEnd - $chunkOffset + 1;
    $range = 'bytes ' . $chunkOffset . '-' . $rangeEnd . '/' . $maxLength;
    $nextShouldStartAt = $rangeEnd + 1;


    if ($stream = fopen($filePath, 'r')) {
      $binaryData = stream_get_contents($stream, $chunkSize, $chunkOffset);
      fclose($stream);
    }

    $api = $this->makeGoogleDriveAPICallPut($uploadURL, $binaryData, $chunkSize, $range);
    $toBeUploaded = get_option('bmip_to_be_uploaded', false);
    if (isset($api['response']) && isset($api['response']['code'])) {
      $code = intval($api['response']['code']);
      if ($code == 308) {

        $task = $toBeUploaded['current_upload']['task'];
        if (!isset($toBeUploaded['failed'])) $toBeUploaded['failed'] = [];
        if (isset($toBeUploaded['failed'][$task])) unset($toBeUploaded['failed'][$task]);

        // All is good
        if ($toBeUploaded) {
          $toBeUploaded['current_upload']['batch'] = intval($batch) + 1;
          $toBeUploaded['current_upload']['progress'] = number_format(($rangeEnd / $maxLength) * 100, 2) . '%';
          update_option('bmip_to_be_uploaded', $toBeUploaded);
        }

      } else if ($code == 200) {

        // Upload finished, upload manifest now
        $manifestUploadURL = $this->createUploadGoogleDriveURL($filePath, $manifestPath, true);
        $manifestRes = $this->uploadManifestFile($manifestUploadURL['uploadURL'], $manifestPath);
        if ($manifestRes['status'] === 'success') {
          $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);
          if (!isset($uploadedBackupStatus[$md5])) {
            $uploadedBackupStatus[$md5] = [];
          }
          $uploadedBackupStatus[$md5]['gdrive'] = true;
          update_option('bmi_uploaded_backups_status', $uploadedBackupStatus);
        }

        $task = $toBeUploaded['current_upload']['task'];
        $toBeUploaded['current_upload'] = [];
        if (!isset($toBeUploaded['failed'])) $toBeUploaded['failed'] = [];
        if (isset($toBeUploaded['failed'][$task])) unset($toBeUploaded['failed'][$task]);

        update_option('bmip_to_be_uploaded', $toBeUploaded);

      } else if ($code == 403) {

        $message = 'Backup file upload to Google Drive could not be completed due to insufficient free space on Google Drive.<br />';
        $message .= 'The plugin will automatically retry uploading the backup file within an hour of this error message.<br />';
        $message .= 'During this time, please try to resolve any issues, such as freeing up space on your Google Drive.<br />';

        // Add required space option to check later
        add_option('bmip_gd_required_space', filesize($filePath));
        // Display message
        set_transient('bmip_display_quota_issues', $message, HOUR_IN_SECONDS);
        // Force to show the message again
        delete_option('bmip_dismissed_quota_notice');

        // Mark the backup as failed to upload 
        $task = $toBeUploaded['current_upload']['task'];
        // Requeueing is handled globally
        // $toBeUploaded['queue'][$task] = [
        //   'name' => $toBeUploaded['current_upload']['name'],
        //   'md5' => $toBeUploaded['current_upload']['md5'],
        //   'json' => $toBeUploaded['current_upload']['json']
        // ];
  
        $toBeUploaded['current_upload'] = [];
        if (!isset($toBeUploaded['failed'])) $toBeUploaded['failed'] = [];
        if (isset($toBeUploaded['failed'][$task])) $toBeUploaded['failed'][$task]++;
        else $toBeUploaded['failed'][$task] = 1;
  
        update_option('bmip_to_be_uploaded', $toBeUploaded);

      } else if ($code == 429) {

        $message = 'Backup file upload to Google Drive could not be completed due to a limit error.<br />';
        $message .= 'Received message: <i>Too Many Requests in a short amount of time.</i><br />';
        $message .= 'The plugin will automatically retry uploading the backup file within 2 minutes of this error message.<br />';


        set_transient('bmip_display_quota_issues', $message, 2 * MINUTE_IN_SECONDS);

      } else {

        Logger::error('[BMI PRO] Error during file upload (Google Drive) code:' . $code);
        if (isset($api['body']) && is_string($api['body'])) {
          Logger::error('[BMI PRO] Message received (body):' . print_r($api['body'], true));
        }

        $task = $toBeUploaded['current_upload']['task'];
        // Requeueing is handled globally
        // $toBeUploaded['queue'][$task] = [
        //   'name' => $toBeUploaded['current_upload']['name'],
        //   'md5' => $toBeUploaded['current_upload']['md5'],
        //   'json' => $toBeUploaded['current_upload']['json']
        // ];

        $toBeUploaded['current_upload'] = [];
        if (!isset($toBeUploaded['failed'])) $toBeUploaded['failed'] = [];
        if (isset($toBeUploaded['failed'][$task])) $toBeUploaded['failed'][$task]++;
        else $toBeUploaded['failed'][$task] = 1;

        update_option('bmip_to_be_uploaded', $toBeUploaded);

      }
    } else {

      $task = $toBeUploaded['current_upload']['task'];
      // Requeueing is handled globally
      // $toBeUploaded['queue'][$task] = [
      //   'name' => $toBeUploaded['current_upload']['name'],
      //   'md5' => $toBeUploaded['current_upload']['md5'],
      //   'json' => $toBeUploaded['current_upload']['json']
      // ];

      $toBeUploaded['current_upload'] = [];
      if (!isset($toBeUploaded['failed'])) $toBeUploaded['failed'] = [];
      if (isset($toBeUploaded['failed'][$task])) $toBeUploaded['failed'][$task]++;
      else $toBeUploaded['failed'][$task] = 1;

      update_option('bmip_to_be_uploaded', $toBeUploaded);

    }

    delete_transient('bmip_upload_ongoing');
    return [ 'status' => 'success', 'data' => $api ];

  }

  public function getGoogleDriveAvailableStorage() {
      $uri = 'about?fields=storageQuota';
      $api = $this->makeGoogleDriveAPICall($uri);
      $quota = $api->storageQuota;
      $totalStorage = $quota->limit;
      $totalUsage = $quota->usage;
      $availableStorage = $totalStorage - $totalUsage;

      return $availableStorage;
  }

}
