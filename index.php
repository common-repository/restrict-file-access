<?php
/**
 * Created by PhpStorm.
 * User: Joscha Eckert
 * Date: 20.03.2019
 * Time: 13:13
 *
 * Plugin Name: Restrict File Access
 * Plugin URI: https://github.com/josxha/WordPress-Plugin-File-Secure
 * Description: Schütze hochgeladene Dateien vor unbefugtem Zugriff. (Im Admin Interface auffindbar unter Medien->Geschützte Dateien)
 * Version: 1.1.2
 * Author: Joscha Eckert
 * License: GPLv2
 * Author URI: https://joscha-eckert.de
 */

if (!defined( 'ABSPATH')) exit;

// /LOCALPATH/wp-content/plugins/PLUGINFOLDER/index.php
define( 'JOSXHARFA_PLUGIN', __FILE__ );

// PLUGINFOLDER/index.php
define( 'JOSXHARFA_PLUGIN_BASENAME', plugin_basename( JOSXHARFA_PLUGIN ) );

// PLUGINFOLDER (plugin name)
define( 'JOSXHARFA_PLUGIN_NAME', trim( dirname( JOSXHARFA_PLUGIN_BASENAME ), '/' ) );

// /local/path/wp-content/plugins/PLUGINFOLDER
define( 'JOSXHARFA_PLUGIN_DIR', untrailingslashit( dirname( JOSXHARFA_PLUGIN ) ) );

// default preferences
define( 'JOSXHARFA_FILE_NOT_FOUND_DEFAULT_TEXT', "File not found.");
define( 'JOSXHARFA_DEFAULT_URL', "https://example.com");
define( 'JOSXHARFA_NOT_PERMITTED_DEFAULT_TEXT', "You need to be logged in to access this file.");


/**
 * @return string
 * "http(s)://DOMAIN/wp-content/uploads/files"
 */
function josxharfa_upload_url() {
	return wp_upload_dir()["baseurl"] . "/files";
}

/**
 * @return string
 * "http(s)://DOMAIN/wp-content/uploads/files"
 */
function josxharfa_upload_dir() {
	return wp_upload_dir()["basedir"] . "/files";
}

/**
 * @param string $path
 * additional path
 * @return string
 * "http(s)://DOMAIN/wp-content/plugins/PLUGINFOLDER"
 */
function josxharfa_plugin_url( $path = '' ) {
    return josxharfa_useSslIfActive(plugins_url( $path, JOSXHARFA_PLUGIN ));
}

/**
 * @param $url
 * an url
 * @return string
 * modify url to https if ssl is enabled
 */
function josxharfa_useSslIfActive($url) {
	if ( is_ssl() and 'http:' == substr( $url, 0, 5 ) )
		return 'https:' . substr( $url, 5 );
	else return $url;
}

/**
 * @return array
 * returns the interesting roles of the wordpress installation
 */
function josxharfa_get_wordpress_roles() {
	global $wp_roles;

	$all_roles = $wp_roles->roles;
	$editable_roles = apply_filters( 'editable_roles', $all_roles );

	return $editable_roles;
}

// run on plugin activation
register_activation_hook(__FILE__, "josxharfa_activation");
function josxharfa_activation() {
	// create necessary files in in upload directory
	$uploadDir = josxharfa_upload_dir();
    if (!file_exists($uploadDir))
        mkdir($uploadDir);
    if (!file_exists($uploadDir."/.htaccess"))
        file_put_contents($uploadDir."/.htaccess", "Deny from all");
    if (!file_exists($uploadDir."/index.html"))
        file_put_contents($uploadDir."/index.html", "");

    // write default settings to database
	$settings = array(
		"onAccess" => array(
			"action" => "text",
			"text" => "",
			"url" => ""
		),
		"notFound" => array(
			"action" => "text",
			"text" => "",
			"url" => ""
		)
	);
	$roles = array();
	foreach ( josxharfa_get_wordpress_roles() as $roleName => $roleData ) {
		$roles[$roleName] = true;
	}
	$settings["userRole"] = $roles;
	update_option(JOSXHARFA_PLUGIN_NAME, $settings, "yes");
}

// run on plugin update
add_action( 'upgrader_process_complete', function( $upgrader_object, $options ) {
	josxharfa_activation();
},10, 2);

require_once JOSXHARFA_PLUGIN_DIR.'/admin/admin.php';
require_once JOSXHARFA_PLUGIN_DIR.'/url_rewrite/url_rewrite.php';
