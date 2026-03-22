$(document).ready(function () {

  if (localStorage.getItem('session_token')) {
    window.location.href = 'profile.html';
    return;
  }

  $('#login-btn').on('click', function () {

    var email    = $('#email').val().trim();
    var password = $('#password').val();

    if (!email || !password) {
      showMsg('Please enter your email and password.', 'danger');
      return;
    }

    var data = {
      email: email,
      password: password
    };

    $('#login-btn').prop('disabled', true).text('Logging in...');

    $.ajax({
      url: 'php/login.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(data),
      success: function (res) {
        if (res.success) {
          localStorage.setItem('session_token', res.token);
          localStorage.setItem('user_id', res.user_id);
          window.location.href = 'profile.html';
        } else {
          showMsg(res.message, 'danger');
          $('#login-btn').prop('disabled', false).text('Login');
        }
      },
      error: function () {
        showMsg('Server error. Please try again.', 'danger');
        $('#login-btn').prop('disabled', false).text('Login');
      }
    });

  });

  function showMsg(msg, type) {
    $('#msg').html('<div class="alert alert-' + type + '">' + msg + '</div>');
  }

});
