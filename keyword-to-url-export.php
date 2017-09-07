<?php
if ( isset ( $_POST['mcnkeywordtourlExportContent'] ) ) {
	$mcnkeywordtourlExportContent = $_POST['mcnkeywordtourlExportContent'];
	$content = '';
	if ( strlen($mcnkeywordtourlExportContent) > 0 ) { 
		$e1 = explode( 'mcn1', $mcnkeywordtourlExportContent );
		natcasesort ($e1 );
		foreach ( $e1 as $e1Value ) {
			$e2 = explode( 'mcn0', $e1Value );
			if ( !empty($e2[0]) ) {
				$content .=	$e2[0] . ', ' . $e2[1] . "\r\n";
			}
		}
	}

	header('Content-Type: application/octet-stream');
	//header('Content-type: text/plain');
	header('Content-Disposition: attachment; filename="keywordtourlexport.txt"');
	//header('Content-Length: ' . strlen($content));
	header('Connection: close');
	print $content;
	die();
} else {
	print( "Unauthorized." );
}
?>