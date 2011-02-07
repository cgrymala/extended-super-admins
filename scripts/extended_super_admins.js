jQuery( function( $ ) {
	$('.esa-options-table:not(:last) tbody').hide();
	$('.esa-options-table thead h3').css({'cursor':'pointer'}).click( function() { $(this).closest('.esa-options-table').find('tbody').toggle() });
	$('.esa-options-table div.caps_info').hide().before('<span class="caps_info_hover">(?)</span>');
	$('span.caps_info_hover').css({'cursor':'pointer'}).click( function() { $(this).next('.caps_info').dialog({'position':'center'}); } );
} );