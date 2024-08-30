jQuery(document).ready(function ($) {
  $("#mcf-copy-shortcode").click(function () {
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val($("#mcf-shortcode").text()).select();
    document.execCommand("copy");
    $temp.remove();

    $("#mcf-copy-message").fadeIn().delay(2000).fadeOut();
  });
});
