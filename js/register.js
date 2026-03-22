$(document).ready(function () {

  $('#reg-btn').on('click', function () {

    var username = $('#username').val().trim();
    var email    = $('#email').val().trim();
    var password = $('#password').val();

    if (!username || !email || !password) {
      showMsg('All fields are required.', 'danger');
      return;
    }

    if (password.length < 6) {
      showMsg('Password must be at least 6 characters.', 'danger');
      return;
    }

    var data = {
      username: username,
      email: email,
      password: password
    };

    $('#reg-btn').prop('disabled', true).text('Registering...');

    $.ajax({
      url: 'php/register.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(data),
      success: function (res) {
        if (res.success) {
          localStorage.removeItem('session_token');
          localStorage.removeItem('user_id');
          showMsg('Account created successfully! Redirecting to login...', 'success');
          setTimeout(function () {
            window.location.href = 'login.html';
          }, 1500);
        } else {
          showMsg(res.message, 'danger');
          $('#reg-btn').prop('disabled', false).text('Register');
        }
      },
      error: function () {
        showMsg('Server error. Please try again.', 'danger');
        $('#reg-btn').prop('disabled', false).text('Register');
      }
    });

  });

  function showMsg(msg, type) {
    $('#msg').html('<div class="alert alert-' + type + '">' + msg + '</div>');
  }

});
