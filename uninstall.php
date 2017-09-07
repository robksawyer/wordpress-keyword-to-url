<?php
if( !defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();
 
delete_option( 'mcn_keyword_to_url_serialized' );
delete_option( 'mcn_keyword_to_url_first_only' );
?>