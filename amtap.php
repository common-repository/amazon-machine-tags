<?php
/*
Plugin Name: Amazon Machine Tags
Plugin URI: http://learningtheworld.eu/2009/amazon-authorization/
Description: The plugin checks for machine tags with ISBN or ASIN numbers, gets the product data from Amazon, and displays it in the sidebar or in a blog article. <a href="options-general.php?page=amtap">Set options</a> where you need to enter your AWS Access Key Identifiers.
Version: 3.0.2
Author: Martin Kliehm
Author URI: http://learningtheworld.eu/

Copyright 2007-2009 by Martin Kliehm <given-name at family-name dot eu>

    The success and error icons were provided by
	http://www.famfamfam.com/lab/icons/silk/
	
	This program is free software; you can redistribute it and/or modify
    it under the terms of the FreeBSD License:
	http://www.freebsd.org/copyright/freebsd-license.html

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    FreeBSD License for more details.
	
There's no code in here to edit. Everything can be edited through the admin
interface, the CSS, XSLT, and translation files. However, if you DO edit this
file, make sure to save it as UTF-8.
*/

/**
 * I18N: load translation (MO) files
 */
if ( defined( 'WPLANG' ) && '' != constant( 'WPLANG' ) )
   load_plugin_textdomain( 'amtap', 'wp-content/plugins/amazon-machine-tags/languages' );

/**
 * Constants
 */
define( 'AMTAP_PLUGINPATH', ABSPATH . 'wp-content/plugins/amazon-machine-tags/' ); // Plugin path
define( 'AMTAP_CACHEPATH', ABSPATH . 'wp-content/cache/' );                 // Cache path

/**
 * Default options
 */
add_option( 'amtap_xml_maxage', 60 * 60 );                                  // Default maximum age of cached HTML: 1 hour
add_option( 'amtap_associate_default', AMTAP::get_locale( true ) );         // Default locale
add_option( 'amtap_headline', 'Further Reading' );                          // Default headline
add_option( 'amtap_target', 'same' );                                       // Default for link target
add_option( 'amtap_rating', 'true' );                                       // Default to show star rating is true
add_option( 'amtap_ip2country', 'true' );                                   // Default for IP to Country is true
add_option( 'amtap_donation', 'false' );                                    // Default for using the author's Associate Tags is false
add_option( 'amtap_item_response_group', 'Images,Medium,Offers,Reviews' );  // Default reponse group for ItemLookup requests
add_option( 'amtap_debug', 'false' );                                       // Debug options are disabled by default
add_filter( 'the_content', array( 'AMTAP', 'get_tags_from_content' ), -10 ); // add content filtering

/**
 * Amazon Machine Tags Plugin (AMTAP) Class
 * @author Martin Kliehm <given-name at family-name dot eu>
 * @version   3.0.2
 * @package   WordPress
 * @since     2.3
 */
class AMTAP {
	/**
	 * init() - Initialize the plugin
	 */
	function init() {
		// Add the plugin to the admin menu
		add_action( 'admin_menu', array( 'AMTAP', 'config_page' ) );
		// Add the plugin CSS to the admin and blog pages
		add_action( 'admin_head', array( 'AMTAP', 'admin_css' ) );
		add_action( 'wp_head', array( 'AMTAP', 'blog_css' ) );
	}
	
	/**
	 * config_page() - Add WordPress action to show the admin configuration page
	 */
	function config_page() {
		if ( function_exists( 'add_submenu_page' ) ) {
			add_options_page( __( 'Amazon Machine Tags Configuration', 'amtap' ), __( 'Amazon Tags', 'amtap' ), 8, 'amtap', array( 'AMTAP', 'admin_page' ) );
		}
	}
	
	/**
	 * admin_page() - Show the admin page
	 */
	function admin_page() {
		// Test access key status
		if ( $sMessage = AMTAP::test_status() )
			$aMessage = explode( ';;', $sMessage );
		/**
		 * Include admin GUI
		 */
		require_once( 'amtap-admin.inc.php' );
	}
	
