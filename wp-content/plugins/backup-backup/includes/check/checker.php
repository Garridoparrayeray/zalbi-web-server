<?php

// Namespace
namespace BMI\Plugin\Checker;

// Use
use BMI\Plugin\BMI_Logger AS Logger;
use BMI\Plugin\Progress\BMI_ZipProgress AS Progress;
use BMI\Plugin\Backup_Migration_Plugin as BMP;

// Exit on direct access
if (!defined('ABSPATH')) exit;

/**
 * BMI_Checker
 */
class BMI_Checker {

  public $issues = array();
  public $progress;

  function __construct($progress = false) {

    $this->progress = $progress;

  }

  public function logs($log, $status = 'INFO') {

    if ($this->progress) {
      $this->progress->log($log, $status);
    }

  }

  public function is_enabled($func) {

    $disabled = explode(',', ini_get('disable_functions'));
    $isDisabled = in_array($func, $disabled);
    if (!$isDisabled && function_exists($func)) return true;
    else return false;

  }

  public function check_free_space($size, $hideRequire = false) {

    if (!$hideRequire) {
      $this->logs(__('Requires at least ', 'backup-backup') . $size . __(' bytes.', 'backup-backup') . ' [' . BMP::humanSize($size) . ']');
    }
    
    $maxTime = 60;
    if ($this->is_enabled('get_ini')) {
      $maxTime = @ini_get('max_execution_time');
      if ($this->is_enabled('ini_set')) @ini_set('max_execution_time', '259200');
    }
    
    $shouldUseDiskFreeSpaceIfAvailable = false;
    
    // If free disk space is larger lower than 50 GBs 
    // OR {
    //   If there is low execution time use space check, as the other may take too much time
    //   If size of the backup is larger than 3 GBs (as it may be to slow to check)
    // }
    if ($this->is_enabled('disk_free_space') && (disk_free_space(BMI_BACKUPS) < 1024*1024*1024*50 || ($size > 1024*1024*1024*3 && $maxTime <= 60)))
      $shouldUseDiskFreeSpaceIfAvailable = true;
    
    if ($this->is_enabled('disk_free_space') && intval(disk_free_space(BMI_BACKUPS)) > 100 && $shouldUseDiskFreeSpaceIfAvailable) {

      $this->logs(__('Disk free space function is not disabled - using it...', 'backup-backup'));
      $this->logs(__('Checking this path/partition: ', 'backup-backup') . BMI_BACKUPS);
      $free = intval(disk_free_space(BMI_BACKUPS));
      $this->logs(__('There is ', 'backup-backup') . number_format($free / 1024 / 1024, 2) . __(' MB free.', 'backup-backup') . ' [' . BMP::humanSize($free) . ']', 'SUCCESS');
      if ($free > $size) {
        $this->logs(__('Great! We have enough space.', 'backup-backup'), 'SUCCESS');
        return true;
      } else {
        return false;
      }

    } else {

      // Log
      $this->logs(__('Disk free space function is disabled by hosting.', 'backup-backup'));
      $this->logs(__('Using dummy file to check free space (it can take some time).', 'backup-backup'));

      // TMP Filename
      $file = BMI_BACKUPS . '/' . '.space_check';
      try {

        $total = $currentTestSize = $size;

        $baseChunk = 65536;
        $maxChunk = 1048576;
        $currentChunk = $baseChunk;
        $iterations = 0;
        
        
        $fh = @fopen($file, 'wb');
        if (!$fh) {
          $this->logs(__('Cannot create test file for space checking.', 'backup-backup'), 'ERROR');
          return false;
        }
        
        $bytesWritten = 0;
        
        while ($currentTestSize > 0) {
          $iterations++;
            
          $writeSize = min($currentChunk, $currentTestSize);
          
          $written = @fwrite($fh, str_repeat('0', $writeSize));
          
          if ($written === false || $written < $writeSize) {
            $this->logs(__('Write failed during space check - insufficient space detected.', 'backup-backup'), 'WARNING');
            fclose($fh);
            if (file_exists($file)) @unlink($file);
            return false;
          }
          
          $bytesWritten += $written;
          $currentTestSize -= $written;
          
          if ($iterations % 10 == 0 && $currentChunk < $maxChunk) {
            $currentChunk = min($currentChunk * 2, $maxChunk);
          }
          
          if ($iterations % 10 == 0) {
            @fflush($fh);
          }
        }
        
        fclose($fh);

        $fs = filesize($file);
        @unlink($file);

        if ($fs > ($total - 1024)) return true;
        else return false;

      } catch (\Exception $e) {

        Logger::error($e);
        if (file_exists($file)) @unlink($file);
        $this->logs(__('Exception during space check: ', 'backup-backup') . $e->getMessage(), 'ERROR');

        return false;

      } catch (\Throwable $e) {

        Logger::error($e);
        if (file_exists($file)) @unlink($file);
        $this->logs(__('Error during space check: ', 'backup-backup') . $e->getMessage(), 'ERROR');

        return false;

      }

    }

  }

}