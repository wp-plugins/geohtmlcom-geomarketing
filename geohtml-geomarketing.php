<?php
/*
Plugin Name: GeoHTML.com GeoMarketing
Plugin URI: http://www.geohtml.com/
Description: A WordPress plugin to change Page and Post content based on visitor location
Version: 0.1
Author: Bellwether Entertainment
Author URI: http://www.geohtml.com/
*/

/*  Copyright 2009  Bellwether Entertainment, LLC  (email : wordpress@geohtml.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Initializes the GeoHTML.com GeoMarketing plugin
 *
 * @since 0.1
 *
 */
if ( !function_exists('geohtml_geomarketing_init') ) :
function geohtml_geomarketing_init() {
	// Initializes the GeoHTML.com GeoMarketing plugin and adds relevant hooks into WordPress
	
	// Add the GeoHTML.com GeoMarketing editor on the Page/Post admin areas
	add_action('edit_form_advanced','geohtml_geomarketing_scripts'); // imports the JavaScript files we'll need
	add_action('edit_page_form','geohtml_geomarketing_scripts'); // imports the JavaScript files we'll need
	if( function_exists( 'add_meta_box' )) {
		add_meta_box( 'geohtml_geomarketing_section', __( 'GeoHTML.com GeoMarketing', 'geohtml_geomarketing_textdomain' ), 'geohtml_geomarketing_editor', 'post', 'advanced', 'high' );
		add_meta_box( 'geohtml_geomarketing_section', __( 'GeoHTML.com GeoMarketing', 'geohtml_geomarketing_textdomain' ),  'geohtml_geomarketing_editor', 'page', 'advanced', 'high' );
	} else {
		add_action('dbx_post_advanced', 'geohtml_geomarketing_editor_legacy' );
		add_action('dbx_page_advanced', 'geohtml_geomarketing_editor_legacy' );
	}
}
endif;

/**
 * References GeoHTML.com JavaScript files to handle saving the GeoMarketing content
 *
 * @since 0.1
 *
 */
if ( !function_exists('geohtml_geomarketing_scripts') ) :
function geohtml_geomarketing_scripts() {
?>
<script src="//www.geohtml.com/js/bws-ajax.js" type="text/javascript"></script>
<script src="//www.geohtml.com/js/wordpress.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="//www.geohtml.com/css/wordpress.css" />
<script type="text/javascript" language="javascript">
//<!--
jQuery(document).ready(Popup_CreateContainer);
//-->
</script>
<?php
}
endif;

/**
 * Generates the GeoHTML.com JavaScript used by visitors to change page content by location
 *
 * @since 0.1
 *
 */
if ( !function_exists('geohtml_geomarketing_pagescripts') ) :
function geohtml_geomarketing_pagescripts() {
	// This will print out the GeoHTMl.com GeoMarkting script reference ... but only if we have
	//		correct settings, are on a page/post page, and have GeoHTML functionality
	//		enabled on the page/post
	$accountID = get_option("geohtml_settings_account");
	if(isset($accountID) && strlen($accountID)) {
		$accountID = urlencode($accountID);
		// Get the post information
		$post = wp_get_single_post();
		
		// Check if this post has GeoMarketing information
		if($post) {
			// Check if this content contains a GeoHTML.com tag
			$geoCampaigns = array();
			$regExp = '/id=[\'"]?geoHTML_campaign_([0-9]+)[\'"]?/';
			$content = $post->post_content;
			if(preg_match_all($regExp, $content, $geoCampaigns) && count($geoCampaigns) > 1)
			{
				// Lowercase content
				$contentLower = strtolower($content);
				// Prep the JavaScript
				$javaScript = "";		
				
				// Get the CampaignIDs
				$matches = $geoCampaigns[1];
				
				// Check if we need to include the GeoHTML.com script reference
				echo geohtml_geomarketing_getscript($accountID,$matches) . "\r\n";
			} // end if-preg_match_all
		} // end if-post
	} // end if-accountID
}
endif;

/**
 * Generates the GeoHTML.com JavaScript file reference to be included on the post/page
 *
 * @since 0.1
 * @param int $accountID The unique identifier for the GeoHTML.com account associated with the JavaScript reference
 */
if ( !function_exists('geohtml_geomarketing_getscript') ) :
function geohtml_geomarketing_getscript($accountID,$campaignIDs) {
	// Get the AccountID as an integer
	$accountID = $accountID + 0;
	if($accountID > 0)
	{
		// get the campaigns as a string
		$campaignsDelimited = "";
		if(is_array($campaignIDs))
		{
			$campaignsDelimited = implode("-", $campaignIDs);
		} else { $campaignsDelimited = $campaignIDs; }
		
		return '<script type="text/javascript" src="//www.geohtml.com/geolocation.js?gh=' . $accountID . '&amp;gc=' . $campaignsDelimited . '"></script>';
	}
}
endif;

