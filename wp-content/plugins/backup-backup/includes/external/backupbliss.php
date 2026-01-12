<?php

// Namespace
namespace BMI\Plugin\External;

// Use
use BMI\Plugin\BMI_Logger as Logger;
use BMI\Plugin\Scanner\BMI_BackupsScanner as Backups;
use BMI\Plugin\Dashboard as Dashboard;

// Exit on direct access
if (!defined('ABSPATH')) {
  exit;
}

class BMI_External_BackupBliss
{

  public function __construct()
  {

    add_action('bmi_premium_remove_backup_file', [&$this, 'deleteBackup']);
    add_action('bmi_premium_remove_backup_json_file', [&$this, 'deleteBackupManifest']);
  }

  public function process($action, $post) {

    $uri = home_url();
    if (substr($uri, 0, 4) != 'http') {
      if (is_ssl()) $uri = 'https://' . home_url();
      else $uri = 'http://' . home_url();
    }

    if ($action == "connect") {
      $ret = $this->getSecret($post['api_key']);
      if($ret[0] !== false) {
        update_option("bmi_pro_backupbliss_key", $ret[1]);
        return ["status"=>'success']; 
      } else {
        if ($ret[1] != "")
          return ["status"=>'fail', "message"=>$ret[1]];
      }
      
      return ["status"=>'fail', "message"=>"Invalid API Key provided!"];
    }

    if ($action == "disconnect") {
      $res = $this->_makeApiCall("plugin/disconnect", "POST", ["site_url"=>$uri]);
      if ($res["status"])
        if ($res["response_data"]["status"]) {
          delete_option("bmi_pro_backupbliss_key");
          return ["status"=>"success"];
        }
      
      return ["status"=>"fail", "message"=> "Error disconnecting from the backupbliss server."];
    }

    if ($action == "storage-info") {
      $res = $this->_makeApiCall("file/storage-info");
      if ($res["status"])
        if ($res["response_data"]["status"]) {
          return ["status"=>"success", "data"=>$res["response_data"]];
        }
      
      return ["status"=>"fail", "message"=>"Error fetching storage info from the backupbliss server."];
    }
  }

  /**
   * checkForBackupsToUpload - Will check for backups that requires to be in sync with cloud
   *
   * @return string[]
   */
  public function checkForBackupsToUpload()
  {
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
    $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);

    $files = $this->parseFiles($this->getAllFiles());
    // if (BMI_DEBUG) {
    //   Logger::error( print_r($files, true));
    // }
   

    if (!$files) return;

    foreach ($localBackups as $name => $details) {

      $md5 = $details[7];
      if (isset($uploadedBackupStatus[$md5]) && isset($uploadedBackupStatus[$md5]['backupbliss'])) {
          continue;
      }

      if (!(isset($files['manifests'][$md5 . '.json']) && isset($files['backups'][$name]))) {

        $task = 'backupbliss_' . $md5;

        // File is not uploaded action required
        if (!isset($requiresUpload['queue'][$task])) {
          $isAnyTaskATM = isset($requiresUpload['current_upload']['task']);


          // if (isset($requiresUpload['failed'][$task])) unset($requiresUpload['failed'][$task]);

          if (($isAnyTaskATM && $requiresUpload['current_upload']['task'] != $task) || !$isAnyTaskATM) {
            $requiresUpload['queue'][$task] = [
              'name' => $name,
              'md5' => $md5,
              'json' => $md5 . '.json'
            ];
          }
        }
      }
    }

