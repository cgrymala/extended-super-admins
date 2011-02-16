jQuery( function( $ ) {
	$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
	/*postboxes.add_postbox_toggles('settings_page_esa_options_page');
	$('.handlediv').click( function() { $(this).closest('.postbox').toggleClass('closed'); } );*/
	var cap_description_dialogs = {};
	
	/*$('.esa-options-table tbody').hide();
	$('.esa-options-table thead h3').attr('title','Click to collapse or expand the options for this role').append( ' <span class="collapse-expand">[+]</span>' );
	$('.esa-options-table thead h3').css({'cursor':'pointer'}).click( function() { $(this).closest('.esa-options-table').find('tbody').toggle(0,toggleOptions) });*/
	$('.esa-options-table div.caps_info').each( 
		function() { 
			$(this).attr('id','caps_info_' + $(this).prev('label').attr('for') )
				.before('<span class="caps_info_hover" id="caps_info_hover_' + $(this).prev('label').attr('for') + '">(?)</span>') 
		}
	);
	$('body').append('<div class="esa-modal-background" id="esa-modal-bg"></div>');
	$('span.caps_info_hover')
		.css({'cursor':'pointer'})
		.each( 
			function() {
			  	var myID = $(this).attr('id');
				var myClass = myID.replace(/_hover_role_caps_(\d+)_/,'_role_caps_');
				myID = myID.replace('_hover_','_');
				if( myClass in cap_description_dialogs ) {
					$( '#' + myID ).remove();
				} else {
					$( '#' + myID ).html( '<div class="esa-dlg-inner">' + $( '#' + myID ).html() + '</div>').prepend('<h3>' + $(this).prev('label').text() + '</h3><span class="esa-close-dlg">x</span>').appendTo('#esa-modal-bg');
					cap_description_dialogs[myClass] = $( '#' + myID );
					cap_description_dialogs[myClass].addClass(myClass);
				}
				/*cap_description_dialogs[myID].dialog({
					'title':$(this).prev('label').text(),
					'position':'center',
					autoOpen:false
				}); */
			}
		)
		.click( 
			function() { 
			  	var myID = $(this).attr('id');
				myID = myID.replace(/_hover_role_caps_(\d+)_/,'_role_caps_');
				/*cap_description_dialogs[myID].dialog('open');*/
				var winWid = $(window).width();
				var winHt = $(window).height();
				$( '#esa-modal-bg' ).css({'width':winWid + 'px', 'height':winHt + 'px' }).show();
				cap_description_dialogs[myID].show();
				
				var dlgWid = cap_description_dialogs[myID].innerWidth();
				var dlgHt = cap_description_dialogs[myID].innerHeight();
				cap_description_dialogs[myID].css( { 'left':((winWid - dlgWid) / 2) + 'px', 'top':((winHt - dlgHt) / 2) + 'px' }).click( function(e) { e.stopPropagation(); return true; } );
			}
		);
	$('.esa-modal-background, .esa-close-dlg').click( function() { $('.caps_info').hide(); $('.esa-modal-background').hide(); } );
	$('body').keydown( function(e) {
		if( e.which == 27 ) {
			$('.esa-modal-background').toggle();
			$('.caps_info').hide();
		}
		return true;
	} );
	if( !$.support.cssFloat ) {
		$('.esa-options-table div.checkbox-container:nth-child(6n+1), .esa-options-table div.checkbox-container:nth-child(6n+2), .esa-options-table div.checkbox-container:nth-child(6n+3)').addClass('even-row');
		$('.esa-options-table div.checkbox-container:nth-child(3n+1)').css({'clear':'left'});
		$('.esa-options-table div.checkbox-container:nth-child(3n+3)').css({'clear':'right'});
	}
	/*function toggleOptions() {
		var $collapseExpand = $(this).closest('.esa-options-table').find('h3 span.collapse-expand');
		var collapseExpandIcon = $collapseExpand.html();
		$collapseExpand.html( (collapseExpandIcon == '[-]') ? '[+]' : '[-]' );
	}*/
} );