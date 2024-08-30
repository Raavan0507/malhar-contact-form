// Malhar's Contact Form JavaScript

jQuery(document).ready(function ($) {
  console.log("Malhar's form script loaded!");

  $("#malhars-contact-form").on("submit", function (e) {
    e.preventDefault();
    console.log("Form submitted, let's process it!");

    var form = $(this);
    var responseDiv = $("#mcf-response");

    // Quick check for empty fields
    var name = $("#mcf-name").val();
    var email = $("#mcf-email").val();
    var message = $("#mcf-message").val();

    if (!name || !email || !message) {
      console.log("Oops, some fields are empty");
      responseDiv
        .removeClass("success")
        .addClass("error")
        .text("Please fill in all fields!");
      return;
    }

    console.log("Sending AJAX request");

    $.ajax({
      type: "POST",
      url: mcf_ajax.ajax_url,
      data:
        form.serialize() + "&action=mcf_submit_form&security=" + mcf_ajax.nonce,
      beforeSend: function () {
        form.find("button").prop("disabled", true).text("Sending");
      },
      success: function (response) {
        console.log("Got response:", response);
        if (response.success) {
          responseDiv
            .removeClass("error")
            .addClass("success")
            .text(response.data);
          form[0].reset();
          console.log("Form submitted successfully!");
        } else {
          responseDiv
            .removeClass("success")
            .addClass("error")
            .text(response.data);
          console.log("Form submission failed:", response.data);
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", status, error);
        responseDiv
          .removeClass("success")
          .addClass("error")
          .text("Oops! Something went wrong. Please try again.");
      },
      complete: function () {
        form.find("button").prop("disabled", false).text("Submit");
        console.log("AJAX request completed");
      },
    });
  });
});
