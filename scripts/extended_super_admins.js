jQuery( function( $ ) {
	var cap_description_dialogs = {};
	
	$('.esa-options-table:not(:last) tbody').hide();
	$('.esa-options-table thead h3').css({'cursor':'pointer'}).click( function() { $(this).closest('.esa-options-table').find('tbody').toggle() });
	$('.esa-options-table div.caps_info').each( 
		function() { 
			$(this).attr('id','caps_info_' + $(this).prev('label').attr('for') )
				.before('<span class="caps_info_hover" id="caps_info_hover_' + $(this).prev('label').attr('for') + '">(?)</span>') 
		}
	);
	$('span.caps_info_hover')
		.css({'cursor':'pointer'})
		.each( 
			function() {
			  	var myID = $(this).attr('id'); 
				myID = myID.replace('_hover_','_');
				cap_description_dialogs[myID] = $('#' + myID );
				cap_description_dialogs[myID].dialog({
					'title':$(this).prev('label').text(),
					'position':'center',
					autoOpen:false
				}); 
			}
		)
		.click( 
			function() { 
			  	var myID = $(this).attr('id'); 
				myID = myID.replace('_hover_','_');
				cap_description_dialogs[myID].dialog('open');
			}
		);
	if( !$.support.cssFloat ) {
		$('.esa-options-table div.checkbox-container:nth-child(6n+1), .esa-options-table div.checkbox-container:nth-child(6n+2), .esa-options-table div.checkbox-container:nth-child(6n+3)').addClass('even-row');
		$('.esa-options-table div.checkbox-container:nth-child(3n+1)').css({'clear':'left'});
		$('.esa-options-table div.checkbox-container:nth-child(3n+3)').css({'clear':'right'});
	}
} );