<?php

  // Namespace
  namespace BMI\Plugin\Heart;

  // Usage
  use BMI\Plugin\BMI_Logger AS Logger;
  use BMI\Plugin\Progress\BMI_ZipProgress_Logger AS Output;
  use BMI\Plugin\Checker\System_Info as SI;
  use BMI\Plugin\Dashboard as Dashboard;
  use BMI\Plugin\Database\BMI_Database as Database;
  use BMI\Plugin\Database\BMI_Database_Exporter as BetterDatabaseExport;
  use BMI\Plugin\Backup_Migration_Plugin as BMP;
  use BMI\Plugin\BMI_Pro_Core as Pro_Core;
  use BMI\Plugin AS BMI;

  // Exit on direct access
  if (!defined('ABSPATH')) exit;

  // Fixes for some cases
  require_once BMI_INCLUDES . '/compatibility.php';

  /**
   * Main class to handle heartbeat of the backup
   */
  class BMI_Backup_Heart {
    
    public $it;
    public $dbit;
    public $abs;
    public $dir;
    public $url;
    public $curl;
    public $config;
    public $content;
    public $backups;
    public $dblast;
    public $output;
    public $useragent;
    public $remote_settings;
    
    public $identy;
    public $manifest;
    public $backupname;
    public $safelimit;
    public $total_files;
    public $rev;
    public $backupstart;
    public $filessofar;
    public $identyfile;
    public $browserSide;
    
    public $identyFolder;
    public $fileList;
    public $dbfile;
    public $db_dir_v2;
    public $db_v2_engine;
    
    public $final_made;
    public $final_batch;
    public $dbitJustFinished;
    public $lock_cli;
    public $startOfBatch;
    public $errorSent = false;
    public $statusSent = false;
    public $backupSize = 0;
    
    public $_zip;
    public $_lib;
    public $batches_left;
    public $res = [ 'status' => 'success', 'default' => true ];

    // Prepare the request details
    function __construct($curlIdenty = false, $config = false, $content = false, $backups = false, $abs = false, $dir = false, $remote_settings = []) {
      
      if (!defined('BMI_CLI_REQUEST')) {
        define('BMI_CLI_REQUEST', false);
      }
      
      $curl = false;
      if ($curlIdenty != false) $curl = true;
      
      $remote_settings = $this->getRemoteSettings($curlIdenty, $curl);
      $this->remote_settings = $remote_settings; 
      if (sizeof($remote_settings) === 0) return;
      
      $this->setupConstants();
      
      $this->it = $remote_settings['it'];
      $this->dbit = $remote_settings['dbit'];
      $this->abs = $abs;
      $this->dir = $dir;
      $this->curl = $curl;
      $this->config = $config;
      $this->content = $content;
      $this->backups = $backups;
      $this->dblast = $remote_settings['dblast'];
      $this->useragent = $remote_settings['useragent'];
      
      $this->identy = $remote_settings['identy'];
      $this->manifest = $remote_settings['manifest'];
      $this->backupname = $remote_settings['backupname'];
      $this->safelimit = intval($remote_settings['safelimit']);
      $this->total_files = $remote_settings['total_files'];
      $this->rev = intval($remote_settings['rev']);
      $this->backupstart = $remote_settings['start'];
      $this->filessofar = intval($remote_settings['filessofar']);
      $this->identyfile = BMI_TMP . DIRECTORY_SEPARATOR . '.' . $this->identy;
      $this->browserSide = (isset($remote_settings['browser']) && ($remote_settings['browser'] === true || $remote_settings['browser'] === 'true')) ? true : false;
      
      if ($curl) {
        // Here we could use nonces, but well, WordPress can't handle nonces in such scenario due to the way its generated
        // We still use "nonce" here to bypass some security plugins as they may block the URL if the nonce string does not exist in such URL
        $this->url = get_home_url(null, sprintf('/?backup-migration=CURL_BACKUP&backup-id=%s&_wpnonce=%s&t=%s&sk=%s', $this->identy, 'Wn19dnWuq', time(), Dashboard\bmi_get_config('REQUEST:SECRET')));
      } else {
        $this->url = null;
      }
      
      $this->identyFolder = BMI_TMP . DIRECTORY_SEPARATOR . 'bg-' . $this->identy;
      $this->fileList = BMI_TMP . DIRECTORY_SEPARATOR . 'files_latest.list';
      $this->dbfile = BMI_TMP . DIRECTORY_SEPARATOR . 'bmi_database_backup.sql';
      $this->db_dir_v2 = BMI_TMP . DIRECTORY_SEPARATOR . 'db_tables';
      $this->db_v2_engine = false;
      
      $this->final_made = $remote_settings['final_made'];
      $this->final_batch = $remote_settings['final_batch'];
      $this->dbitJustFinished = $remote_settings['dbitJustFinished'];
      $this->backupSize = 0;
      if (isset($remote_settings['backupSize']))
        $this->backupSize = $remote_settings['backupSize'];
      
      $this->startOfBatch = time();
      if (isset($remote_settings['startOfBatch']))
        $this->startOfBatch = $remote_settings['startOfBatch'];
      
      $this->lock_cli = BMI_BACKUPS . '/.backup_cli_lock';
      if ($this->it > 1) @touch($this->lock_cli);
      
      if ($this->isFunctionEnabled('ini_set') && $this->isFunctionEnabled('session_status') && session_status() != PHP_SESSION_ACTIVE) {
        ini_set('display_errors', 1);
        ini_set('error_reporting', E_ALL);
        ini_set('log_errors', 1);
        ini_set('error_log', BMI_CONFIG_DIR . '/background-errors.log');
      }

    }
    
    // Get "remote_settings" from file created by the server
    public function getRemoteSettings($curlIdenty, $curl = false) : array {
      $settings_name = 'currentBackupConfig.' . 'php';
      $settings_path = BMP::fixSlashes(BMI_TMP . DIRECTORY_SEPARATOR . $settings_name);
      
      if (!file_exists($settings_path) && $curl) {
        Logger::error('Settings path does not exist for backup-process.php');
        return [];
      }
      
      if (!file_exists($settings_path)) {
        Logger::error('Config file does not exist, please try to run the backup process once again.');
        return $this->send_error('Config file does not exist, please try to run the backup process once again.', true);
      }
      
      $remote_settings = file_get_contents($settings_path);
      $remote_settings = (array) json_decode(substr($remote_settings, 8));
      
      if (!isset($remote_settings['identy'])) {
        Logger::error('Identy is not set in the config, which prevents backup-process.php from running.');
        return [];
      }
      
      if ($curl && $curlIdenty != $remote_settings['identy']) {
        Logger::error('backup-process.php runs via CURL but the identy provided by CURL does not match config.');
        return [];
      }
      
      return $remote_settings;
    }
    
    // Save remote setting for next batch
    public function saveRemoteSettings() {
      $settings_name = 'currentBackupConfig.' . 'php';
      $settings_path = BMP::fixSlashes(BMI_TMP . DIRECTORY_SEPARATOR . $settings_name);
      
      $this->remote_settings['identy'] = $this->identy;
      $this->remote_settings['manifest'] = $this->manifest;
      $this->remote_settings['backupname'] = $this->backupname;
      $this->remote_settings['safelimit'] = $this->safelimit;
      $this->remote_settings['total_files'] = $this->total_files;
      $this->remote_settings['rev'] = $this->rev;
      $this->remote_settings['start'] = $this->backupstart;
      $this->remote_settings['filessofar'] = $this->filessofar;
      $this->remote_settings['browser'] = $this->browserSide;
      $this->remote_settings['it'] = $this->it;
      $this->remote_settings['dbit'] = $this->dbit;
      $this->remote_settings['dblast'] = $this->dblast;
      $this->remote_settings['final_made'] = $this->final_made;
      $this->remote_settings['final_batch'] = $this->final_batch;
      $this->remote_settings['dbitJustFinished'] = $this->dbitJustFinished;
      $this->remote_settings['startOfBatch'] = $this->startOfBatch;
      $this->remote_settings['backupSize'] = $this->backupSize;
      
      if (file_exists($settings_path)) @unlink($settings_path);
      file_put_contents($settings_path, '<?php //' . json_encode($this->remote_settings));
    }
    
    // Setup constants and handle request
    public function setupConstants() {
      
      if (!defined('BMI_CURL_REQUEST')) define('BMI_CURL_REQUEST', true);
      if (!defined('BMI_CLI_REQUEST')) define('BMI_CLI_REQUEST', false);
      if (!defined('BMI_SAFELIMIT')) define('BMI_SAFELIMIT', intval($this->remote_settings['safelimit']));
      
      if ($this->isFunctionEnabled('ignore_user_abort')) @ignore_user_abort(true);
      if ($this->isFunctionEnabled('set_time_limit')) @set_time_limit(259200);
      if ($this->isFunctionEnabled('ini_set') && $this->isFunctionEnabled('session_status') && session_status() != PHP_SESSION_ACTIVE) {
        @ini_set('max_input_time', '259200');
        @ini_set('max_execution_time', '259200');
      }
      
      if (!isset($this->remote_settings['browser'])) $this->remote_settings['browser'] = false;
      
      // Don't block server handler
      // if ($this->isFunctionEnabled('session_write_close')) @session_write_close();
      
    }
    
    // Make sure it's impossible to unlink some files
    public function unlinksafe($path) {
      
      if (substr($path, 0, 7) == 'file://') {
        $path = substr($path, 7);
      }
      
      $path = realpath($path);
      if ($path === false) return;
      if (strpos($path, 'wp-config.php') !== false) return;
      if (substr($path, -8) == '/backups') return;
      
      @unlink('file://' . $path);
      
    }

    // Human size from bytes
    public static function humanSize($bytes) {
      if (is_int($bytes)) {
        $label = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($i = 0; $bytes >= 1024 && $i < (count($label) - 1); $bytes /= 1024, $i++);

        return (round($bytes, 2) . " " . $label[$i]);
      } else return $bytes;
    }

    // Create new process
    public function send_beat($manual = false, &$logger = null) {
      
      $globalLockFile = BMI_TMP . DIRECTORY_SEPARATOR . '.beat_lock_' . $this->identy;
      $maxLockAge = 10;
      
      if (is_null($logger)) $this->load_logger();
      else if ($logger instanceof Output) $this->output = $logger;
      
      if (file_exists($globalLockFile)) {
        $lockAge = time() - filemtime($globalLockFile);
        if ($lockAge < $maxLockAge) {
          sleep($maxLockAge - $lockAge + 1);
          @unlink($globalLockFile);
        } else {
          @unlink($globalLockFile);
        }
      }
      
      $fp = fopen($globalLockFile, 'c');
      if (!$fp) {
        $this->output->log('Could not open lock file for writing, skipping this beat.', 'WARN');
        return;
      }
      if (!flock($fp, LOCK_EX | LOCK_NB)) {
        $this->output->log('Another process is already running, skipping this beat.', 'WARN');
        fclose($fp);
        return;
      }
      
      try {

        $header = array(
          'Content-Accept:*/*',
          'Access-Control-Allow-Origin:*'
        );
        
        $this->saveRemoteSettings();
        $c = curl_init();
             curl_setopt($c, CURLOPT_POST, 1);
             curl_setopt($c, CURLOPT_COOKIESESSION, false);
             curl_setopt($c, CURLOPT_TIMEOUT, 20);
             curl_setopt($c, CURLOPT_VERBOSE, false);
             curl_setopt($c, CURLOPT_HEADER, false);
             curl_setopt($c, CURLOPT_URL, $this->url);
             curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
             curl_setopt($c, CURLOPT_MAXREDIRS, 10);
             curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
             curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
             curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
             curl_setopt($c, CURLOPT_HTTPHEADER, $header);
             curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'POST');
             curl_setopt($c, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
             curl_setopt($c, CURLOPT_USERAGENT, $this->useragent);

        $r = curl_exec($c);

        $statusCode = curl_getinfo($c, CURLINFO_HTTP_CODE);
        if ($statusCode > 299) {
          Logger::error(print_r(curl_getinfo($c), true));
          $this->output->log('Response: ' . $r, 'ERROR');
          $this->send_error('There was something wrong with the request, the response code was: ' . $statusCode, true);
        }

        if ($manual === true && $logger !== null) {
          if ($r === false) {
            if (intval(curl_errno($c)) === CURLE_OPERATION_TIMEDOUT || $statusCode === 429) {
              Logger::error(print_r(curl_getinfo($c), true));
              Logger::error(curl_errno($c) . ': ' . curl_error($c));
              $this->output->log('There was something wrong with the request:', 'WARN');
              $this->output->log(curl_errno($c) . ': ' . curl_error($c), 'WARN');
            }
          } else {
            $this->output->log('Request sent successfully, without error returned.', 'SUCCESS');
          }
        }

        curl_close($c);
        if (isset($this->output)) $this->output->end();

      } catch (\Exception $e) {

        error_log($e->getMessage());
        $this->output->log($e->getMessage(), 'ERROR');
        if (isset($this->output)) $this->output->end();

      } catch (\Throwable $e) {

        error_log($e->getMessage());
        $this->output->log($e->getMessage(), 'ERROR');
        if (isset($this->output)) $this->output->end();

      } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
        @unlink($globalLockFile);
      }

    }

    // Load backup logger
    public function load_logger() {
      
      if ($this->output instanceof Output) return;
      
      require_once BMI_INCLUDES . '/logger.php';
      require_once BMI_INCLUDES . '/progress/logger-only.php';

      $this->output = new Output();
      $this->output->start();

    }

    // Remove common files
    public function remove_commons() {
      
      if (is_null($this->fileList)) return;

      // Remove list if exists
      $identyfile = $this->identyfile;
      $logfile = BMI_TMP . DIRECTORY_SEPARATOR . 'bmi_logs_this_backup.log';
      $clidata = BMI_TMP . DIRECTORY_SEPARATOR . 'bmi_cli_data.json';
      $settings_path = BMI_TMP . DIRECTORY_SEPARATOR . 'currentBackupConfig.php';
      if (file_exists($this->fileList)) $this->unlinksafe($this->fileList);
      if (file_exists($this->dbfile)) $this->unlinksafe($this->dbfile);
      if (file_exists($this->manifest)) $this->unlinksafe($this->manifest);
      if (file_exists($logfile)) $this->unlinksafe($logfile);
      if (file_exists($clidata)) $this->unlinksafe($clidata);
      if (file_exists($identyfile)) $this->unlinksafe($identyfile);
      if (file_exists($identyfile . '-running')) $this->unlinksafe($identyfile . '-running');
      if (file_exists($this->lock_cli)) $this->unlinksafe($this->lock_cli);
      if (file_exists($settings_path)) $this->unlinksafe($settings_path);

      // Remove backup
      if (file_exists(BMI_BACKUPS . '/.running')) $this->unlinksafe(BMI_BACKUPS . '/.running');
      if (file_exists(BMI_BACKUPS . '/.space_check')) $this->unlinksafe(BMI_BACKUPS . '/.space_check');
      if (file_exists(BMI_BACKUPS . '/.abort')) $this->unlinksafe(BMI_BACKUPS . '/.abort');
      if (file_exists(BMI_BACKUPS . '/.last_triggered')) $this->unlinksafe(BMI_BACKUPS . '/.last_triggered');

      // Remove group folder
      if (file_exists($this->identyFolder)) {
        $files = glob($this->identyFolder . '/*');
        foreach ($files as $file) if (is_file($file)) $this->unlinksafe($file);
        @rmdir($this->identyFolder);
      }

      // Remove tmp database files
      if (file_exists($this->db_dir_v2) && is_dir($this->db_dir_v2)) {
        $files = glob($this->db_dir_v2 . '/*');
        foreach ($files as $file) if (is_file($file)) $this->unlinksafe($file);
        if (is_dir($this->db_dir_v2)) @rmdir($this->db_dir_v2);
      }

    }

    // Make success
    public function send_success() {
      
      $this->load_logger();
      
      // Display the success
      $this->output->log('Backup completed successfully!', 'SUCCESS');
      $this->output->log('#001', 'END-CODE');

      // Remove common files
      $this->remove_commons();

      // End logger
      if (isset($this->output)) @$this->output->end();

      $this->actionsAfterProcess(true);
      $this->it += 1;
      
      // Set header for browser
      if ($this->browserSide) {

        // Content finished
        $this->sendResponse(true);

      }

      // End the process
      exit;

    }

    // Make error
    public function send_error($reason = false, $abort = false) {
      
      if ($this->errorSent) return;
      $this->errorSent = true;
      
      $this->load_logger();

      // Log error
      $this->output->log('Something went wrong with background process... ' . '(part: ' . $this->it . ')', 'ERROR');
      if ($reason !== false) $this->output->log('Reason: ' . $reason, 'ERROR');
      $this->output->log('Removing backup files... ', 'ERROR');

      // Remove common files
      $this->remove_commons();

      // Remove backup
      if (file_exists(BMI_BACKUPS . DIRECTORY_SEPARATOR . $this->backupname)) $this->unlinksafe(BMI_BACKUPS . DIRECTORY_SEPARATOR . $this->backupname);

      // Abort step
      $this->output->log('Aborting backup... ', 'STEP');
      if ($abort === false) $this->output->log('#002', 'END-CODE');
      else $this->output->log('#004', 'END-CODE');
      if (isset($this->output)) @$this->output->end();

      $this->actionsAfterProcess();
      $this->it += 1;
      
      // Set header for browser
      if ($this->browserSide) {

        // Content finished
        $this->sendResponse(false, true);

      }
      exit;

    }

    // Group files for batches
    public function make_file_groups() {
      
      if (!(file_exists($this->fileList) && is_readable($this->fileList))) {
        return $this->send_error('File list is not accessible or does not exist, try to run your backup process once again.', true);
      }

      $this->output->log('Making batches for each process...', 'STEP');
      $list_path = $this->fileList;

      $file = fopen($list_path, 'r');
      $this->output->log('Reading list file...', 'INFO');
      $first_line = explode('_', fgets($file));
      $files = intval($first_line[0]);
      $firstmax = intval($first_line[1]);

      if ($files > 0) {
        $batches = 100;
        if ($files <= 200) $batches = 100;
        if ($files > 200) $batches = 400;
        if ($files > 1600) $batches = 600;
        if ($files > 3200) $batches = 1000;
        if ($files > 6400) $batches = 2000;
        if ($files > 12800) $batches = 4000;
        if ($files > 25600) $batches = 5000;
        if ($files > 30500) $batches = 10000;
        if ($files > 60500) $batches = 20000;
        if ($files > 90500) $batches = 40000;
        if ($files > 100000) $batches = 60000;
        if ($files > 150000) $batches = 80000;

        $this->output->log('Each batch will contain up to ' . $batches . ' files.', 'INFO');
        $this->output->log('Large files takes more time, you will be notified about those.', 'INFO');

        $folder = $this->identyFolder;
        if (!(file_exists($folder) && is_dir($file))) {
          @mkdir($folder, 0755, true);
        }

        $limitcrl = 96;
        if (BMI_CLI_REQUEST === true) {
          $limitcrl = 512;
          if ($files > 30000) $limitcrl = 1024;
        }

        $i = 0; $bigs = 0; $prev = 0; $currsize = 0;
        while (($line = fgets($file)) !== false) {

          $line = explode(',', $line);
          $last = sizeof($line) - 1;
          $size = intval($line[$last]);
          unset($line[$last]);
          $line = implode(',', $line);

          $i++;
          if ($firstmax != -1 && $i > $firstmax) $bigs++;
          $suffix = intval(ceil(abs($i / $batches))) + $bigs;

          if ($prev == $suffix) {
            $currsize += $size;
          } else {
            $currsize = $size;
            $prev = $suffix;
          }

          $skip = false;
          if ($currsize > ($limitcrl * (1024 * 1024))) $skip = true;

          $groupFile = $folder . DIRECTORY_SEPARATOR . $this->identy . '-' . $suffix . '.files';
          $group = fopen($groupFile, 'a');
                   fwrite($group, $line . ',' . $size . "\r\n");
                   fclose($group);

          if ($skip === true) $bigs++;
          unset($line);

        }

        fclose($file);
        if (file_exists($this->fileList)) $this->unlinksafe($this->fileList);

      } else {

        $this->output->log('No file found to be backed up, omitting files.', 'INFO');

      }

      if (file_exists($this->fileList)) $this->unlinksafe($this->fileList);
      $this->output->log('Batches completed...', 'SUCCESS');

    }

    // Final batch
    public function get_final_batch() {

      $db_root_dir = BMI_TMP . DIRECTORY_SEPARATOR;
      $logs = $db_root_dir . 'bmi_logs_this_backup.log';
      $_manifest = $this->manifest;
      
      if (strpos($logs, 'file://') !== false) $logs = substr($logs, 7);
      if (strpos($_manifest, 'file://') !== false) $_manifest = substr($_manifest, 7);

      $log_file = fopen($logs, 'w');
                  fwrite($log_file, file_get_contents(BMI_BACKUPS . DIRECTORY_SEPARATOR . 'latest.log'));
                  fclose($log_file);
      $files = [$logs, $_manifest];

      return $files;

    }

    // Final logs
    public function log_final_batch() {

      $this->output->log('Finalizing backup', 'STEP');
      $this->output->log('Closing files and archives', 'STEP');
      $this->output->log('Archiving of ' . $this->total_files . ' files took: ' . number_format(microtime(true) - floatval($this->backupstart), 2) . 's', 'INFO');

      if (!BMI_CLI_REQUEST) {
        if (!$this->browserSide) sleep(1);
      }

      if (file_exists(BMI_BACKUPS . '/.abort')) {
        $this->send_error('Backup aborted manually by user.', true);
        return;
      }

      $this->send_success();

    }

    // Load batch
    public function load_batch() {

      if (!(file_exists($this->identyFolder) && is_dir($this->identyFolder))) {
        return $this->send_error('Temporary directory does not exist, please start the backup once again.', true);
      }

      $allFiles = scandir($this->identyFolder);
      $files = array_slice((array) $allFiles, 2);
      if (sizeof($files) > 0) {

        $largest = $files[0]; $prev_size = 0;
        for ($i = 0; $i < sizeof($files); ++$i) {
          $curr_size = filesize($this->identyFolder . DIRECTORY_SEPARATOR . $files[$i]);
          if ($curr_size > $prev_size) {
            $largest = $files[$i];
            $prev_size = $curr_size;
          }
        }
        $this->batches_left = sizeof($files);

        if (sizeof($files) == 1) {
          $this->final_batch = true;
        }

        return $this->identyFolder . DIRECTORY_SEPARATOR . $largest;

      } else {

        $this->log_final_batch();
        return false;

      }

    }

    // Cut Path for ZIP structure
    public function cutDir($file) {

      if (substr($file, -4) === '.sql') {

        if ($this->db_v2_engine == true) {

          $path = 'db_tables' . DIRECTORY_SEPARATOR . basename($file);

        } else {

          $path = basename($file);

        }

      } else {

        $path = basename($file);

      }
      
      $path = str_replace('\\', '/', $path);
      return $path;

    }

    // Add files to ZIP – The Backup
    public function add_files($files = [], $file_list = false, $final = false, $dbLog = false) {

      try {
          
        $zipArchive = false;
        if (class_exists('\ZipArchive') || class_exists('ZipArchive')) {
          if (!isset($this->_zip)) {
            $this->_zip = new \ZipArchive();
          }

          if ($this->_zip) {
            $zipArchive = true;
          } else {
            $zipArchive = false;
          }
        }

        // Check if PclZip exists
        if ($zipArchive === false) {
          if (!class_exists('PclZip')) {
            if (!defined('PCLZIP_TEMPORARY_DIR')) {
              $bmi_tmp_dir = BMI_TMP;
              if (!file_exists($bmi_tmp_dir)) {
                @mkdir($bmi_tmp_dir, 0775, true);
              }

              define('PCLZIP_TEMPORARY_DIR', $bmi_tmp_dir . DIRECTORY_SEPARATOR . 'bmi-');
            }
            
            if (!defined('PCLZIP_READ_BLOCK_SIZE')) {
              define('PCLZIP_READ_BLOCK_SIZE', 32768);
            }
          }

          // Require the LIB and check if it's compatible
          $alternative = dirname($this->dir) . '/backup-backup-pro/includes/pcl.php';
          if ($this->rev === 2 || !file_exists($alternative)) {
            require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
          } else {
            require_once $alternative;
            if ($this->it === 1) {
              $this->output->log('Using dedicated PclZIP for Premium Users.', 'INFO');
              if ($dbLog == true) {
                $this->output->log('Adding database SQL file(s) to the backup file.', 'STEP');
              }
            }
          }
          
          // Get/Create the Archive
          if (!isset($this->_lib)) {
            $this->_lib = new \PclZip(BMI_BACKUPS . DIRECTORY_SEPARATOR . $this->backupname);
          }

          if (!$this->_lib) {
            $this->send_error('PHP-ZIP: Permission Denied or zlib cannot be found');
            return;
          }
          
        } else {
          
          $backupPath = BMI_BACKUPS . DIRECTORY_SEPARATOR . $this->backupname;
          if (BMI_CLI_REQUEST) {
            if (!isset($this->zip_initialized)) {
              if (file_exists($backupPath)) $this->_zip->open($backupPath);
              else $this->_zip->open($backupPath, \ZipArchive::CREATE);
            }
          } else {
            if (file_exists($backupPath)) $this->_zip->open($backupPath);
            else $this->_zip->open($backupPath, \ZipArchive::CREATE);
          }
          
          if ($this->it === 1) {
            $this->output->log('Using ZipArchive extension for this backup process.', 'INFO');
            if ($dbLog == true) {
              $this->output->log('Adding database SQL file(s) to the backup file.', 'STEP');
            }
          }
          
        }

        if (sizeof($files) <= 0) {
          return false;
        }
        
        $back = 0;
        $files = array_filter($files, function ($path) {
          if (is_readable($path) && file_exists($path) && !is_link($path)) return true;
          else {
            $this->output->log("Excluding file that cannot be read: " . $path, 'warn');
            return false;
          }
        });

        $_abspath = ABSPATH;
        $_bmi_tmp = BMI_TMP;
        $_wp_content = WP_CONTENT_DIR;
        if (strpos($_abspath, 'file://') !== false) $_abspath = substr($_abspath, 7);
        if (strpos($_bmi_tmp, 'file://') !== false) $_bmi_tmp = substr($_bmi_tmp, 7);
        if (strpos($_wp_content, 'file://') !== false) $_wp_content = substr($_wp_content, 7);

        $needManipulation = false;
        if ( strpos($_wp_content, $_abspath) === false ) {
          $needManipulation = true;
        }

        // Add files
        if ($final || $dbLog) {
          
          if ($zipArchive) {
            for ($i = 0; $i < sizeof($files); ++$i) {
              
              // Add the file
              if (is_dir($files[$i])) {
                $this->_zip->addEmptyDir($this->cutDir($files[$i]));
              } else {
                $this->_zip->addFile($files[$i], $this->cutDir($files[$i]));
              }
              
            }
          } else { 
            
            // Final configuration
            if (sizeof($files) > 0) {
              $back = $this->_lib->add($files, PCLZIP_OPT_REMOVE_PATH, $_bmi_tmp . DIRECTORY_SEPARATOR, PCLZIP_OPT_ADD_TEMP_FILE_ON, PCLZIP_OPT_TEMP_FILE_THRESHOLD, $this->safelimit);
            }
            
          }
          
          if ($dbLog === false) {
            $this->final_made = true;
          }

        } else {
          
          if ($zipArchive) {
            
            for ($i = 0; $i < sizeof($files); ++$i) {

              if ($needManipulation) {
                if (strpos($files[$i], $_wp_content) !== false) {
                  $path = 'wordpress' . DIRECTORY_SEPARATOR . 'wp-content' . DIRECTORY_SEPARATOR . substr($files[$i], strlen($_wp_content));
                } else {
                  $path = 'wordpress' . DIRECTORY_SEPARATOR . substr($files[$i], strlen($_abspath));
                }
              } else {
                $path = 'wordpress' . DIRECTORY_SEPARATOR . substr($files[$i], strlen($_abspath));
              }
              
              $path = BMP::fixSlashes($path);
              
              // Add the file
              if (is_dir($files[$i])) {
                $this->_zip->addEmptyDir($path);
              } else { 
                $this->_zip->addFile($files[$i], $path);
              }
              
            }
            
          } else {

            if ($needManipulation) {
              $coreFiles = [];
              $contentFiles = [];
              foreach ($files as $file) {
                if (strpos($file, $_wp_content) !== false) {
                  $contentFiles[] = $file;
                } else {
                  $coreFiles[] = $file;
                }
              }
              
              $back_1 = $this->_lib->add($coreFiles, PCLZIP_OPT_REMOVE_PATH, $_abspath, PCLZIP_OPT_ADD_PATH, 'wordpress' . DIRECTORY_SEPARATOR, PCLZIP_OPT_ADD_TEMP_FILE_ON, PCLZIP_OPT_TEMP_FILE_THRESHOLD, $this->safelimit);
              $back_2 = $this->_lib->add($contentFiles, PCLZIP_OPT_REMOVE_PATH, $_wp_content, PCLZIP_OPT_ADD_PATH, 'wordpress' . DIRECTORY_SEPARATOR . 'wp-content' . DIRECTORY_SEPARATOR, PCLZIP_OPT_ADD_TEMP_FILE_ON, PCLZIP_OPT_TEMP_FILE_THRESHOLD, $this->safelimit);
              $back = $back_1 && $back_2;
              
            } else {
              $back = $this->_lib->add($files, PCLZIP_OPT_REMOVE_PATH, $_abspath, PCLZIP_OPT_ADD_PATH, 'wordpress' . DIRECTORY_SEPARATOR, PCLZIP_OPT_ADD_TEMP_FILE_ON, PCLZIP_OPT_TEMP_FILE_THRESHOLD, $this->safelimit);
            }
            
          }

        }

        // Check if there was any error
        touch(BMI_BACKUPS . '/.running');
        if ($zipArchive) {
          if (!BMI_CLI_REQUEST || $final) {
            $result = $this->_zip->close();

            if ($result === true) {

              // Remove batch
              if ($file_list && file_exists($file_list)) {
                $this->unlinksafe($file_list);
              }

            } else {
              $this->output->log('not_enough_space', 'verbose');

              $this->send_error('Error, there is most likely not enough space for the backup.');
              return false;

            }
          } else {

            // Remove batch
            if ($file_list && file_exists($file_list)) {
              $this->unlinksafe($file_list);
            }

          }
        } else {
            
          if ($back == 0) {

            $this->send_error($this->_lib->errorInfo(true));
            return false;

          } else {

            if ($file_list && file_exists($file_list)) {
              $this->unlinksafe($file_list);
            }

          }
          
        }

      } catch (\Exception $e) {

        error_log($e->getMessage());
        $this->send_error($e->getMessage());
        return false;

      } catch (\Throwable $e) {

        error_log($e->getMessage());
        $this->send_error($e->getMessage());
        return false;

      }

    }

    // ZIP one of the grouped files
    public function zip_batch() {

      $_dbfile = $this->dbfile;
      $_db_dir_v2 = $this->db_dir_v2;
      if (strpos($_dbfile, 'file://') !== false) $_dbfile = substr($_dbfile, 7);
      if (strpos($_db_dir_v2, 'file://') !== false) $_db_dir_v2 = substr($_db_dir_v2, 7);
              
      if ($this->it === 1) {
        
        $files = [];
        if (file_exists($this->dbfile)) {
          $files[] = $_dbfile;
        } elseif (file_exists($this->db_dir_v2) && is_dir($this->db_dir_v2)) {
          $this->db_v2_engine = true;
          $db_files = scandir($this->db_dir_v2);
          foreach ($db_files as $i => $name) {
            if (!($name == '.' || $name == '..')) {
              $files[] = $_db_dir_v2 . DIRECTORY_SEPARATOR . $name;
            }
          }
        }

        if (sizeof($files) > 0) {
          $this->add_files($files, false, false, true);
          $this->output->log('Database added to the backup file.', 'SUCCESS');
          $this->output->log('Performing site files backup...', 'STEP');
          return true;
        }

        $this->output->log('Performing site files backup...', 'STEP');

      }

      $list_file = $this->load_batch();
      if ($list_file === false) return true;
      $files = explode("\r\n", file_get_contents($list_file));

      $total_size = 0;
      $parsed_files = [];
      
      $absWo = ABSPATH;
      $wpcDirWo = WP_CONTENT_DIR;
      
      if (strpos($absWo, 'file://') !== false) $absWo = substr(ABSPATH, 7);
      if (strpos($wpcDirWo, 'file://') !== false) $wpcDirWo = substr(WP_CONTENT_DIR, 7);

      for ($i = 0; $i < sizeof($files); ++$i) {
        if (strlen(trim($files[$i])) <= 1) {
          $this->total_files--;
          continue;
        }

        $files[$i] = explode(',', $files[$i]);
        $last = sizeof($files[$i]) - 1;
        $size = intval($files[$i][$last]);
        unset($files[$i][$last]);
        $files[$i] = implode(',', $files[$i]);

        $file = null;
        if ($files[$i][0] . $files[$i][1] . $files[$i][2] === '@1@') {
          $file = $wpcDirWo . DIRECTORY_SEPARATOR . substr($files[$i], 3);
        } else if ($files[$i][0] . $files[$i][1] . $files[$i][2] === '@2@') {
          $file = $absWo . DIRECTORY_SEPARATOR . substr($files[$i], 3);
        } else {
          $file = $files[$i];
        }

        if (!file_exists($file)) {
          $this->output->log('Removing this file from backup (it does not exist anymore): ' . $file, 'WARN');
          $this->total_files--;
          continue;
        }

        // if (filesize($file) === 0) {
        //   $this->output->log('Removing this file from backup (file size is equal to 0 bytes): ' . $file, 'WARN');
        //   $this->total_files--;
        //   continue;
        // }

        $parsed_files[] = $file;
        $total_size += $size;
        unset($file);
      }

      unset($files);
      if (sizeof($parsed_files) === 1) {
        $this->output->log('Adding: ' . sizeof($parsed_files) . ' file...' . ' [Size: ' . $this->humanSize($total_size) . ']', 'INFO');
        $this->output->log('Alone-file mode for: ' . $parsed_files[0] . ' file...', 'INFO');
      } else $this->output->log('Adding: ' . sizeof($parsed_files) . ' files...' . ' [Size: ' . $this->humanSize($total_size) . ']', 'INFO');

      if ((60 * (1024 * 1024)) < $total_size) $this->output->log('Current batch is quite large, it may take some time...', 'WARN');

      $this->add_files($parsed_files, $list_file);
      $this->filessofar += sizeof($parsed_files);

      $currentBackupSize = filesize(BMI_BACKUPS . DIRECTORY_SEPARATOR . $this->backupname);
      $this->output->progress($this->filessofar . '/' . $this->total_files);
      $this->output->log('Milestone: ' . $this->filessofar . '/' . $this->total_files . ' [' . $this->batches_left . ' batches left]', 'SUCCESS');
      $this->output->log('Backup file size: ' . $this->humanSize($currentBackupSize), 'VERBOSE');
      $this->output->log('Time since last batch: ' . (time() - $this->startOfBatch) . ' seconds.', 'VERBOSE');
      $this->startOfBatch = time();
      
      if ($this->backupSize > $currentBackupSize)
        return $this->send_error('Backup size is smaller than it should be, it has been damaged, it may not be restorable, abort recommended.', true);
      
      $this->backupSize = $currentBackupSize;

      if ($this->final_batch === true) {
        $this->output->log('Adding final files to this batch...', 'STEP');
        $this->output->log('Adding manifest as addition...', 'INFO');

        $additionalFiles = $this->get_final_batch();
        $this->add_files($additionalFiles, false, true);
        $this->log_final_batch();
        return true;
      }

    }

    // Shutdown callback
    public function shutdown() {

      // Check if there was any error
      $err = error_get_last();
      if ($err != null) {
        Logger::error('Shuted down');
        Logger::error(print_r($err, true));
        $this->output->log('Background process had some issues, more details printed to global logs.', 'WARN');
      }

      // Remove lock
      if (BMI_CLI_REQUEST && $this->lock_cli && file_exists($this->lock_cli)) {
        $this->unlinksafe($this->lock_cli);
      }

      // Send next beat to handle next batch
      if (BMI_CLI_REQUEST) {
        $this->res = [ 'status' => 'success' ];
        return true; 
      }
      
      if (file_exists($this->identyfile)) {

        // Set header for browser
        if ($this->browserSide) {
          
          $this->saveRemoteSettings();
          
          // Content finished
          $this->sendResponse(false);

        } else {

          $this->send_beat();

        }

      } else {
        if (!$this->statusSent && !isset($GLOBALS['BMI::RESPONSE::SENT'])) {
          $this->statusSent = true;
          return BMP::res([ 'status' => 'success' ]);
        }
      }

    }
    
    public function sendResponse($finish = false, $error = false) {
      $res = [
        'status' => 'success',
        'backup_completed' => (($finish == true) ? 'true' : 'false'),
        'backup_process_error' => (($error == true) ? 'true' : 'false')
      ];
      
      if ($error == true)
        $res['status'] = 'error';
      
      $this->res = $res;
      
      if (!$this->statusSent && !isset($GLOBALS['BMI::RESPONSE::SENT'])) {
        $this->statusSent = true;
        BMP::res($res);
      }
      
      return $res;
    }

    // Handle received batch
    public function handle_batch() {

      // Check if aborted
      if (file_exists(BMI_BACKUPS . '/.abort')) {
        if (!isset($this->output)) $this->load_logger();
        $this->res = [ 'status' => 'error' ];
        $this->send_error('Backup aborted manually by user.', true);
        return;
      }

      // Check if it was triggered by verified user
      if (!file_exists($this->identyfile)) {
        $this->res = [ 'status' => 'error' ];
        return;
      }

      // Register shutdown
      register_shutdown_function([$this, 'shutdown']);

      // Load logger
      $this->load_logger();

      set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        Logger::error('Bypasser error:');
        Logger::error($errno . ' - ' . $errstr);
        Logger::error($errfile . ' - ' . $errline);
        
        $this->load_logger();
        $this->output->log('Bypasser error:', 'ERROR');
        $this->output->log($errno . ' - ' . $errstr, 'ERROR');
        $this->output->log($errfile . ' - ' . $errline, 'ERROR');
        
        $this->res = [ 'status' => 'error' ];
        return;
      }, E_ERROR);

      // Notice parent script
      touch($this->identyfile . '-running');
      touch(BMI_BACKUPS . '/.running');

      // CLI case
      if (BMI_CLI_REQUEST) {

        $this->output->log('Starting database backup exporter', 'STEP');
        $this->output->log('Database exporter started via CLI', 'VERBOSE');
        while ($this->dbit !== -1) {
          $this->databaseBackupMaker();
        }

        // Log
        $this->output->log("PHP CLI initialized - process ran successfully", 'SUCCESS');
        $this->make_file_groups();

        // Make ZIP
        $this->output->log('Making archive...', 'STEP');
        while (!$this->final_made) {
          touch($this->identyfile . '-running');
          touch(BMI_BACKUPS . '/.running');
          $this->it += 1;
          $this->zip_batch();
        }

      } else {

        // Background
        if ($this->dbit !== -1) {

          if ($this->dbit === 0) {
            $this->output->log('Background process initialized', 'SUCCESS');
            $this->output->log('Starting database backup exporter', 'STEP');
            $this->output->log('Database exporter started via WEB REQUESTS', 'VERBOSE');
          }

          $this->databaseBackupMaker();
          return $this->res;

        } else {

          if ($this->it === 0) {

            $this->make_file_groups();
            $this->it += 1;
            $this->output->log('Making archive...', 'STEP');

          } else {
            
            $this->zip_batch();
            $this->it += 1;
            
          }

        }

      }

    }

    public function fixSlashes($str, $slash = false) {
      // Old version
      // $str = str_replace('\\\\', DIRECTORY_SEPARATOR, $str);
      // $str = str_replace('\\', DIRECTORY_SEPARATOR, $str);
      // $str = str_replace('\/', DIRECTORY_SEPARATOR, $str);
      // $str = str_replace('/', DIRECTORY_SEPARATOR, $str);

      // if ($str[strlen($str) - 1] == DIRECTORY_SEPARATOR) {
      //   $str = substr($str, 0, -1);
      // }
      
      // Since 1.3.2
      $protocol = '';
      if ($slash == false) $slash = DIRECTORY_SEPARATOR;
      if (substr($str, 0, 7) == 'http://') $protocol = 'http://';
      else if (substr($str, 0, 8) == 'https://') $protocol = 'https://';
      
      $str = substr($str, strlen($protocol));
      $str = preg_replace('/[\\\\\/]+/', $slash, $str);
      $str = rtrim($str, '/\\' );

      return $protocol . $str;
    }
    
    public function isFunctionEnabled($func) {
      $disabled = explode(',', ini_get('disable_functions'));
      $isDisabled = in_array($func, $disabled);
      if (!$isDisabled && function_exists($func)) return true;
      else return false;
    }

    // Database batch maker and dumper
    // We need WP instance for that to get access to wpdb
    public function databaseBackupMaker() {

      if ($this->dbit === -1) {
        $this->res = [ 'status' => 'error' ];
        return;
      }

      // DB File Name for that type of backup
      $dbbackupname = 'bmi_database_backup.sql';
      $database_file = $this->fixSlashes(BMI_TMP . DIRECTORY_SEPARATOR . $dbbackupname);
      $shouldBackupDB = apply_filters('bmip_database_backup', Dashboard\bmi_get_config('BACKUP:DATABASE') == 'true');
      if ( $shouldBackupDB ) {

        if (Dashboard\bmi_get_config('OTHER:BACKUP:DB:SINGLE:FILE') == 'true') {

          // Require Database Manager
          require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'manager.php';

          // Log what's going on
          $this->output->log('Making single-file database backup (using deprecated engine, due to used settings)', 'STEP');

          // Get database dump
          $databaser = new Database(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
          $databaser->exportDatabase($dbbackupname);
          $this->output->log("Database size: " . $this->humanSize(filesize($database_file)), 'INFO');
          $this->output->log('Database (single-file) backup finished.', 'SUCCESS');

          $this->dbitJustFinished = true;
          $this->dbit = -1;
          
          $this->res = [ 'status' => 'success' ];
          return true;

        } else {

          // Log what's going on
          if ($this->dbit === 0) {
            $this->output->log("Making database backup (using v3 engine, requires at least v1.2.2 to restore)", 'STEP');
            $this->output->log("Iterating database...", 'INFO');
          }

          // Require Database Manager
          require_once BMI_INCLUDES . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'better-backup-v3.php';

          $database_file_dir = $this->fixSlashes((dirname($database_file))) . DIRECTORY_SEPARATOR;
          $better_database_files_dir = $database_file_dir . 'db_tables';
          $better_database_files_dir = str_replace('file:', 'file://', $better_database_files_dir);

          if (!is_dir($better_database_files_dir)) @mkdir($better_database_files_dir, 0755, true);
          $db_exporter = new BetterDatabaseExport($better_database_files_dir, $this->output, $this->dbit, intval($this->backupstart));

          $dbBatchingEnabled = false;
          if (Dashboard\bmi_get_config('OTHER:BACKUP:DB:BATCHING') == 'true') {
            $dbBatchingEnabled = true;
          } else {
            if ($this->dbit === 0) {
              $this->output->log("Database batching is disabled in options, consider to use this option if your database backup fails.", 'WARN');
            }
          }

          if (BMI_CLI_REQUEST === true || $dbBatchingEnabled === false) {

            $results = $db_exporter->export();

            $this->output->log("Database backup finished", 'SUCCESS');
            $this->dbitJustFinished = true;
            $this->dbit = -1;
            $this->dblast = 0;

          } else {
            
            $results = $db_exporter->export($this->dbit, $this->dblast);

            $this->dbit = intval($results['batchingStep']);
            $this->dblast = intval($results['finishedQuery']);
            $dbFinished = $results['dumpCompleted'];

            if ($dbFinished == true) {
              $this->output->log("Database backup finished", 'SUCCESS');
              $this->dbitJustFinished = true;
              $this->dbit = -1;
            }

          }

          $this->res = [ 'status' => 'success' ];
          return true;

        }

      } else {

        $this->output->log('Database will not be dumped due to user settings.', 'INFO');
        $this->dbitJustFinished = true;
        $this->dbit = -1;
        
        $this->res = [ 'status' => 'success' ];
        return true;

      }

    }

    public function actionsAfterProcess($success = false) {
      if ($success == true) {
        Logger::log("Backup file created successfully via backup-process.php");
        BMP::handle_after_cron();
      } else {
        Logger::log("Backup file creation failed via backup-process.php");
      }
      
      if (has_action('bmi_premium_after_process') || (defined('BACKUP_TRIGGERED_BY_URL') && BACKUP_TRIGGERED_BY_URL === true)){
        do_action('bmi_premium_after_process', $success, 'backup', defined('BACKUP_TRIGGERED_BY_URL') && BACKUP_TRIGGERED_BY_URL === true);
      }    
      
      return null;

    }
    

  }
