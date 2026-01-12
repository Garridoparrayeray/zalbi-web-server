<?php

namespace BMI\Plugin\External;


// Exit on direct access
if (!defined('ABSPATH')) {
    exit;
}

use BMI\Plugin\BMI_Logger as Logger;
use BMI\Plugin\Dashboard as Dashboard;
use BMI\Plugin\Backup_Migration_Plugin as BMP;
use BMI\Plugin\Scanner\BMI_BackupsScanner as Backups;

/**
 * BMI_External_Dropbox
 * 
 * This class is responsible for handling all Dropbox related operations
 */

class BMI_External_Dropbox
{
    public $dropboxId = 'bmip_dropbox';
    public $dropboxAuthCodeOption = 'bmip_dropbox_auth_code';
    public $dropboxAccessToken = 'bmip_dropbox_access_token';
    public $dropboxApiUrl = 'https://api.dropboxapi.com/2/';
    public $dropboxContentUrl = 'https://content.dropboxapi.com/2/';

    public function __construct()
    {
        add_action('bmi_premium_remove_backup_file', [&$this, 'deleteDropboxBackup']);
        add_action('bmi_premium_remove_backup_json_file', [&$this, 'deleteDropboxBackupJson']);
        add_action('delete_transient_bmip_dropbox_issue', [&$this, 'deleteDropboxIssue']);
    }

    public function deleteDropboxIssue()
    {
        delete_option('bmip_dropbox_correct_offset');
        delete_option('bmip_dropbox_required_space');
        delete_option('bmip_dropbox_dismiss_issue');
    }

    /**
     * request make a request to Dropbox API using cURL
     * @param string $endpoint
     * @param array|string $params
     * @param array $headers
     * @param string $format "rpc" or "content" for different Dropbox API endpoints
     * @param array $loggingData data to be logged
     * @return string response from the request or "error" if error
     */
    public function request($endpoint, $params = array(), $headers = array(), $format = 'rpc', $loggingData = array())
    {

        $accessToken = get_transient($this->dropboxAccessToken);

        if (get_transient('bmip_dropbox_issue') == 'auth_error' || !$accessToken) {
            $accessToken = $this->configureAccessToken(get_transient('bmip_dropbox_issue') == 'auth_error' && $accessToken);
            if ($accessToken !== false && get_transient('bmip_dropbox_issue') == 'auth_error') delete_transient('bmip_dropbox_issue');
        }


        if (!$accessToken) {
            if (in_array(get_transient('bmip_dropbox_issue'), ['auth_error', false])) {
                set_transient('bmip_dropbox_issue', 'auth_error_disconnected');
            }
            return "error";
        }


        $headers[] = 'Authorization: Bearer ' . $accessToken;


        $ch = curl_init();
        $apiUrl = $format == 'rpc' ? $this->dropboxApiUrl : $this->dropboxContentUrl;
        $timeout = $format == 'rpc' ? 100 : 300;
        curl_setopt($ch, CURLOPT_URL, $apiUrl . $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    
        curl_setopt($ch, CURLOPT_POST, true);
        if ($params) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($params) ? $params : json_encode($params));
        }

