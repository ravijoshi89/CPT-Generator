(function($){
	$('#wpbody .wrap').wrapInner('<div id="cptg-col-left" />');
	$('#wpbody .wrap').wrapInner('<div id="cptg-cols" />');
	$('#cptg-col-right').removeClass('hidden').prependTo('#cptg-cols');
	$('#cptg-cpt-overview').removeClass('hidden').insertBefore('#cptg-col-left #ajax-response');

	$('#cptg-col-left > .icon32').insertBefore('#cptg-cols');
	$('#cptg-col-left > h2').insertBefore('#cptg-cols');
})(jQuery);