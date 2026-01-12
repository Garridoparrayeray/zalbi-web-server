<?php

  // Namespace
  namespace BMI\Plugin\Dashboard;

  // Exit on direct access
  if (!defined('ABSPATH')) {
    exit;
  }

?>

<div class="overlay-premium">
  <div class="already_ready">
    <div class="flex flexcenter">
      <div>
        <img src="<?php echo $this->get_asset('images', 'premium.svg') ?>" alt="premium-bg" class="fixed-icon-width">
      </div>
    </div>
    <div class="secondary-all">
      <?php echo BMI_ALREADY_IN_PRO; ?>
    </div>
  </div>
</div>
