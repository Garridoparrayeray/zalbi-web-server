<?php

  /**
   * Main renderer for the Review Banner
   *
   * @category Child Plugin
   * @author iClyde <kontakt@iclyde.pl>
   */

  // Namespace
  namespace Inisev\Subs;

  // Disallow direct access
  if (!defined('ABSPATH')) exit;

  $backupblissPricing = 'https://backupbliss.com/pricing';
  $bbStorage = 'https://storage.backupbliss.com';
  $bmiPremium = 'https://backupbliss.com';

?>

<div class="bmi-banner" id="new-bb-banner">
    <!-- Close (X) button -->
    <img src="<?php echo $this->_asset('imgs/bg.svg'); ?>" alt="Left background" class="bmi-banner__left-bg" />
    <a href="#" class="bmi-banner__close" target="_blank">×</a>

    <div class="bmi-banner__header">
      <div>
        Meet the <span class="bb-highlight">new Backup Migration</span>
        <span class="small">(by BackupBliss)</span>
      </div>
    </div>

    <div class="bmi-banner__cards">
      <!-- Card 1 -->
      <div class="bmi-banner__card" id="bmi-banner__card-free-external-storage" style="display: none;">
        <div class="bmi-banner__card-header">
          <img src="<?php echo $this->_asset('imgs/cloud-options.svg'); ?>" alt="Cloud storage icons" />
           <span>
            More <div class="bmi-banner__free-underlined"><span>free</span></div> external storage options
           </span>
        </div>
        <span class="bmi-banner__card-text">
          You can now save your backups automatically on 
          <b>Google Drive, Dropbox, Amazon S3, FTP</b> etc. for free.
        </span>
      </div>

      <div class="bmi-banner__card bmi-banner__premium-card" id="bmi-banner__card-premium-external-storage" style="display: none;">
        <div class="bmi-banner__img-wrapper">
          <img src="<?php echo $this->_asset('imgs/premium-cloud-options.svg'); ?>" alt="Cloud storage icons" />
        </div>
        <div class="bmi-banner__card-content">
          <div class="bmi-banner__card-header">
            <span>
              More external storage options 
            </span>
          </div>
          <div class="bmi-banner__card-text">
            <span>
              You can now save your backups automatically on 
              <b>Dropbox, Amazon S3, FTP</b> etc. for free.
            </span>
          </div>
        </div>
      </div>

      <!-- Card 2 -->
      <div class="bmi-banner__card" id="bmi-banner__card-free-storage" style="display: none;">
        <div class="bmi-banner__card-header">
          <img src="<?php echo $this->_asset('imgs/1gb-free.svg'); ?>" alt="1 GB free icon" />
          <span>1 GB of <br><div class="bmi-banner__free-underlined"><span>free</span></div> storage</span>
        </div>
        <span class="bmi-banner__card-text">
          We added our <b>own storage option,</b> giving you 
          1 GB of free space (and 
           <a href="<?php echo $backupblissPricing; ?>" target="_blank" class="bmi-links">very affordable</a>
           plans for more)! <a href="<?php echo $bbStorage; ?>" target="_blank" class="bmi-links">Learn more</a>
        </span>
      </div>

      <div class="bmi-banner__card bmi-banner__premium-card" id="bmi-banner__card-premium-storage" style="display: none;">
        <div class="bmi-banner__img-wrapper">
          <img src="<?php echo $this->_asset('imgs/5gb-free.svg'); ?>" alt="5 GB premium icon" />
        </div>
        <div class="bmi-banner__card-content">
          <div class="bmi-banner__card-header">
            <span>
              5 GB of <div class="bmi-banner__free-underlined"><span>free</span></div> storage
            </span>
          </div>
          <div class="bmi-banner__card-text">
            <span>
              We added our <b>own storage option,</b> giving you 5 GB of free space as premium user!
              <a href="<?php echo $bbStorage; ?>" target="_blank" class="bmi-links">Check it out</a>
            </span>
          </div>
        </div>
      </div>


        



      <!-- Card 3 -->
      <div class="bmi-banner__card" id="bmi-banner__card-4gb-upgraded" style="display: none;">
        <div class="bmi-banner__card-header">
          <img src="<?php echo $this->_asset('imgs/4gb-upgraded.svg'); ?>" alt="4 GB double backup size" />
          <span>Double <br> backup size</span>
        </div>
        <span class="bmi-banner__card-text">
          We doubled the supported backup size in the free 
          plugin <b>from 2 GB to 4 GB!</b> (Unlimited in 
          <a href="<?php echo $bmiPremium; ?>" target="_blank" class="bmi-links">premium</a>)
        </span>
      </div>
    </div>

    <div class="bmi-banner__footer">
      <div class="bmi-banner__footer-text">
        <img src="<?php echo $this->_asset('imgs/bmi-logo.svg'); ?>" alt="BackupBliss logo" />
        <span>
          BackupBliss is now the <b>no-brainer solution</b> for backups, 
          migrations and creating staging sites
        </span>
      </div>
      <div>
        <button class="bmi-banner__cta-button redirect-to-bmi">Cool, let's get started!</button>
      </div>
    </div>
    <a href="#" class="bmi-banner__dismiss-link">Dismiss this forever</a>
  </div>