$( "#region" ).change(function() {
  $( "#region option:selected" ).each(function() {
    var region = $( this ).val();
    $.ajax({
      url: "../xmlhttp.php",
      data: {
        action : 'getprefixes',
        region: region
      },
      type: "post",
      dataType: 'html',
      success: function(response){
        $("#prefix").html(response);
      },
      error: function(response) {
        alert("There was an error "+response.responseText);
      }
    });
  });
});
