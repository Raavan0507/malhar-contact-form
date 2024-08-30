<?php
/*
Plugin Name: Malhar's Contact Form
Description: Custom Plugin by Malhar Prajapati
Version: 1.0
Author: Malhar
*/

// Stop direct access to this file
if (!defined('ABSPATH')) {
    die('No direct access allowed');
}

// Load CSS and JS
function mcf_load_stuff() {
    wp_enqueue_style('mcf-styles', plugins_url('css/style.css', __FILE__));
    wp_enqueue_script('mcf-script', plugins_url('js/script.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('mcf-script', 'mcf_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mcf_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'mcf_load_stuff');

// Admin page stuff
function mcf_admin_scripts($hook) {
    if ('toplevel_page_malhars-contact-form' !== $hook) {
        return;
    }
    wp_enqueue_script('mcf-admin-script', plugins_url('js/admin-script.js', __FILE__), array('jquery'), null, true);
}
add_action('admin_enqueue_scripts', 'mcf_admin_scripts');



// Shortcode for the form
function mcf_form_shortcode() {
    ob_start();
    ?>
    <form id="malhars-contact-form" class="mcf-form">
        <div class="mcf-form-group">
            <label for="mcf-name">Name (required)</label>
            <input type="text" id="mcf-name" name="name" required>
        </div>
        <div class="mcf-form-group">
            <label for="mcf-email">Email (required)</label>
            <input type="email" id="mcf-email" name="email" required>
        </div>
        <div class="mcf-form-group">
            <label for="mcf-message">Message (required)</label>
            <textarea id="mcf-message" name="message" required></textarea>
        </div>
        <?php wp_nonce_field('mcf_nonce', 'mcf_nonce'); ?>
        <button type="submit">Send</button>
    </form>
    <div id="mcf-response"></div>
    <?php
    return ob_get_clean();
}
add_shortcode('malhars_contact_form', 'mcf_form_shortcode');

// Handle form submission
function mcf_handle_submission() {
    check_ajax_referer('mcf_nonce', 'security');

    // Check required fields
    $required = array('name', 'email', 'message');
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            wp_send_json_error("Hey, $field is required!");
            exit;
        }
    }

    // Check email
    if (!is_email($_POST['email'])) {
        wp_send_json_error('That email doesn\'t look right...');
        exit;
    }

    // Clean up the input
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $message = sanitize_textarea_field($_POST['message']);

    // Spam check
    if (mcf_is_this_spam($name, $email, $message)) {
        wp_send_json_error('Looks like spam to me!');
        exit;
    }

    // Save to DB
    global $wpdb;
    $table = $wpdb->prefix . 'mcf_submissions';
    $wpdb->insert(
        $table,
        array(
            'name' => $name,
            'email' => $email,
            'message' => $message,
            'submitted_at' => current_time('mysql')
        )
    );

    // Send email
    $to = get_option('admin_email');
    $subject = 'New message from your website';
    $body = "Name: $name\nEmail: $email\nMessage: $message";
    wp_mail($to, $subject, $body);

    wp_send_json_success('Thanks for your message!');
}
add_action('wp_ajax_mcf_submit_form', 'mcf_handle_submission');
add_action('wp_ajax_nopriv_mcf_submit_form', 'mcf_handle_submission');

// Akismet spam check
function mcf_is_this_spam($name, $email, $message) {
    if (!function_exists('akismet_init')) {
        return false; 
    }

    global $akismet_api_host, $akismet_api_port;

    $comment = array(
        'author'    => $name,
        'email'     => $email,
        'content'   => $message
    );

    $query_string = http_build_query($comment);
    $response = akismet_http_post($query_string, $akismet_api_host, '/1.1/comment-check', $akismet_api_port);

    return $response[1] == 'true';
}

// Create DB table when plugin is activated
function mcf_create_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'mcf_submissions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        message text NOT NULL,
        submitted_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'mcf_create_table');

// Add Akismet settings link
function mcf_add_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=akismet-key-config">Akismet Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'mcf_add_settings_link');

// admin menu
function mcf_add_admin_menu() {
    add_menu_page(
        'Malhar\'s Contact Form',
        'Contact Form',
        'manage_options',
        'malhars-contact-form',
        'mcf_admin_page',
        'dashicons-email-alt'
    );
}
add_action('admin_menu', 'mcf_add_admin_menu');

// Admin page content
function mcf_admin_page() {
    ?>
    <div class="wrap">
        <h1>Malhar's Contact Form</h1>
        <p>Use this shortcode to display the contact form on any page or post:</p>
        <div class="mcf-shortcode-container">
            <code id="mcf-shortcode">[malhars_contact_form]</code>
            <button id="mcf-copy-shortcode" class="button">Copy Shortcode</button>
        </div>
        <div id="mcf-copy-message" style="display:none;">Shortcode copied!</div>
    </div>
    <?php
}

?>