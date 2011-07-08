/**
 * Extended Super Admins
 * Admin JavaScript functions
 * @version 0.7
 */

jQuery( function( $ ) {
	$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
	/*postboxes.add_postbox_toggles('settings_page_esa_options_page');
	$('.handlediv').click( function() { $(this).closest('.postbox').toggleClass('closed'); } );*/
	var cap_description_dialogs = {};
	
	$('body').append('<div class="esa-modal-background" id="esa-modal-bg"></div>');
	$('._role_caps h3').each( function() {
		$(this).append('<a class="esa-close-dlg">x</a>');
	} );
	$('._role_caps').hide();
	$('span.caps_info_hover')
		.css({'cursor':'pointer'})
		.each( 
			function() {
				var myID, myClass;
			  	myID = $(this).attr('id');
				myClass = myID.replace(/caps_info_hover_(\d+)_/,'_role_caps_');
				myID = myID.replace(/caps_info_hover_(\d+)_/,'_role_caps_');
				if( !( myID in cap_description_dialogs ) ) {
					$( '#' + myID ).appendTo('#esa-modal-bg');
					cap_description_dialogs[myID] = $( '#' + myID );
				}
			}
		)
		.click( 
			function() { 
				var myID, winWid, winHt, dlgWid, dlgHt;
			  	myID = $(this).attr('id');
				myID = myID.replace(/caps_info_hover_(\d+)_/,'_role_caps_');
				winWid = $(window).width();
				winHt = $(window).height();
				$( '#esa-modal-bg' ).css({'width':winWid + 'px', 'height':winHt + 'px' }).show();
				cap_description_dialogs[myID].show();
				
				dlgWid = cap_description_dialogs[myID].innerWidth();
				dlgHt = cap_description_dialogs[myID].innerHeight();
				cap_description_dialogs[myID].css( { 'left':((winWid - dlgWid) / 2) + 'px', 'top':((winHt - dlgHt) / 2) + 'px' }).click( function(e) { e.stopPropagation(); return true; } );
			}
		);
	$('.esa-modal-background, .esa-close-dlg').click( function() { $('._role_caps').hide(); $('.esa-modal-background').hide(); } );
	$('body').keydown( function(e) {
		if( e.which == 27 ) {
			$('.esa-modal-background').toggle();
			$('._role_caps').hide();
		}
		return true;
	} );
	if( !$.support.cssFloat ) {
		$('.esa-options-table div.checkbox-container:nth-child(6n+1), .esa-options-table div.checkbox-container:nth-child(6n+2), .esa-options-table div.checkbox-container:nth-child(6n+3)').addClass('even-row');
		$('.esa-options-table div.checkbox-container:nth-child(3n+1)').css({'clear':'left'});
		$('.esa-options-table div.checkbox-container:nth-child(3n+3)').css({'clear':'right'});
	}
} );