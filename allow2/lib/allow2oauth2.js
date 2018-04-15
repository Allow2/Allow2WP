var allow2Popup = null;

function requestAllow2OauthCode() {

  var host = 'https://api.allow2.com';
  var OAUTHURL = host + '/oauth2/authorize';
  var popupurl = OAUTHURL + '?client_id=' + php_data.token + '&redirect_uri=' + encodeURI(php_data.redirect_uri)
    + '&response_type=code&user_id=' + php_data.user_id;

  var pairButton = jQuery('#allow2-pair-button');
  allow2Popup = window.open(popupurl, "allow2authwindow", 'width=800, height=600');

  var loc = window.location;
  var baseUrl = loc.protocol + "//" + loc.hostname + (loc.port ? ":" + loc.port : "");

  window.addEventListener("message", function (event) {
    if (event.origin != baseUrl) {
      // something from an unknown domain, let's ignore it
      console.log('ignore', event.origin, baseUrl);
      return;
    }

    let code = event.data;

    var response_url = allow2Popup.document.URL;
    var allow2_auth_code = gup(response_url, 'code');
    // We don't actually have an access token yet, have to pass this to the server to complete the sequence
    var data = {
      action: 'allow2_finish_code_exchange',
      nonce: php_data.nonce,
      auth_code: allow2_auth_code
    };
    console.log('ajaxurl', ajaxurl, 'data', data);
    jQuery.ajax({
      url: ajaxurl,
      method: 'POST',
      data: data,
      dataType: 'json',
      error: function (jqXHR, textStatus, errorThrown) {
        console.log(jqXHR, textStatus, errorThrown);
        allow2Popup.location.href = '/?allow2_callback=error';
      },
      success: function (response, textStatus, jqXHR) {
        console.log(response, textStatus, jqXHR);
        if (response.status == "success") {
          allow2Popup.close();
          jQuery('#allow2Connected').removeClass('hidden');
          jQuery('#allow2Connect').addClass('hidden');
          return;
        }
        allow2Popup.location.href = '/?allow2_callback=error';
      }
    });
  });

// 	var pollTimer = window.setInterval(function() { 
// 		try {
// 			console.log('url:', allow2Popup.document.URL);
// 			var offset = allow2Popup.document.URL.indexOf(php_data.redirect_uri);
// 			if ((offset > -1) && (offset < 6)) {
// 				window.clearInterval(pollTimer);
// 				var response_url = allow2Popup.document.URL;
// 				var allow2_auth_code = gup(response_url, 'code');
// 				// We don't actually have an access token yet, have to pass this to the server to complete the sequence
// 				var data = {
// 					action: 'allow2_finish_code_exchange',
// 					nonce: php_data.nonce,
// 					auth_code: allow2_auth_code
// 				};
// 				console.log('ajaxurl', ajaxurl, 'data', data);
// 				jQuery.ajax({
// 					url: ajaxurl,
// 					method: 'POST',
// 					data: data,
// 					dataType: 'json',
// 					error: function( jqXHR, textStatus, errorThrown ) {
// 						console.log(jqXHR, textStatus, errorThrown);
// 						//allow2Popup.location.href='/?allow2_callback=error';
// 					},
// 					success: function( response, textStatus, jqXHR ) {
// 						console.log(response, textStatus, jqXHR);
// 						if (response.status == "success") {
// 							allow2Popup.close();
// 							jQuery('#allow2Connected').removeClass('hidden');
// 							jQuery('#allow2Connect').addClass('hidden');
// 							return;
// 						}
// 						//allow2Popup.location.href='/?allow2_callback=error';
// 					}
// 				});
// 			}
// 		} catch(e) {
// 			console.log('catch', e);
// 		}
// 	}, 500);
}

// helper function to parse out the query string params
function gup(url, name) {
  name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
  var regexS = "[\\?#&]" + name + "=([^&#]*)";
  var regex = new RegExp(regexS);
  var results = regex.exec(url);
  if (results == null)
    return "";
  else
    return results[1];
}

/**
 * checkAllow2Status
 *
 * Calling this routine will trigger a check on the wordpress end for this account.
 * The wordpress plugin will contact the Allow2 service to just check the status of the pairing,
 * if still active the server will return a status 200 (success).
 * if the service has been disconnected, the server will forget the pairing details and
 * and return status 403 (forbidden) in which case we reload the page to allow it to be reconnected.
 */
function checkAllow2Status() {
  var data = {
    action: 'allow2_check_status',
    nonce: php_data.nonce
  };
  var spinner = jQuery('#allow2_status_check_spinner');
  var button = jQuery('#allow2_status_button');
  spinner.css("display", "inline-block");
  button.prop('disabled', true);
  try {
    jQuery.ajax({
      url: ajaxurl,
      method: 'POST',
      data: data,
      dataType: 'json',
      error: function (jqXHR, textStatus, errorThrown) {
        console.log(jqXHR.status);
        spinner.css("display", "none");
        button.prop('disabled', false);
        if (jqXHR.status == 403) {
          // settings have been removed on the server
          jQuery('#allow2Connected').addClass('hidden');
          jQuery('#allow2Connect').removeClass('hidden');
          return;
        }
        alert('Unexpected Error, please try again');
      },
      success: function (response, textStatus, jqXHR) {
        spinner.css("display", "none");
        button.prop('disabled', false);
        console.log(jqXHR.status, response);
        if (response.status != "connected") {	// this is a hack as the return status codes aren't working yet
          // settings have been removed on the server
          jQuery('#allow2Connected').addClass('hidden');
          jQuery('#allow2Connect').removeClass('hidden');
          return;
        }
        alert('Still connected to Allow2');
      }
    });
  } catch (e) {
    spinner.css("display", "none");
    button.prop('disabled', false);
    console.log('catch', e);
  }
}