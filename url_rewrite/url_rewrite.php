<?php
/**
 * Created by PhpStorm.
 * User: Joscha Eckert
 * Date: 20.03.2019
 * Time: 15:00
 */

class JosxhaRfaFileSecure {
	private $settings;

	public function __construct() {
		$this->settings = stripslashes_deep(get_option(JOSXHARFA_PLUGIN_NAME));
	}

	function activate() {
		global $wp_rewrite;
		$this->flush_rewrite_rules();
	}

	// Took out the $wp_rewrite->rules replacement so the rewrite rules filter could handle this.
	function create_rewrite_rules( $rules ) {
		global $wp_rewrite;
		$newRule  = array( 'file/(.+)' => 'index.php?file=' . $wp_rewrite->preg_index( 1 ) );
		$newRules = $newRule + $rules;

		return $newRules;
	}

	function add_query_vars( $qvars ) {
		$qvars[] = 'file';

		return $qvars;
	}

	function flush_rewrite_rules() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	function template_redirect_intercept() {
		global $wp_query;
		if ( $wp_query->get( 'file' ) ) {
			$this->pushoutput( $wp_query->get( 'file' ) );
			exit;
		}
	}

	function pushoutput( $message ) {
		$this->output( $message );
	}

	function accessNotPermitted() {
		$preferences = $this->settings['onAccess'];
		if ($preferences['action'] === "redirect") {
			header("Location: " . $preferences['url']);
			die();
		}
		if ($preferences['text'] === "")
			die(JOSXHARFA_NOT_PERMITTED_DEFAULT_TEXT);
		else
			die($preferences['text']);
	}

	function fileNotFound() {
		$preferences = $this->settings['notFound'];
		if ($preferences['action'] === "redirect") {
			header("Location: " . $preferences['url']);
			die();
		}
		if ($preferences['text'] === "")
			die(JOSXHARFA_FILE_NOT_FOUND_DEFAULT_TEXT);
		else
			die($preferences['text']);
	}

	function output( $filename ) {
		// test if user is allowed to view file
		if (!is_user_logged_in())
			$this->accessNotPermitted();

		$rolesOfUser = get_currentuserinfo()->roles;        // array of all user roles of the active user
		$allowedUserRoles = $this->settings["userRole"];    // dictionary with all relevant available user roles
		$success = false;
		foreach ( $rolesOfUser as $role ) {
			if (in_array($role, $allowedUserRoles) && $allowedUserRoles[$role] === true) {
				$success = true;
				break;
			}
		}
		if (!$success)
			$this->accessNotPermitted();

		// show file
		$file_path = josxharfa_upload_dir() . "/" . $filename;
		if ( ! file_exists( $file_path ) ) {
			$this->fileNotFound();
		}

		if ( function_exists( 'mime_content_type' ) ) {
			$mime = mime_content_type( $file_path );
		}
		if ( ! isset( $mime ) || $mime === false ) {
			$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
			switch ( $extension ) {
				case "png":
					$mime = 'image/png';
					break;
				case "jpg":
				case "jpeg":
					$mime = 'image/jpeg';
					break;
				case "gif":
					$mime = 'image/gif';
					break;
				case "mp3":
					$mime = 'audio/mpeg';
					break;
				case "mp4":
					$mime = 'video/mp4';
					break;
				case "pdf":
					$mime = 'application/pdf';
					break;
				case "txt":
					$mime = 'text/plain';
					break;
				case "docx":
				case "xlsx":
				case "doc":
				case "odt":
				case "xls":
				case "ppt":
				case "pptx":
				case "csv":
				default:
					$mime = 'application/octet-stream';
					break;
			}
		}

		header( "Content-type: $mime" );
		header( 'Content-Length: ' . filesize( $file_path ) );
		header( "Content-Disposition:" . $this->get_content_disposition( $mime ) . ";filename=" . $filename );

		readfile( $file_path );
	}

	function get_content_disposition( $mime ) {
		$inline = [ 'image/png', 'image/jpeg', 'image/gif', 'audio/mpeg', 'video/mp4', 'application/pdf', 'text/plain' ];

		return in_array( $mime, $inline ) ? 'inline' : 'attachment';
	}
}

$FileSecureCode = new JosxhaRfaFileSecure();
register_activation_hook( __file__, array( $FileSecureCode, 'activate' ) );

// Using a filter instead of an action to create the rewrite rules.
// Write rules -> Add query vars -> Recalculate rewrite rules
add_filter( 'rewrite_rules_array', array( $FileSecureCode, 'create_rewrite_rules' ) );
add_filter( 'query_vars', array( $FileSecureCode, 'add_query_vars' ) );

// Recalculates rewrite rules during admin init to save resourcees.
// Could probably run it once as long as it isn't going to change or check the
// $wp_rewrite rules to see if it's active.
add_filter( 'admin_init', array( $FileSecureCode, 'flush_rewrite_rules' ) );
add_action( 'template_redirect', array( $FileSecureCode, 'template_redirect_intercept' ) );
