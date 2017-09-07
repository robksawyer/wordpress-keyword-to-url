<?php
/*
Plugin Name: Keyword to URL
Description: The plugin allows administrators to build and save a table of keywords and URLs. Once saved any post (or page) that has the keyword will now show that word (or phrase) as a link.
Version: 1.5
License: GPL

Copyright 2017 MBird

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//-------------------------------------------------
//Filter

add_filter( 'the_content', 'keyword_to_url_filter_handler' );
//add_filter( 'the_excerpt', 'keyword_to_url_filter_handler' );

function keyword_to_url_filter_handler( $content ) {
	
	//---------------------------------------------
	//If the special comment is present then exit
	
	//Users can add this comment anywhere in a post to signal not to use the plugin for that post
	if ( stripos( $content, '<!-- no keywords -->' ) !== false ) { 
		return $content;
	}

	//---------------------------------------------
	//Get the keyword/url list from database
	
	$mcn_keyword_to_url_serialized = get_option('mcn_keyword_to_url_serialized');
	$mcn_keyword_to_url_first_only = get_option('mcn_keyword_to_url_first_only');
	
	//If there are no keywords then exit
	if ( strlen( $mcn_keyword_to_url_serialized ) == 0 ) { return $content; }
	
	//De-serilaize keywords
	$aKeywords = array();
	$s = explode( 'mcn1', $mcn_keyword_to_url_serialized );
	foreach ( $s as $pair ) {
		if ( !empty( $pair[0] ) ) {
			array_push( $aKeywords, explode( 'mcn0', $pair ) );
		}
	}
	
	//---------------------------------------------
	//Find all pre-existing selectors
	
	$aSelector = array();
	$i = 0;
	$startSelector = 0;
	$endSelector = 0;
	
	while( $i < strlen( $content ) ) {
		
		//We have found a selector ('<' followed by a non-space char)
		if ( ( substr($content, $i, 1) == '<' ) && ( substr($content, $i+1, 1) != ' ' ) ) {
			$startSelector = $i;							
			
			//Selectors and their contents that we want to disregard
			//We want to mark the selector, its arguments, and its content
			//Example: we want to mark the entire <a href="localhost">Localhost</a>
			if ( substr($content, $i, 3) == '<a ' ) {			
				while( $i < strlen( $content ) ) {				
					if (substr($content, $i, 4) == '</a>') {	
						$endSelector = $i + 4;
							array_push( $aSelector, array( $startSelector, $endSelector ) );
						break;
					}
					$i++;
				}
			} else if ( 
				( substr( $content, $i, 2 ) == '<h' ) &&
				( is_numeric( substr($content, $i+2, 1 ) ) )
			) {			
				while( $i < strlen( $content ) ) {				
					if ( substr( $content, $i, 3 ) == '</h' ) {	
						$endSelector = $i+3;
							array_push( $aSelector, array( $startSelector, $endSelector ) );
						break;
					}
					$i++;
				}
			} else {
			//Selectors that we want to disregard only their arguments
			//We only want to mark the selector and its arguments, not its content
			//Example: '<p class="test">Test</p>' we only mark '<p class="test">' and '</p>', not 'Test'
				while( $i < strlen( $content ) ) {				
					if ( substr( $content, $i, 1 ) == '>' ) {		
						$endSelector = $i+1;
						array_push( $aSelector, array( $startSelector, $endSelector ) );
						break;
					}
					$i++;
				}
			}
		}
		$i++;
	}
	
	/*
	//Debug
	$aDebug = array();
	for ( $i=0; $i<count($aSelector); $i++ ) {
		array_push( $aDebug, substr($content, $aSelector[$i][0], $aSelector[$i][1]-$aSelector[$i][0]) );
	}
	var_dump($aDebug);
	*/
	
	//---------------------------------------------
	//Find all the keyword boundaries
	
	$aReplace = array();
	for ( $i=0; $i<count($aKeywords); $i++ ) {
		//I'd like to replace the regex below with my own parser in the future
		preg_match_all( '/\b' . $aKeywords[$i][0] .'\b/i', $content, $matches, PREG_OFFSET_CAPTURE );
		$aMatches = $matches[0];
		for ( $j=0; $j<count($aMatches); $j++ ) {
			$m = $aMatches[$j][1]; 
			$n = $m + strlen( $aMatches[$j][0] ) - 1;
			$inAnchor = 0;
			for ( $k=0; $k<count($aSelector); $k++ ) {
				$startAnchor = $aSelector[$k][0];
				$closeAnchor = $aSelector[$k][1];
				if ( ( $m > $startAnchor ) && ( $n < $closeAnchor ) ) {
					$inAnchor = 1;
					break;
				}
			}
			if ( !$inAnchor ) {
				array_push( $aReplace, array( $m, $n, $aMatches[$j][0], $aKeywords[$i][1] ) );
				if ( $mcn_keyword_to_url_first_only == 1 ) {
					break;
				} 
			}
		}
	}
	
	//See notes on the compareOrder funtion below
	usort( $aReplace, 'compareOrder' );
	
	//---------------------------------------------
	//Replace keywords with their URLs

	$i = 0;
	$temp = '';
	for ( $j = 0; $j<count($aReplace); $j++ ) {
		$keywordStart = $aReplace[$j][0];
		if ( $keywordStart > $i ) {
			$keywordEnd = $aReplace[$j][1];
			$keyword = $aReplace[$j][2];
			$url = $aReplace[$j][3];
			$temp .= substr( $content, $i, $keywordStart - $i );
			$urlPrefix = '';
			if ( strpos( $url, '@' ) === false ) {
				$urlPrefix = 'http://';
			} else {
				$urlPrefix = 'mailto:';
			}
			$temp .= '<a href="' . $urlPrefix . $url . '">' . $keyword . '</a>';
			$i = $keywordEnd + 1;
		}
	}
	
	$temp .= substr( $content, $i );
	return ( $temp );
}

