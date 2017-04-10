<?php


// @codingStandardsIgnoreFile

/**
 * Auto-configure S3
 *
 * @wp-hook init
 */
function initS3()
{
  // just check for config
  if (!defined('AWS_BUCKET') || !defined('AWS_REGION')) {
    return;
  }

  $plugins = [
    'amazon-s3-and-cloudfront/wordpress-s3.php',
    'amazon-web-services/amazon-web-services.php'
  ];
  $options = get_option('tantan_wordpress_s3');

  // check for plugins -> enable plugins mu-plugin
  foreach($plugins as $plugin) {
    if (!is_plugin_active( $plugin )) {
      return;
    }
  }

  // very simple, just to
  if (!$options || !array_key_exists('bucket', $options)) {
    update_option('tantan_wordpress_s3', [
      'bucket' => AWS_BUCKET,
      'region' => AWS_REGION,
      'manual_bucket' => 1,
      'post_meta_version' => 6,
      'domain' => 'path',
      'cloudfront' => '',
      'object-prefix' => 'data/uploads',
      'copy-to-s3'    => 1,
      'serve-from-s3' => 1,
      'remove-local-file' => 1,
      'force-https' => 1,
      'object-versioning' => 0,
      'use-yearmonth-folders' => 1,
      'enable-object-prefix' => 1
    ]);
  }

}
add_action('init', 'initS3');

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
