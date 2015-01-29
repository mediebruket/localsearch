jQuery('form#lokalhistform').on("keyup keypress", function(e) {
  var code = e.keyCode || e.which;
  if (code  == 13) {
    e.preventDefault();
	alert ("Hey");
    return false;
  }
});

// Tabbed content

jQuery(document).ready(function(){
    jQuery("#finnlokalhistorietabs li").click(function(e){
        if (!jQuery(this).hasClass("active")) {
            var tabNum = jQuery(this).index();
            var nthChild = tabNum+1;
            jQuery("#finnlokalhistorietabs li.active").removeClass("active");
            jQuery(this).addClass("active");
            jQuery("#finnlokalhistorietab li.active").removeClass("active");
            jQuery("#finnlokalhistorietab li:nth-child("+nthChild+")").addClass("active");
        }
    });
});

// Facebook sharer window

function fbShare(url, winWidth, winHeight) {
	var winTop = (screen.height / 2) - (winHeight / 2);
	var winLeft = (screen.width / 2) - (winWidth / 2);
	window.open('http://www.facebook.com/sharer.php?u=' + url, 'sharer', 'top=' + winTop + ',left=' + winLeft + ',toolbar=0,status=0,width=' + winWidth + ',height=' + winHeight);
    }

// hide missing images
jQuery("img").error(function(){
        $(this).hide();
});

var finnlokalhistorie_input_selector = '#lokalhistorie_search';

// Start Ready
jQuery(document).ready(function() {

	// Live Search
	// On Search Submit and Get Results
    function lokalhistoriesearch() {
	    var query_value = jQuery(finnlokalhistorie_input_selector).val();
	    var makstreff = jQuery('#finnlokalhist_makstreff').val();
      var show_share_links = jQuery('#finnlokalhist_show_share_links').val();

    jQuery('#finnlokalhistorie_search-string').html(query_value);
		if(query_value !== '') {
		jQuery.ajax({
				type: "POST",
				url: pluginsUrl,
				data: { query: query_value, makstreff : makstreff, show_share_links: show_share_links },
				cache: true,
				success: function(lokalhistoriehtml){
					jQuery("#finnlokalhistorie_results").html(lokalhistoriehtml);
				}
			});
		}return false;
	}

	jQuery( document ).on("keyup", finnlokalhistorie_input_selector, function(e) {
		// Set Timeout
	    clearTimeout(jQuery.data(this, 'timer'));

	    // Set Search String
		var search_string = jQuery(finnlokalhistorie_input_selector).val();
		// Do Search
		if (search_string.length < 3) {
		   	jQuery("#finnlokalhistorie_results").fadeOut();
	    	jQuery('#lokalhistorieresults-text').fadeOut();
	    }else{
	    	jQuery("#finnlokalhistorie_results").fadeIn();
	    	jQuery('#lokalhistorieresults-text').fadeIn();
	    	jQuery(this).data('timer', setTimeout(lokalhistoriesearch, 100));
	    };
	});

});
