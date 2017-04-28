<?php

/**
* Auto-configure S3
 *
 * @wp-hook init
 */
function initS3()
{
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
  if (!function_exists('is_plugin_active')) {
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
add_action('init', 'initS3');

/**
* @param string $path
 * @param null $s3Client
 * @param string|null $bucket
 * @param bool $asString
 *
 * @return bool|\Guzzle\Http\EntityBodyInterface|string
 */
function fetch_file_from_s3(string $path, $s3Client = null, string $bucket = null, $asString = true)
{
	try {

		if (is_null($s3Client)) {
			$s3Client = get_s3_client();
		}

		if (!is_string($bucket)) {
			$bucket = get_s3_instance()->get_setting('bucket');
		}

		$command = $s3Client->getCommand('GetObject', [
		            'Bucket' => $bucket,
		            'Key'    => $path,
		        ]);
		// 		Get the JSON response data and extract the log records
		        $command->execute();


		/**
		* @var \Guzzle\Http\Message\Response $response
		         */
		        $response = $command->getResponse();

		return $response->getBody($asString);

	}
	catch (\Exception $e) {
		echo $e->getMessage();
		return false;
	}
}


/**
* @param string $key
 * @param string $secret
 * @param string $region
 *
 * @return \Aws\S3\S3Client
 */
function create_s3_client(string $key, string $secret, $region = 'eu-west-1') {
	return Aws\Common\Aws::factory(['key' => $key, 'secret' => $secret])
	        ->get('s3', ['region' => $region, 'signature' => 'v4',]);
}


/**
* @return \Aws\S3\S3Client
 */
function get_s3_client()
{
	return get_s3_instance()->get_s3client(
	        get_s3_instance()->get_setting('region')
	    );
}

function get_s3_instance() {

	/**
	* @see web/wp-content/plugins/wp-amazon-s3-and-cloudfront/wordpress-s3.php#L60
	     * @var \Amazon_S3_And_CloudFront $as3cf
	     */
	    global $as3cf;

	return $as3cf;
}


/**
* @return string
 */
function get_s3_setting_bucket() {
	return get_s3_instance()->get_setting('bucket');
}


/**
* @param string $path
 * @param string $bucket
 * @param \Aws\S3\S3Client $client
 *
 * @return array
 */
function s3_list_objects($path = null, string $bucket, \Aws\S3\S3Client $client) {
	try {
		$result = $client->listObjects([
		  'Bucket' => $bucket,
		  'Prefix' => $path,
		  'Delimiter' => '/'
		]);

		if (!$result->hasKey('Contents')) {
			return [];
		}

		return $result->get('Contents');
	}
	catch (\Exception $e) {
		return [];
	}
}


/**
 * @param string $from
 * @param string $to
 * @param string $bucket
 * @param \Aws\S3\S3Client $client
 *
 * @return bool
 */
function s3_move_object(string $from, string $to, string $bucket, \Aws\S3\S3Client $client) {
	try {
		// copy the object and allow overriding default parameters if desired, but by default copy metadata
		$client->copyObject([
		  'Bucket' => $bucket,
		  'Key' => $to,
		  'CopySource' => '/' . $bucket . '/' . rawurlencode($from),
		  'MetadataDirective' => 'COPY'
		]);

		// delete the original object
		$client->deleteObject([
		  'Bucket' => $bucket,
		  'Key'    => $from
		]);
	}
	catch (\Exception $e) {
		return false;
		//t		rigger_error(implode("\n", (array) $e->getMessage()), E_USER_WARNING);
	}

	return true;
}
