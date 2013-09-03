<?php
/**
 * Retrieve and use Codex information about capabilities
 * @package WordPress
 * @subpackge ExtendedSuperAdmins
 * @since 0.4a
 * @todo Improve the way parsed Wiki info is stored
 * @todo Improve the way timestamps are checked - hopefully find a way to use the revision date in the Codex to compare against the retrieval timestamp
 */

/**
 * A function to retrieve the Roles and Capabilities page from the WordPress Codex
 * 
 * Relies on the MediaWiki API. If the Codex information has been retrieved in the last 7 days,
 * we return the information as we have it stored in the local database. If not, we add that
 * information to the local database and return it directly from the Codex.
 *
 * Completely rewritten in 0.7
 *
 * @since 0.4a
 * @uses WP_Http::request()
 * @uses maybe_unserialize
 * @uses get_site_transient()
 * @uses add_site_transient()
 * @return string the raw Wiki information from the Codex
 */
function getCodexCapabilities() {
	$capsInfo = maybe_unserialize( get_site_transient( '_esa_capsCodexInfo' ) );
	if( !empty( $capsInfo ) )
		return maybe_unserialize( $capsInfo['pageContent'] );
		/*die( var_dump( $capsInfo['pageContent'] ) );*/
	
	if( !class_exists( 'WP_Http' ) )
		require_once( ABSPATH . '/wp-includes/class-http.php' );
	
	/*$revInfo = new WP_Http();
	$revInfo = maybe_unserialize( $revInfo->request( str_replace( 'rvprop=content|', 'rvprop=', ESA_CODEX_PAGE ) ) );
	$revInfo = maybe_unserialize( $revInfo['body'] );
	$revInfo = maybe_unserialize( $revInfo['query'] );
	$revInfo = maybe_unserialize( $revInfo['pages'] );
	$revInfo = array_shift( $revInfo );
	$revInfo = $revInfo['revisions'][0];
	$revInfo = $revInfo['timestamp'];*/
	/*if( is_array( $capsInfo ) && $capsInfo['time_retrieved'] >= $revInfo ) {
		$capsInfo['time_retrieved'] = time();
		add_site_option( '_esa_capsCodexInfo', $capsInfo );
		unset( $revInfo );
		return $capsInfo['pageContent'];
	}*/
	/*$capsInfo = maybe_unserialize( $capsInfo->request( 'http://codex.wordpress.org/api.php?action=query&prop=revisions&meta=siteinfo&titles=Roles_and_Capabilities&rvprop=content|timestamp&format=php' ) );*/
	$capsInfo = new WP_Http();
	$capsInfo = maybe_unserialize( $capsInfo->request( ESA_CODEX_PAGE . ESA_CODEX_QUERY ) );
	if( is_a( $capsInfo, 'WP_Error' ) )
		wp_die( '<p>' . __( 'While attempting to retrieve the capabilities information from the Codex, we encountered the following error:', ESA_TEXT_DOMAIN ) . '</p>' . $capsInfo->get_error_message() );
	/*if( $capsInfo['response']['code'] != 200 ) {
		if( ( $capsInfo = get_site_option( '_esa_capsCodexInfo', false, true ) ) !== false )
			return $capsInfo['pageContent'];
		else
			return false;
	}*/
	$tmp = maybe_unserialize( $capsInfo['body'] );
	$tmp = maybe_unserialize( $tmp['query'] );
	$tmp = maybe_unserialize( $tmp['pages'] );
	$tmp = array_shift( $tmp );
	$tmp = $tmp['revisions'][0];
	$capsInfo = array( 'time_retrieved' => time(), 'revision_time' => strtotime( $tmp['timestamp'] ), 'pageContent' => $tmp['*'] );
	$revInfo = new WP_Http();
	$pageContent = urlencode( $capsInfo['pageContent'] );
	$pageContent = explode( '%0A%0A', $pageContent );
	while( count( $pageContent ) ) {
		$tmpContent = '';
		while( strlen( $tmpContent ) < 1000 ) {
			if( empty( $pageContent ) )
				continue 2;
			$tmpContent .= array_shift( $pageContent ) . '%0A%0A';
		}
		$page[] = maybe_unserialize( $revInfo->request( ESA_CODEX_PAGE . ESA_CODEX_PARSE_QUERY . $tmpContent ) );
	}
	$page[] = maybe_unserialize( $revInfo->request( ESA_CODEX_PAGE . ESA_CODEX_PARSE_QUERY . $tmpContent ) );
	$content = '';
	foreach( $page as $p ) {
		if( !is_a( $p, 'WP_Error' ) ) {
			$page_info = maybe_unserialize( $p['body'] );
			$page_info = maybe_unserialize( $page_info['parse'] );
			$page_info = maybe_unserialize( $page_info['text'] );
			$page_info = maybe_unserialize( $page_info['*'] );
			
			$page_info = preg_replace( '#<table id="toc" class="toc".+</script>#s', '', $page_info );
			$page_info = preg_replace( '/<!--.+-->/s', '', $page_info );
			$page_info = preg_replace( '#<p><br />\s+</p>#s', '', $page_info );
			$page_info = preg_replace( '#<a name="([^"]+)" id="([^"]+)"></a><h3>#s', '<h3 id="$2">', $page_info );
			$page_info = preg_replace( '#<h2>.+</h2>#s', '', $page_info );
			$page_info = preg_replace( '/href="#([^"]+)"/', 'http://codex.wordpress.org/Roles_and_Capabilities/#$1', $page_info );
			$page_info = preg_replace( '#href="(?!http://)#', 'href="http://codex.wordpress.org$1', $page_info );
			/*$content_start = ( strpos( $page_info, '</script>' ) + strlen( '</script>' ) );
			$content_end = strpos( $page_info, '<!--' );
			$content .= "\n" . 'Content start: ' . $content_start . "\n" . 'Content end: ' . $content_end . "\n" . 'Content length: ' . strlen( $page_info ) . "\n";
			$content .= substr( $page_info, $content_start, ( $content_end - $content_start ) );*/
			$content .= $page_info;
		} else {
			$content .= $p->get_error_message();
		}
	}
	$content = explode( '<h3 id="', $content );
	
	$content_array = array();
	foreach( $content as $c ) {
		$idpos = strpos( $c, '"' );
		$id = substr( $c, 0, $idpos );
		$c = str_replace( $id . '"> <span class="mw-headline">' . $id . '</span></h3>', '', $c );
		if( !empty( $id ) )
			$content_array[$id] = '<div id="_role_caps_' . esc_attr( $id ) . '" class="_role_caps"><h3>' . $id . '</h3><div class="_single_cap">' . trim( $c ) . '</div></div>';
	}
	$capsInfo['pageContent'] = $content_array;
	/*die( '<pre><code>' . htmlentities( $content ) . '</code></pre>' );*/
	set_site_transient( '_esa_capsCodexInfo', $capsInfo, 30 * 24 * 60 * 60 );
	return $capsInfo['pageContent'];
}

