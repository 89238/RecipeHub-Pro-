<?php
/**
 * Plugin Name: RecipeHub Pro - Recipe Management System
 * Plugin URI: https://github.com/yourusername/recipe-collector
 * Description: A comprehensive recipe management system with user registration, admin interface, and REST API endpoints.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://github.com/yourusername
 * License: GPL v2 or later
 * Text Domain: recipe-collector
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RC_VERSION', '1.0.0');
define('RC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RC_PLUGIN_FILE', __FILE__);

// Include required files
require_once RC_PLUGIN_DIR . 'includes/class-rc-database.php';
require_once RC_PLUGIN_DIR . 'includes/class-rc-api.php';
require_once RC_PLUGIN_DIR . 'includes/class-rc-admin.php';
require_once RC_PLUGIN_DIR . 'includes/class-rc-frontend.php';
require_once RC_PLUGIN_DIR . 'includes/class-rc-auth.php';

/**
 * Main Recipe Collector Class
 */
class Recipe_Collector {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array('RC_Database', 'create_tables'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize components
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    public function init() {
        // Initialize authentication
        RC_Auth::get_instance();
        
        // Initialize API
        RC_API::get_instance();
        
        // Initialize admin
        if (is_admin()) {
            RC_Admin::get_instance();
        }
        
        // Initialize frontend
        RC_Frontend::get_instance();
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('recipe-collector-style', RC_PLUGIN_URL . 'assets/css/style.css', array(), RC_VERSION);
        wp_enqueue_script('recipe-collector-script', RC_PLUGIN_URL . 'assets/js/script.js', array('jquery'), RC_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('recipe-collector-script', 'rcAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rc_nonce'),
            'rest_url' => rest_url('recipe-collector/v1/'),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'user_id' => get_current_user_id()
        ));
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'recipe-collector') !== false) {
            wp_enqueue_style('recipe-collector-admin-style', RC_PLUGIN_URL . 'assets/css/admin.css', array(), RC_VERSION);
            wp_enqueue_script('recipe-collector-admin-script', RC_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), RC_VERSION, true);
            
            wp_localize_script('recipe-collector-admin-script', 'rcAdminAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rc_admin_nonce'),
                'rest_url' => rest_url('recipe-collector/v1/'),
                'rest_nonce' => wp_create_nonce('wp_rest')
            ));
        }
    }
    
    public function deactivate() {
        // Cleanup on deactivation if needed
    }
}

// Initialize the plugin
Recipe_Collector::get_instance();

