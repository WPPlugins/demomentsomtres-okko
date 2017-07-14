jQuery(document).ready(function (){
  jQuery("div.dms3okkoall").each(function(){
    var d=jQuery(this);
    jQuery.ajax({
      url: dms3okko.ajaxurl,
      data: {
        action: "dms3okkoall",
      },
      success: function(result){
        d.html(result.data);
      },
    });
  });
  jQuery("div.dms3okko").each(function(){
    var d=jQuery(this);
    var slug=d.attr("data");
    jQuery.ajax({
      url: dms3okko.ajaxurl,
      data: {
        action: "dms3okko",
        dms3okkoslug: slug,
      },
      success: function(result){
        d.html(result.data);
      },
    });
  });

});