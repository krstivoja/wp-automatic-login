<?php
/**
 * Plugin Name: Automatic Admin Login
 * Description: Automatically logs in an admin user if accessed from a specified IP or display the current user IP if the checkbox is enabled.
 * Version: 1.6
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
    $show_ip = get_option('one_click_show_ip', false); // Get the option for showing IP

    // Check if show IP is enabled
    if ($show_ip) {
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            alert('Your IP address is: " . $user_ip . "');
        });
        </script>";
        return; // Exit the function here to prevent further action
    }
    
    // Automatic login logic
    if ($saved_ip && $user_ip === $saved_ip && $selected_admin_id) {
        // Get the current page URL to return to after login
        $current_url = $_SERVER['REQUEST_URI'];
        $login_url = admin_url('admin-ajax.php?action=one_click_admin_login&redirect_to=' . urlencode($current_url));
        $login_url = add_query_arg('_wpnonce', wp_create_nonce('one_click_login_nonce'), $login_url);
        echo "<script>
     document.addEventListener('DOMContentLoaded', function() {
         setTimeout(function() {
             window.location.href = '" . $login_url . "';
         }, 200);
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
        
        // Redirect to the original page or dashboard if no redirect is specified
        $redirect_to = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : admin_url();
        wp_redirect($redirect_to);
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
        
        $admin_user_id = isset($_POST['one_click_admin_user']) ? intval($_POST['one_click_admin_user']) : 0;
        $admin_ip = isset($_POST['one_click_admin_ip']) ? sanitize_text_field($_POST['one_click_admin_ip']) : '';
        $show_ip = isset($_POST['one_click_show_ip']) ? true : false;

        update_option('one_click_admin_user', $admin_user_id);
        update_option('one_click_admin_ip', $admin_ip);
        update_option('one_click_show_ip', $show_ip); // Save the checkbox value
        
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }
    
    $selected_admin = get_option('one_click_admin_user');
    $saved_ip = get_option('one_click_admin_ip');
    $show_ip = get_option('one_click_show_ip', false); // Get the current setting for show IP
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
                <tr>
                    <th>Show IP instead of Auto-Login:</th>
                    <td>
                        <input type="checkbox" name="one_click_show_ip" value="1" <?php checked($show_ip, true); ?> />
                        <label for="one_click_show_ip">Display current user IP on page load</label>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}