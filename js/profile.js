$(document).ready(function () {

  var token  = localStorage.getItem('session_token');
  var userId = localStorage.getItem('user_id');

  if (!token || !userId) {
    window.location.href = 'login.html';
    return;
  }

  $.ajax({
    url: 'php/profile.php',
    method: 'GET',
    headers: {
      'X-Session-Token': token,
      'X-User-Id': userId
    },
    success: function (res) {
      if (res.success) {
        if (res.profile) {
          var p = res.profile;
          $('#fullname').val(p.fullname || '');
          $('#age').val(p.age || '');
          $('#dob').val(p.dob || '');
          $('#contact').val(p.contact || '');
          $('#bio').val(p.bio || '');
        }
      } else {
        localStorage.clear();
        window.location.href = 'login.html';
      }
    },
    error: function () {
      localStorage.clear();
      window.location.href = 'login.html';
    }
  });

  $('#save-btn').on('click', function () {

    var data = {
      fullname: $('#fullname').val().trim(),
      age: $('#age').val(),
      dob: $('#dob').val(),
      contact: $('#contact').val().trim(),
      bio: $('#bio').val().trim()
    };

    if (!data.fullname) {
      showMsg('Full name is required.', 'danger');
      return;
    }

    $('#save-btn').prop('disabled', true).text('Saving...');

    $.ajax({
      url: 'php/profile.php',
      method: 'POST',
      contentType: 'application/json',
      headers: {
        'X-Session-Token': token,
        'X-User-Id': userId
      },
      data: JSON.stringify(data),
      success: function (res) {
        if (res.success) {
          showMsg('Profile saved successfully!', 'success');
        } else {
          showMsg(res.message || 'Failed to save profile.', 'danger');
        }
        $('#save-btn').prop('disabled', false).text('Save Profile');
      },
      error: function () {
        showMsg('Server error. Please try again.', 'danger');
        $('#save-btn').prop('disabled', false).text('Save Profile');
      }
    });

  });

  $('#logout-btn').on('click', function () {
    localStorage.removeItem('session_token');
    localStorage.removeItem('user_id');
    window.location.href = 'login.html';
  });

  function showMsg(msg, type) {
    $('#msg').html('<div class="alert alert-' + type + '">' + msg + '</div>');
    setTimeout(function () {
      $('#msg').empty();
    }, 3000);
  }

});
