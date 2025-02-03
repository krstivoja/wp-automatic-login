<?php
/**
 * Plugin Name: Automatic Admin Login
 * Description: Automatically logs in an admin user if accessed from a specified IP or displays the current user IP in settings.
 * Version: 2.2
 * Author: Marko Krstic (Modified by ChatGPT)
 */

// Hook into wp_footer to trigger automatic login after page load
add_action('wp_footer', 'automatic_admin_login_script');

function automatic_admin_login_script() {
    if (is_user_logged_in()) {
        return;
    }

    $user_ip = $_SERVER['REMOTE_ADDR'];
    // Get the allowed login pairs - each item is an array with keys: 'admin_user' and 'ip'
    $login_pairs = get_option('one_click_admin_logins', []);

    // Look for a login pair where the allowed IP matches the current user's IP
    foreach ($login_pairs as $index => $pair) {
        if (!empty($pair['ip']) && $pair['ip'] === $user_ip && !empty($pair['admin_user'])) {
            $login_url = admin_url('admin-ajax.php') . '?action=one_click_admin_login&pair=' . $index . '&_wpnonce=' . wp_create_nonce('one_click_login_nonce');

            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    window.location.href = '" . esc_js($login_url) . "';
                });
            </script>";
            break;
        }
    }
}

// Handle the admin login securely via AJAX
add_action('wp_ajax_one_click_admin_login', 'handle_one_click_admin_login');
add_action('wp_ajax_nopriv_one_click_admin_login', 'handle_one_click_admin_login');

function handle_one_click_admin_login() {
    check_ajax_referer('one_click_login_nonce', '_wpnonce');

    $pair_index = isset($_GET['pair']) ? intval($_GET['pair']) : -1;
    $login_pairs = get_option('one_click_admin_logins', []);
    if (!isset($login_pairs[$pair_index])) {
        wp_die('Invalid login pair specified.');
    }
    
    $pair = $login_pairs[$pair_index];
    // Verify that the request is coming from the same allowed IP
    if ($_SERVER['REMOTE_ADDR'] !== $pair['ip']) {
        wp_die('IP address mismatch.');
    }
    
    $admin_user = get_user_by('ID', $pair['admin_user']);
    if (!$admin_user || !user_can($admin_user, 'administrator')) {
        wp_die('Invalid admin user.');
    }
    
    wp_set_auth_cookie($admin_user->ID);
    wp_redirect(admin_url());
    exit;
}

// Add the settings page to the admin menu
add_action('admin_menu', 'one_click_admin_settings_page');

function one_click_admin_settings_page() {
    add_options_page('Automatic Admin Login', 'Automatic Admin Login', 'manage_options', 'one-click-admin-login', 'one_click_admin_settings_page_html');
}

