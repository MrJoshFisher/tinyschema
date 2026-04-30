<?php
/**
 * Output saved schema code into <head> for singular posts.
 * Meta key: _tts_schema_code
 * Only outputs on enabled post types.
 */

 add_action('wp_head', function () {
	if (!is_singular()) return;

	$post_id = get_queried_object_id();
	if (!$post_id) return;

	$post_type = get_post_type($post_id);
	if (!$post_type) return;

	// Only on post types selected in settings
	$options = get_option('tts_options', ['post_types' => []]);
	$enabled = !empty($options['post_types']) && is_array($options['post_types']) ? $options['post_types'] : [];
	if (!in_array($post_type, $enabled, true)) return;

	$code = get_post_meta($post_id, '_tts_schema_code', true);
	if (!is_string($code) || trim($code) === '') return;

	// If they pasted full <script ...>...</script>, output as-is.
	if (stripos($code, '<script') !== false) {
		echo "\n" . $code . "\n";
		return;
	}

	// Otherwise treat it as raw JSON and wrap it.
	echo "\n<script type=\"application/ld+json\">\n";
	echo $code; // no escaping, JSON must remain valid
	echo "\n</script>\n";
}, 20);

/** Output GLOBAL schema in head on every front-end page */
add_action('wp_head', function () {
	if (is_admin()) return;

	$options = get_option('tts_options', ['global_schema' => '']);
	$global  = isset($options['global_schema']) ? (string) $options['global_schema'] : '';

	if (trim($global) === '') return;

	echo "\n<script type=\"application/ld+json\">\n" . $global . "\n</script>\n";
}, 5);