//---------------------------------------------
//Utilities

//Reorder the $aReplace array by keywordStart, -KeywordEnd
//This way we order by keyword start and then by the largest keyword that starts there
function compareOrder( $a, $b ) {
	$retval = $a[0] - $b[0];
	if( !$retval ) { 
		return $b[1] - $a[1];
	}
	return $retval;
}

//Our initial string to sort contains the keyword-URL pair with a separator: mcn0
//Example keyword: Site, URL: localhost would look like this: Sitemcn0localhost
//We want to sort only the keyword portion and also have it case-insensitive
//It would be nice to add a natural order algorithm to this in the future 
function compareOrder2( $a, $b ) {

	if ( strtolower($a) == strtolower($b) ) { return 0; }
	
	//Obtain the portion before the separator mcn0
	$ai = strpos( $a, 'mcn0' );
	$aa = substr( $a, 0, $ai );
	
	$bi = strpos( $b, 'mcn0' );
	$bb = substr( $b, 0, $bi );
	
	//Check that portion from above in a case-insensitive manner
	return ( strtolower($aa) < strtolower($bb) ) ? -1 : 1;
}

//-------------------------------------------------
//Admin page

if ( is_admin() ){

	add_action( 'admin_enqueue_scripts', 'add_keyword_to_url_admin_enqueue_scripts' );
	add_action( 'admin_menu', 'add_keyword_to_url_admin_menu' );

	function add_keyword_to_url_admin_enqueue_scripts($hook) {
		
		//Ensure our plugin only loads on our admin page -- rather than on every admin page!
		//We use a global ($add_keyword_to_url_settings_page). See also notes in add_keyword_to_url_admin_menu().
		global $add_keyword_to_url_settings_page;
		if ( $hook != $add_keyword_to_url_settings_page ) {
			return;
		}
		
		wp_enqueue_script( 
			'add_keyword_to_url_script',  
			plugins_url( 'keyword-to-url.js', __FILE__ ), 
			false, 
			'1.5' );
	}

	function add_keyword_to_url_admin_menu() {
		
		//Note if the name had spaces then be sure to use dashes instead of spaces in the slug!
		
		//See notes in add_keyword_to_url_admin_enqueue_scripts($hook)
		
		global $add_keyword_to_url_settings_page;
		$add_keyword_to_url_settings_page = add_options_page(
			'Keyword to URL', 'Keyword to URL', 'administrator',
			'keyword-to-url', 'keyword_to_url_html_page' );
	}
}

