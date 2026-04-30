<?php
/**
 * Code editor textarea (line numbers + syntax highlighting) in a meta box
 * Uses WordPress CodeMirror via wp_enqueue_code_editor()
 */

// Only allow users whose email ends with @trio-media.co.uk
function tts_user_is_allowed() {
	$user = wp_get_current_user();
	if (!$user || empty($user->user_email)) return false;

	$email = strtolower(trim($user->user_email));
	return (substr($email, -strlen('@trio-media.co.uk')) === '@trio-media.co.uk');
}


/** 1) Add meta box on selected post types */
add_action('add_meta_boxes', function () {
	if (!tts_user_is_allowed()) return;

	$options = get_option('tts_options', ['post_types' => []]);
	$enabled = !empty($options['post_types']) && is_array($options['post_types']) ? $options['post_types'] : [];
	if (!$enabled) return;

	foreach ($enabled as $post_type) {
		add_meta_box(
			'tts_schema_code_box',
			'Trio Tiny Schema',
			'tts_render_schema_code_box',
			$post_type,
			'normal',
			'default'
		);
	}
});

/** 2) Enqueue CodeMirror only when editing enabled post types */
add_action('admin_enqueue_scripts', function ($hook) {
	// Only post editor screens
	if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;

	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (!$screen || empty($screen->post_type)) return;

	$options = get_option('tts_options', ['post_types' => []]);
	$enabled = !empty($options['post_types']) && is_array($options['post_types']) ? $options['post_types'] : [];
	if (!in_array($screen->post_type, $enabled, true)) return;

	$settings = wp_enqueue_code_editor([
        'type'       => 'application/ld+json',
        'codemirror' => [
          
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
        'codemirror-nord',
        includes_url('js/codemirror/theme/nord.css'),
        ['wp-codemirror']
    );
    
    wp_add_inline_script(
        'wp-theme-plugin-editor',
        'jQuery(function($){
            var settings = ' . wp_json_encode($settings) . ';
            if (settings && $("#tts_schema_code").length) {
                wp.codeEditor.initialize($("#tts_schema_code"), settings);
            }
        });'
    );
});

/** 3) Render meta box with textarea */
function tts_render_schema_code_box($post) {
	$value = get_post_meta($post->ID, '_tts_schema_code', true);

	wp_nonce_field('tts_save_schema_code', 'tts_schema_code_nonce');
	?>
	< style="margin:0 0 8px;">
    Paste JSON-LD only (no &lt;script&gt; tag). It will be wrapped automatically.
	</p>
	<textarea
		id="tts_schema_code"
		name="tts_schema_code"
		rows="14"
		style="width:100%;font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;"
	><?php echo esc_textarea($value); ?></textarea>
	<?php
}

/** 4) Save */
add_action('save_post', function ($post_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (wp_is_post_revision($post_id)) return;

	if (!isset($_POST['tts_schema_code_nonce']) || !wp_verify_nonce($_POST['tts_schema_code_nonce'], 'tts_save_schema_code')) return;
	if (!current_user_can('edit_post', $post_id)) return;

	$options = get_option('tts_options', ['post_types' => []]);
	$enabled = !empty($options['post_types']) && is_array($options['post_types']) ? $options['post_types'] : [];
	$post_type = get_post_type($post_id);

	if (!$post_type || !in_array($post_type, $enabled, true)) return;

	// Keep code mostly intact. Use wp_unslash only; avoid sanitize_text_field (it will mangle JSON).
	$new = isset($_POST['tts_schema_code']) ? wp_unslash($_POST['tts_schema_code']) : '';

	if (trim($new) === '') {
		delete_post_meta($post_id, '_tts_schema_code');
	} else {
		update_post_meta($post_id, '_tts_schema_code', $new);
	}
}, 10, 1);