	/**
	 * verify_key() - AWS access key verification
	 * @param    string   $AWSAccessKeyId
	 * @param    string   $AWSAccessKeySecret
	 * @return   string   $sKeyStatus
	 */
	function verify_key( $AWSAccessKeyId, $AWSAccessKeySecret ) {
		$sRequest = AMTAP::build_rest_url( $AWSAccessKeyId, $AWSAccessKeySecret, true, 'amtap-key-verification.v3.xsl', 'amtap-aws-key-verification' );
		$sKeyStatus = AMTAP::transform( $sRequest, '', 'amtap-key-verification.v3.xsl', 'amtap-aws-key-verification' );
		
		// Set key status
		switch( $sKeyStatus ) {
			case 'true' :
				$sKeyStatus = 'valid';
				update_option( 'amtap_key_status', $sKeyStatus );
				break;
			case 'failed' :
			case false :
				$sKeyStatus = 'failed';
				break;
			case 'nodirectory' :
				$sKeyStatus = 'no_directory';
				break;
			case 'nocache' :
				$sKeyStatus = 'cache_not_writable';
				break;
			default :
				$sKeyStatus = 'invalid';
				break;
		}
		return $sKeyStatus;
	}

	/**
	 * get_items() - Get and print items for a post
	 *
	 * Implement in the sidebar like AMTAP::get_items()
	 * @param    array    $aTags
	 * @param    string   $sType   'content' | 'sidebar'
	 * @return   string   $sResult
	 */
	function get_items( $aTags = '', $sType = 'sidebar' ) {
		// Get default tags
		$sDefaultTags = get_option( 'amtap_default_tags' );
		if ( $sDefaultTags != '' ) {
			$aDefaultTags = explode( ',', $sDefaultTags );
			$aDefaultTags = AMTAP::get_machine_tags( $aDefaultTags );
		}
						
		// Return if this is a category, archive, home, or search page
		$bIsOverviewPage = ( is_category() || is_archive() || is_search() || is_home() );
		if ( !AMTAP::cached_key_status() || ( $sType == 'sidebar' && !$aDefaultTags && $bIsOverviewPage ) )
			return false;
		// Get AWS Access Key
		$AWSAccessKeyId = get_option( 'aws_access_key_id' );
		$AWSAccessKeySecret = get_option( 'aws_access_key_secret' );
		// Get response group
		$sResponseGroup = get_option( 'amtap_item_response_group' );
		// Set XSLT file
		$sXsl = ( $aTags ) ? 'amtap-html-content.v3.xsl' : 'amtap-html-sidebar.v3.xsl';
		$sCacheFilename = 'amtap-aws-items';
		
		// Get top level domain (locale) if override is allowed, or default
		$sTld = ( get_option( 'amtap_ip2country' ) == 'true' ) ? AMTAP::get_locale() : get_option( 'amtap_associate_default' );
		$sAssociateTag = get_option( 'amtap_associate_id_' . $sTld );
		// Associate Tag is empty and donate option is set
		if ( $sAssociateTag == '' && get_option( 'amtap_donation' ) == 'true' )
			$sAssociateTag = AMTAP::get_author_tag( $sTld );
			
		// Get machine tags
		if ( isset( $aDefaultTags ) && $sType == 'sidebar' ) {
			$aFilteredTags = ( $aMachineTags = AMTAP::get_machine_tags( $aTags ) ) ? array_merge( $aDefaultTags, $aMachineTags ) : $aDefaultTags;
		} else {
			$aFilteredTags = AMTAP::get_machine_tags( $aTags );
		}
		
		if ( $aFilteredTags ) {
			// Crop to Amazon's maximum size of 10
			$aFilteredTags = array_slice( $aFilteredTags, 0, 10 );
			// Set tags
			$sItemId = implode( ',', $aFilteredTags );
			// Build REST URL
			$sRequest = AMTAP::build_rest_url( $AWSAccessKeyId, $AWSAccessKeySecret, false, $sXsl, $sCacheFilename, $sTld, 'ItemLookup', $sResponseGroup, $sItemId, $sAssociateTag );
			// XSL Transformation
			$sResult = AMTAP::transform( $sRequest, $sTld, $sXsl, $sCacheFilename, $sType );
			
			// Replace stuff and return the result if the request was successful
			if ( $sResult && $sResult != 'failed' && !strpos( $sResult, 'is not a valid value for AWSAccessKeyId' ) ) {
				// Clean result
				$sResult = AMTAP::clean_result( $sResult, $sTld );
				// Return (X)HTML code
				if ( $aTags ) {
					// Return result in content
					return $sResult;
				} else {
					// Print result to sidebar
					echo $sResult;
					// Print the request URL if debugging is enabled
					if ( get_option( 'amtap_debug' ) == 'true' )
						echo "\n" . '<!-- Debug XML: ' . htmlspecialchars( $sRequest ) . ' -->' . "\n";
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * cached_key_status() - Check content of the cached key verification
	 *
	 * The key is automatically verified when it is entered and saved on the
	 * admin page. If there's no saved file or the saved file doesn't contain
	 * 'true', any request to the AWS API is futile
	 * @return   boolean  true|false
	 */
	function cached_key_status() {
		$sCachefile = AMTAP_CACHEPATH . 'amtap-aws-key-verification.txt';
		if ( file_exists( $sCachefile ) ) {
			// get cached key status
			$sKeyStatus = file_get_contents( $sCachefile );
			if ( $sKeyStatus == 'true' )
				return true;
		} else if ( get_option( 'amtap_key_status' ) == 'valid' ) {
			return true;
		}
		return false;
	}
	
	/**
	 * build_rest_url() - Build Amazon API REST URL
	 * @param    string   $AWSAccessKeyId
	 * @param    string   $AWSAccessKeySecret
	 * @param    boolean  $bValidateKey
	 * @param    string   $sXsl
	 * @param    string   $sCacheFilename
	 * @param    string   $sTld
	 * @param    string   $sOperation
	 * @param    string   $sResponseGroup
	 * @param    string   $sItemId
	 * @param    string   $sAssociateTag
	 * @return   string   $sRequest
	 */
	function build_rest_url( $AWSAccessKeyId, $AWSAccessKeySecret, $bValidateKey = false, $sXsl = 'amtap-html-sidebar.v3.xsl', $sCacheFilename = 'amtap-aws-items', $sTld = 'us', $sOperation = 'Help', $sResponseGroup = 'Help', $sItemId = '', $sAssociateTag = '' ) {
		// Pull in the sha256 encoding
		require_once( 'sha256.inc.php' );
		// Amazon ECS URI
		$sAmazonUri = 'http://xml-' . $sTld . '.amznxslt.com/onca/xml';
		// Amazon Web Services params 
		$aAwsParams = array(
			'Service' => 'AWSECommerceService',
			'Version' => '2009-07-01',
			'AWSAccessKeyId' => $AWSAccessKeyId,
			'Operation' => $sOperation,
			'ResponseGroup' => $sResponseGroup,
			'Timestamp' => gmdate( 'Y-m-d\TH:i:s\Z' )
		);
		$aAwsParams[ 'Style' ] = get_bloginfo( 'wpurl' ) . '/wp-content/plugins/amazon-machine-tags/' . $sXsl;
		$aAwsParams[ 'ContentType' ] = ( $sCacheFilename == 'amtap-aws-items' ) ? 'text/html' : 'text/plain';
		// Assign more default values for a validation request
		if ( $bValidateKey ) {
			$aAwsParams['About'] = 'ItemIds';
			$aAwsParams['HelpType'] = 'ResponseGroup';
		} else {
			$aAwsParams['AMTAPHeadline'] = get_option( 'amtap_headline' );
			if ( get_option( 'amtap_target' ) == 'new' )
				$aAwsParams['AMTAPTarget'] = 'new';
			if ( get_option( 'amtap_rating' ) == 'true' )
				$aAwsParams['AMTAPRating'] = 'show';
		}
		// Assign values for an item request
		if ( $sAssociateTag )
			$aAwsParams['AssociateTag'] = $sAssociateTag;
		if ( $sItemId )
			$aAwsParams['ItemId'] = $sItemId;
		
		// Sort array by key
		ksort( $aAwsParams );
		
		// Construct key/value pars
		foreach( $aAwsParams as $key => $value ) {
			$aAwsPairs[] = str_replace( '%7E', '~', rawurlencode( $key ) ) . '=' . str_replace( '%7E', '~', rawurlencode( $value ) );
		}
		$sQuery = implode( $aAwsPairs, '&' );
		
		// Encode signature
		$sSign = 'GET' . "\n" . 'xml-' . $sTld . '.amznxslt.com' . "\n" . '/onca/xml' . "\n" . $sQuery;
		$hmac = AMTAP::oauth_hmacsha1( $AWSAccessKeySecret, $sSign );
				
		// Return URL
		$sRequest = $sAmazonUri . '?' . $sQuery . '&Signature=' . str_replace( '%7E', '~', rawurlencode( $hmac ) );
		return $sRequest;
	}
	
	/**
	 * transform() - Query the Amazon API, return HTML code
	 * @global   object   $wp_query 
	 * @param    string   $sRequest
	 * @param    string   $sTld
	 * @param    string   $sXsl
	 * @param    string   $sCacheFilename
	 * @param    string   $sType
	 * @return   string   $result
	 */
	function transform( $sRequest = '', $sTld = 'us', $sXsl = 'amtap-html-sidebar.v3.xsl', $sCacheFilename = 'amtap-aws-items', $sType = 'sidebar' ) {
		// Get post ID
		global $wp_query;
		$iPostId = $wp_query->post->ID;
		// Get request URI
		if ( !$sRequest ) $sRequest = AMTAP::build_rest_url( get_option( 'aws_access_key_id' ), get_option( 'aws_access_key_secret' ), true, 'amtap-key-verification.v3.xsl', 'amtap-aws-key-verification' );
		
		// Get content
		// Load cache if it exists
		$sCachefile = ( $sCacheFilename == 'amtap-aws-items' ) ? $sCacheFilename . '-for-post-' . $iPostId . '-in-' . $sType . '-' . $sTld . '.html' : $sCacheFilename . '.txt';
		$sCachefile = AMTAP_CACHEPATH . $sCachefile;
		if ( file_exists( $sCachefile ) ) {
			$result = $oCache = file_get_contents( $sCachefile );
			$iCacheAge = time() - filectime( $sCachefile );
			$bCacheOutdated = ( $iCacheAge < time() - get_option( 'amtap_xml_maxage' ) );
		}
		
		// Get fresh content by REST request if cached XML is too old or doesn't exist
		if ( !$oCache || $bCacheOutdated || $sCacheFilename == 'amtap-aws-key-verification' ) {
			
			// Try to get fresh results with cURL first
			if ( function_exists( 'curl_init' ) ) {
				$oCurlHandle = curl_init();
				curl_setopt( $oCurlHandle, CURLOPT_URL, $sRequest);
				curl_setopt( $oCurlHandle, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt( $oCurlHandle, CURLOPT_RETURNTRANSFER, true);
				curl_setopt( $oCurlHandle, CURLOPT_FAILONERROR, true );
				
				// Get fresh content
				$oFresh = curl_exec( $oCurlHandle );
				// Set result to FALSE if HTTP return code is in error
				$iHttpCode = curl_getinfo( $oCurlHandle, CURLINFO_HTTP_CODE );
				curl_close( $oCurlHandle );
				$result = ( $iHttpCode < 400 ) ? $oFresh : 'failed';
			} else {
				// Try alternate method
				$oFresh = file_get_contents( $sRequest );
				$result = ( $oFresh !== false ) ? $oFresh : 'failed';
			}
		}
		
		// Save file to cache
		if ( $oFresh != 'failed' ) {
			if ( !is_dir( AMTAP_CACHEPATH ) ) {
				// directory doesn't exist
				$result = 'nodirectory';
			} else if ( !is_writable( AMTAP_CACHEPATH ) ) {
				// directory is not writable
				$result = 'nocache';
			} else {
				// save file to cache
				$oFileHandle = fopen( $sCachefile, 'w' );
				fwrite( $oFileHandle, $oFresh );
				fclose( $oFileHandle );
			}
		} else if ( !$oCache ) {
			// Both retrieving methods have failed and there's no cached version
			die ( 'AMTAP: cannot retrieve content from Amazon Web Services or cache' );
		}
		return $result;
	}
	
	/**
	 * clean_result() - Clean XHTML result
	 * @param    string   $sResult
	 * @param    string   $sTld
	 * @return   string   $sResult
	 */
	function clean_result( $sResult, $sTld ) {
		// Cut XML declaration from result if xsl:omit-xml-declaration failed
		$sResult = preg_replace( '/\<\?xml .*\?\>/', '', $sResult );
		// Replace "EUR" with euro character
		$sResult = preg_replace( '/EUR /', '&#8364;&#160;', $sResult );
		$sResult = preg_replace( '/￥ /', '&#165;&#160;', $sResult );
		$sResult = preg_replace( '/£/', '&#163;', $sResult );
		$sResult = preg_replace( '/\$/', '&#36;', $sResult );
		// Replace pages with localized content
		$aPages = array( 'de' => ' Seiten,', 'jp' => 'ページ,' );
		if ( $sTld == 'de' || $sTld == 'fr' || $sTld == 'jp' ) {
			if ( $sTld != 'fr' )
				$sResult = preg_replace( '/ pages,/', $aPages[ $sTld ], $sResult );
			$sResult = preg_replace( '/(lang=")(\w{2})(")/', "$1" . $sTld . "$3", $sResult );
		}
		return $sResult;
	}
	
	/**
	 * test_status() - Triggers key validation, returns status messages
	 */
	function test_status() {
		if ( isset( $_GET['updated'] ) ) {
			// check local server
			$aIP = preg_match( '/(\d*)\.(\d*)\.(\d*)\.(\d*)/', $_SERVER['SERVER_ADDR'], $aMatches );
			$bIsLocal = ( $aMatches[1] == '127' || $aMatches[1] == '10' || $aMatches[1] == '192' && $aMatches[2] == '168' || $aMatches[1] == '172' && intval( $aMatches[2] ) >= 16 && intval( $aMatches[2] ) <= 31 );
			// check AWS Access Key
			$key = get_option( 'aws_access_key_id' );
			$secret = get_option( 'aws_access_key_secret' );
			if ( empty( $key ) || empty( $secret ) ) {
				$sKeyStatus = 'empty';
				$ms[] = 'new_key_empty';
			} else if ( $bIsLocal ) {
				$sKeyStatus = 'local';
				$ms[] = 'localhost';
			} else {
				$sKeyStatus = AMTAP::verify_key( $key, $secret );
			}
			// Set status messages
			if ( $sKeyStatus == 'valid' ) {
				$ms[] = 'new_key_valid';
			} else if ( $sKeyStatus == 'invalid' ) {
				$ms[] = 'new_key_invalid';
			} else if ( $sKeyStatus == 'failed' ) {
				$ms[] = 'no_connection';
			} else if ( $sKeyStatus == 'no_directory' ) {
				$ms[] = 'no_directory';
			} else if ( $sKeyStatus == 'cache_not_writable' ) {
				$ms[] = 'cache_not_writable';
			}
		}
		
		// Status message texts
		$messages = array(
			'new_key_valid' => array( 'status' => 'updated', 'text' => __( 'Your access keys have been verified.', 'amtap' ) ),
			'localhost' => array( 'status' => 'error localhost', 'text' => __( 'Your server is located in a private IP address space. Amazon Web Services cannot fetch the file that is required to validate the access key.', 'amtap' ) ),
			'new_key_empty' => array( 'status' => 'error empty', 'text' => __( 'Your access key has been cleared. However, a valid key is required to access Amazon Web Services.', 'amtap' ) ),
			'ajax_key_empty' => array( 'status' => 'error empty', 'text' => __( 'The access key or the secret key is empty. However, a valid key and secret is required to access Amazon Web Services.', 'amtap' ) ),
			'new_key_invalid' => array( 'status' => 'error invalid', 'text' => __( 'The access key or the secret key you entered is invalid. Please double-check it.', 'amtap' ) ),
			'no_connection' => array( 'status' => 'error connection', 'text' => __( 'No connection to Amazon Web Services. Please retry later.', 'amtap' ) ),
			'no_directory' => array( 'status' => 'error directory', 'text' => __( 'Cache directory does not exist. Please create wp-content/cache/', 'amtap' ) ),
			'cache_not_writable' => array( 'status' => 'error cache', 'text' => __( 'Cache directory is not writable. Please check directory permissions.', 'amtap' ) )
		);
		
		// Print or return status messages
		if ( count( $ms ) ) {
			foreach ( $ms as $m ) {
				$sMessage = $messages[$m]['status'].';;'.$messages[$m]['text'];
				// Return to PHP
				return $sMessage;
			}
		} else {
			return false;
		}
	}

	/**
	 * get_the_tags() - Return an array of post tags
	 *
	 * Checks different ways to access implemented tags and returns them
	 * as an array or FALSE if there are no tags
	 * @global   object          $wpdb
	 * @global   object          $wp_query
	 * @return   array|boolean   $aTags|false
	 */
 	function get_the_tags() {
		global $wp_query;
		$aTags = array();
		
		// Get tags from the WP 2.3 core function
		if ( function_exists( get_the_tags ) ) {
			$aWpTags = get_the_tags( $wp_query->post->ID );
			if ( $aWpTags ) {
				foreach( $aWpTags as $tag ) { 
					$aTags[] = $tag->name; 
				}
			}
		}
		// Try to get it somewhere else
		if ( !count( $aTags ) ) {
			if ( function_exists( get_bunny_tags ) ) {
				// Bunny Tags
				// Try to get the tags from the tags field
				$aTags = get_bunny_tags( ' ', 'tags' );
				// Try to get the tags from the keywords field
				if ( !count( $aTags ) )
					$aTags = get_bunny_tags( ', ', 'keywords' );
			} else if ( function_exists( get_the_post_keywords ) ) {
				// Jerome's Keywords
				$aTags = explode( ',', get_the_post_keywords() );
			}
		}
		
		// Return the array or false
		if ( count( $aTags ) ) {
			return $aTags;
		} else {
			return false;
		}
	}
	
	/**
	 * get_machine_tags() - Return an array of filtered machine tags
	 *
	 * Searches for tags matching the machine tag structure with "isbn", "ean", or "asin" as predicate
	 * @return   array|boolean   $aFilteredTags|false
	 */
 	function get_machine_tags( $aTags = '' ) {
		// Get tags
		if ( !$aTags )
			$aTags = AMTAP::get_the_tags();
		$aFilteredTags = array();
		
		if ( $aTags ) {
			foreach ( $aTags as $sTag ) {
				// Search for tags matching the machine tag structure with "isbn", "ean", or "asin" as predicate
				if ( preg_match( '/(amazon|book):(asin|ean|isbn)=([\w]+)[\-]?([\w?]*)/i', $sTag, $matches ) ) {
					$aFilteredTags[] = $matches[3].$matches[4];
				}
			}
		}
		
		// Return the array or false
		if ( count( $aFilteredTags ) ) {
			return $aFilteredTags;
		} else {
			return false;
		}
	}
	
	/**
	 * get_tags_from_content() - Get tags from WordPress content
	 *
	 * Searches for a flag in the form [amtap book:isbn=1234567890]
	 * @param    string   $sContent
	 * @return   string   $sContent   flags replaced by items
	 */
	function get_tags_from_content( $sContent ) {
		// echo 'get_tags_from_content';
		if ( preg_match_all( '/\[amtap\s((amazon|book):(asin|ean|isbn)=(\w+))\]/six', $sContent, $aMatches ) ) {
			// Get all tags
			foreach ( $aMatches[0] as $sFlag ) {
				// Add slashes for the regex
				$aFlags[] = '/\\' . $sFlag . '/';
			}
			
			// Get items from Amazon
			$sItems = AMTAP::get_items( $aMatches[1], 'content' );
			
			// Remove line breaks to prevent the automatic inclusion of <br/> elements
			$sItems = preg_replace( '/(\015\012)|(\015)|(\012)/', '', $sItems);
			// Match anything within a division
			preg_match_all( '/<div[^>]*>(.*?)<\/div>/ix', $sItems, $aItems, null );
			// Replace flags with items
			$sContent = preg_replace( $aFlags, $aItems[0], $sContent );
		}
		return $sContent;
	}
	
	/**
	 * get_locale() - Get locale by IP
	 * @param    boolean  $bInit
	 * @return   string   $sLocale
	 */
	function get_locale( $bInit = false ) {
		// Set array of Amazon locales
		$aLocales = array( 'ca', 'de', 'fr', 'jp', 'uk', 'us' );
		// Set EU country codes
		$aEurope = array( 'ad', 'al', 'at', 'ba', 'be', 'bg', 'by', 'ch', 'cz', 'de', 'dk', 'ee', 'es', 'fi', 'fr', 'gr', 'hr', 'hu', 'ie', 'it', 'kz', 'li', 'lu', 'lv', 'mc', 'md', 'me', 'mk', 'mt', 'nl', 'no', 'pl', 'pt', 'ro', 'rs', 'ru', 'se', 'si', 'sk', 'sm', 'tr', 'ua', 'va', 'uk', 'yu' );
		
		if ( function_exists( 'wp_ip2c_getCountryCode2' ) ) {
			// Get 2 letter country code from IP to Country DB
			$sCountrycode = wp_ip2c_getCountryCode2( 0, $_SERVER[ 'REMOTE_ADDR' ] );
			if ( $sCountrycode == 'gb' )
				$sCountrycode = 'uk';
			
			// Compare country codes with locales
			foreach ( $aLocales as $sLocale ) {
				// Country code is exact match
				if ( $sLocale == $sCountrycode )
					return $sLocale;
			}
			// no exact match, but country is within Europe
			foreach ( $aEurope as $sCountry ) {
				// Country is within the EU
				if ( $sCountry == $sCountrycode ) {
					// Match languages
					$sLang = AMTAP::get_language();
					return ( $sLang == 'en' ) ? 'uk' : $sLang;
				}
			}
		}
		// no match: return default or US if default is not set
		return ( $bInit ) ? 'us' : get_option( 'amtap_associate_default' );
	}
	
	/**
	 * get_language() - Get HTTP header accept languages
	 */
	function get_language() {
		// European Amazon languages
		$aLang = array( 'de', 'en', 'fr' );
		$aUserLang = array();
		
		// Get browser languages from HTTP accept header
		if ( array_key_exists( 'HTTP_ACCEPT_LANGUAGE', $_SERVER ) && $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) {
			$aAcceptLang = explode( ',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
			foreach ( $aAcceptLang as $sAcceptLang ) {
				$aTempLang = explode( ';', $sAcceptLang );
				$aTempLangBase = explode( '-', trim( $aTempLang[0] ) );
				$aUserLang[] = trim( $aTempLangBase[0] );
			}
		}
		foreach ( $aUserLang as $sUserLang ) {
			foreach ( $aLang as $sLang ) {
				// Compare European Amazon languages with browser accept languages
				if ( $sUserLang == $sLang )
					return $sLang;
			}
		}
		// No match or no HTTP accept header: return default
		return 'en';
	}
	
	/**
	 * get_author_tag() - Donate unused Amazon credits to the author
	 *
	 * If the blog owner has chosen to donate Amazon karma for locales where he is
	 * not an Amazon Associate, return the author's Associate Tag
	 *
	 * @param    string   $sTld
	 * @return   string   $aAuthorTags[ $sTld ]
	 */
	function get_author_tag( $sTld ) {
		$aAuthorTags = array(
			'ca' => 'leartheworl04-20',
			'de' => 'leartheworl-21',
			'fr' => 'leartheworl0d-21',
			'jp' => 'leartheworl-22',
			'uk' => 'leartheworl0c-21',
			'us' => 'leartheworl-20'
		);
		return $aAuthorTags[ $sTld ];
	}
	
	/**
	 * sort_locales() - Sort locales by locale name
	 *
	 * Sorts (translated) locales alphabetically on the admin page
	 *
	 * @param    array   $aLocales
	 * @return   array   $aSortedLocales
	 */
	function sort_locales( $aLocales ) {
	 	$aHash = array();
		foreach ( $aLocales as $key => $value ) {
			$aHash[ $aLocales[ $key ][ 'locale' ] ] = $value;
			$aHash[ $aLocales[ $key ][ 'locale' ] ][ 'code' ] = $key;
		}
		// Sort by key
		ksort( $aHash );
		// Construct the output array
		$aSortedLocales = array();
		foreach ( $aHash as $aLocale ) {
			$aSortedLocales[] = $aLocale;
		}
		return $aSortedLocales;
	 }
	 
	/**
	 * oauth_hmacsha1() - return the base64 HMAC SHA1 encoded string
	 * @author  Kellan Elliott-McCrea
	 * @url     http://laughingmeme.org/2007/11/08/how-to-calculate-a-base64-encoded-hmac-sha1-in-php-for-oauth/
	 *  
	 * @param   string   $AWSAccessKeySecret
	 * @param   string   $sSign
	 * @return  string   hmac encoded data
	 */
	function oauth_hmacsha1( $AWSAccessKeySecret, $sSign ) {
		return base64_encode( AMTAP::hmacsha1( $AWSAccessKeySecret, $sSign ) );
	}
	/**
	 * hmacsha1() - return the HMAC SHA1 encoded string
	 * @author  Kellan Elliott-McCrea
	 * 
	 * @param   string   $key
	 * @param   string   $data
	 * @return  string   hmac encoded data
	 */
	function hmacsha1( $key, $data ) {
		$blocksize = 64;
		$hashfunc= 'sha256';
		if ( strlen( $key ) > $blocksize )
		    $key = pack( 'H*', $hashfunc( $key, true ) );
		$key = str_pad( $key, $blocksize, chr(0x00) );
		$ipad = str_repeat( chr(0x36), $blocksize );
		$opad= str_repeat( chr(0x5c), $blocksize );
		$hmac = pack(
            'H*', $hashfunc(
                ($key^$opad) . pack(
                    'H*', $hashfunc(
                        ($key^$ipad) . $data
                    )
                )
            )
        );
		return $hmac;
	}

	
	/**
	 * admin_css() - Add CSS to the admin interface
	 */
	function admin_css() {
		echo '<style type="text/css">' . "\n";
		include( AMTAP_PLUGINPATH . 'css/amtap-admin.css' );
		echo '</style>' . "\n";
	}
	
	/**
	 * blog_css() - Add CSS to the blog interface
	 */
	function blog_css() {
		echo '<link rel="stylesheet" type="text/css" href="' . get_bloginfo( 'url' ) . '/wp-content/plugins/amazon-machine-tags/css/amtap-blog.css" />' . "\n";
	}
}

// initialize the plugin
add_action('init', array( 'AMTAP', 'init' ) );

?>