/**
 * Adds the JavaScript to change Post/Page content based on target locations if it does not already exist in the Post/Page contnet
 *
 * @since 0.1
 *
 */
if ( !function_exists('geohtml_geomarketing_contentscript') ) :
function geohtml_geomarketing_contentscript($content) {
	// Make sure oure GeoHTML.com settings are valid
	$accountID = get_option("geohtml_settings_account");
	if(!isset($accountID) || strlen($accountID) < 1) { return $content; }
	
	// Check if this content contains a GeoHTML.com tag
	$geoCampaigns = array();
	$regExp = '/id=[\'"]?geoHTML_campaign_([0-9]+)[\'"]?/';
	if(preg_match_all($regExp, $content, $geoCampaigns) && count($geoCampaigns) > 1)
	{
		// Lowercase content
		$contentLower = strtolower($content);
		// Prep the JavaScript
		$javaScript = "";		
		
		// Get the CampaignIDs
		$matches = $geoCampaigns[1];
		
		// Generate the JavaScript wrapper
		$javaScript .= '<script type="text/javascript">' . "\r\n//<!--\r\n";
		
		// Get the JavaScript functions
		foreach($matches as $geoCampaignID)
		{		
			// Get the CampaignID we're working with
			if($geoCampaignID + 0 < 1) {
				continue;
			}
			
			// Generate the JavaScript method
			$javaFunction = 'geoHTML_GetAd(' . $geoCampaignID . ',"geoHTML_campaign_' . $geoCampaignID . '");';
			
			// Add the JavaScript if the content does not already contain it
			if(!strstr($contentLower,strtolower($javaFunction)))
			{
				$javaScript .= $javaFunction . "\r\n";
			}
		}
		
		// Close out the JavaScript
		$javaScript .= "//-->\r\n</script>\r\n";
		
		// Add the JavaScript to the content
		if($javaFunction) // make sure we have least one function added
		{
			$content .= $javaScript;
		}
	}
	return $content;
}
endif;

/**
 * Creates the GeoHTML.com admin area on the Page/Post editor for WordPress >= 2.5
 *
 * @since 0.1
 *
 */
if ( !function_exists('geohtml_geomarketing_editor') ) :
function geohtml_geomarketing_editor() {
	// Use nonce for AJAX verification
	echo '<input type="hidden" name="geohtml_geomarketing_noncename" id="geohtml_geomarketing_noncename" value="' . 
    wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
	
	// Get the post information
	$post = wp_get_single_post();
	
	// Check if this post has GeoMarketing information
	$geoCampaignID = get_post_meta($post->ID,'_geoHTML_Campaign',true);
	if ($geoCampaignID) {
?>
<div id="geohtml_geomarketing_summary"></div>
<script type="text/javascript" language="javascript">
//<![CDATA[
WordPress_ShowSummary("geohtml_geomarketing_summary","<?php echo urlencode($geoCampaignID); ?>");
//]]>
</script>
<?php
	} else { // This does not yet have a GeoMarketing campaign
?>
<div id="geohtml_geomarketing_summary">
<h3 class="alt"><img src="//www.geohtml.com/images/icons/icon_globe.gif" /> Enable GeoMarketing</h3>
<p><a id="btnEnableGeoHTML" href="#" onclick="WordPress_CreateCampaign(&quot;geohtml_geomarketing_summary&quot;, getElement(&quot;title&quot;).value); return false;">Enable GeoMarketing</a> functionality for this page/post.</p>
</div>
<?php
	}
	// Holds dummy information
?>
<div id="geohtml_geomarketing_holder" class="ghost"></div>
<?php
}
endif;

/**
 * Creates the GeoHTML.com admin area on the Page/Post editor for WordPress < 2.5
 *
 * @since 0.1
 *
 */
 if ( !function_exists('geohtml_geomarketing_editor_legacy') ) :
function geohtml_geomarketing_editor_legacy() {
?>
<div class="dbx-b-ox-wrapper">
	<fieldset id="myplugin_fieldsetid" class="dbx-box">
	<div class="dbx-h-andle-wrapper"><h3 class="dbx-handle"><?php __( 'GeoHTML.com GeoMarketing', 'geohtml_geomarketing_textdomain' ) ?></h3></div>
	<div class="dbx-c-ontent-wrapper"><div class="dbx-content"><?php geohtml_geomarketing_editor(); ?></div></div>
	</fieldset>
</div>
<?php
}
endif;

/**
 * Activates the plugin, creating the necessary options
 *
 * @since 0.1
 *
 */