/**
 * Determines whether or not the capability is included in the codex info
 * @since 0.7a
 * @param string $cap the name of the cap to look for
 * @param array $caps_descriptions an array containing the items that have already been located
 * @param bool $include_titles deprecated
 * @uses getCodexCapabilities()
 * @return bool whether or not the cap is in the codex info
 */
function findCap( $cap, $caps_descriptions=array(), &$capsPage=NULL ) {
	if( array_key_exists( $cap, $caps_descriptions ) )
		return $caps_description[$cap];
	
	$capsPage = ( empty( $capsPage ) ) ? getCodexCapabilities() : $capsPage;
	return strstr( $capsPage, 'id="' . $cap . '"' );
}

if( !function_exists( 'findCap' ) ) {
	/**
	 * Locates the specific capability information
	 *
	 * Parses the Roles and Capabilities information that was retrieved from the codex, and 
	 * finds the specific capability to be returned.
	 * Returns a semi-parsed version of the information
	 * @since 0.4a
	 * @param string $cap the name of the cap to look for
	 * @param array $caps_descriptions an array containing the items that have already been located
	 * @param bool $include_titles whether or not to include the heading in the returned string
	 * @uses parseWiki()
	 * @uses getCodexCapabilities()
	 * @return string the semi-parsed Wiki information from the Codex
	 */
	function findCap( $cap, $caps_descriptions=array(), $include_titles=false ) {
		$searchstr = '<h3> <span class="mw-headline">' . $cap . '</span></h3>';
		if( array_key_exists( $cap, $caps_descriptions ) )
			return $caps_descriptions[$cap];
		
		$capsPage = getCodexCapabilities();
		if( !strstr( $capsPage, $searchstr ) )
			return false;
		
		$startPos = strpos( $capsPage, $searchstr );
		$endPos = strpos( $capsPage, '<a name="', ( $startPos + strlen( $searchstr ) ) );
		if( empty( $endPos ) )
			$capsInfo = substr( $capsPage, ( $startPos + strlen( $searchstr ) ) );
		else
			$capsInfo = substr( $capsPage, ( $startPos + strlen( $searchstr ) ), ( $endPos - ( $startPos + strlen( $searchstr ) ) ) );
		return '' . ( $include_titles ? '<h3>' . $cap . '</h3>' : '' ) . $capsInfo;
	}
}

