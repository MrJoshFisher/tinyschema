<?php 
/**
 * Plugin Name: Trio Tiny Schema
 * Description: A plugin to add schema.org microdata to WordPress posts and pages.
 * Version: 1.0
 * Author: Josh Fisher
 * Author URI: mailto:josh@trio-media.co.uk
 * License: GPL2
 * Text Domain: trio-tiny-schema
 */

/**
 * Plugin Update Checker
 */

 require __DIR__ . '/admin/plugin-update-checker/plugin-update-checker.php';

 use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
 
 add_action('plugins_loaded', function () {
	 $updateChecker = PucFactory::buildUpdateChecker(
		 'https://github.com/MrJoshFisher/tinyschema/',
		 __FILE__,
		 'tinyschema'
	 );
 
	 $updateChecker->getVcsApi()->enableReleaseAssets();
 });

/**
 * Add settings link to plugin page
 */
add_filter('plugin_action_links', function ($links, $plugin_file) {

	// Only run for THIS plugin (works even if this isn't the main file)
	if (dirname($plugin_file) !== dirname(plugin_basename(__FILE__))) {
		return $links;
	}

	$settings_link = '<a href="' . esc_url(
		admin_url('options-general.php?page=trio-tiny-schema-settings')
	) . '">Settings</a>';

	array_unshift($links, $settings_link);

	return $links;

}, 10, 2);

/**
  * Include Admin Settings Page
  */
require_once plugin_dir_path(__FILE__) . 'admin/inc_settings.php';

/**
 * Include Admin Meta Box
 */
require_once plugin_dir_path(__FILE__) . 'admin/inc_schema_block.php';

/**
 * Include Frontend Output
 */
require_once plugin_dir_path(__FILE__) . 'admin/inc_schema_header.php';