if ( !function_exists('geohtml_geomarketing_activate') ) :
function geohtml_geomarketing_activate() {
	// Manage options
	add_option("geohtml_settings_login", ""); // the login for the GeoHTML account
	add_option("geohtml_settings_password", ""); // the password for the GeoHTML account
	add_option("geohtml_settings_account", ""); // the GeoHTML AccountID to use with this plugin
}
endif;

/**
 * Deactivates the plugin, removing the options
 *
 * @since 0.1
 *
 */
if ( !function_exists('geohtml_geomarketing_deactivate') ) :
function geohtml_geomarketing_deactivate() {
	// Manage options
	delete_option("geohtml_settings_login"); // the login for the GeoHTML account
	delete_option("geohtml_settings_password"); // the password for the GeoHTML account
	delete_option("geohtml_settings_account"); // the GeoHTML AccountID to use with this plugin
	
	// Remove any post related settings
	$allposts = get_posts('numberposts=0&post_type=post&post_status=');
	foreach( $allposts as $postinfo) {
    	delete_post_meta($postinfo->ID, '_geoHTML_Campaign');
	}
}
endif;

/**
 * Updates existing settings
 *
 * @since 0.1
 * @param string $login The login for the GeoHTML account
 * @param string $pass The password for the GeoHTML account
 * @param string $account The GeoHTML AccountID to use with this plugin (Default Value: auto)
 * @param string $basedir The string to remove from the URLs (e.g. the /category/ folder)
 */
if ( !function_exists('geohtml_geomarketing_update') ) :
function geohtml_geomarketing_update($login, $pass, $account) {
	// Update options
	update_option("geohtml_settings_login", $login); // the login for the GeoHTML account
	update_option("geohtml_settings_password", $pass); // the password for the GeoHTML account
	update_option("geohtml_settings_account", $account); // the GeoHTML AccountID to use with this plugin
}
endif;

/**
 * Generates the option menu
 *
 * @since 0.1
 *
 */
if ( !function_exists('geohtml_geomarketing_menu') ) :
function geohtml_geomarketing_menu() {
	// Add the menu option
	add_options_page("GeoHTML.com GeoMarketing", "GeoHTML.com GeoMarketing", "manage_options", __FILE__, "geohtml_geomarketing_admin");
	
	// Init the plugin
	geohtml_geomarketing_init();
}
endif;

/**
 * Generates the admin page
 *
 * @since 0.1
 *
 */
if ( !function_exists('geohtml_geomarketing_admin') ) :
function geohtml_geomarketing_admin() {
	$errorMessage = geohtml_geomarketing_adminsubmit();
	$bSubmitted = strlen($errorMessage) > 0;
	
	// Determine the input values
	$tbEmailAddress = get_option("geohtml_settings_login");
	$tbPassword = "";
	if($bSubmitted) {
		$tbEmailAddress = $_POST["tbEmailAddress"];
		$tbPassword = $_POST["tbPassword"];
	}
	
	// Get scripts and CSS
	geohtml_geomarketing_scripts();
?>
<div id="geohtml_geomarketing_summary">
	<?php echo geohtml_geomarketing_geturl("cmd=site_message&m=wordPress_adminConsole", "tbEmailAddress=" . urlencode($tbEmailAddress) . "&tbPassword=" . urlencode($tbPassword)); ?>
</div>
<?php if ( $bSubmitted ) : ?>
<script type="text/javascript" language="javascript">
//<!--
showElement_NoFade('<?= $errorMessage ?>');
//-->
</script>
<?php endif; ?>
<?
}
endif;

/**
 * Handles the admin page submission
 *
 * @since 0.1
 *
 * @return string
 */
if ( !function_exists('geohtml_geomarketing_adminsubmit') ) :
function geohtml_geomarketing_adminsubmit() {
	// Check if we're updating settings
	if(isset($_POST["tbEmailAddress"]) || isset($_POST["tbPassword"]) || isset($_POST["ddlAccount"]))
	{
		// Get the values
		if(!isset($_POST["tbEmailAddress"]) || strlen($_POST["tbEmailAddress"]) < 1)
		{
			return "tbEmailAddress-rfv";
		}
		if(!isset($_POST["tbPassword"]) || strlen($_POST["tbPassword"]) < 1)
		{
			return "tbPassword-rfv";
		}
		
		// Validate the entries against GeoHTML.com
		$postVariables .=
			"gh=" . urlencode($_POST["ddlAccount"]) .
			"&gl=" . urlencode($_POST["tbEmailAddress"]) .
			"&gp=" . urlencode($_POST["tbPassword"]);		
		
		$accountID = geohtml_geomarketing_geturl("cmd=popup_form&f=wordPress_ValidateSettings",$postVariables);
		if($accountID < 1) {
			echo '<script type="text/javascript">alert("Returned: ' . $accountID . '\\nAccount: ' . $_POST['ddlAccount'] . '\\nLogin: ' . $_POST['tbEmailAddress'] . '\\nPass: ' . $_POST['tbPassword'] . '");</script>';
			return "invalidlogin";
		}
		
		// Save the settings
		geohtml_geomarketing_update($_POST["tbEmailAddress"],$_POST["tbPassword"],$accountID);
		return "settingssaved";
	}
	
	// All set
	return "";
}
endif;

