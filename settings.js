jQuery(function () { (function ($) {
    $(".tab").click(function () {
        var href = $(this).attr("href");
        $(".pane").hide();
        $(href).fadeIn("fast");
        $(".tab").removeClass("current");
        $(this).addClass("current");
    });
    $(".button.erase").click(function () {
        if ($(this).is("#erase"))
            return confirm("Are you sure you want to permanently erase all page widgets?");
        return confirm("Are you sure you want to erase these widgets?");
    });
})(jQuery); });
