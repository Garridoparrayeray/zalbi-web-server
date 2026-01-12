<?php

namespace BMI\Plugin\External;

require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 's3-client' . DIRECTORY_SEPARATOR . 's3.php';

use BMI\Plugin\Dashboard;
use BMI\Plugin\BMI_Logger as Logger;
use BMI\Plugin\Scanner\BMI_BackupsScanner as Backups;
use BMI\Plugin\Backup_Migration_Plugin as BMP;

// Exception Class for S3 Client Errors
class S3ClientException extends \Exception
{
}


class S3Client
{
    /** @var S3 */
    private $s3;
    
    /** @var bool */
    private $status = false;
    
    /** @var string */
    private $rootDir = '';
    
    /** @var string */
    private $bucket = '';
    
    /** @var array */
    private $config = array();
    
    /** @var int */
    private $chunkSize = 5242880; // 5MB default chunk size
    
    /**
     * Constructor
     */
    public function __construct($endpoint = 's3.amazonaws.com')
    {
        $this->s3 = new S3(null, null, false, $endpoint);
    }
    
    /**
     * Sets the root directory for the S3 bucket.
     *
     * @param string $rootDir
     * @return $this
     */
    public function setRootDir($rootDir)
    {
        $this->rootDir = BMP::fixSlashes($rootDir);
        return $this;
    }
    
    /**
     * Sets the bucket name for the S3 connection.
     *
     * @param string $bucket
     * @return $this
     */
    public function setBucket($bucket)
    {
        $this->bucket = trim($bucket);
        return $this;
    }

    /**
     * Sets the configuration for the S3 connection.
     */
    
    public function setConfig(array $config)
    {
        $this->config = $config;
        $this->s3->setAuth($config['accessKey'], $config['secretKey']);
        $this->s3->setSSL(true);
        $this->s3->setRegion($config['region']);
        $this->s3->setServerSideEncryption($config['sse']);
        $this->s3->setStorageClass($config['storageClass']);
        $this->bucket = $config['bucket'];
        $this->rootDir = BMP::fixSlashes($config['path']);
        return $this;
    }

    /**
     * Gets the connection status.
     *
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * Sets the connection status.
     *
     * @param bool $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = (bool) $status;
        return $this;
    }

    /**
     * Connects to the S3 server with improved error handling.
     *
     * @param string $accessKey AWS access key
     * @param string $secretKey AWS secret key
     * @param string $bucket S3 bucket name
     * @param string $region AWS region (optional)
     * @param string $storageClass Storage class (default: 'STANDARD')
     * @param string $sse Server-side encryption (optional)
     * @return array Connection status
     * @throws S3ClientException
     */
    public function connect($accessKey, $secretKey, $bucket, $region = '', $storageClass = 'STANDARD', $sse = '')
    {
        if ($this->status) {
            return array('status' => 'connected');
        }

        try {
            // Configure S3 client
            $this->s3->setAuth($accessKey, $secretKey);
            $this->s3->setSSL(true);
            $this->s3->setRegion($region);
            $this->s3->setServerSideEncryption($sse);
            $this->s3->setStorageClass($storageClass);
            
            $this->bucket = $bucket;
            
            // Test connection
            $testResult = $this->s3->getBucket($bucket, null, null, 1);
            if ($testResult === false) {
                throw new S3ClientException($testResult);
            }
            
            $this->status = true;
            return array('status' => 'connected');
            
        } catch (\Exception $e) {
            $this->status = false;
            Logger::error('[S3Client] Connection failed: ' . $e->getMessage());
            
            $message = $e->getMessage();
            if (strpos($message, 'the region') !== false && strpos($message, 'is wrong') !== false) {
                return array('status' => 'error', 'error' => 'Connection failed: The region is wrong.');
            }
            
            return array('status' => 'error', 'error' => 'Connection failed: ' . $message);
        }
    }

    /**
     * Test the S3 connection by performing basic operations
     *
     * @return true|string True on success, error message on failure
     */
    public function testConnection()
    {
        try {
            $this->s3->setExceptions(true);
            $this->s3->getBucket($this->bucket,null, null, 1);
            
            $testKey = '.bmi_permission_test_' . uniqid();
            $testContent = 'test';
            
            $writeTest = $this->s3->putObject($testContent, $this->bucket, $testKey);

            if ($writeTest === false) {
                return 'Failed to write test object.';
            }
            
            $getTest = $this->s3->getObject($this->bucket, $testKey);
            

            if ($getTest->body !== $testContent) {
                return 'Test object content mismatch.';
            }
            
            $deleteTest = $this->s3->deleteObject($this->bucket, $testKey);

            if ($deleteTest === false) {
                return 'Failed to delete test object.';
            }
            
            $this->s3->setExceptions(false);
            return true;

        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (strpos($message, 'the region') !== false && strpos($message, 'is wrong') !== false) {
                return 'The region is wrong.';
            }
            return $message;
        }
    }

    /**
     * Builds a URI for S3 operations with proper path handling
     *
     * @param string $fileName
     * @param string $bucket
     * @param string $path
     * @return string
     */
    private function buildUri($fileName, $bucket = '', $path = '')
    {
        $path = empty($path) ? $this->rootDir : BMP::fixSlashes($path);
        $path = trim($path, '/');
        $fileName = trim($fileName, '/');
        
        return empty($path) ? $fileName : $path . '/' . $fileName;
    }