function keyword_to_url_html_page() {
?>
	<a id="mcnkeywordtourlBaseAnchor" href="#">Test</a>
	<div>
		
		<p style="color: red;">This plugin is no longer being maintained and should be considered deprecated.</p>
		
		<h2>Keyword to URL Options</h2>
		
		<p>
		Keyword to URL creates a list of keywords with corresponding URLs. 
		<br />Any keyword that appears in your posts (and pages) will show up as a link.
		</p>
		
		<p>
		Keyword: <input id="mcnkeywordtourlKeyword" type="text" />
		URL: <input id="mcnkeywordtourlURL" type="text" />
		<input id="mcnkeywordtourlAdd" type="button" value="Add" />
		<span id="mcnkeywordtourlValid1a" style="color:#f00">* Please enter a Keyword and a URL.</span>
		<span id="mcnkeywordtourlValid2a" style="color:#f00">* Quotes are not allowed in the Keyword or the URL.</span>
		<span id="mcnkeywordtourlValid2c" style="color:#f00">* Ampersands (&amp;) are not allowed in the Keyword.</span>
		<span id="mcnkeywordtourlValid3a" style="color:#f00">* The keyword is already in the table.</span>
		</p>
		
		<table id="mcnkeywordtourlTable">
		<tbody>
		<?php
		$mcn_keyword_to_url_serialized = get_option( 'mcn_keyword_to_url_serialized' );
		if ( strlen( $mcn_keyword_to_url_serialized ) > 0 ) { 
			$e1 = explode( 'mcn1', $mcn_keyword_to_url_serialized );
			usort( $e1, 'compareOrder2' );
			foreach ( $e1 as $e1Value ) {
				$e2 = explode( 'mcn0', $e1Value );
				if ( !empty( $e2[0] ) ) {
					$urlPrefix = ( strpos( $e2[1], '@' ) ) ? 'mailto:' : 'http://';
					print(
						'<tr>' .
							'<td>' . 
								'<span class="mcnkeywordtourlRemoveClass">Remove</span>' .
							'</td>' .
							'<td class="mcnkeywordtourlKeywordClass">' . $e2[0] . '</td>' .
							'<td><a href="' . $urlPrefix . $e2[1] . '">' . $e2[1] . '</a></td>' .
						'</tr>');
				}
			}
		}
		?>
		</tbody>
		</table>
		
		<form method="post" action="options.php">
			<?php wp_nonce_field( 'update-options' ); ?>
			
			<input 
				id="mcn_keyword_to_url_serialized" name="mcn_keyword_to_url_serialized"
				type="hidden"
				value='<?php echo get_option( 'mcn_keyword_to_url_serialized' ); ?>' />
			
			<br />
			<input 
				id="mcn_keyword_to_url_first_only" name="mcn_keyword_to_url_first_only"
				type="checkbox" 
				value="1" <?php checked( get_option( 'mcn_keyword_to_url_first_only' ), 1 ); ?> />
			<span>Just link the first occurrence of the keyword in the post (otherwise all occurrences are linked).</span>
			
			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="page_options" value="mcn_keyword_to_url_serialized, mcn_keyword_to_url_first_only" />
			<p>
			<input type="submit" value="<?php _e( 'Save Changes' ) ?>" />
			<strong>Be sure to Save Changes before leaving this page.</strong>
			</p>
		</form>
		
		<br />
		<h2>Import</h2>
		<div>
			<p>
			<strong>Importing</strong> just updates the list on this page. You need to <strong>Save Changes</strong> to accept the imported items.
			<br />If you would rather cancel the import just refresh or leave this page without clicking <strong>Save Changes</strong>.
			</p>
			<p>
			You may get a message that says <em>Some entries did not import</em>. Reasons for entries being rejected include:
			<br /><em>-You selected the <strong>Append checkbox</strong> and duplicate entries already exist in the table.</em>
			<br /><em>-Duplicate entries exist in your import file.</em>
			<br /><em>-The keyword or its URL contains single or double quotes (which are not allowed).</em>
			<br /><em>-The keyword contains an ampersand (which is not allowed).</em>
			</p>
			
			<form id="mcnkeywordtourlImportForm" name="mcnkeywordtourlImportForm" action="#">
				<p>
				<input 
					id="mcnkeywordtourlImportAppend" name="mcnkeywordtourlImportAppend"
					type="checkbox" 
					value="1" />
				<span>Append imported keywords to the existing list.</span>
				</p>
				<input id="mcnkeywordtourlImportFileChooser" type="file" />
				<input id="mcnkeywordtourlImportLoad" type="button" value="Import" />
				<span id="mcnkeywordtourlValid2b" style="color:#f00">* Some entries did not import.</span>
				<span id="mcnkeywordtourlValid4" style="color:#f00">* Only text files (.csv and .txt) are allowed.</span>
				<span id="mcnkeywordtourlValid5" style="color:#f00">* Your browser does not support HTML5 file reading.</span>
				<br />
			</form>
			
			<p>&nbsp;</p>
			<h2>Export</h2>
			<p><strong>Exporting</strong> only exports what is currently saved.
			<br />Please <strong>Save Changes</strong> if you have updated the table above before exporting.
			</p>
			
			<form 
				method="post" id="mcnkeywordtourlExportForm" name="mcnkeywordtourlExportForm"
				action="<?php echo plugin_dir_url( __FILE__ ); ?>keyword-to-url-export.php">
				<input 
					id="mcnkeywordtourlExportContent" name="mcnkeywordtourlExportContent"
					type="hidden"
					value="<?php echo get_option('mcn_keyword_to_url_serialized'); ?>"
				/>
				<input id="mcnkeywordtourlExport" type="Submit" value="Export" />
			</form>
		</div>
		
	</div>
<?php
}
?>
