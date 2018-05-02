<?php

defined( 'ABSPATH' ) || exit;

class Axelspringer_AWS_Integrations {

	public function __construct() {
		add_action( 'init', array( $this, 'init_S3' ) );
	}

	public function init_S3() {
		// 	just check for config
		if (!defined('AWS_BUCKET') || !defined('AWS_REGION')) {
			return;
		}

		$plugins = [
		'amazon-s3-and-cloudfront/wordpress-s3.php',
		'amazon-web-services/amazon-web-services.php'
		];

		$options = get_option('tantan_wordpress_s3');

		$defaults = [
		'bucket' => AWS_BUCKET,
		'region' => AWS_REGION,
			'manual_bucket' => 1,
			'post_meta_version' => 6,
			'domain' => 'path',
			'cloudfront' => '',
			'object-prefix' => 'data/uploads/',
			'copy-to-s3'    => 1,
			'serve-from-s3' => 1,
			'remove-local-file' => 1,
			'force-https' => 1,
			'object-versioning' => 0,
			'use-yearmonth-folders' => 1,
			'enable-object-prefix' => 1
		];

		// check to include
		if ( ! function_exists('is_plugin_active' ) ) {
			include_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}

		// 	check for plugins -> enable plugins mu-plugin
		foreach($plugins as $plugin) {
		if (!is_plugin_active( $plugin )) {
				return;
			}
		}

		// 	very simple, just to
		update_option('tantan_wordpress_s3', $defaults);
	}
}

$axelspringer_aws_integrations = new Axelspringer_AWS_Integrations();