/**
 * Parse the Wiki formatting of information from the Codex
 *
 * Currently returns semi-parsed information. Parses the level 3 headers
 * and local links from the Wiki information.
 * @since 0.4a
 * @param string $content the content to be parsed
 * @todo Parse ordered and unordered lists
 * @todo Parse external links
 * @uses wpautop()
 * @return string the semi-parsed information
 */
function parseWiki( $content ) {
	/* If it's already been parsed, or there is nothing to parse, we return the content */
	if( empty($content) || ( stristr( $content, '<p>' ) && !strstr( $content, '[' ) && !strstr( $content, '\'\'\'' ) ) )
		return $content;
	
	/* Parse the content in long-hand (meaning that each function call is on its own line */
	$content = wpautop( $content );
	$content = preg_replace( '/\[\[\#([^\]]+?)\|([^\]]+?)\]\]/', '<a href="http://codex.wordpress.org/Roles_and_Capabilities#$1">$2</a>', $content );
	$content = preg_replace( '/\[\[([^\]]+?)\|([^\]]+?)\]\]/', '<a href="http://codex.wordpress.org/$1" target="_codex_window">$2</a>', $content );
	$content = preg_replace( '/\[([^(\[|\s|\])]+?)\s([^(\[|\s|\])]+?)\]/', '<a href="$1">$2</a>', $content );
	$content = preg_replace( '#\'\'\'([^\']+?)\'\'\'#', '<strong>$1</strong>', $content );
	return $content;
	
	/* Parse the content using nested functions rather than one-at-a-time
	   If we found, for some reason, that the code above was really slow, 
	   we'd try it this way, instead; but I suspect this is actually slower.
	   We normally would never get this far, unless we comment out the lines 
	   above. */
	return preg_replace( '#\'\'\'([^\']+?)\'\'\'#', '<strong>$1</strong>', preg_replace( '/\[([^(\[|\s|\])]+?)\s([^(\[|\s|\])]+?)\]/', '<a href="$1">$2</a>', preg_replace( '/\[\[([^\]]+?)\|([^\]]+?)\]\]/', '<a href="http://codex.wordpress.org/$1" target="_codex_window">$2</a>', preg_replace( '/\[\[\#([^\]]+?)\|([^\]]+?)\]\]/', '<a href="http://codex.wordpress.org/Roles_and_Capabilities#$1">$2</a>', wpautop( $content ) ) ) ) );
	
	/* Try using the Codex API to parse the content. Because this has to make an HTTP
	   request every time, this is really just here for future reference. This code
	   should never be used as-is, which is why we're returning the content before we
	   get here. */
	$capsInfo = new WP_Http;
	$tmp = $capsInfo->request( 'http://codex.wordpress.org/api.php?action=parse&format=php&text=' . urlencode($content) );
	if( 200 == $tmp['response']['code'] ) {
		$tmp = maybe_unserialize( $tmp['body'] );
		$tmp = maybe_unserialize( $tmp['parse'] );
		$tmp = maybe_unserialize( $tmp['text'] );
		$tmp = $tmp['*'];
		return html_entity_decode( $tmp );
	}
}
?>