var allow2Popup = null;

/**
 * startAllow2Request
 *
 * Calling this routine will trigger a check on the wordpress end for this account.
 * The wordpress plugin will contact the Allow2 service to generate a short-lived token password and return it to this call
 * if successful, the short-use token can be used to create the request and then submit it.
 * token lasts for a few hours or until the request is received (and it's automatically deleted).
 */
function startAllow2Request() {
  var data = {
    action: 'allow2_start_request',
    nonce: php_data.nonce,
    a2_sr_n: php_data.a2_sr_n
  };
  var spinner = jQuery('#allow2_make_request_spinner');
  var button = jQuery('#allow2_request_button');
  spinner.css("display", "inline-block");
  button.prop('disabled', true);
  try {
    jQuery.ajax({
      url: ajaxurl,
      method: 'POST',
      data: data,
      dataType: 'json',
      error: function (jqXHR, textStatus, errorThrown) {
        console.log(jqXHR.status, textStatus, errorThrown);
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
        console.log(jqXHR.status, textStatus, response);
        console.log('success');
        makeAllow2Request(php_data.nonce, response.tempToken);
      }
    });
  } catch (e) {
    spinner.css("display", "none");
    button.prop('disabled', false);
    console.log('catch', e);
  }
}

function makeAllow2Request(tempToken, secretToken) {

  var host = 'https://api.allow2.com';
  var requestURL = host + '/request/newRequest?a=' + tempToken + '&b=' + secretToken;
  var allow2RequestPopup = window.open(requestURL, "allow2requestwindow", 'width=800,height=600');
  console.log(requestURL, allow2RequestPopup);
  // todo: detect blocked popups and warn the user they need to allow popups.

}