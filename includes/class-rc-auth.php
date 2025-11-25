<?php
/**
 * Authentication Class - Custom Registration and Login
 */

if (!defined('ABSPATH')) {
    exit;
}

class RC_Auth {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_rc_register', array($this, 'handle_register'));
        add_action('wp_ajax_nopriv_rc_register', array($this, 'handle_register'));
        add_action('wp_ajax_rc_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_rc_login', array($this, 'handle_login'));
        add_action('wp_ajax_rc_logout', array($this, 'handle_logout'));
        add_action('wp_ajax_nopriv_rc_logout', array($this, 'handle_logout'));
        
        // Add shortcodes
        add_shortcode('rc_register', array($this, 'register_form'));
        add_shortcode('rc_login', array($this, 'login_form'));
    }
    
    /**
     * Handle user registration
     */
    public function handle_register() {
        check_ajax_referer('rc_nonce', 'nonce');
        
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'All fields are required.'));
        }
        
        if ($password !== $confirm_password) {
            wp_send_json_error(array('message' => 'Passwords do not match.'));
        }
        
        if (strlen($password) < 6) {
            wp_send_json_error(array('message' => 'Password must be at least 6 characters.'));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Invalid email address.'));
        }
        
        // Check if username exists
        if (username_exists($username)) {
            wp_send_json_error(array('message' => 'Username already exists.'));
        }
        
        // Check if email exists
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Email already registered.'));
        }
        
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }
        
        // Auto login
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        wp_send_json_success(array(
            'message' => 'Registration successful!',
            'redirect' => home_url('/recipes/')
        ));
    }
    
    /**
     * Handle user login
     */
    public function handle_login() {
        check_ajax_referer('rc_nonce', 'nonce');
        
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;
        
        if (empty($username) || empty($password)) {
            wp_send_json_error(array('message' => 'Username and password are required.'));
        }
        
        $user = wp_authenticate($username, $password);
        
        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => 'Invalid username or password.'));
        }
        
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);
        
        wp_send_json_success(array(
            'message' => 'Login successful!',
            'redirect' => home_url('/recipes/')
        ));
    }
    
    /**
     * Handle logout
     */
    public function handle_logout() {
        check_ajax_referer('rc_nonce', 'nonce');
        
        wp_logout();
        
        wp_send_json_success(array(
            'message' => 'Logged out successfully.',
            'redirect' => home_url()
        ));
    }
    
    /**
     * Registration form shortcode
     */
    public function register_form($atts) {
        if (is_user_logged_in()) {
            return '<p>You are already logged in. <a href="' . wp_logout_url(home_url()) . '">Logout</a></p>';
        }
        
        ob_start();
        ?>
        <div class="rc-register-form">
            <h2>Register</h2>
            <form id="rc-register-form">
                <div class="rc-form-group">
                    <label for="rc-username">Username</label>
                    <input type="text" id="rc-username" name="username" required>
                </div>
                <div class="rc-form-group">
                    <label for="rc-email">Email</label>
                    <input type="email" id="rc-email" name="email" required>
                </div>
                <div class="rc-form-group">
                    <label for="rc-password">Password</label>
                    <input type="password" id="rc-password" name="password" required>
                </div>
                <div class="rc-form-group">
                    <label for="rc-confirm-password">Confirm Password</label>
                    <input type="password" id="rc-confirm-password" name="confirm_password" required>
                </div>
                <div class="rc-form-group">
                    <button type="submit" class="rc-btn rc-btn-primary">Register</button>
                </div>
                <div class="rc-form-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Login form shortcode
     */
    public function login_form($atts) {
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            return '<p>Welcome, ' . esc_html($current_user->display_name) . '! <a href="' . wp_logout_url(home_url()) . '">Logout</a></p>';
        }
        
        ob_start();
        ?>
        <div class="rc-login-form">
            <h2>Login</h2>
            <form id="rc-login-form">
                <div class="rc-form-group">
                    <label for="rc-login-username">Username</label>
                    <input type="text" id="rc-login-username" name="username" required>
                </div>
                <div class="rc-form-group">
                    <label for="rc-login-password">Password</label>
                    <input type="password" id="rc-login-password" name="password" required>
                </div>
                <div class="rc-form-group">
                    <label>
                        <input type="checkbox" name="remember" value="1"> Remember me
                    </label>
                </div>
                <div class="rc-form-group">
                    <button type="submit" class="rc-btn rc-btn-primary">Login</button>
                </div>
                <div class="rc-form-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}