        // @see https://stackoverflow.com/questions/35031236/could-not-access-dropbox-api-via-parse-cloud-code-although-works-with-curl
        if ($endpoint == 'users/get_space_usage') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(null));
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_message = curl_error($ch);
            Logger::error('[BMI PRO] Something went wrong with cURL request: ' . $error_message);
            return 'error';
        }
    
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $retryAfter = BMP::getRetryAfterIfAvailable($ch, $response);

        curl_close($ch);

        $data = array(
            'response' => $response,
            'code' => $code,
            'loggingData' => $loggingData,
            'retryAfter' => $retryAfter
        );
        $this->afterProcess($data);

        if (!in_array($code, [200, 206])) {
            return 'error';
        }
    
        return $response;
    }

    /**
     * createFolder create a folder in Dropbox
     * @param string $folderName full path of the folder
     * @return false|string false if error, folder id if success
     */
    public function createFolder($folderName)
    {
        $params = array(
            'path' => '/' . $folderName,
            'autorename' => false
        );

        $getFolderMeta = $this->getFileMeta($folderName); // Avoid already existing folder error
        if ($getFolderMeta) {
            return $getFolderMeta['id'];
        }

        $headers = array(
            'Content-Type: application/json'
        );

        $response = $this->request('files/create_folder_v2', $params, $headers);

        if ($response === 'error') {
            return false;
        }

        $response = json_decode($response, true);

        return $response['metadata']['id'];
    }

    /**
     * getFileMeta get the file id of a file in Dropbox
     * @param string $fileName name of file or file id of the file in Dropbox
     * @return false|array false if error, array of metadata if success
     */
    public function getFileMeta($fileName)
    {
        $isId = (substr($fileName, 0, 3) == 'id:');
        if (!$isId && $fileName[0] != '/') {
            $fileName = '/' . $fileName;
        }

        $params = array(
            'path' => $fileName
        );

        $headers = array(
            'Content-Type: application/json'
        );

        $response = $this->request('files/get_metadata', $params, $headers);

        if ($response === 'error') {
            return false;
        }

        $response = json_decode($response, true);

        return $response;
    }

    /**
     * deleteFile delete a file/folder in Dropbox 
     *   if it is a folder, all files inside the folder will be deleted
     * @param string $fileName name of file or file id of the file in Dropbox
     * @return bool true if success, false if error
     */
    public function deleteFile($fileName)
    {
        $isId = (substr($fileName, 0, 3) == 'id:');
        if (!$isId && $fileName[0] != '/') {
            $fileName = '/' . $fileName;
        }

        $params = array(
            'path' => $fileName
        );

        $headers = array(
            'Content-Type: application/json'
        );

        $response = $this->request('files/delete_v2', $params, $headers);

        if ($response === 'error') {
            return false;
        }

        return true;
    }

    /**
     * download get the content of a file in Dropbox
     * @param string $fileName name of file or file id of the file in Dropbox
     * @param string $range range of the file to download (optional) e.g. '0-100' for first 100 bytes
     * @return false|string false if error, string response if success (content of the file)
     */
    public function getFileContent($fileName, $range = '')
    {
        $isId = (substr($fileName, 0, 3) == 'id:');
        if (!$isId && $fileName[0] != '/') {
            $fileName = '/' . $fileName;
        }

        $headers = array(
            'Dropbox-API-Arg: {"path": "' . $fileName . '"}',
            'Content-Type: text/plain'
        );

        if ($range) {
            $headers[] = 'Range: bytes=' . $range;
        }

        $response = $this->request('files/download', array(), $headers, 'content');

        if ($response === 'error') {
            return false;
        }

        return $response;
    }

    /**
     * listFiles list all files in a folder in Dropbox
     * @return false|array[] false if error, array of entries if success
     * @see https://www.dropbox.com/developers/documentation/http/documentation#files-list_folder
     */
    public function listFiles()
    {

        $params = array(
            'path' => '',
            'include_non_downloadable_files' => false
        );

        $headers = array(
            'Content-Type: application/json'
        );

        $response = $this->request('files/list_folder', $params, $headers);

        if ($response === 'error') {
            return false;
        }
        
        $response = json_decode($response, true);
        
        $entries = $response['entries'];

        if (isset($response['has_more']) && $response['has_more'] === true) { // In Almost all cases, this will be false
            $cursor = $response['cursor'];
            $entries = array_merge($entries, $this->listFilesContinue($cursor));
        }

        return $entries;
    }

    private function listFilesContinue($cursor)
    {
        $params = array(
            'cursor' => $cursor
        );

        $headers = array(
            'Content-Type: application/json'
        );

        $response = $this->request('files/list_folder/continue', $params, $headers);

        if ($response === 'error') {
            return false;
        }

        $files = [];
        $response = json_decode($response, true);

        foreach ($response['entries'] as $entry) {
            $files[] = $entry['name'];
        }

        if (isset($response['has_more']) && $response['has_more'] === true) {
            $cursor = $response['cursor'];
            $files = array_merge($files, $this->listFilesContinue($cursor));
        }

        return $files;
    }


    /**
     * startUploadSession start an upload session in Dropbox
     * 
     * @return false|string false if error, session id if success
     */
    public function startUploadSession()
    {

        $header = array(
            'Dropbox-API-Arg: {"close": false}',
            'Content-Type: application/octet-stream'
        );

        $response = $this->request('files/upload_session/start', array(), $header, 'content');

        if ($response === 'error') {
            return false;
        }

        $response = json_decode($response, true);

        return $response['session_id'];
    }

    /**
     * uploadChunk upload a chunk of a file to Dropbox using upload session 
     * 
     * @param string $sessionId valid session id
     * @param string $filePath full path of the file
     * @param int $offset offset of the file
     * @return false|int false if error, size of the chunk uploaded if success
     */
    public function uploadChunk($sessionId, $filePath, $offset, $maxRetries = 3)
    {
        if (!file_exists($filePath)) {
            Logger::error('[BMI PRO] File not found: ' . $filePath);
            return false;
        }

        $fileSize = filesize($filePath);
        $availableMemory = BMP::getAvailableMemoryInBytes();
        
        if (($availableMemory / 4) < 4194304) {
            $response = [
                'error_summary' => 'not_enough_memory'
            ];
            $this->errorHandler($response, 500);
            Logger::error('[BMI PRO] Not enough memory to upload file: ' . $filePath);
            return false;
        }
    
        $chunkSize = min($availableMemory / 4, 10485760); // Max 10MB
        $chunkSize = $chunkSize - ($chunkSize % 4194304); // Round down to nearest multiple of 4MB
    
        $retryCount = 0;
        while ($retryCount < $maxRetries) {
            if ($offset + $chunkSize > $fileSize) {
                $chunkSize = $fileSize - $offset;
            }
    
            $header = array(
                'Dropbox-API-Arg: {"cursor": {"session_id": "' . $sessionId . '", "offset": ' . $offset . '}, "close": ' . ($offset + $chunkSize == $fileSize ? 'true' : 'false') . '}',
                'Content-Type: application/octet-stream'
            );

            if (($stream = fopen($filePath, 'r')) && $offset < $fileSize) {
                fseek($stream, $offset);
                $chunk = fread($stream, $chunkSize);
                fclose($stream);
            } else {
                Logger::error('[BMI PRO] Could not open file: ' . $filePath);
                return false;
            }

            $response = $this->request('files/upload_session/append_v2', $chunk, $header, 'content');

            if ($response === 'error') {
                $issue = get_transient('bmip_dropbox_issue');
                if ($issue == 'incorrect_offset') {
                    $correctOffset = get_option('bmip_dropbox_correct_offset', false);
                    if ($correctOffset) {
                        $offset = $correctOffset;
                        delete_option('bmip_dropbox_correct_offset');
                        $retryCount++;
                        continue;
                    }
                }
                return false;
            }
    
            return $offset + $chunkSize;
        }
        Logger::error('[BMI PRO] Max retries reached for uploading chunk');
        return false;    
    }


    /**
     * finishUpload finish the upload session of a file in Dropbox
     * 
     * @param string $sessionId valid session id
     * @param string $filePathOnDropbox full path of the file
     * @param int $offset offset of the file
     * @return false|string false if error, file id if success
     */
    public function finishUpload($sessionId, $filePathOnDropbox, $offset)
    {
        $filePathOnDropbox = '/' . basename($filePathOnDropbox);

        $header = array(
            'Dropbox-API-Arg: {"cursor": {"session_id": "' . $sessionId . '", "offset": ' . $offset . '}, "commit": {"path": "' . $filePathOnDropbox . '", "mode": "add", "autorename": true, "mute": false}}',
            'Content-Type: application/octet-stream'
        );

        $loggingData = array(
            'fileSize' => $offset
        );

        $response = $this->request('files/upload_session/finish', array(), $header, 'content', $loggingData);

        if ($response === 'error') {
            return false;
        }

        $response = json_decode($response, true);

        return $response['id'];
    }

    /**
     * uploadFile upload a file to Dropbox
     * used for files less than 10MB
     * 
     * @param string $filePath full path of the file
     * @return false|string false if error, file id if success
     */
    public function uploadFile($filePath)
    {
        if (file_exists($filePath)) {
            $fileSize = filesize($filePath);
        } else {
            Logger::error('[BMI PRO] File not found: ' . $filePath);
            return false;
        }

        if ($fileSize > 10485760) { // 10MB
            Logger::error('[BMI PRO] File size is greater than 10MB: ' . $filePath);
            return false;
        }

        $filePathOnDropbox = '/' . basename($filePath);

        $header = array(
            'Dropbox-API-Arg: {"path": "' . $filePathOnDropbox . '"}',
            'Content-Type: application/octet-stream'
        );

        if ($stream = fopen($filePath, 'r')) {
            $params = stream_get_contents($stream);
            fclose($stream);
        } else {
            Logger::error('[BMI PRO] Could not open file: ' . $filePath);
            return false;
        }

        $loggingData = array(
            'fileSize' => $fileSize
        );

        $response = $this->request('files/upload', $params, $header, 'content', $loggingData);

        if ($response === 'error') {
            return false;
        }

        $response = json_decode($response, true);

        return $response['id'];

    }

    /**
     * afterProcess handle the response of a request to Dropbox API
     *   if success, clear any previous issues
     *   if error, handle the error response
     * 
     * @param array $data response data from request in format ['code' => int, 'response' => string, 'retryAfter' => int, 'loggingData' => array]
     * @return void
     */
    public function afterProcess($data)
    {
        $code = $data['code'];
        $response = json_decode($data['response'], true);
        $retryAfter = $data['retryAfter'];
        $fileSize = isset($data['loggingData']['fileSize']) ? $data['loggingData']['fileSize'] : null;
        if (!in_array($code, [200, 206])) {
            $this->errorHandler($response, $code, $retryAfter, $fileSize);
        } 

    }

    /**
     * errorHandler handle the error response of a request to Dropbox API
     * 
     * @param array $response response data
     * @param int $code response code
     * @param int $retryAfter retry after time
     * @param int $fileSize file size (optional) for insufficient space error
     * @return void
     * @see https://www.dropbox.com/developers/documentation/http/documentation#error-handling
     */
    public function errorHandler($response, $code, $retryAfter = HOUR_IN_SECONDS, $fileSize = null)
    {
        switch ($code) {
        case 401:
            Logger::debug('[BMI PRO] Unauthorized access to Dropbox API: ' . json_encode($response));
            set_transient('bmip_dropbox_issue', 'auth_error', HOUR_IN_SECONDS);
            break;
        case 403:
            Logger::debug('[BMI PRO] Forbidden access to Dropbox API: ' . json_encode($response));
            set_transient('bmip_dropbox_issue', 'forbidden', HOUR_IN_SECONDS);
            break;
        case 500:
            Logger::debug('[BMI PRO] Internal server error: ' . json_encode($response));
            set_transient('bmip_dropbox_issue', 'internal_error', HOUR_IN_SECONDS);
            break;
        case 429:
            Logger::debug('[BMI PRO] Too many requests to Dropbox API. Retry after: ' . $retryAfter);
            set_transient('bmip_dropbox_issue', 'rate_limit', $retryAfter);
            break;
        case 409:
            Logger::debug('[BMI PRO] Conflict in Dropbox API: ' . json_encode($response));
            if (isset($response['error_summary']) && strpos($response['error_summary'], 'incorrect_offset') !== false) {
                set_transient('bmip_dropbox_issue', 'incorrect_offset', HOUR_IN_SECONDS);
                $correctOffset = false;
                if (isset($response['error']['correct_offset'])) $correctOffset = $response['error']['correct_offset'];
                elseif (isset($response['error']['lookup_failed']['correct_offset'])) $correctOffset = $response['error']['lookup_failed']['correct_offset'];
                if ($correctOffset !== false && is_int($correctOffset)) update_option('bmip_dropbox_correct_offset', $correctOffset);
            } 
            if (isset($response['error_summary']) && strpos($response['error_summary'], 'path_lookup') !== false || strpos($response['error_summary'], 'path_not_found') !== false || strpos($response['error_summary'], 'folder_not_found') !== false) {
                set_transient('bmip_dropbox_issue', 'path_lookup', HOUR_IN_SECONDS);
            }
            if (isset($response['error_summary']) && strpos($response['error_summary'], 'insufficient_space') !== false) {
                set_transient('bmip_dropbox_issue', 'insufficient_space', HOUR_IN_SECONDS);
                if ($fileSize) {
                    update_option('bmip_dropbox_required_space', $fileSize);
                }
            }
            break;
        default:
            Logger::debug('[BMI PRO] Unknown error in Dropbox API: ' . (is_string($response) ? $response : json_encode($response)));
            break;
        }
        update_option('bmip_dropbox_dismiss_issue', false);
    }

    /**
     * getParsedFiles get the list of files in Dropbox folder and their metadata (JSON files)
     * 
     * @return array[]|bool compact array of zip files and json files if success, false if error
     *                  format: ['zipFilesName' => ['filename.zip' => ['id' => 'file_id', 'size' => 'file_size']], 'jsonFilesPath' => ['path_lower']]
     */ 
    public function getParsedFiles()
    {
        $files = $this->listFiles();
        if ($files === false) return false;
        $zipFilesName = [];
        $jsonFilesPath = [];
        foreach ($files as $file) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (in_array($ext, ['zip', 'tar', 'gz'])) {
                $zipFilesName[$file['name']] = ['id' => $file['id'], 'size' => $file['size']];
            } else if (strpos($file['name'], '.json') !== false) {
                $jsonFilesPath[] = $file['path_lower'][0] == '/' ? substr($file['path_lower'], 1) : $file['path_lower'];
            }

        }
        return compact('zipFilesName', 'jsonFilesPath');
    }

    /**
     * getAvailableSpace get the space usage of Dropbox account
     * 
     * @return false|array false if error, array of space usage if success
     */
    public function getSpaceUsage()
    {

        $header = array(
            'Content-Type: application/json'
        );

        $response = $this->request('users/get_space_usage', array(), $header);

        if ($response === 'error') {
            return false;
        }

        $response = json_decode($response, true);
        
        return $response;
    }


    /************************************************************************************************************* */
    /*********************  DELETE DROPBOX BACKUP  **************************************************************** */
    /************************************************************************************************************* */
    public function deleteDropboxBackup($md5){
        if ($this->verifyConnection()['result'] != 'connected') {
            return false;
        }

        $manifestFile = $md5 . '.json';
        if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . $manifestFile)) {
            $manifestContent = json_decode(file_get_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . $manifestFile), true);
        } else {
            $manifestContent = json_decode($this->getFileContent('/' . $manifestFile), true);
        }
        if ($manifestContent == false) {
            return false;
        }
        $backupName = $manifestContent['name'];
        $deleteManifest = $this->deleteFile($manifestFile);
        $deleteZip = $this->deleteFile('/' . $backupName);
        if ($deleteManifest && $deleteZip) {
            return true;
        }
        return false;    
    }

    public function deleteDropboxBackupJson($manifestFile){
        if ($this->verifyConnection()['result'] != 'connected') {
            return false;
        }

        $deleteManifest = $this->deleteFile('/' . $manifestFile);
        if ($deleteManifest) {
            return true;
        }
        return false;
    }



    /************************************************************************************************************* */
    /*********************  Dropbox Plugin Functions  ************************************************************ */
    /************************************************************************************************************* */


    /**
     * uploadDropboxBackup - Uploads a backup to Dropbox
     * @param string $sessionId - session id of the upload
     * @param string $backupName - name of the backup to upload
     * @param int $offset - offset of the file to upload
     * @param string $md5 - md5 hash of the backup to get the manifest file
     * @return array explain the status of the upload process in format
     *    [
     *      'status' => 'finished' | 'error' | 'continue',
     *      (status == 'continue') ? 'offset' => int : null,
     *      (status == 'error') ? 'error' => 'internal_file_not_found' | 'not_enough_memory' | 'could_not_start_session' | 'could_not_upload_chunk' | 'could_not_finish_upload' | 'could_not_upload_backup_in_one_go' | 'insufficient_space' | 'could_not_upload_manifest' : null
     *    ]
     */
    public function uploadDropboxBackup($sessionId, $backupName, $offset, $md5)
    {
        $backupPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupName;
        $manifestPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $md5 . '.json';

        if (!file_exists($backupPath) || !file_exists($manifestPath)) {
            Logger::error('[BMI PRO] File not found: ' . $backupName);
            return [
              'status' => 'error',
              'error' => 'internal_file_not_found'
            ];
        }

        $spaceUsage = $this->getSpaceUsage();
        if ($spaceUsage === false) {
            return [
              'status' => 'error',
              'error' => 'could_not_get_space_usage'
            ];
        }
        $availableSpace = $spaceUsage['allocation']['allocated'] - $spaceUsage['used'];

        if ($availableSpace < filesize($backupPath)) {
            Logger::error('[BMI PRO] Not enough space to upload file: ' . $backupName);
            update_option('bmip_dropbox_dismiss_issue', false);
            update_option('bmip_dropbox_required_space', filesize($backupPath));
            set_transient('bmip_dropbox_issue', 'insufficient_space', HOUR_IN_SECONDS);
            return [
              'status' => 'error',
              'error' => 'insufficient_space'
            ];
        }

        if ($sessionId == '') {
            $fileSize = filesize($backupPath);
            $availableMemory = BMP::getAvailableMemoryInBytes();

            if (($availableMemory / 4) < 4194304) {
                Logger::error('[BMI PRO] Not enough memory to upload file: ' . $backupName);
                update_option('bmip_dropbox_dismiss_issue', false);
                set_transient('bmip_dropbox_issue', 'not_enough_memory', HOUR_IN_SECONDS);
                return [
                  'status' => 'error',
                  'error' => 'not_enough_memory'
                ];
            }

            if (($availableMemory / 4) <= $fileSize && $fileSize < 10485760) {
                $uploadResult = $this->uploadFile($backupPath);
                if ($uploadResult) {
                    $manifestUploadResult = $this->uploadFile($manifestPath);
                    if ($manifestUploadResult) return ['status' => 'finished'];
                    else return ['status' => 'error', 'error' => 'could_not_upload_manifest'];
                } else {
                    return ['status' => 'error', 'error' => 'could_not_upload_backup_in_one_go'];
                }
            }
            if ($sessionId == ''){
              $sessionId = $this->startUploadSession();
            }
            if ($sessionId === false) {
                return [
                  'status' => 'error',
                  'error' => 'could_not_start_session'
                ];
            }
            return [
              'status' => 'continue',
              'offset' => 0,
              'sessionId' => $sessionId
            ];
        } else {
            if ($offset < filesize($backupPath)){
              $newOffset = $this->uploadChunk($sessionId, $backupPath, $offset);
              if ($newOffset === false) {
                  return [
                    'status' => 'error',
                    'error' => 'could_not_upload_chunk'
                  ];
              }
              return [
                'status' => 'continue',
                'offset' => $newOffset
              ];
            } else {
              $fileId = $this->finishUpload($sessionId, $backupPath, $offset);
              if ($fileId) {
                  $manifestUploadResult = $this->uploadFile($manifestPath);
                  if ($manifestUploadResult) return ['status' => 'success'];
                  else return ['status' => 'error', 'error' => 'could_not_upload_manifest'];
              } else {
                  return ['status' => 'error', 'error' => 'could_not_finish_upload'];
              }
            }

        }

    }

    /**
     * checkForBackupsToUploadToDropbox - Checks for backups to upload to Dropbox
     * update bmip_to_be_uploaded option with the list of backups to upload
     * 
     * @return array explain the status of the upload process in format
     *    [
     *      'status' => 'success'
     *    ]
     */
    public function checkForBackupsToUploadToDropbox() {

        $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::DROPBOX');
        if (!($isEnabled === true || $isEnabled === 'true')) {
          return ['status' => 'not_enabled'];
        }
    
        $requiresUpload = get_option('bmip_to_be_uploaded', [
          'current_upload' => [],
          'queue' => [],
          'failed' => []
        ]);
    
        require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'scanner' . DIRECTORY_SEPARATOR . 'backups.php';
        $backups = new Backups();
        $backupsAvailable = $backups->getAvailableBackups("local");
        $localBackups = $backupsAvailable['local'];
        $parsedDropboxFiles = $this->getParsedFiles();
        if($parsedDropboxFiles === false) return ['status' => 'error'];
        $backupsFileName = isset($parsedDropboxFiles['zipFilesName']) ? $parsedDropboxFiles['zipFilesName'] : [];
        $manifestFilesPath = isset($parsedDropboxFiles['jsonFilesPath']) ? $parsedDropboxFiles['jsonFilesPath'] : [];
        $availableManifests = array_map(function($path) {
          return pathinfo($path, PATHINFO_FILENAME);
        }, $manifestFilesPath);
        $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);

   
        
        foreach($localBackups as $name => $details) {
          $md5 = $details[7];
          if (isset($uploadedBackupStatus[$md5]) && isset($uploadedBackupStatus[$md5]['dropbox'])) {
              continue;
          }
          $isBackupNotExists = !in_array($md5, $availableManifests) || !in_array($name, array_keys($backupsFileName));
          if ($isBackupNotExists && !(isset($requiresUpload['current_upload']['task']) && $requiresUpload['current_upload']['task'] == 'dropbox_' . $md5)) {
            $requiresUpload['queue']['dropbox_' . $md5] = [
                'name' => $name,
                'md5'  => $details[7],
                'json' => $details[7] . '.json'
            ];
          }
        }
  
        update_option('bmip_to_be_uploaded', $requiresUpload);
        return ['status' => 'success'];
      }
  
    /**
     * restartUpload - Restarts the upload process of a backup to Dropbox. 
     * 
     * This function clears the current and failed Dropbox uploads, then checks for any backups that need to be uploaded again. (Instead of deleting uploads option for all external storages)
     * 
     * @return array explain the status of the upload process in format
     *   [
     *      'status' => 'success'
     *   ]
     */
    public function restartUploadprocess() {
        $requiredToUpload = get_option('bmip_to_be_uploaded', [
            'current_upload' => [],
            'queue' => [],
            'failed' => []
        ]);
    
        if (isset($requiredToUpload['current_upload']['task']) && strpos($requiredToUpload['current_upload']['task'], 'dropbox') !== false) {
            unset($requiredToUpload['current_upload']);
        }
    
        foreach ($requiredToUpload['failed'] as $key => $value) {
            if (strpos($key, 'dropbox') !== false) {
                unset($requiredToUpload['failed'][$key]);
            }
        }
    
        update_option('bmip_to_be_uploaded',  $requiredToUpload);
        return $this->checkForBackupsToUploadToDropbox();
    }
      
          
  
    /************************************************************************************************************* */
    /*********************  Dropbox Authorization Functions  ***************************************************** */
    /************************************************************************************************************* */

    /**
     * configureAccessToken - Configures the access token for Dropbox
     * 
     * @param bool $forceGetNewAccessToken - force to get a new access token
     * 
     * @return string|bool access token if success, false if error
     */
    public function configureAccessToken($forceGetNewAccessToken = false)
    {
        $uri = home_url();
        if (substr($uri, 0, 4) != 'http') {
          if (is_ssl()) $uri = 'https://' . home_url();
          else $uri = 'http://' . home_url();
        }
        $authorizationCode = get_option($this->dropboxAuthCodeOption, '');
        $dropboxId = get_option($this->dropboxId, '');
        $issue = get_transient('bmip_dropbox_issue');
    
        $url = 'https://authentication.backupbliss.com/v1/dropbox/token';
        $response = wp_remote_post($url, array(
          'method' => 'POST',
          'timeout' => 15,
          'redirection' => 2,
          'httpversion' => '1.0',
          'blocking' => true,
          'body' => array(
            'client_id' => $authorizationCode,
            'site_token' => $dropboxId,
            'force_refresh' => $forceGetNewAccessToken,
            'redirect_uri' => $uri
          )
        ));
    
        if (is_wp_error($response)) {
          $error_message = $response->get_error_message();
          Logger::error('[BMI PRO] Something went wrong during getting dropbox token:' . $error_message);
          return false;
        } else {
          $result = json_decode($response['body']);
          if (isset($result->expiration) && isset($result->access_token)) {
            $expiresInSeconds = intval($result->expiration) - intval(microtime(true));
            $accessToken = $result->access_token;
            set_transient($this->dropboxAccessToken, $accessToken, $expiresInSeconds);
            if (in_array($issue, ['auth_error', 'auth_error_disconnected'])) delete_transient('bmip_dropbox_issue');
            return $accessToken;
          }
          if ($issue == 'auth_error') set_transient('bmip_dropbox_issue', 'auth_error_disconnected');
          return false;
        }
    }


    /**
     * verifyDropboxConnection - Checks if the Dropbox is still granted and tokens are not expired
     * 
     * @param bool $forceGetNewAccessToken - force to get a new access token
     * 
     * @return array explain the status of the connection in format
     *   [
     *    'status' => 'success' | 'error',
     *    'result' => 'connected' | 'disconnected' 
     *  ]
     */
    public function verifyConnection( $forceGetNewAccessToken = false ) {

        $tempKeyDropboxFile = BMI_TMP . DIRECTORY_SEPARATOR . 'dropboxKeys.php';
        if (file_exists($tempKeyDropboxFile)) {
          $dropboxKeys = file_get_contents($tempKeyDropboxFile);
          if (strpos($dropboxKeys, "\n") !== false) {
            $lines = explode("\n", $dropboxKeys);
            if (sizeof($lines) == 4) {
                $dropboxId = substr($lines[1], 2);
                $dropboxAuthCode = substr($lines[2], 2);
                if (function_exists('wp_load_alloptions')) {
                    wp_load_alloptions(true);
                }
                delete_option($this->dropboxId);
                delete_option($this->dropboxAuthCodeOption);
                if (function_exists('wp_load_alloptions')) {
                    wp_load_alloptions(true);
                }
                update_option($this->dropboxId, $dropboxId);
                update_option($this->dropboxAuthCodeOption, $dropboxAuthCode);
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

          unlink($tempKeyDropboxFile);
        }

        $baseurl = home_url();
        if (substr($baseurl, 0, 4) != 'http') {
          if (is_ssl()) $baseurl = 'https://' . home_url();
          else $baseurl = 'http://' . home_url();
        }
  
        $dropboxAuthCode = get_option($this->dropboxAuthCodeOption, '');
        $dropboxId = get_option($this->dropboxId, '');
        $currentAccessToken = get_transient($this->dropboxAccessToken);
        $issue = get_transient('bmip_dropbox_issue');

    
        $url = 'https://authentication.backupbliss.com/v1/dropbox/verify';
        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'timeout' => 15,
            'redirection' => 2,
            'httpversion' => '1.0',
            'blocking' => true,
            'body' => array(
                'client_id' => $dropboxAuthCode,
                'site_token' => $dropboxId,
                'force_refresh' => $forceGetNewAccessToken || ($issue == 'auth_error' && $currentAccessToken),
                'redirect_uri' => $baseurl
            )
        ));
    
        $res = 'disconnected';
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            Logger::error('[BMI PRO] Something went wrong during Dropbox connection verification:' . $error_message);
            return [ 'status' => 'error', 'result' => 'disconnected' ];
        } else {
            $result = json_decode($response['body']);
            if (isset($result->status)) {
                if (isset($result->expiration) && isset($result->access_token)) {
                    $expiresInSeconds = intval($result->expiration) - intval(microtime(true));
                    $accessToken = $result->access_token;
                    set_transient($this->dropboxAccessToken, $accessToken, $expiresInSeconds);
                }
                if ($result->status == 'disconnected' && BMI_DEBUG) {
                    Logger::error('[BMI PRO] Dropbox connection is disconnected in order to this response: ' . json_encode($result));
                }
                if ($result->status == 'disconnected') $res = 'disconnected';
                if ($result->status == 'connected') $res = 'connected';
                if ($result->status == 'error') $res = 'disconnected';
            }

            if ($res == 'disconnected' && $issue == 'auth_error') set_transient('bmip_dropbox_issue', 'auth_error_disconnected');
            else if ($res == 'connected' && in_array($issue, ['auth_error', 'auth_error_disconnected'])) delete_transient('bmip_dropbox_issue');
            return [ 'status' => 'success', 'result' => $res ];
        }
  
      }


      /**
       * disconnect - Removes the Dropbox connection
       * 
       * @return array explain the status of the connection in format
       *  [
       *   'status' => 'success' | 'error'
       * ]
       */
      public function disconnect() {
        $baseurl = home_url();
        if (substr($baseurl, 0, 4) != 'http') {
          if (is_ssl()) $baseurl = 'https://' . home_url();
          else $baseurl = 'http://' . home_url();
        }

        $dropboxAuthCode = get_option($this->dropboxAuthCodeOption, '');
        $dropboxId = get_option($this->dropboxId, '');
  
        $url = 'https://authentication.backupbliss.com/v1/dropbox/disconnect';
        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'timeout' => 15,
            'redirection' => 2,
            'httpversion' => '1.0',
            'blocking' => true,
            'body' => array(
                'client_id' => $dropboxAuthCode,
                'site_token' => $dropboxId,
                'redirect_uri' => $baseurl
            )
        ));
  
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            Logger::error('[BMI PRO] Something went wrong during Dropbox removal process:' . $error_message);
            return [ 'status' => 'error' ];
        }

        return [ 'status' => 'success' ];
  
      }

}
