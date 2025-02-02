# Automatic Admin Login

## Description

The **Automatic Admin Login** plugin automatically logs in an admin user when accessed from a specified IP address. If enabled, it will also display the current user’s IP address instead of logging in automatically. This is useful for administrators who want to quickly access the WordPress dashboard from a trusted location without going through the standard login process.

## What Does It Solve?

For site administrators, especially in local or development environments, it can be time-consuming to manually log in each time after reloading the page or using tools like browser-sync. This plugin automates the login process, providing quick access for authorized users from specified IP addresses.

## Features

- **Automatic Login**: Automatically logs in the specified admin user when the correct IP address is detected.
- **IP Address Display**: Optionally shows the user’s IP address instead of logging in automatically.
- **Admin User Selection**: Choose which admin user to log in automatically.
- **Security**: Ensures that the login only happens from a specified IP address.
- **Nonce Protection**: Uses WordPress nonces to ensure that the login request is secure.

## How It Works

1. **IP Address Validation**: The plugin checks the user’s IP address against the stored value in the settings.
2. **Auto-Login**: If the user’s IP matches the specified one and they are not logged in, the plugin automatically logs them in as the selected admin user.
3. **IP Display**: If the option is enabled, the plugin will display the user’s IP address instead of attempting automatic login.
4. **Login Redirection**: After successful login, the user is redirected to the original page or the admin dashboard.

## Installation

1. Upload the `automatic-admin-login.php` file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the plugin settings page under **Settings > Automatic Admin Login** to configure the admin user and IP address.

## Usage

- Once activated, if the specified conditions (correct IP address) are met, the plugin will automatically log in the admin user.
- If the checkbox to display the user’s IP address is enabled, the IP will be shown instead of performing the login.

## Settings

- **Admin User**: Select which admin user should be logged in automatically.
- **Allowed IP for Auto-Login**: Enter the IP address from which the auto-login is allowed.
- **Show IP Instead of Auto-Login**: If enabled, the current user’s IP address will be displayed on the page instead of logging in automatically.

## Security Considerations

- **IP Address Validation**: Only allows login from an authorized IP address to prevent unauthorized access.
- **Nonce Protection**: Uses a nonce to validate requests, ensuring that login attempts are legitimate.
- **Admin Role Check**: Only allows login as an administrator.

## Author

- Marko Krstic