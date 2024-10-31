jQuery(function () { (function ($) {
    var settings = $("#page-widgets-wrap");
    settings.find(".button.erase").click(function () {
        if ($(this).is("#erase"))
            return confirm("Are you sure you want to permanently erase all page widgets?");
        return confirm("Are you sure you want to erase these widgets?");
    });
})(jQuery); });
