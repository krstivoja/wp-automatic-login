<?php
/**
 * Plugin Name: Automatic Admin Login
 * Description: Automatically logs in an admin user if accessed from a specified IP.
 * Version: 1.4
 * Author: Marko Krstic
 */

// Hook into wp_footer to trigger automatic login after page load
add_action('wp_footer', 'automatic_admin_login_script');

function automatic_admin_login_script() {
    if (is_user_logged_in()) {
        return;
    }
    
    $saved_ip = get_option('one_click_admin_ip');
    $selected_admin_id = get_option('one_click_admin_user');
    $user_ip = $_SERVER['REMOTE_ADDR'];
    
    if ($saved_ip && $user_ip === $saved_ip && $selected_admin_id) {
        $login_url = admin_url('admin-ajax.php?action=one_click_admin_login');
        $login_url = add_query_arg('_wpnonce', wp_create_nonce('one_click_login_nonce'), $login_url);
        echo "<script>
     document.addEventListener('DOMContentLoaded', function() {
         setTimeout(function() {
             window.location.href = '" . $login_url . "';
         }, 1000);
     });
 </script>";
    }
}

// Handle the admin login
add_action('wp_ajax_one_click_admin_login', 'handle_one_click_admin_login');
add_action('wp_ajax_nopriv_one_click_admin_login', 'handle_one_click_admin_login');

function handle_one_click_admin_login() {
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'one_click_login_nonce')) {
        wp_die('Security check failed.');
    }
    
    $selected_admin_id = get_option('one_click_admin_user');
    $admin_user = get_user_by('ID', $selected_admin_id);
    
    if ($admin_user) {
        wp_set_auth_cookie($admin_user->ID);
        wp_redirect(admin_url());
        exit;
    } else {
        wp_die('Admin user not found.');
    }
}

// Add settings page
add_action('admin_menu', 'one_click_admin_settings_page');

function one_click_admin_settings_page() {
    add_options_page('Automatic Admin Login', 'Automatic Admin Login', 'manage_options', 'one-click-admin-login', 'one_click_admin_settings_page_html');
}

function one_click_admin_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_POST['one_click_admin_user']) && isset($_POST['one_click_admin_ip'])) {
        check_admin_referer('one_click_admin_settings');
        update_option('one_click_admin_user', intval($_POST['one_click_admin_user']));
        update_option('one_click_admin_ip', sanitize_text_field($_POST['one_click_admin_ip']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }
    
    $selected_admin = get_option('one_click_admin_user');
    $saved_ip = get_option('one_click_admin_ip');
    $users = get_users(['role__in' => ['administrator']]);
    ?>
    <div class="wrap">
        <h1>Automatic Admin Login Settings</h1>
        <form method="post">
            <?php wp_nonce_field('one_click_admin_settings'); ?>
            <table class="form-table">
                <tr>
                    <th>Select Admin User:</th>
                    <td>
                        <select name="one_click_admin_user">
                            <?php foreach ($users as $user) : ?>
                                <option value="<?php echo $user->ID; ?>" <?php selected($selected_admin, $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Allowed IP for Auto-Login:</th>
                    <td><input type="text" name="one_click_admin_ip" value="<?php echo esc_attr($saved_ip); ?>" /></td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}