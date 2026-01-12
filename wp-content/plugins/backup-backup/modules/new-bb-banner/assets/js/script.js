// Always close the code, cause you can make conflicts (same for css use prefixes)
(function ($) {

  
  let nonce = new_bb_banner.dismiss_nonce;
  let is_backup_pro_exists = new_bb_banner.is_backup_pro_exists;
  let current_plugin = new_bb_banner.current_plugin;
  let is_bmi_exists = new_bb_banner.is_bmi_exists;

  if (is_backup_pro_exists) {
    $('#bmi-banner__card-premium-external-storage').show();
    $('#bmi-banner__card-free-external-storage').hide();

    $('#bmi-banner__card-premium-storage').show();
    $('#bmi-banner__card-free-storage').hide();

    $('#bmi-banner__card-4gb-upgraded').hide();

  } else {
    $('#bmi-banner__card-premium-external-storage').hide();
    $('#bmi-banner__card-free-external-storage').show();

    $('#bmi-banner__card-premium-storage').hide();
    $('#bmi-banner__card-free-storage').show();

    $('#bmi-banner__card-4gb-upgraded').show();
  }

  function banner_hide() {
    $('#new-bb-banner').hide(300);
  }

  $('.bmi-banner__cta-button.redirect-to-bmi').on('click', function () {

    let currentUrl = window.location.href;
    let shouldRedirectToBMI = false;

    if (!currentUrl.includes('page=backup-migration')) {
      shouldRedirectToBMI = true;  
    }

    $.post(ajaxurl, { 
      action: 'dismiss_new_bb_banner', 
      nonce: nonce, 
      token: 'new_bb_banner', 
      shouldRedirectToBMI:  shouldRedirectToBMI
    }).done(function (res) {
      if (shouldRedirectToBMI && res.data.redirect) {
        window.location.href = res.data.redirect;
      }
    }).fail(function (err) {
      console.error(err);
    });

    banner_hide();

  });

  $('.bmi-banner__dismiss-link, .bmi-banner__close').on('click', function (e) {
    e.preventDefault();
    $.post(ajaxurl, { 
      action: 'dismiss_new_bb_banner', 
      nonce: nonce, 
      token: 'new_bb_banner'
    }).done(function (res) {
    }).fail(function (err) {
      console.error(err);
    });
    
    banner_hide();
  });

})(jQuery);
