<?php

/**
 * Plugin Name: Recaptcha Comments
 * Description: Adds reCAPTCHA to the comments form to prevent spam.
 * Version: 1.0
 * Author: Rajan Shrestha
 * Author URI: https://github.com/rajanstha/recaptcha-comments
 * Requires at least: 5
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: recaptcha-comments
 */

class RecaptchaComments
{

    /**
     * Class constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        // Adds reCAPTCHA to comments form
        add_action('comment_form_after_fields', array($this, 'add_recaptcha_to_form'));

        // Verifies reCAPTCHA response
        add_filter('preprocess_comment', array($this, 'verify_recaptcha_response'));

        // Creates settings page in the WordPress admin area
        add_action('admin_menu', array($this, 'create_settings_page'));

        // Add plugin settings page link in plugin action links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));

        if (is_admin()) {
            add_action('admin_init', array($this, 'register_settings'));
        }
    }

    /**
     * Adds reCAPTCHA to comments form
     *
     * @access public
     * @return void
     */
    public function add_recaptcha_to_form()
    {
        // Retrieves site key from options
        $site_key = get_option('rc_recaptcha_site_key');
        $lang = explode('-', get_bloginfo('language'));
        // Adds reCAPTCHA to form and enqueues script
        echo <<<HTML
            <div class="comment-form-recaptcha" style="margin: 12px 0; min-height: 78px;">
                <div class="g-recaptcha" data-sitekey="' . $site_key . '"></div>
            </div>
        HTML;
        wp_enqueue_script('recaptcha', 'https://www.google.com/recaptcha/api.js?hl=' . $lang[0], [], false, false);
    }

    /**
     * Verifies reCAPTCHA response
     *
     * @param array $commentdata Comment data
     * @access public
     * @return array Comment data
     */
    public function verify_recaptcha_response($commentdata)
    {
        // Retrieves secret key from options
        $secret_key = get_option('rc_recaptcha_secret_key');
        // Makes request to the reCAPTCHA server
        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
            'body' => array(
                'secret' => $secret_key,
                'response' => $_POST['g-recaptcha-response'],
                'remoteip' => $_SERVER['REMOTE_ADDR']
            )
        ));
        $result = json_decode($response['body'], true);

        if (!$result['success']) {
            // Throws error if response is invalid
            wp_die(__('Error: reCAPTCHA response is invalid.', 'recaptcha-comments'));
        }

        return $commentdata;
    }

    /**
     * Creates settings page in the WordPress admin area
     *
     * @access public
     * @return void
     */
    public function create_settings_page()
    {
        add_options_page(
            'Recaptcha Comments', // Page title
            'Recaptcha Comments', // Menu title
            'manage_options', // Capability
            'recaptcha-comments', // Menu slug
            array($this, 'settings_page_html') // Callback function
        );
    }

    /**
     * Outputs HTML for the settings page
     *
     * @access public
     * @return void
     */
    public function settings_page_html()
    {
        // Ensures only users with the appropriate capability can access the settings page
        if (!current_user_can('manage_options')) {
            return;
        }

        // Retrieves reCAPTCHA keys from options
        $site_key = get_option('rc_recaptcha_site_key');
        $secret_key = get_option('rc_recaptcha_secret_key');
?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('recaptcha-comments'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="rc_recaptcha_site_key">Site Key</label>
                        </th>
                        <td>
                            <input type="text" id="rc_recaptcha_site_key" name="rc_recaptcha_site_key" value="<?php echo esc_attr($site_key); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="rc_recaptcha_secret_key">Secret Key</label>
                        </th>
                        <td>
                            <input type="text" id="rc_recaptcha_secret_key" name="rc_recaptcha_secret_key" value="<?php echo esc_attr($secret_key); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
<?php
    }

    /**
     * Adds settings link to the plugins admin page.
     *
     * @param array $actions
     * @return void
     */
    public function add_action_links($actions)
    {
        $mylinks = array(
            '<a href="' . admin_url('options-general.php?page=recaptcha-comments') . '">Settings</a>',
        );
        $actions = array_merge($actions, $mylinks);
        return $actions;
    }

    /**
     * Register the recaptcha settings
     *
     * @return void
     */
    public function register_settings()
    {
        register_setting('recaptcha-comments', 'rc_recaptcha_site_key');
        register_setting('recaptcha-comments', 'rc_recaptcha_secret_key');
    }
}

// Initialize the plugin
new RecaptchaComments();
