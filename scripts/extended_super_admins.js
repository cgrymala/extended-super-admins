/* ESA 0.3a */
jQuery( function( $ ) {
	$('.esa-options-table:not(:last) tbody').hide();
	$('.esa-options-table thead h3').css({'cursor':'pointer'}).click( function() { $(this).closest('.esa-options-table').find('tbody').toggle() });
} );