    /**
     * Starts a multipart upload session.
     *
     * @param string $fileName
     * @param string $bucket
     * @param string $path
     * @param array $metaHeaders Optional meta headers
     * @return string|bool Upload ID or false on failure
     */
    public function startUploadSession($fileName, $bucket = '', $path = '', $metaHeaders = [])
    {
        if (!$this->status) {
            return false;
        }
        $uri = $this->buildUri($fileName, $bucket, $path);
        $bucket = $bucket ? $bucket : $this->bucket;
        return $this->s3->createMultipartUpload($bucket, $uri, 'private', $metaHeaders);
    }

    /**
     * Uploads a chunk of data with improved error handling and memory management
     *
     * @param string $uploadId
     * @param string $fileName
     * @param int $partNumber
     * @param string $data
     * @param string $bucket
     * @param string $path
     * @return string|bool ETag or false on failure
     */
    public function uploadChunk($uploadId, $fileName, $partNumber, $data, $bucket = '', $path = '')
    {
        if (!$this->status) {
            Logger::error('[S3Client] Cannot upload chunk: Not connected');
            return false;
        }

        try {
            $uri = $this->buildUri($fileName, $bucket, $path);
            $bucket = $bucket ? $bucket : $this->bucket;
            
            // Validate chunk size
            $dataSize = strlen($data);
            if ($dataSize > $this->chunkSize * 2) {
                throw new S3ClientException('Chunk size exceeds maximum allowed size');
            }
            
            $result = $this->s3->uploadPart($bucket, $uri, $uploadId, $partNumber, $data);
            
            if ($result === false) {
                throw new S3ClientException('Failed to upload chunk');
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error(sprintf(
                '[S3Client] Failed to upload chunk for file %s (Part %d): %s',
                $fileName,
                $partNumber,
                $e->getMessage()
            ));
            return false;
        }
    }

    /**
     * Ends a multipart upload session.
     *
     * @param string $uploadId
     * @param string $fileName
     * @param array $parts
     * @param bool $success
     * @param string $bucket
     * @param string $path
     * @return bool
     */
    public function endUploadSession($uploadId, $fileName, $parts, $success, $bucket = '', $path = '')
    {
        if (!$this->status) {
            return false;
        }
        $uri = $this->buildUri($fileName, $bucket, $path);
        $bucket = $bucket ? $bucket : $this->bucket;
        if ($success) {
            return $this->s3->completeMultipartUpload($bucket, $uri, $uploadId, $parts);
        }
        return $this->s3->abortMultipartUpload($bucket, $uri, $uploadId);
    }

    /**
     * Deletes a file from S3.
     *
     * @param string $fileName
     * @param string $bucket
     * @param string $path
     * @return bool
     */
    public function deleteFile($fileName, $bucket = '', $path = '')
    {
        if (!$this->status) {
            return false;
        }
        $uri = $this->buildUri($fileName, $bucket, $path);
        $bucket = $bucket ? $bucket : $this->bucket;
        return $this->s3->deleteObject($bucket, $uri);
    }

    /**
     * Lists files in the S3 bucket.
     *
     * @param string $bucket
     * @param string $path
     * @return array|bool
     */
    public function listFiles($bucket = '', $path = '')
    {
        if (!$this->status) {
            return false;
        }
        $bucket = $bucket ? $bucket : $this->bucket;
        $fullPath = $path ? BMP::fixSlashes($path) : $this->rootDir;
        return $this->s3->getBucket($bucket, $fullPath);
    }

    /**
     * Gets file metadata.
     *
     * @param string $fileName
     * @param string $bucket
     * @param string $path
     * @return array|bool
     */
    public function getFileMeta($fileName, $bucket = '', $path = '')
    {
        if (!$this->status) {
            return false;
        }
        $uri = $this->buildUri($fileName, $bucket, $path);
        $bucket = $bucket ? $bucket : $this->bucket;
        return $this->s3->getObjectInfo($bucket, $uri);
    }

    /**
     * Uploads an entire file.
     *
     * @param string $fileName
     * @param string $localPath
     * @param string $bucket
     * @param string $path
     * @param array $metaHeaders Optional meta headers
     * @return bool
     */
    public function uploadFile($fileName, $localPath, $bucket = '', $path = '', $metaHeaders = [])
    {
        if (!$this->status) {
            return false;
        }
        $uri = $this->buildUri($fileName, $bucket, $path);
        $bucket = $bucket ? $bucket : $this->bucket;
        try {
            $input = file_get_contents($localPath);
            if ($input === false) {
                Logger::error('[S3Client] Failed to read file: ' . $localPath);
                return false;
            }
            $result = $this->s3->putObject($input, $bucket, $uri, 'private', $metaHeaders);
            if ($result === false) {
                Logger::error('[S3Client] Failed to upload file: ' . $fileName);
            }
            return $result;
        } catch (\Exception $e) {
            Logger::error('[S3Client] Exception during file upload: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets file content.
     *
     * @param string $fileName
     * @param int $offset
     * @param int $length
     * @param string $bucket
     * @param string $path
     * @return string|bool
     */
    public function getFileContent($fileName, $offset = 0, $length = -1, $bucket = '', $path = '')
    {
        if (!$this->status) {
            return false;
        }
        $uri = $this->buildUri($fileName, $bucket, $path);
        $bucket = $bucket ? $bucket : $this->bucket;
        $range = 'bytes=' . $offset . '-' . ($length === -1 ? '' : ($offset + $length - 1));
        $response = $this->s3->getObject($bucket, $uri, false, $range);
        return $response === false ? false : $response->body;
    }

    /**
     * Creates a directory if it doesn't exist.
     *
     * @param string $fullPath
     * @param string $bucket
     * @return bool
     */
    public function createDirectoryIfNotExists($fullPath, $bucket = '')
    {
        if (!$this->status) {
            return false;
        }
        $bucket = $bucket ? $bucket : $this->bucket;
        $fullPath = BMP::fixSlashes($fullPath);
        if ($this->s3->getObjectInfo($bucket, $fullPath) !== false) {
            return true;
        }
        $parts = explode(DIRECTORY_SEPARATOR, trim($fullPath, DIRECTORY_SEPARATOR));
        $currentPath = '';
        foreach ($parts as $part) {
            $currentPath .= DIRECTORY_SEPARATOR . $part;
            if (!$this->s3->getObjectInfo($bucket, $currentPath)) {
                if (!$this->s3->createFolder($bucket, $currentPath)) {
                    return false;
                }
            }
        }
        return $this->s3->getObjectInfo($bucket, $fullPath) !== false;
    }
}

class BMI_External_S3 {

    private $s3Client;
    private $s3Provider = 'aws';
    private const SINGLE_UPLOAD_THRESHOLD = 10485760;
    private const CHUNK_SIZE = 10485760;
    private $checkConnection = false;

    public const S3_PROVIDERS_REGIONS = [
        'aws' => [
            'us-east-1' => 'N. Virginia (us-east-1)',
            'us-east-2' => 'Ohio (us-east-2)',
            'us-west-1' => 'N. California (us-west-1)',
            'us-west-2' => 'Oregon (us-west-2)',
            'af-south-1' => 'Cape Town (af-south-1)',
            'ap-east-1' => 'Hong Kong (ap-east-1)',
            'ap-south-1' => 'Mumbai (ap-south-1)',
            'ap-northeast-3' => 'Osaka (ap-northeast-3)',
            'ap-northeast-2' => 'Seoul (ap-northeast-2)',
            'ap-southeast-1' => 'Singapore (ap-southeast-1)',
            'ap-southeast-2' => 'Sydney (ap-southeast-2)',
            'ap-northeast-1' => 'Tokyo (ap-northeast-1)',
            'ca-central-1' => 'Central (ca-central-1)',
            'eu-central-1' => 'Frankfurt (eu-central-1)',
            'eu-west-1' => 'Ireland (eu-west-1)',
            'eu-west-2' => 'London (eu-west-2)',
            'eu-south-1' => 'Milan (eu-south-1)',
            'eu-west-3' => 'Paris (eu-west-3)',
            'eu-north-1' => 'Stockholm (eu-north-1)',
            'me-south-1' => 'Bahrain (me-south-1)',
            'sa-east-1' => 'São Paulo (sa-east-1)',
        ],
        'wasabi' => [
            'us-west-1' => 'Oregon (us-west-1)',
            'us-east-1' => 'Virginia (us-east-1)',
            'us-east-2' => 'Virginia (us-east-2)',
            'us-central-1' => 'Texas (us-central-1)',
            'ca-central-1' => 'Canada (ca-central-1)',
            'eu-west-1' => 'England (eu-west-1)',
            'eu-west-3' => 'England (eu-west-3)',
            'eu-west-2' => 'France (eu-west-2)',
            'eu-central-1' => 'Netherlands (eu-central-1)',
            'eu-central-2' => 'Germany (eu-central-2)',
            'eu-south-1' => 'Italy (eu-south-1)',
            'ap-northeast-1' => 'Japan (ap-northeast-1)',
            'ap-northeast-2' => 'Japan (ap-northeast-2)',
            'ap-southeast-2' => 'Australia (ap-southeast-2)',
            'ap-southeast-1' => 'Singapore (ap-southeast-1)',
        ],
        'digitalocean' => [
            'nyc3' => 'New York 3',
            'ams3' => 'Amsterdam 3',
            'sgp1' => 'Singapore 1',
            'sfo2' => 'San Francisco 2',
        ],
    ];
    public const S3_PROVIDERS_ENDPOINTS = [
        'aws' => 's3.amazonaws.com',
        'wasabi' => 's3.wasabisys.com',
        'digitalocean' => 'nyc3.digitaloceanspaces.com',
    ];

    /**
     * Constructor
     *
     * @param string $provider S3-compatible service identifier (e.g. 'aws', 'wasabi', etc.)
     */
    public function __construct($provider)
    {
        if ($provider) {
            $this->s3Provider = $provider;
        }
        $endpoint = self::S3_PROVIDERS_ENDPOINTS[$this->s3Provider];
        $this->s3Client = new S3Client($endpoint);
        $this->registerHooks();
        set_error_handler([$this, 'errorHandler'], E_USER_WARNING);
    }

    public function errorHandler($errno, $context, $errfile, $errline)
    {
        $context = json_decode($context, true);
        if (isset($context['code'])){
            switch ($context['code']) {
                case 429:
                    $this->setIssue('rate_limit');
                    break;
                case 403:
                    $this->setIssue('forbidden');
                    break;
                case 401:
                    $this->setIssue('disconnected');
                    break;
            }
        }
        return true;
    }

    /**
     * Initializes the S3 connection
     */
    private function initializeConnection()
    {
        $connectionStatus = get_transient('bmip_' . $this->s3Provider . '_connection_status');
        $configs = $this->retrieveS3Configs();
        if ($connectionStatus == true && get_transient('bmip_' . $this->s3Provider . '_issue') == false ) {
            $this->deleteIssue();
        } else {
            if ($configs['accessKey'] == '' || $configs['secretKey'] == '' || $configs['bucket'] == '' || $configs['region'] == '') {
                return;
            }
            $connectionStatus = $this->s3Client->connect(
                $configs['accessKey'],
                $configs['secretKey'],
                $configs['bucket'],
                $configs['region'],
                $configs['storageClass'],
                $configs['sse']
            )['status'] == 'connected';
            if ($connectionStatus) {
                $this->deleteIssue();
            } else {
                if (get_option('bmip_' . $this->s3Provider . '_was_connected', false)) {
                    $this->setIssue('disconnected');
                }
            }
        }

        if ($connectionStatus) {
            $this->s3Client->setConfig($configs);
        }
        $this->s3Client->setStatus($connectionStatus);

    }
    /**
     * Initializes hooks for the S3 provider
     */
    private function registerHooks() {
        
        add_action('bmi_premium_remove_backup_file', [&$this, 'deleteBackup']);
        add_action('bmi_premium_remove_backup_json_file', [&$this, 'deleteBackupJson']);
        add_action('update_option_bmip_' . $this->s3Provider . '_access_key', [&$this, 'restartUploadProcess']);
        add_action('update_option_bmip_' . $this->s3Provider . '_secret_key', [&$this, 'restartUploadProcess']);
        add_action('update_option_bmip_' . $this->s3Provider . '_bucket', [&$this, 'restartUploadProcess']);
        add_action('update_option_bmip_' . $this->s3Provider . '_storage_class', [&$this, 'restartUploadProcess']);
        add_action('update_option_bmip_' . $this->s3Provider . '_path', [&$this, 'restartUploadProcess']);
        add_action('update_option_bmip_' . $this->s3Provider . '_region', [&$this, 'restartUploadProcess']);
        add_action('update_option_bmip_' . $this->s3Provider . '_sse', [&$this, 'restartUploadProcess']);
        add_action('set_transient_bmip_' . $this->s3Provider . '_connection_status', [&$this, 'restartUploadProcess']);
        add_action('delete_transient_bmip_' . $this->s3Provider . '_issue', [&$this, 'resetIssueDisplayOption']);
    }

    /**
     * Dismisses the issue display option
     */
    public function resetIssueDisplayOption()
    {
        delete_option('bmip_' . $this->s3Provider . '_dismiss_issue');
    }

    /**
     * Checks for backups to upload to S3 bucket
     *
     * @return array Status of the upload process
     */
    public function checkForBackupsToUpload()
    {
        $isEnabled = Dashboard\bmi_get_config('STORAGE::EXTERNAL::' . strtoupper($this->s3Provider));
        if (!($isEnabled === true || $isEnabled === 'true')) {
            update_option('bmip_to_be_uploaded', [ 'current_upload' => [], 'queue' => [] ]);
            return ['status' => 'not_enabled'];
        }
        if ($this->getConnectionStatus() === false) {
            return ['status' => 'error'];
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
        $parsedS3Files = $this->getParsedFiles();
        if ($parsedS3Files === false) {
            return ['status' => 'error'];
        }
        $backupsFileName = isset($parsedS3Files['zipFilesName']) ? $parsedS3Files['zipFilesName'] : [];
        $manifestFilesPath = isset($parsedS3Files['jsonFilesPath']) ? $parsedS3Files['jsonFilesPath'] : [];
        $availableManifests = array_map(function ($path) {
            return pathinfo($path, PATHINFO_FILENAME);
        }, $manifestFilesPath);
        $uploadedBackupStatus = get_option('bmi_uploaded_backups_status', []);


        foreach ($localBackups as $name => $details) {
            $md5 = $details[7];
            if (isset($uploadedBackupStatus[$md5]) && isset($uploadedBackupStatus[$md5][$this->s3Provider])) {
                continue;
            }
            $isBackupNotExists = !in_array($md5, $availableManifests) || !in_array($name, array_keys($backupsFileName));
            if ($isBackupNotExists && !(isset($requiresUpload['current_upload']['task']) && $requiresUpload['current_upload']['task'] == $this->s3Provider . '_' . $md5)) {
                $requiresUpload['queue'][$this->s3Provider . '_' . $md5] = [
                    'name' => $name,
                    'md5' => $md5,
                    'json' => $md5 . '.json',
                ];
            }
        }

        update_option('bmip_to_be_uploaded', $requiresUpload);
        return ['status' => 'success'];
    }

    /**
     * Restarts the upload process of backups
     *
     * @return array Status of the upload process
     */
    public function restartUploadProcess()
    {
        $requiredToUpload = get_option('bmip_to_be_uploaded', [
            'current_upload' => [],
            'queue' => [],
            'failed' => []
        ]);

        if (isset($requiredToUpload['current_upload']['task']) && strpos($requiredToUpload['current_upload']['task'], $this->s3Provider) !== false) {
            unset($requiredToUpload['current_upload']);
        }

        if (!isset($requiredToUpload['failed'])) {
            $requiredToUpload['failed'] = [];
        }

        foreach ($requiredToUpload['failed'] as $key => $value) {
            if (strpos($key, $this->s3Provider . '_') !== false) {
                unset($requiredToUpload['failed'][$key]);
            }
        }

        update_option('bmip_to_be_uploaded', $requiredToUpload);
        return $this->checkForBackupsToUpload();
    }

    /**
     * Retrieves and parses files from the S3 bucket
     *
     * @return array|bool Parsed files or false on error
     */
    public function getParsedFiles()
    {
        if ($this->getConnectionStatus() === false) {
            return false;
        }
        $zipFilesName = [];
        $jsonFilesPath = [];
        $files = $this->s3Client->listFiles();

        if ($files === false) {
            return false;
        }

        $path = get_option('bmip_' . $this->s3Provider . '_path', '');
        foreach ($files as $filename => $metadata) {
            $fileData = pathinfo($filename);
            if ($fileData['dirname'] == '.' && $path != '') {
                continue;
            }
            if ($fileData['dirname'] != '.' && $fileData['dirname'] != $path) {
                continue;
            }
            $filename = $fileData['basename'];
            $metadata['name'] = $filename;
            $extension = strtolower($fileData['extension']);
            if (in_array($extension, array('zip', 'tar', 'gz'))) {
                $zipFilesName[$filename] = ['id' => $filename, 'size' => $metadata['size']];
            } elseif ($extension === 'json' && strlen($fileData['filename']) === 32) {
                $jsonFilesPath[] = $filename;
            }
        }

        return compact('zipFilesName', 'jsonFilesPath');
    }

    /**
     * Verifies the S3 connection status
     *
     * @return array Connection status
     */
    public function verifyConnection()
    {
        $status = $this->getConnectionStatus();
        if ($status == true) {
            return ['result' => 'connected'];
        } else {
            return ['result' => 'disconnected'];
        }
    }
    /**
     * Tests the S3 bucket connection
     *
     * @param string $host
     * @param int $port
     * @param string $authType
     * @param string $username
     * @param string $password
     * @param string|null $fingerPrint
     * @param string|null $passphrase
     * @return array Connection test result
     */
    public function testConnection($accessKey, $secretKey, $bucket, $region, $path, $storageClass = 'STANDARD', $sse = '')
    {
        try {
            // Initialize connection test status
            $testStatus = [
                'credentials' => false,
                'bucket_exists' => false,
                'bucket_access' => false,
                'permissions' => [
                    'list' => false,
                    'write' => false,
                    'delete' => false
                ]
            ];

            $this->s3Client->setConfig([
                'accessKey' => $accessKey,
                'secretKey' => $secretKey,
                'bucket' => $bucket,
                'region' => $region,
                'path' => $path,
                'storageClass' => $storageClass,
                'sse' => $sse
            ]);

            $connectResult = $this->s3Client->testConnection();
            
            if ($connectResult !== true) {
                // Add detailed error information
                return [
                    'status' => 'error',
                    'error' => $connectResult,
                    'test_status' => $testStatus
                ];
            }

            $testStatus['credentials'] = true;
            $testStatus['bucket_exists'] = true;
            $testStatus['bucket_access'] = true;
            $testStatus['permissions']['list'] = true;
            $testStatus['permissions']['write'] = true;
            $testStatus['permissions']['delete'] = true;

            return [
                'status' => 'success',
                'test_status' => $testStatus,
                'message' => 'Connection test successful. All required permissions verified.'
            ];

        } catch (\Exception $e) {
            if (BMI_PRO_DEBUG) {
                Logger::error('[BMI_External_S3] Test connection failed: ' . $e->getMessage());
            }

            return [
                'status' => 'error',
                'error' => 'Connection test failed: ' . $e->getMessage(),
                'test_status' => $testStatus ?? null
            ];
        }
    }

    /**
     * Disconnects from the S3 bucket
     *
     * @return array Status of the disconnection
     */
    public function disconnect()
    {
        delete_option('bmip_' . $this->s3Provider . '_access_key');
        delete_option('bmip_' . $this->s3Provider . '_secret_key');
        delete_option('bmip_' . $this->s3Provider . '_bucket');
        delete_option('bmip_' . $this->s3Provider . '_storage_class');
        delete_option('bmip_' . $this->s3Provider . '_path');
        delete_option('bmip_' . $this->s3Provider . '_region');
        delete_option('bmip_' . $this->s3Provider . '_sse');
        delete_option('bmip_' . $this->s3Provider . '_was_connected');
        delete_transient('bmip_' . $this->s3Provider . '_connection_status');
        $this->restartUploadProcess();
        Dashboard\bmi_set_config('STORAGE::EXTERNAL::' . strtoupper($this->s3Provider), false);
        return ['status' => 'success'];
    }

    /**
     * Uploads a backup to the S3 bucket.
     *
     * @param string $uploadId
     * @param string $backupName
     * @param int $offset
     * @param string $md5
     * @return array
     */
    public function uploadBackup($uploadId, $backupName, $offset, $md5)
    {
        if ($this->getConnectionStatus() === false) {
            return ['status' => 'error', 'error' => 'disconnected'];
        }
        $backupPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupName;
        $manifestPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $md5 . '.json';

        if (!file_exists($backupPath)) {
            $this->restartUploadProcess();
            return ['status' => 'error', 'error' => 'internal_file_not_found'];
        }
        $fileSize = filesize($backupPath);

        try {
            if ($fileSize <= self::SINGLE_UPLOAD_THRESHOLD) {
                return $this->uploadSmallFile($backupName, $backupPath, $manifestPath, $md5);
            }
            return $this->uploadLargeFile($uploadId, $backupName, $backupPath, $offset, $fileSize, $manifestPath, $md5);
        } catch (\Exception $e) {
            Logger::error('[BMI PRO] Upload failed for ' . $backupName . ': ' . $e->getMessage());
            if ($uploadId) {
                $this->s3Client->endUploadSession($uploadId, $backupName, [], false);
                $this->removeParts($uploadId);
            }
            return [
                'status' => 'error',
                'error' => 'upload_failed',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Uploads a small file in a single request.
     *
     * @param string $backupName
     * @param string $backupPath
     * @param string $manifestPath
     * @param string $md5
     * @return array
     */
    private function uploadSmallFile($backupName, $backupPath, $manifestPath, $md5)
    {
        $fileMd5 = md5_file($backupPath);
        $metaHeaders = ['md5' => $fileMd5];
        $uploadResult = $this->s3Client->uploadFile($backupName, $backupPath, '', '', $metaHeaders);
        if (!$uploadResult) {
            Logger::error('[BMI PRO] Failed to upload small backup file: ' . $backupName);
            return ['status' => 'error', 'error' => 'upload_backup'];
        }

        $meta = $this->s3Client->getFileMeta($backupName);
        if ($meta === false || !isset($meta['x-amz-meta-md5']) || $meta['x-amz-meta-md5'] !== $fileMd5) {
            Logger::error('[BMI PRO] MD5 mismatch for small file: ' . $backupName . '. Local: ' . $fileMd5 . ', Remote: ' . ($meta['x-amz-meta-md5'] ?? 'N/A') . '. Deleting remote file.');
            $this->s3Client->deleteFile($backupName); // Attempt to delete corrupted file
            return ['status' => 'error', 'error' => 'md5_mismatch'];
        }
        $manifestResult = $this->s3Client->uploadFile($md5 . '.json', $manifestPath);
        if (!$manifestResult) {
            Logger::error('[BMI PRO] Failed to upload manifest for ' . $backupName);
            $this->s3Client->deleteFile($backupName);
            return ['status' => 'error', 'error' => 'upload_manifest'];
        }
        return ['status' => 'success'];
    }

    /**
     * Uploads a large file using multipart upload.
     *
     * @param string $uploadId
     * @param string $backupName
     * @param string $backupPath
     * @param int $offset
     * @param int $fileSize
     * @param string $manifestPath
     * @param string $md5
     * @return array
     */
    private function uploadLargeFile($uploadId, $backupName, $backupPath, $offset, $fileSize, $manifestPath, $md5)
    {
        $fileMd5 = md5_file($backupPath);
        if (!$uploadId) {
            $metaHeaders = ['md5' => $fileMd5];
            $uploadId = $this->s3Client->startUploadSession($backupName, '', '', $metaHeaders);
            if ($uploadId === false) {
                throw new \Exception('Failed to start upload session.');
            }
            return ['status' => 'continue', 'offset' => 0, 'uploadId' => $uploadId];
        }
        if ($offset < $fileSize) {
            $newOffset = $this->uploadChunk($uploadId, $backupName, $offset);
            if ($newOffset === false) {
                throw new \Exception('Failed to upload chunk.');
            }
            return ['status' => 'continue', 'offset' => $newOffset, 'uploadId' => $uploadId];
        }
        $parts = $this->getParts($uploadId);
        $endResult = $this->s3Client->endUploadSession($uploadId, $backupName, $parts, true);
        if ($endResult === false) {
            throw new \Exception('Failed to complete upload session.');
        }
        $this->removeParts($uploadId);

        $meta = $this->s3Client->getFileMeta($backupName);
        if ($meta === false || !isset($meta['x-amz-meta-md5']) || $meta['x-amz-meta-md5'] !== $fileMd5) {
            Logger::error('[BMI PRO] MD5 mismatch for large file: ' . $backupName . '. Local: ' . $fileMd5 . ', Remote: ' . ($meta['x-amz-meta-md5'] ?? 'N/A'));
            $this->s3Client->deleteFile($backupName);
            return ['status' => 'error', 'error' => 'md5_mismatch'];
        }

        $manifestResult = $this->s3Client->uploadFile($md5 . '.json', $manifestPath);
        if (!$manifestResult) {
            Logger::error('[BMI PRO] Failed to upload manifest for ' . $backupName);
            $this->s3Client->deleteFile($backupName);
            return ['status' => 'error', 'error' => 'upload_manifest'];
        }
        return ['status' => 'success'];
    }

    /**
     * Uploads a chunk of a backup.
     *
     * @param string $uploadId
     * @param string $backupName
     * @param int $offset
     * @return int|bool
     */
    public function uploadChunk($uploadId, $backupName, $offset)
    {
        if ($this->getConnectionStatus() === false) {
            return false;
        }
        $backupPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $backupName;
        $backupFile = fopen($backupPath, 'r');
        if ($backupFile === false) {
            Logger::error('[BMI PRO] Unable to open backup file: ' . $backupName);
            return false;
        }
        if (fseek($backupFile, $offset) !== 0) {
            fclose($backupFile);
            Logger::error('[BMI PRO] Failed to seek in file: ' . $backupName);
            return false;
        }
        $data = fread($backupFile, self::CHUNK_SIZE);
        fclose($backupFile);
        if ($data === false) {
            Logger::error('[BMI PRO] Failed to read from file: ' . $backupName);
            return false;
        }
        $parts = $this->getParts($uploadId);
        $partNumber = empty($parts) ? 1 : max(array_keys($parts)) + 1;
        $eTag = $this->s3Client->uploadChunk($uploadId, $backupName, $partNumber, $data);
        if ($eTag === false) {
            Logger::error('[BMI PRO] Failed to upload chunk for file: ' . $backupName);
            return false;
        }
        $this->addPart($uploadId, $partNumber, $eTag);
        return $offset + strlen($data);
    }

    /**
     * Checks if a file exists on the S3 bucket
     *
     * @param string $fileName Path to the file on the S3 bucket
     * @return bool
     */
    public function isFileExists($fileName)
    {
        if ($this->getConnectionStatus() === false) {
            return false;
        }
        $file = $this->s3Client->getFileMeta($fileName);
        return $file !== false;
    }

    /**
     * Get file metadata from the S3 bucket
     * 
     * @param string $fileName Path to the file on the S3 bucket
     * @return array|bool File metadata or false on error
     */
    public function getFileMeta($fileName)
    {
        if ($this->getConnectionStatus() === false) {
            return false;
        }
        return $this->s3Client->getFileMeta($fileName);
    }

    /**
     * Deletes a backup from the S3 bucket
     *
     * @param string $md5 MD5 of the backup to delete
     * @return bool
     */
    public function deleteBackup($md5)
    {
        if ($this->getConnectionStatus() === false) {
            return false;
        }

        $manifestFile = $md5 . '.json';
        if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . $manifestFile)) {
            $manifestContent = json_decode(file_get_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . $manifestFile), true);
        } else {
            $manifestContent = json_decode($this->s3Client->getFileContent($manifestFile), true);
        }
        $backupName = isset($manifestContent['name']) ? $manifestContent['name'] : '';
        if (empty($backupName)) {
            Logger::error('[BMI PRO] Manifest does not contain backup name.');
            return false;
        }
        $deleteManifest = $this->s3Client->deleteFile($manifestFile);
        $deleteBackup = $this->s3Client->deleteFile($backupName);
        return $deleteManifest && $deleteBackup;
    }

    /**
     * Deletes a backup manifest from the S3 bucket
     *
     * @param string $md5 MD5 of the backup to delete
     * @return bool
     */
    public function deleteBackupJson($md5)
    {
        if ($this->getConnectionStatus() === false) {
            return false;
        }

        $manifestFile = $md5 . '.json';
        return $this->s3Client->deleteFile($manifestFile);
    }

    /**
     * Retrieves S3 configs from temporary file or options
     *
     * @return array Configs array
     */
    public function retrieveS3Configs()
    {
        $tempKeyS3File = BMI_TMP . DIRECTORY_SEPARATOR . $this->s3Provider . 'Keys.php';

        $options = [
            'bmip_' . $this->s3Provider . '_access_key',
            'bmip_' . $this->s3Provider . '_secret_key',
            'bmip_' . $this->s3Provider . '_bucket',
            'bmip_' . $this->s3Provider . '_storage_class',
            'bmip_' . $this->s3Provider . '_path',
            'bmip_' . $this->s3Provider . '_region',
            'bmip_' . $this->s3Provider . '_sse'
        ];

        if (file_exists($tempKeyS3File) && !file_exists(BMI_BACKUPS . '/.migration_lock')) {
            $sftpKeys = file_get_contents($tempKeyS3File);
            $lines = explode("\n", $sftpKeys);
    
            foreach ($options as $index => $option) {
                if (isset($lines[$index + 1])) {
                    update_option($option, trim(substr($lines[$index + 1], 2)));
                }
            }

            @unlink($tempKeyS3File);
        }

        $configs = [
            'accessKey' => get_option('bmip_' . $this->s3Provider . '_access_key'),
            'secretKey' => get_option('bmip_' . $this->s3Provider . '_secret_key'),
            'bucket' => get_option('bmip_' . $this->s3Provider . '_bucket'),
            'storageClass' => get_option('bmip_' . $this->s3Provider . '_storage_class'),
            'path' => get_option('bmip_' . $this->s3Provider . '_path'),
            'region' => get_option('bmip_' . $this->s3Provider . '_region'),
            'sse' => get_option('bmip_' . $this->s3Provider . '_sse')
        ];
        
        return $configs;
    }

    /**
     * Get file content from the S3 bucket
     * 
     * @param string $fileName Path to the file on the S3 bucket
     * @param string $range Range of bytes to retrieve in the format "start-end"
     * @return string|bool File content or false on error
     */
    public function getFileContent($fileName, $range = '0-0')
    {
        if ($this->getConnectionStatus() === false) {
            return false;
        }
        if ($range === '0-0') {
            return $this->s3Client->getFileContent($fileName);
        }

        $range = explode('-', $range);
        
        if (count($range) !== 2) {
            return false;
        }

        $offset = intval($range[0]);
        $length = intval($range[1]) - $offset + 1;


        return $this->s3Client->getFileContent($fileName, $offset, $length);
    }

    /**
     * Get the manifest content from the S3 bucket
     * 
     * @param string $md5 MD5 of the backup
     * @return array|bool Manifest content or false on error
     */
    public function getManifestContent($md5)
    {
        if ($this->getConnectionStatus() === false) {
            return false;
        }
        $manifestFile = $md5 . '.json';
        $manifestContent = $this->s3Client->getFileContent($manifestFile);
        if ($manifestContent === false) {
            return false;
        }
        return json_decode($manifestContent, true);
    }

    
    /**
     * Get parts for upload
     * 
     * @param string $uploadId
     * @param string $fileName
     * @return array
     */
    public function getParts($uploadId)
    {
        return get_option('bmip_' . $this->s3Provider . '_parts_' . $uploadId, []);
    }

    /**
     * Set parts for upload
     * 
     * @param string $uploadId
     * @param string $partNumber
     * @param string $part
     */
    public function addPart($uploadId, $partNumber, $part)
    {
        $parts = $this->getParts($uploadId);
        $parts[$partNumber] = $part;
        update_option('bmip_' . $this->s3Provider . '_parts_' . $uploadId, $parts);
    }
    
    /**
     * Remove parts for upload
     * @param mixed $uploadId
     * @return void
     */
    public function removeParts($uploadId)
    {
        delete_option('bmip_' . $this->s3Provider . '_parts_' . $uploadId);
    }

    /**
     * Get the issue status
     *
     * @return array Issue status
     */
    public function getIssue()
    {
        if (Dashboard\bmi_get_config('STORAGE::EXTERNAL::' . strtoupper($this->s3Provider)) != true) {
            return [
                'issue' => false,
                'retryAfter' => false,
                'dismissed' => false
            ];
        }
        return [
            'issue' => get_transient('bmip_' . $this->s3Provider . '_issue'),
            'retryAfter' => human_time_diff(get_option('_transient_timeout_bmip_' . $this->s3Provider . '_issue'), current_time('timestamp')),
            'dismissed' => $this->isIssueDismissed()
        ];
    }

    /**
     * Set the issue status
     *
     * @param string $issue
     * @param int $timeout
     */
    public function setIssue($issue, $timeout = HOUR_IN_SECONDS)
    {
        $currentIssue = get_transient('bmip_' . $this->s3Provider . '_issue');
        if ($currentIssue == $issue) {
            return;
        }
        if ($currentIssue == 'forbidden' && $issue == 'disconnected') {
            return;
        }

        set_transient('bmip_' . $this->s3Provider . '_issue', $issue, $timeout);
        if (in_array($issue, ['disconnected', 'forbidden'])) {
            delete_transient('bmip_' . $this->s3Provider . '_connection_status');
            delete_option('bmip_' . $this->s3Provider . '_was_connected');
        }
        delete_option('bmip_' . $this->s3Provider . '_dismiss_issue');
    }

    /**
     * Dismisses the issue
     */
    public function dismissIssue()
    {
        update_option('bmip_' . $this->s3Provider . '_dismiss_issue', true);
    }

    public function deleteIssue()
    {
        if (get_transient('bmip_' . $this->s3Provider . '_issue') == false) {
            return;
        }
        set_transient('bmip_' . $this->s3Provider . '_connection_status', true, HOUR_IN_SECONDS);
        update_option('bmip_' . $this->s3Provider . '_was_connected', true);
        if (in_array(get_transient('bmip_' . $this->s3Provider . '_issue'), ['disconnected', 'forbidden'])) {
            delete_transient('bmip_' . $this->s3Provider . '_issue');
        }
    }

    /**
     * Checks if the issue is dismissed
     *
     * @return bool
     */
    public function isIssueDismissed()
    {
        return get_option('bmip_' . $this->s3Provider . '_dismiss_issue', false);
    }

    public function getRegions()
    {
        return isset(self::S3_PROVIDERS_REGIONS[$this->s3Provider]) ? self::S3_PROVIDERS_REGIONS[$this->s3Provider] : [];
    }

    public function getConnectionStatus()
    {
        if (!$this->checkConnection) {
            $this->initializeConnection();
            $this->checkConnection = true;
        }
        return $this->s3Client->getStatus();
    }


}