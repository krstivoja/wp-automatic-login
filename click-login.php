<?php
/**
 * Plugin Name: Automatic Admin Login
 * Description: Automatically logs in an admin user if accessed from a specified IP or displays the current user IP in settings.
 * Version: 1.9
 * Author: Marko Krstic
 */

// Hook into wp_footer to trigger automatic login after page load
add_action('wp_footer', 'automatic_admin_login_script');

function automatic_admin_login_script() {
    if (is_user_logged_in()) {
        return;
    }

    $user_ip = $_SERVER['REMOTE_ADDR'];
    $saved_ip = get_option('one_click_admin_ip');
    $selected_admin_id = get_option('one_click_admin_user');

    // Auto-login if IP matches
    if ($saved_ip === $user_ip && $selected_admin_id) {
        $login_url = admin_url('admin-ajax.php') . '?action=one_click_admin_login&_wpnonce=' . wp_create_nonce('one_click_login_nonce');

        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                window.location.href = '" . esc_js($login_url) . "';
            });
        </script>";
    }
}

// Handle the admin login securely
add_action('wp_ajax_one_click_admin_login', 'handle_one_click_admin_login');
add_action('wp_ajax_nopriv_one_click_admin_login', 'handle_one_click_admin_login');

function handle_one_click_admin_login() {
    check_ajax_referer('one_click_login_nonce', '_wpnonce');

    $selected_admin_id = get_option('one_click_admin_user');
    $admin_user = get_user_by('ID', $selected_admin_id);

    if (!$admin_user || !user_can($admin_user, 'administrator')) {
        wp_die('Invalid admin user.');
    }

    wp_set_auth_cookie($admin_user->ID);
    wp_redirect(admin_url());
    exit;
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

    if (!empty($_POST['one_click_admin_user']) && check_admin_referer('one_click_admin_settings')) {
        update_option('one_click_admin_user', intval($_POST['one_click_admin_user']));
        update_option('one_click_admin_ip', sanitize_text_field($_POST['one_click_admin_ip'] ?? ''));

        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $selected_admin = get_option('one_click_admin_user');
    $saved_ip = get_option('one_click_admin_ip');
    $users = get_users(['role' => 'administrator']);
    $current_ip = $_SERVER['REMOTE_ADDR'];
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
                                <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($selected_admin, $user->ID); ?>>
                                    <?php echo esc_html($user->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Allowed IP for Auto-Login:</th>
                    <td><input type="text" name="one_click_admin_ip" value="<?php echo esc_attr($saved_ip); ?>" /></td>
                </tr>
                <tr>
                    <th>Your Current IP:</th>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <strong id="current-ip"><?php echo esc_html($current_ip); ?></strong>
                            <button type="button" id="copy-ip" class="button">Copy</button>
                        </div>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <script>
document.addEventListener("DOMContentLoaded", function() {
    let copyButton = document.getElementById("copy-ip");

    if (!navigator.clipboard) {
        console.warn("Clipboard API not supported. Falling back to manual selection.");
        copyButton.addEventListener("click", function() {
            let ipElement = document.getElementById("current-ip");
            let range = document.createRange();
            let selection = window.getSelection();

            range.selectNodeContents(ipElement);
            selection.removeAllRanges();
            selection.addRange(range);

            document.execCommand("copy"); // Fallback for older browsers
            copyButton.textContent = "Copied!";
            setTimeout(() => copyButton.textContent = "Copy", 2000);
        });
        return;
    }

    copyButton.addEventListener("click", function() {
        let ipText = document.getElementById("current-ip").textContent;
        navigator.clipboard.writeText(ipText).then(function() {
            copyButton.textContent = "Copied!";
            setTimeout(() => copyButton.textContent = "Copy", 2000);
        }).catch(function(err) {
            console.error("Failed to copy: ", err);
        });
    });
});
    </script>
    <?php
}