jQuery(document).ready(function($) {
  var currentlyDisabled = false;

  $.MyPopUps = {
    request_in_process: false,
    site_url: $('#MYPOPUPS_URL').val(),
    Auth: {},
    list: {},
    message: false
  };

  $('.js_test_logged').click(function(e) {
    e.preventDefault();
    $.MyPopUps.displayLogin();
    $.MyPopUps.connectAgree();
    agreed();
  });

  $('.js_refresh').click(function(e) {
    e.preventDefault();
    $.MyPopUps.list = {};
    $('#wp-mypopups-main-list').html('');
    $.MyPopUps.loadPopups();

    $('#wp-mypopups-main-list-empty').hide();
    $('#wp-mypopups-visit-btn').show();
    if ($('.wp-mypopup-item').length == 0) {
      $('#wp-mypopups-main-list-empty').show();
      $('#wp-mypopups-visit-btn').hide();
    }

  });

  $('.js_create_new').click(function() {
    window.open($.MyPopUps.site_url);
  });

  // display messages
  $.MyPopUps.showMessage = function(message, type) {

    if ($.MyPopUps.message) clearTimeout($.MyPopUps.message);
    $.MyPopUps.message = false;
    $('.wp-mypopup-message').remove();
    if (message == '') return;
    var template = _.template($('#wp-mypopup-message-template').html());

    $('#wp-mypopups').before(template({
      "message": message,
      "type": type ? type : ''
    }));

    $.MyPopUps.message = setTimeout(function() {
      $('.wp-mypopup-message').remove();
    }, 10000);

  }

  // display login window from server
  $.MyPopUps.displayLogin = function() {
    let url = $.MyPopUps.site_url + '/api/auth/login';
    let title = 'MyPopUps';
    let options = {
      url,
      title,
      width: 600,
      height: 720
    }
    const dualScreenLeft = window.screenLeft !== undefined ? window.screenLeft : window.screen.left
    const dualScreenTop = window.screenTop !== undefined ? window.screenTop : window.screen.top
    const width = window.innerWidth || document.documentElement.clientWidth || window.screen.width
    const height = window.innerHeight || document.documentElement.clientHeight || window.screen.height
    options.left = ((width / 2) - (options.width / 2)) + dualScreenLeft
    options.top = ((height / 2) - (options.height / 2)) + dualScreenTop
    const optionsStr = Object.keys(options).reduce((acc, key) => {
      acc.push(`${key}=${options[key]}`)
      return acc
    }, []).join(',');

    window.open(url, title, optionsStr, '_blank');
  };

  function isJsonString(str) {
    if (typeof str != 'string') return false;
    try {
      let json = JSON.parse(str);
      return (typeof json === 'object');
    } catch (e) {
      return false;
    }
  }

  // get request from login window and save token
  window.addEventListener('message', function(e) {
    $.MyPopUps.request_in_process = false;
    if (typeof e.data === 'string' && isJsonString(e.data) && e.origin === 'https://mypopups.com') {
      let data = JSON.parse(e.data);
      if (typeof data.type === 'string' && data.type.toUpperCase() === 'AUTH_SUCCESS_MESSAGE') {
        $.MyPopUps.setAuthData(data.response);
      } else {
        jQuery.MyPopUps.showMessage($('#mpu-translations_autherr').text().trim())
      }
    } else {
      if (e.origin.includes('mypopups')) {
        jQuery.MyPopUps.showMessage($('#mpu-translations_orgerr').text().trim())
      }
    }
  }, false);

  $.MyPopUps.setAuthData = function(data, muted) {
    $.MyPopUps.Auth = data;
    var d = new Date();
    d.setTime(d.getTime() + (data.expires_in * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = "MyPopUps_token=" + data.token + ";" + expires + ";path=/";
    $.MyPopUps.loadPopups(muted);
    var domain = window.location.hostname.replace(/^www\./, '');
    var request = {
      action: 'wp_mypopups',
      call_handler: 'pop-up-pop-up_main_ajax_hook',
      user_id: data.id ? data.id : 0,
      token: data.token,
      domain: domain,
      nonce: mypopups_localize_script.nonce
    };
    jQuery.post(ajaxurl, request, function(response) {
      // console.log(response.message);
    });
  };

  $.MyPopUps.processRes = function(request) {

    $.MyPopUps.request_in_process = false;
    $('#wp-mypopups-loader').hide();
    if (request.data) {

      $('#wp-mypopups-welcome').hide();
      $('#wp-mypopups-main').show();
      $('#wp-mypopups-carrousel').show();
      $('#wpfooter').show();

      $.each(request.data, function(i, e) {
        $.MyPopUps.list[e.slug] = $.MyPopUps.convertPopupData(e, false);
        if (e.split_tests) {
          $.each(e.split_tests, function(i, st) {
            $.MyPopUps.list[st.slug] = $.MyPopUps.convertPopupData(st, e.slug);
          });
        }
      });

      $.MyPopUps.showPopups();
      $.MyPopUps.showMoreButton(request.links);
      $.MyPopUps.saveData();

      $('#wp-mypopups-main-list-empty').hide();
      $('#wp-mypopups-visit-btn').show();
      if ($('.wp-mypopup-item').length == 0) {
        $('#wp-mypopups-main-list-empty').show();
        $('#wp-mypopups-visit-btn').hide();
      }

    } else if (request.message) {
      jQuery.MyPopUps.showMessage(request.message);
      $('#wp-mypopups-welcome').show();
    }

  }

  $.MyPopUps.gTBDM = function(data, dm, cb) {

    $.get($.MyPopUps.site_url + '/api/themes/' + dm, data, function(request) {

      cb(request);

    }).fail(function() {
      $.MyPopUps.request_in_process = false;
      $('#wp-mypopups-loader').hide();
      if (!muted) jQuery.MyPopUps.showMessage($('#mpu-translations_noresp').text().trim());
      $('#wp-mypopups-welcome').show();
      cb(false);
    });

  }

  $.MyPopUps.loadPopups = function(muted, page) {
    if ($.MyPopUps.request_in_process) return;
    $.MyPopUps.request_in_process = true;
    $('#wp-mypopups > div').hide();
    $('#wp-mypopups-loader').show();

    var data = {
      'token': $.MyPopUps.Auth.token
    };

    if (page) data.page = page;
    var domain = window.location.hostname.replace(/^www\./, '');

    let themes = [];
    $.MyPopUps.gTBDM(data, domain, function(res1) {
      $.MyPopUps.gTBDM(data, 'www.' + domain, function(res2) {

        let res = false;
        if (res1) {

          if (res === false) res = res1;
          if (res1.data) themes.push(...res1.data);

        }

        if (res2) {

          if (res === false) res = res2;
          if (res2.data) themes.push(...res2.data);

        }

        res.data = themes;
        $.MyPopUps.processRes(res);

      });
    });

  };

  $.MyPopUps.convertPopupData = function(popup, parent) {
    result = {
      "name": popup.name,
      "slug": popup.slug,
      "status": popup.status,
      "url": popup.url,
      "embed_url": popup.embed_url,
    }
    if (parent) result.parent = parent;
    return result;
  }

  $.MyPopUps.showPopups = function() {
    var out = '';
    var template = _.template($('#wp-mypopup-template').html());
    $.each($.MyPopUps.list, function(i, e) {
      out += template(e);
    });
    $('#wp-mypopups-main-list').html(out);

    $('.wp-mypopup-button-text__enabled, .wp-mypopup-button-text__disabled, .wp-mypopup-button').click(function(e) {
      jQuery.MyPopUps.showMessage('')
      var button = $(this);
      if (!button.data('slug'))
        button = $(this).parents('.wp-mypopup-button');

      if (!button.data('slug'))
        button = $(this).parents('.wp-mypopup-button');

      var slug = button.data('slug');
      var status = button.hasClass('wp-mypopup-button__enabled') ? 0 : 1;
      var data = {
        'token': $.MyPopUps.Auth.token,
        'slug': slug,
        'status': status
      };
      jQuery.post($.MyPopUps.site_url + '/api/themes/set-status', data,
        function(response) {
          if (response.success) {
            $.MyPopUps.list[slug].status = status ? $('#mpu-translations_enabled').text().trim() : $('#mpu-translations_disabled').text().trim();
            $.MyPopUps.saveData({
              slug: $.MyPopUps.list[slug]
            });
            if (status)
              $(button).addClass('wp-mypopup-button__enabled');
            else
              $(button).removeClass('wp-mypopup-button__enabled');
          } else {
            let url = $.MyPopUps.site_url;
            if (status) {
              jQuery.MyPopUps.showMessage($('#mpu-translations_cantchanges').text().trim());
            } else {
              jQuery.MyPopUps.showMessage($('#mpu-translations_cantchanges').text().trim());
            }
          }

        }
      ).fail(function(err) {
        let url = $.MyPopUps.site_url;
        let message = $('#mpu-translations_cantchanges').text().trim();

        if (typeof err.responseJSON != 'undefined')
          if (typeof err.responseJSON.message != 'undefined')
            message = err.responseJSON.message;

        jQuery.MyPopUps.showMessage(message);
      });
    });

  }

  $.MyPopUps.saveData = function(list) {
    if (list == undefined) {
      list = $.MyPopUps.list
    }
    var data = {
      action: 'wp_mypopups',
      call_handler: 'pop-up-pop-up_main_ajax_hook',
      list: list,
      nonce: mypopups_localize_script.nonce
    };
    jQuery.post(ajaxurl, data, function(response) {
      // console.log(response.status);
    });
  };

  $.MyPopUps.connectAgree = function() {

    let data = {
      action: 'wp_mypopups',
      call_handler: 'pop-up-pop-up_main_ajax_hook',
      agreed: true,
      nonce: mypopups_localize_script.nonce
    }

    jQuery.post(ajaxurl, data, function(response) {});

  }

  $.MyPopUps.showMoreButton = function(links) {
    $('#wp-mypopups-more-button').remove();
    if (links && links.next) {
      $('#wp-mypopups-main-list').append('<div id="wp-mypopups-more-button">' + $('#mpu-translations-showmore').text().trim() + '</div>');
      var next = parseInt(links.next.substr(links.next.indexOf('page=') + 5));
      $('#wp-mypopups-more-button').click(function() {
        $.MyPopUps.loadPopups(false, next);
      })
    }
  }

  $.MyPopUps.addChat = function() {

    if ($('#support-mpu').length === 0) {
      $('#wp-mypopups').append('<script id="support-mpu" src="//code.jivosite.com/widget/D4LbjyxrUr" async></script>');
      setTimeout(function() {
        $('#jvlabelWrap-fake').hide();
      }, 100);
      var loaded = false;
      let loadinter = setInterval(function() {
        if (loaded == true) clearInterval(loadinter);
        if (typeof window.jivo_api !== 'undefined') {
          window.jivo_api.open()
          loaded = true;
        }
      }, 30);
    }
  }

  $('#jvlabelWrap-fake').on('click', $.MyPopUps.addChat);
  $('#wp-mypopups-loader').css('padding-top', parseInt($(window).height() / 2 - 50) + 'px');

  // get token from cookie
  function agreed() {
    if (currentlyDisabled) return;
    var token = '';
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) == ' ') {
        c = c.substring(1);
      }
      if (c.indexOf('MyPopUps_token=') == 0) {
        token = c.substring('MyPopUps_token='.length, c.length);
        if (typeof token == 'undefined' || `${token}` == 'undefined') {
          document.cookie = "MyPopUps_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
          token = '';
        }
      }
    }

    if (token > '') {
      $.post($.MyPopUps.site_url + '/api/auth/refresh', {
          'token': token
        },
        function(res) {
          $.MyPopUps.setAuthData(res, true);
        }
      )
    }
  }

  if ($('#MYPOPUPS_CAN_CALL').val() === 'true') agreed();
  else {
    currentlyDisabled = true;
    document.cookie = "MyPopUps_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
  }

});