/**
 * Handles the AJAX methods of the plugin functionality
 *
 * @since 0.1
 *
 * @return string
 */
if ( !function_exists('geohtml_geomarketing_ajax') ) :
function geohtml_geomarketing_ajax() {
	// Check if we're doing meta work
	if(isset($_REQUEST["wp_cmd"]))
	{
		switch(strtoupper($_REQUEST["wp_cmd"]))
		{
			case "DELETE-META":
				if(isset($_REQUEST["post_ID"]))
				{
					delete_post_meta($_REQUEST["post_ID"],'_geoHTML_Campaign');
				}
				break;
			case "ADD-META":
				if(isset($_REQUEST["post_ID"]) && isset($_REQUEST['gc']))
				{
					add_post_meta($_REQUEST["post_ID"],'_geoHTML_Campaign',$_REQUEST['gc'],true) or update_post_meta($_REQUEST['post_ID'],'_geoHTML_Campaign',$_REQUEST['gc']);
				}
				break;
			default:
				break;
		}
	} else {
		// Create the GET variable string (this will pass the variables with the addslashes method applied, the AJAX handler is expecting that and will handle removing them)
		$ajaxRequest .= http_build_query($_GET);
		
		// Create the POST variable string (this will pass the variables with the addslashes method applied, the AJAX handler is expecting that and will handle removing them)
		$postVariables = http_build_query($_POST);
		// Modify the postVariables to include account settings
		if(strlen($postVariables) > 0) { $postVariables .= "&"; }
		$postVariables .=
			"gh=" . urlencode(get_option("geohtml_settings_account"));
		if(!strstr($postVariables,"&gl=")) {
			$postVariables .= "&gl=" . urlencode(get_option("geohtml_settings_login"));
		}
		if(!strstr($postVariables,"&gp=")) {
			$postVariables .= "&gp=" . urlencode(get_option("geohtml_settings_password"));
		}
		
		// Get the AJAX response from GeoHTML.com
		echo geohtml_geomarketing_geturl($ajaxRequest,$postVariables);
	}
	
	// Stop processing
	die();
}
endif;

/**
 * Handles retrieving the GeoHTML.com responses for AJAX requests
 *
 * @since 0.1
 * @param string $getVariables The string to include as part of the AJAX URL
 * @param string $postVariables The content string to include as part of the AJAX post request
 * @return string
 */
if ( !function_exists('geohtml_geomarketing_geturl') ) :
function geohtml_geomarketing_geturl($getVariables,$postVariables) {
	// Make sure the parameters are valid
	if(!isset($getVariables) || !isset($postVariables)) { return ""; }
	
	// Call the AJAX handler on GeoHTML.com to save GeoMarketing related changes
	$ajaxRequest = "https://www.geohtml.com/aux_incl/ajax/default.aspx?$getVariables";
		
	// Get the AJAX response from GeoHTML.com
	$contextOptions =
		array( 'http' =>
				array('method' => 'POST',
					  'header' =>
					  "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
					  "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.7) Gecko/2009021910 Firefox/3.0.7 (.NET CLR 3.5.30729)\r\n" .
					  "Content-Type: application/x-www-form-urlencoded; charset=UTF-8\r\n" .
					  "Content-Length: " . strlen($postVariables) . "\r\n",
					  'content' => $postVariables
					  )
			);
	$context = stream_context_create($contextOptions);
	return file_get_contents($ajaxRequest,false,$context);
	//var_dump($http_response_header);;
	//print_r($http_response_header);
}
endif;

// Add activation handlers
register_activation_hook(__FILE__,'geohtml_geomarketing_activate');
register_deactivation_hook(__FILE__,'geohtml_geomarketing_deactivate');

// Add menu handler
add_action('admin_menu','geohtml_geomarketing_menu');
// Add AJAX handler
add_action('wp_ajax_geohtml_geomarketing','geohtml_geomarketing_ajax');
// Add handler for the <head> tag on Page/Post pages
add_action('wp_head', 'geohtml_geomarketing_pagescripts');
// Add handler to change the content on a Page/Post to include the GeoHTML.com script
add_filter('the_content', 'geohtml_geomarketing_contentscript');
?>