function one_click_admin_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Process form submission: expects an array of rows from the dynamic table.
    if (isset($_POST['login_pairs']) && is_array($_POST['login_pairs']) && check_admin_referer('one_click_admin_settings')) {
        $login_pairs = [];
        foreach ($_POST['login_pairs'] as $pair) {
            $admin = isset($pair['admin']) ? intval($pair['admin']) : 0;
            $ip = isset($pair['ip']) ? sanitize_text_field($pair['ip']) : '';
            if ($admin && $ip) {
                $login_pairs[] = array('admin_user' => $admin, 'ip' => $ip);
            }
        }
        update_option('one_click_admin_logins', $login_pairs);
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    // Get the saved login pairs and list all administrator users.
    $login_pairs = get_option('one_click_admin_logins', []);
    // Ensure there is at least one row to display
    if (empty($login_pairs)) {
        $login_pairs[] = array('admin_user' => 0, 'ip' => '');
    }
    $users = get_users(array('role' => 'administrator'));
    $current_ip = $_SERVER['REMOTE_ADDR'];
    ?>
    <div class="wrap">
        <h1>Automatic Admin Login Settings</h1>
        <form method="post">
            <?php wp_nonce_field('one_click_admin_settings'); ?>
            <table class="form-table">
                <thead>
                    <tr>
                        <th style="width:50px;">Admin User</th>
                        <th style="width:50px;">Allowed IP</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="login-pairs-table">
                    <?php
                    $i = 0;
                    foreach ($login_pairs as $pair):
                    ?>
                    <tr class="login-pair-row">
                    <td >
                        <select name="login_pairs[<?php echo $i; ?>][admin]" style="width: 100%;">
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($pair['admin_user'], $user->ID); ?>>
                                <?php echo esc_html($user->display_name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td >
                        <input type="text" name="login_pairs[<?php echo $i; ?>][ip]" value="<?php echo esc_attr($pair['ip']); ?>" style="width: 100%;" />
                    </td>
                    <td style="text-align: left;">
                        <?php if ($i > 0) : ?>
                            <button type="button" class="delete-row button">Delete</button>
                        <?php endif; ?>
                    </td>
                </tr>
                    <?php
                        $i++;
                    endforeach;
                    ?>
                </tbody>
            </table>
            <p>
                <button type="button" id="add-pair" class="button">Add New Row</button>
            </p>
            <table class="form-table" style="margin-top: 20px;">
                <tr>
                    <th>Your Current IP:</th>
                    <td>
                        <div style="display: flex; align-items: left; gap: 10px;">
                            <strong id="current-ip"><?php echo esc_html($current_ip); ?></strong>
                            <button type="button" id="copy-ip" class="button">Copy</button>
                        </div>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <!-- Hidden template used for adding new login pair rows -->
    <template id="login-row-template">
        <tr class="login-pair-row">
            <td>
                <select name="login_pairs[__INDEX__][admin]">
                    <?php foreach ($users as $user): ?>
                    <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="text" name="login_pairs[__INDEX__][ip]" value="" />
            </td>
            <td>
                <button type="button" class="delete-row button">Delete</button>
            </td>
        </tr>
    </template>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Clipboard copy functionality for current IP
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
                document.execCommand("copy");
                copyButton.textContent = "Copied!";
                setTimeout(() => copyButton.textContent = "Copy", 2000);
            });
        } else {
            copyButton.addEventListener("click", function() {
                let ipText = document.getElementById("current-ip").textContent;
                navigator.clipboard.writeText(ipText).then(function() {
                    copyButton.textContent = "Copied!";
                    setTimeout(() => copyButton.textContent = "Copy", 2000);
                }).catch(function(err) {
                    console.error("Failed to copy: ", err);
                });
            });
        }

        // Function to update delete button visibility based on row index.
        function updateDeleteButtons() {
            let rows = document.querySelectorAll("#login-pairs-table tr");
            rows.forEach((row, index) => {
                let lastCell = row.querySelector("td:last-child");
                if (!lastCell) return;
                // Always remove the delete button from the first row.
                if (index === 0) {
                    lastCell.innerHTML = '';
                } else {
                    lastCell.innerHTML = '<button type="button" class="delete-row button">Delete</button>';
                }
            });
        }

        // Add new row functionality
        let addPairButton = document.getElementById("add-pair");
        let loginPairsTable = document.getElementById("login-pairs-table");
        let template = document.getElementById("login-row-template").innerHTML;
        // Determine initial index count based on existing rows
        let newRowIndex = loginPairsTable.children.length;

        addPairButton.addEventListener("click", function() {
            let newRowHtml = template.replace(/__INDEX__/g, newRowIndex);
            loginPairsTable.insertAdjacentHTML('beforeend', newRowHtml);
            newRowIndex++;
            updateDeleteButtons();
        });

        // Delete row functionality
        loginPairsTable.addEventListener("click", function(e) {
            if(e.target && e.target.classList.contains("delete-row")) {
                let row = e.target.closest("tr");
                row.parentElement.removeChild(row);
                updateDeleteButtons();
            }
        });

        // Initial update for delete buttons
        updateDeleteButtons();
    });
    </script>
    <?php
}