    update_option('bmip_to_be_uploaded', $requiresUpload);
    return ['status' => 'success'];
  }

  public function parseFiles($files)
  {

    $parsedFiles = ['backups' => [], 'manifests' => []];

    if ($files === false) return false;

    foreach ($files as $index => $file) {
      if (isset($file['folder'])) {
        continue; //Skip directories
      }

      $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

      if (BMI_DEBUG) {
        // Logger::error("parseFiles - " . $file['name'] . " - " . print_r($file['file'], true));
        // Logger::error("parseFiles - ext - $extension");
      }

      if (in_array($extension, ["zip", "tar", "gz"])) {
        $type = 'backups';
      }

      if ($extension === 'json') {
        $type = 'manifests';
      }

      if (isset($type)) {
        $parsedFiles[$type][$file['name']] = [
          'size' => $file['size']
        ];
        unset($type);
      }
    }


    if (BMI_DEBUG) {
      // Logger::error("parseFiles - " . print_r($parsedFiles, true));
    }

    return $parsedFiles;
  }

  public function getSecret($api_key = false)
  {
    $tempKeyBackupBlissFiles = BMI_TMP . DIRECTORY_SEPARATOR . 'backupblissKeys.php';
    if (file_exists($tempKeyBackupBlissFiles)) {
      $backupblissKeys = file_get_contents($tempKeyBackupBlissFiles);
      if (strpos($backupblissKeys, "\n") !== false) {
        $lines = explode("\n", $backupblissKeys);
        if (sizeof($lines) == 3) {
          $backupbliss_key = substr($lines[1], 2);
          if (function_exists('wp_load_alloptions')) {
            wp_load_alloptions(true);
          }
          delete_option("bmi_pro_backupbliss_key");
          if (function_exists('wp_load_alloptions')) {
            wp_load_alloptions(true);
          }
          update_option("bmi_pro_backupbliss_key", $backupbliss_key);
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

      unlink($tempKeyBackupBlissFiles);
    }

    $uri = home_url();
    if (substr($uri, 0, 4) != 'http') {
      if (is_ssl()) $uri = 'https://' . home_url();
      else $uri = 'http://' . home_url();
    }

    if (!$api_key)
      $key = get_option('bmi_pro_backupbliss_key', false);
    else
      $key = $api_key;

    if ($key === false) {
      return [false, ""];
    } elseif($api_key === false) {
      return [$key !== false, $key];
    }

    $url = BMI_BB_STORAGE_API_URI . '/plugin/verify';
    $response = wp_remote_post($url, array(
      'method' => 'POST',
      'timeout' => 15,
      'redirection' => 2,
      'httpversion' => '1.0',
      'blocking' => true,
      'headers' => ["Content-Type"=>"application/json", "Authorization" => "Bearer ". $key],
      'body' => json_encode([
        "site_url" => $uri
      ])
    ));

    if (is_wp_error($response)) {
      $error_message = $response->get_error_message();
      Logger::error('[BMI PRO] Something went wrong while authenticating the BackupBliss api key:' . $error_message);
      return [false, $error_message];
    } else {

      $http_code = wp_remote_retrieve_response_code($response);
      if (BMI_DEBUG) {
        Logger::error("[BMI PRO] BackupBliss getSecret: $http_code - " . print_r($response['body'], true));
      }
      if ($http_code == 200) {
        $this->removeNotice("invalid_key");
        $result = json_decode($response['body']);


        if ($result->status) {
          return [true, $key];
        }
      } else if ($http_code == 401) {
        if ($api_key) //Authentication request so no need to show notice.
          return [false, ""];
        $this->_keyDeactivatedNotice();
      } else if ($http_code == 429) {
        $result = json_decode($response['body']);
        return [false, $result->message];
      }
      return [false, ""];
    }
  }

  public function showNotice($type, $message, $time = 0)
  {
    if (BMI_DEBUG) {
      Logger::error("showNotice($type, $message, $time)");
    }

    set_transient('bmip_backupbliss_notice_' . $type, $message, $time);
    $transients = [];
    $current_trasients = get_transient('bmip_backupbliss_notices');
    if ($current_trasients) $transients = $current_trasients;
    $transients[$type] = $type;
    set_transient('bmip_backupbliss_notices', $transients);
  }

  public function hideFailureWarnNotice($exp) {
    set_transient('bmip_backupbliss_hide_failure_notice', true, $exp);
  }

  public function showFailureWarnNotice() {
    delete_transient('bmip_backupbliss_hide_failure_notice');
  }

  public function canShowFailureWarnNotice() {
    return !get_transient("bmip_backupbliss_hide_failure_notice", false);
  }

  public function removeNotice($type)
  {
    // if (BMI_DEBUG) {
    //   Logger::error("removeNotice($type)");
    // }

    delete_transient('bmip_backupbliss_notice_' . $type);
    $current_trasients = get_transient('bmip_backupbliss_notices');
    if (isset($current_trasients[$type])) {
      unset($current_trasients[$type]);
      set_transient('bmip_backupbliss_notices', $current_trasients);
    }
  }

  public function hideNotice($type, $time = 0)
  {
    if (BMI_DEBUG) {
      Logger::error("hideNotice($type, $time)");
    }

    set_transient('bmip_backupbliss_notice_hide_' . $type, true, $time);
  }

  public function canShowNotice($type)
  {
    return !get_transient('bmip_backupbliss_notice_hide_' . $type, false);
  }

  public function getNotice($type)
  {
    // if (BMI_DEBUG) {
    //   Logger::error("getNotice($type)");
    // }

    return get_transient("bmip_backupbliss_notice_" . $type);
  }

  public function getNotices()
  {
    $bmip_backupbliss_notices = get_transient("bmip_backupbliss_notices");
    $temp_notices = $bmip_backupbliss_notices;
    $notices = [];
    if ($bmip_backupbliss_notices) {
      foreach ($bmip_backupbliss_notices as $notice) {
        $noticemessage = get_transient("bmip_backupbliss_notice_" . $notice);
        if ($noticemessage) {
          $notices[$notice] = $noticemessage;
        } else {
          unset($temp_notices[$notice]);
        }
      }
    }

    if ($bmip_backupbliss_notices !== $temp_notices) {
      set_transient("bmip_backupbliss_notices", $temp_notices);
    }

    return $notices;
  }


  private function _makeApiCall($url, $req_type = "GET", $body = [], $custom_headers = null)
  {
    $url = BMI_BB_STORAGE_API_URI . $url;

    if (BMI_DEBUG) {
      $backtrace = debug_backtrace();
      // Get the caller's function name
      $callerFunction = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : 'unknown';
    }
    if (BMI_DEBUG) {
       Logger::error("[BMI PRO][BackupBliss] REQUEST FROM $callerFunction() in _makeApiCall($url, $req_type, " . ($req_type != "PUT" ? print_r($body, true) : "BINARYDATA") . ", " . print_r($custom_headers, true) . ")");
    }

    $secret = $this->getSecret();
    if (!$secret[0]) {
      return ["status" => false];
    }

    $max_execution_time_pre_limit = ini_get('max_execution_time') - 2;

    $headers = $custom_headers == null ? [
      'Authorization: Bearer ' . $secret[1],
      'Accept: application/json',
      'Content-Type: application/json',
    ] : $custom_headers;



    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $req_type);
    curl_setopt($ch, CURLOPT_TIMEOUT, $max_execution_time_pre_limit);

    if ($req_type == "POST") {
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    } elseif ($req_type == "PUT") {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($req_type == "DELETE") {
      return $http_code;
    }

    if (curl_errno($ch)) {
      Logger::error("[BMI PRO][BackupBliss] Error in _makeApiCall request: Type: $req_type HTTP Code: $http_code. Error: " . curl_error($ch));
      return ["status" => false, "http_code" => $http_code, "response_data" => $response];
    }

    curl_close($ch);

    if ($http_code >= 200 && $http_code <= 299) {
      $response_data = $custom_headers == null ? json_decode($response, true) : $response;
      if (BMI_DEBUG) {
        //Logger::error("[BMI PRO] RESPONSE _makeApiCall - " . print_r($response_data, true));
      }
      return ["status" => true, "http_code" => $http_code, "response_data" => $response_data];
    } else {
      if ($http_code == 401) {
        $this->_keyDeactivatedNotice();
      } elseif ($http_code == 403) {
        $response_data = json_decode($response, true);
        $this->_accessPermissionNotice($response_data['message']);
      }
      Logger::error("[BMI PRO][BackupBliss] Error in _makeApiCall request: Type: $req_type HTTP Code: $http_code. Response: $response");
      return ["status" => false, "http_code" => $http_code, "response_data" => $response];
    }
  }

  private function _accessPermissionNotice($message) {
    Logger::error("[BMI] " . $message);
    $this->showNotice("invalid_permission", $message, 0);
  }

  private function _keyDeactivatedNotice() {
    Logger::error("[BMI] The API key is either invalid or deactivated.");
    $message = 'There was an error while authenticating the BackupBliss API key.<br />';
    $message .= 'Your BackupBliss API key got deactivated, hence moving backups to the BackupBliss storage is failing or will fail.<br>';
    $message .= 'Please connect with a new API key by accessing your <a target="_blank" href="' . BMI_BB_STORAGE_URI . '">backupbliss storage</a> account.';

    $this->showNotice("invalid_key", $message);
    delete_option('bmi_pro_backupbliss_key');
    $this->removeNotice("storage_warn");
    $this->removeNotice("upload_issue");
    $this->removeNotice("upload_issue_space");
  }

  public function getFileDetailByName($file_name) {
    $response_data = $this->_makeApiCall("file/file-info/$file_name");

    if($response_data["status"] && $response_data["response_data"]["status"]) {
      return $response_data["response_data"]["file_info"];
    }

    return false;
  }

  private function deleteFile($file_name)
  {
    if (BMI_DEBUG) {
      Logger::error("deleteFile($file_name)");
    }
    
    $url = "file/delete/$file_name";

    $http_code = $this->_makeApiCall($url, "DELETE");

    // Handle the response based on the status code
    if ($http_code == 200) {
      // 'File deleted successfully'
      return True;
    } else {
      //Logger::error("[BMI PRO] Error in deleteFile. HTTP Code: $http_code.");
      return False;
    }
  }

  public function deleteBackup($md5)
  {

    if (BMI_DEBUG) {
      Logger::error("deleteBackup($md5)");
    }

    $manifest = $this->getManifest($md5);
    if ($manifest) {
      $this->deleteFile($manifest->name);
    }
  }

  public function getManifest($md5)
  {
    $manifest = false;
    $localManifest = BMI_BACKUPS . DIRECTORY_SEPARATOR . $md5 . '.json';

    if (file_exists($localManifest)) {

      $manifestData = file_get_contents($localManifest);
      $manifest = json_decode($manifestData);
    } else {

      $manifestData = $this->getFile($md5 . '.json');
      if (is_array($manifestData) && $manifestData["file_data"]) {

        $manifest = json_decode($manifestData["file_data"]);
      }
    }
    return $manifest;
  }

  public function deleteBackupManifest($md5_json)
  {
    if (BMI_DEBUG) {
      Logger::error("deleteBackupManifest($md5_json)");
    }

    $this->deleteFile($md5_json);
  }

  public function initiateUploadSession($file_path)
  {

    $uri = home_url();
    if (substr($uri, 0, 4) != 'http') {
      if (is_ssl()) $uri = 'https://' . home_url();
      else $uri = 'http://' . home_url();
    }

    $file_name = basename($file_path);

    if (BMI_DEBUG) {
      Logger::error("[BMI PRO][BackupBliss] initiateUploadSession - $file_path - $file_name");
    }


    $url = 'file/initiate-upload-session';
    $body = [
      'filename' => $file_name,
      'site_url' => $uri,
      'file_size' => filesize($file_path)
    ];

    $response = $this->_makeApiCall($url, "POST", $body);
    if ($response["status"]) {
      $session = $response["response_data"];
      if ($session["status"] && isset($session['upload_id']) && !empty($session['upload_id'])) {
        return $session;
      } else {
        Logger::error("[BMI PRO][BackupBliss] Failed to create upload session: " . json_encode($session));
        return false;
      }
    } else {
      Logger::error("[BMI PRO][BackupBliss] Failed to create upload session: " . $response);
      return false;
    }
  }

  private function uploadChunkWithSession($upload_session, $chunk_data, $start_byte, $end_byte, $total_size)
  {
    if (BMI_DEBUG) {
      // Logger::error("[BMI PRO] BEFORE UPLOAD uploadChunkWithSession(" . $upload_session['uploadUrl'] . ", $start_byte, $end_byte, $total_size" . ")\n" . print_r($headers, true));
    }

    $response = $this->_makeApiCall("file/upload-chunk/".$upload_session['upload_id'], "PUT", $chunk_data);

    if ($response["status"]) {
      if (BMI_DEBUG) {
        // Logger::error("[BMI PRO] AFTER UPLOAD uploadChunkWithSession\n" . print_r($response, true));
      }
      return $response;
    }

    Logger::error("[BMI PRO] Failed to upload chunks. Start byte: $start_byte. End byte: $end_byte. Total Size: $total_size");
    return $response;
  }

  public function getAllFiles()
  {
    $response = $this->_makeApiCall('file/backups');
    if (BMI_DEBUG) {
      // Logger::error("[BMI PRO] getAllBackups " . print_r($response, true));
    }
    if ($response["status"] && $response["response_data"]["status"]) {
      $files = $response["response_data"];
      return $files['backups'];
    }

    return false;
  }

  private function downloadFile($file_details, $start_byte = 0, $end_byte = null)
  {
    if (BMI_DEBUG) {
      //Logger::error("downloadFile(" . print_r($file_details, true) . ", $start_byte, $end_byte)");
    }
    if (!isset($file_details['download_hash'])) {
      Logger::error('[BMI PRO] Download URL not available in the file details.');
      return false;
    }

    $headers = [];

    // Set the Range header for partial download
    if ($end_byte !== null) {
      $headers = [
        'Range: bytes=' . $start_byte . '-' . $end_byte
      ];
    } else {
      $headers = [
        'Range: bytes=' . $start_byte . '-'
      ];
    }

    $response = $this->_makeApiCall("file/download/".$file_details["download_hash"], "GET", [], $headers);

    if ($response["status"]) {
      return $response["response_data"];
    }

    return false;
  }

  public function getFile($file_name, $start_byte = 0, $end_byte = null)
  {
    if (BMI_DEBUG) {
      Logger::error("getFile $file_name");
    }
    $file_detail = $this->getFileDetailByName($file_name);
    if ($file_detail) {
      return ["file_detail" => $file_detail, "file_data" => $this->downloadFile($file_detail, $start_byte, $end_byte)];
    }
    return false;
  }

  public function getStorageInfo()
  {
    $response = $this->_makeApiCall("file/storage-info");

    if (BMI_DEBUG) {
      // Logger::error("getStorageInfo - " . print_r($response, true));
    }

    if ($response["status"] && $response["response_data"]["status"]) {
      return $response["response_data"]["storage_info"];
    }

    return false;
  }


  public function uploadFile($uploadSession, $filePath, $manifestPath, $md5, $batch, $bytesPerRequest)
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

    # Microsoft recommended multiple value 320Kb
    $chunkSize = 4 * 327680 * intval($bytesPerRequest / 1024 / 1024);

    # Limit the chunk size to max of 50MB with multiple of recommended value
    $maxChunkSize = 50 * 1024 * 1024;
    $chunkSize = $chunkSize > $maxChunkSize ? $maxChunkSize : $chunkSize;

    $chunkOffset = (($batchNumber - 1) * $chunkSize);
    $rangeEnd = (($chunkSize * $batchNumber) - 1);

    if ($rangeEnd >= $maxLength) $rangeEnd = $maxLength - 1;
    if (($chunkSize + $chunkOffset) >= $maxLength) $chunkSize = $rangeEnd - $chunkOffset + 1;
    $nextShouldStartAt = $rangeEnd + 1;


    if ($stream = fopen($filePath, 'r')) {
      $binaryData = stream_get_contents($stream, $chunkSize, $chunkOffset);
      fclose($stream);
    }


    $toBeUploaded = get_option('bmip_to_be_uploaded', false);

    if (isset($toBeUploaded['current_upload']['verifying'])) {
      if (!$this->getFileDetailByName($md5 . '.json') || !$this->getFileDetailByName(basename($filePath))) {
        $task = $toBeUploaded['current_upload']['task'];
        $toBeUploaded['current_upload'] = [];
        if (!isset($toBeUploaded['failed'])) $toBeUploaded['failed'] = [];
        $toBeUploaded['failed'][$task] = 1;

        update_option('bmip_to_be_uploaded', $toBeUploaded);
        return ['status' => 'fail', 'data' => 'File not found on server during verification.'];
      }
      $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);
      if (!isset($uploadedBackupStatus[$md5])) {
        $uploadedBackupStatus[$md5] = [];
      }
      $uploadedBackupStatus[$md5]['backupbliss'] = true;
      update_option('bmi_uploaded_backups_status', $uploadedBackupStatus);

      $task = $toBeUploaded['current_upload']['task'];
      $toBeUploaded['current_upload'] = [];
      if (!isset($toBeUploaded['failed'])) $toBeUploaded['failed'] = [];
      if (isset($toBeUploaded['failed'][$task])) unset($toBeUploaded['failed'][$task]);
      update_option('bmip_to_be_uploaded', $toBeUploaded);
      return ['status' => 'success', 'data' => 'File verified successfully.'];
    }

    if (BMI_DEBUG)
    {
      Logger::error("Before uploadFile - " . print_r($uploadSession, true));
      Logger::error("Before uploadFile - " . print_r($toBeUploaded['current_upload']['batch'], true));
    }

    $response = $this->uploadChunkWithSession($uploadSession, $binaryData, $chunkOffset, $rangeEnd, $maxLength);

    $code = intval($response['http_code']);

    if ($response["status"]) {

      if ($rangeEnd == $maxLength - 1) //Last chunk already uploaded so complete upload and upload manifest
      {
        if ($code != 201) //If upload is not already completed
        {
          $response = $this->_makeApiCall('file/complete-upload', "POST", ['upload_id'=>$uploadSession['upload_id']]);
          $code = intval($response['http_code']);
          if ($code != 201) { //Something failed while completing the upload
            $task = $toBeUploaded['current_upload']['task'];
            $toBeUploaded['current_upload'] = [];
            if (!isset($toBeUploaded['failed'])) $toBeUploaded['failed'] = [];
            $toBeUploaded['failed'][$task] = 1;

            update_option('bmip_to_be_uploaded', $toBeUploaded);
            return ['status' => 'fail', 'data' => $response];
          }
        }

        $manifestUploadSession = $this->initiateUploadSession($manifestPath);

        if (!$manifestUploadSession) { //Something failed while completing the upload
          $task = $toBeUploaded['current_upload']['task'];
          $toBeUploaded['current_upload'] = [];
          if (!isset($toBeUploaded['failed'])) $toBeUploaded['failed'] = [];
          $toBeUploaded['failed'][$task] = 1;

          update_option('bmip_to_be_uploaded', $toBeUploaded);
          return ['status' => 'fail', 'data' => $response];
        }

        if ($stream = fopen($manifestPath, 'r')) {
          $binaryData = stream_get_contents($stream);
          fclose($stream);
        }

        $size = strlen($binaryData);
        $manifestRes = $this->uploadChunkWithSession($manifestUploadSession, $binaryData, 0, $size - 1, $size);
        if (
          $manifestRes['status'] == true 
          && intval($manifestRes['http_code']) == 201
        ) {
          $toBeUploaded['current_upload']['verifying'] = true;
          update_option('bmip_to_be_uploaded', $toBeUploaded);
          return ['status' => 'success', 'data' => $response];
        }

        return ['status' => 'fail', 'data' => $manifestRes];
      }

     
      //Chunk accepted, let's continue uploading
      if ($code == 202) {
       
        $task = $toBeUploaded['current_upload']['task'];
        if (!isset($toBeUploaded['failed'])) $toBeUploaded['failed'] = [];
        if (isset($toBeUploaded['failed'][$task])) unset($toBeUploaded['failed'][$task]);

        $toBeUploaded['current_upload']['batch'] = intval($batch) + 1;
        $toBeUploaded['current_upload']['progress'] = number_format(($rangeEnd / $maxLength) * 100, 2) . '%';
        update_option('bmip_to_be_uploaded', $toBeUploaded);

        if (BMI_DEBUG)
          Logger::error("After uploadFile - " . print_r($toBeUploaded['current_upload']['batch'], true));

        $test = get_option('bmip_to_be_uploaded', false);

        if (BMI_DEBUG)
          Logger::error("After updating option uploadFile - " . print_r($test['current_upload']['batch'], true));
      }
    } elseif ($code == 507) {
     
      $error_message_notice = 'Moving backups to your storage is failing or will fail because you don’t have enough space.';

      add_option("bmip_backupbliss_required_space", $filePath);
      $this->showNotice("upload_issue_space", $error_message_notice, 60 * 60);
    } elseif ($code == 508) {
     
      $error_message_notice = 'You’re using more space than allowed. No new backups will be moved to your storage and some of the <b>existing backups will be deleted very soon</b>. ';

      $this->showNotice("upload_issue_space", $error_message_notice, 60 * 60);
    } elseif ($code == 429) {
     
      $error_message_notice = 'Upload to BackupBliss could not finish, due to rate limit error.<br />';
      $error_message_notice .= 'Received message: <i>Too Many Requests in a short amount of time.</i><br />';
      $error_message_notice .= 'Plugin will retry uploading automatically after 2 minutes.<br />';

      $this->showNotice('upload_issue', $error_message_notice, 60 * 2);
    } else {

      Logger::error('[BMI PRO] Error during file upload (BackupBliss) code:' . $code);
      if (isset($response['response_data']) && is_string($response['response_data'])) {
        Logger::error('[BMI PRO] Message received (body):' . print_r($response['response_data'], true));
      }

      $error_message_notice = 'Upload to BackupBliss could not finish, due to an error:<br />';
      if (is_string($response['response_data'])) {
        $error = json_decode($response['response_data']);
        if (isset($error->error->message) || isset($error->message)) {
          $errorMessage = isset($error->error->message) ? $error->error->message : $error->message;
          $error_message_notice .= "Code: $code - Received message:<i>" . $errorMessage . '</i><br />';
        } else if ($error == null) {
          $cleanResponse = strip_tags(trim($response["response_data"]));
          $cleanResponse = preg_replace('/\s+/', ' ', $cleanResponse);
          if (empty($cleanResponse)) {
            $cleanResponse = 'Unknown server error occurred';
          }
          $error_message_notice .= "HTTP Error Response: <i>" . htmlspecialchars($cleanResponse) . '</i><br />';
        }
      }

      if ($code == 0) {
        $error_message_notice .= "Received Message: <i>Connection timed out. (This is most likely due to a connection issue in your server.)</i><br />";
      }



      $error_message_notice .= "Plugin will retry uploading automatically within a minute since this error.";
      $this->showNotice("upload_issue", $error_message_notice, 60);
    }


    if (isset($error_message_notice)) {
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
    return ['status' => 'success', 'data' => $response];
  }

}
