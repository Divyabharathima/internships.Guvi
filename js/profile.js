$(document).ready(function () {
  $.ajax({
    url: "/internship/php/profile.php",
    data: { gmail: localStorage.getItem('userEmail') },
    method: "POST",
    dataType: "json",
    success: function (response) {
      $('#name').text(response.name);
      $('#age').text(response.age);
      $('#date').text(response.dob);
      $('#contact').text(response.contact);
      $('#address').text(response.address);

      // Edit page code
      $(document).ready(function () {
        // Retrieve the value from stored it in a variable
        var input1 = response.name;
        var input2 = response.age;
        var input3 = response.dob;
        var input4 = response.contact;
        var input5 = response.address;
        // Set the value of the input box
        $('#name1').val(input1);
        $('#age1').val(input2);
        $('#date1').val(input3);
        $('#contact1').val(input4);
        $('#address1').val(input5);
      });
    }
  });

  $('#myButton').click(function () {
    var condition = true;

    if (condition) {
      window.location.href = "/internship/views/profile_edit.html";
      console.log('User Email:', localStorage.getItem('userEmail'));
    }
  });

  function logout() {
    window.location.href = "login.html";
  }

  document.getElementById("logoutButton").addEventListener("click", logout);
});
