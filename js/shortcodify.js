jQuery(function() {
	jQuery( ".sc_accordion.autoheight" ).accordion();
    jQuery( ".sc_accordion.collapsible" ).accordion({
	    collapsible: true
    });
    jQuery( ".sc_accordion.no-auto" ).accordion({
	    heightStyle: "content"
    });
});