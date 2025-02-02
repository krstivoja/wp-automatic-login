# One Click Admin Login

## Description

The **One Click Admin Login** plugin displays a login button in the footer for non-logged-in users, allowing them to log in as admin with one click. This feature is particularly useful for site administrators who need quick access to the admin dashboard without going through the standard login process.

## What does it solve?

If you run localwp or any other local development environment, you can quickly log in as admin, but it yoo need hot reload and you start using browser-sync you will need to login again and manually. This will log you automatically if you are supper user with ID 1.

## Features

- **Easy Access**: Add login button only if you are not logged in.
- **Security**: Utilizes WordPress nonces to ensure that the login request is secure and valid.
- **Fallback User**: If a specific user (e.g., 'dev') is not found, the plugin defaults to logging in the super admin (user ID 1).

## How It Works

1. **Button Display**: The plugin hooks into the WordPress footer to display a login button when a user is not logged in.
2. **Nonce Verification**: When the button is clicked, the plugin verifies the nonce to ensure the request is legitimate.
3. **User Authentication**: If the specified user is not found, the plugin attempts to log in the super admin. Upon successful login, the user is redirected to the admin dashboard.

## Installation

1. Upload the `click-login.php` file to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. The login button will now appear in the footer for non-logged-in users.

## Usage

Simply visit your WordPress site as a non-logged-in user, and you will see the "Admin Login" button in the bottom right corner. Clicking this button will log you in as the admin user.

## Author

- Marko Krstic
