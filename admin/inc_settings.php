<?php
// Admin menu
add_action('admin_menu', function () {
	add_options_page(
		'Trio Tiny Schema Settings',
		'Trio Tiny Schema',
		'manage_options',
		'trio-tiny-schema-settings',
		'tts_render_settings_page'
	);
});

// Register setting
add_action('admin_init', function () {
	register_setting(
		'trio_tiny_schema_options',
		'tts_options',
		[
			'type'              => 'array',
			'sanitize_callback' => 'tts_sanitize_options',
			'default'           => [
				'post_types'     => [],
				'global_schema'  => '',
			],
		]
	);

	add_settings_section('tts_main', '', '__return_false', 'trio_tiny_schema');
});

// Sanitise
function tts_sanitize_options($input) {
	$out = [
		'post_types'    => [],
		'global_schema' => '',
	];

	$public = get_post_types(['public' => true], 'names');

	if (!empty($input['post_types']) && is_array($input['post_types'])) {
		foreach ($input['post_types'] as $pt) {
			$pt = sanitize_key($pt);
			if (in_array($pt, $public, true)) {
				$out['post_types'][] = $pt;
			}
		}
	}
	$out['post_types'] = array_values(array_unique($out['post_types']));

	// Keep code intact (don’t use sanitize_text_field)
	if (isset($input['global_schema'])) {
		$out['global_schema'] = wp_unslash($input['global_schema']);
	}

	return $out;
}

/**
 * Enqueue CodeMirror on the plugin settings page
 * Mode: JSON-LD
 * Theme: dracula (dark)
 */
add_action('admin_enqueue_scripts', function ($hook) {
	if ($hook !== 'settings_page_trio-tiny-schema-settings') return;

	$settings = wp_enqueue_code_editor([
		'type'       => 'application/ld+json',
		'codemirror' => [
			'theme'        => 'dracula',
			'lineNumbers'  => true,
			'lineWrapping' => true,
			'indentUnit'   => 2,
			'tabSize'      => 2,
		],
	]);

	if ($settings === false) return;

	wp_enqueue_script('wp-theme-plugin-editor');
	wp_enqueue_style('wp-codemirror');

	wp_enqueue_style(
		'codemirror-dracula',
		includes_url('js/codemirror/theme/dracula.css'),
		['wp-codemirror']
	);

	wp_add_inline_script(
		'wp-theme-plugin-editor',
		'jQuery(function($){
			var settings = ' . wp_json_encode($settings) . ';
			if (settings && $("#tts_global_schema").length) {
				wp.codeEditor.initialize($("#tts_global_schema"), settings);
			}
		});'
	);
});

// Render settings page
function tts_render_settings_page() {
	$options   = get_option('tts_options', ['post_types' => [], 'global_schema' => '']);
	$selected  = !empty($options['post_types']) && is_array($options['post_types']) ? $options['post_types'] : [];
	$postTypes = get_post_types(['public' => true], 'objects');
	$global    = isset($options['global_schema']) ? $options['global_schema'] : '';
	?>
	<div class="wrap">
		<h1><?php echo esc_html(get_admin_page_title()); ?></h1>

		<form method="post" action="options.php">
			<?php settings_fields('trio_tiny_schema_options'); ?>

			<h2>Enable schema on post types</h2>
			<?php foreach ($postTypes as $post_type) : ?>
				<label style="display:block;margin:6px 0;">
					<input
						type="checkbox"
						name="tts_options[post_types][]"
						value="<?php echo esc_attr($post_type->name); ?>"
						<?php checked(in_array($post_type->name, $selected, true)); ?>
					/>
					<?php echo esc_html($post_type->label); ?>
				</label>
			<?php endforeach; ?>

			<hr style="margin:18px 0;" />

			<h2>Global schema</h2>
			<p style="margin-top:0;">Paste JSON-LD only (no &lt;script&gt; tag). It will be wrapped automatically.</p>

			<textarea
				id="tts_global_schema"
				name="tts_options[global_schema]"
				rows="14"
				style="width:100%;font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;"
			><?php echo esc_textarea($global); ?></textarea>

			<?php
			do_settings_sections('trio_tiny_schema');
			submit_button();
			?>
		</form>
	</div>
	<?php
}