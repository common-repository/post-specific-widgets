jQuery(function () { (function($) {
  //  The Widgets page loaded as an iframe
  if ($("body").hasClass("widgets-php") && top !== window) {
    var content = $("#wpbody .wrap");
    content.detach();
    $("#wpwrap").replaceWith(content);
    content.css({ paddingLeft: "10px" });
    var width = $("body").width();
    parent.iframeLoaded(width);
    
    $(".widget").css({ position: 'relative', zIndex: 100 });        
  }
})(jQuery); });

// expand and move an iframe to reflect its internal width
function iframeLoaded (width) {
  var tbwindow = jQuery("#TB_window");
  
  // width
  var currentwidth = tbwindow.width();
  var difference = width - currentwidth;

  var marginleft = parseInt(tbwindow.css("margin-left"), 10);
  tbwindow.css({ marginLeft: (marginleft-difference)+"px" });  
  jQuery("#TB_window, iframe").css({ width: (width+20)+"px" });
  
  // height
  if (jQuery(parent.document.body).hasClass("appearance_page_bang-page-widgets-settings")) {
    var winheight = jQuery(parent.document).height();
    winheight = winheight - 80;
    tbwindow.css({ marginTop: (-winheight/2 + 10)+"px", height: (winheight)+"px" });
  
    var titleheight = tbwindow.find("#TB_title").height();
    var frameheight = winheight - titleheight;
    jQuery("iframe").css({ height: (frameheight)+"px" });
  }
}
