<?php
/**
 * Admin Interface Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RC_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_rc_admin_save_recipe', array($this, 'handle_save_recipe'));
        add_action('wp_ajax_rc_admin_delete_recipe', array($this, 'handle_delete_recipe'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'RecipeHub Pro',
            'RecipeHub Pro',
            'manage_options',
            'recipe-collector',
            array($this, 'admin_page'),
            'dashicons-food',
            30
        );
        
        add_submenu_page(
            'recipe-collector',
            'All Recipes',
            'All Recipes',
            'manage_options',
            'recipe-collector',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'recipe-collector',
            'Add New Recipe',
            'Add New',
            'manage_options',
            'recipe-collector-add',
            array($this, 'add_recipe_page')
        );
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap rc-admin-wrap">
            <h1>RecipeHub Pro - All Recipes</h1>
            <div class="rc-admin-container">
                <div class="rc-admin-header">
                    <a href="<?php echo admin_url('admin.php?page=recipe-collector-add'); ?>" class="button button-primary">Add New Recipe</a>
                    <div class="rc-search-box">
                        <input type="text" id="rc-search-input" placeholder="Search recipes...">
                        <button id="rc-search-btn" class="button">Search</button>
                    </div>
                </div>
                <div class="rc-status-filter">
                    <label>Filter by Status: </label>
                    <select id="rc-status-filter">
                        <option value="all">All Recipes</option>
                        <option value="published">Published</option>
                        <option value="pending">Pending Approval</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div id="rc-recipes-list" class="rc-recipes-list">
                    <!-- Recipes will be loaded here via JavaScript -->
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Add recipe page
     */
    public function add_recipe_page() {
        $recipe_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $recipe = null;
        
        if ($recipe_id > 0) {
            $recipe = RC_Database::get_recipe($recipe_id);
            if (!$recipe) {
                $recipe_id = 0;
            }
        }
        ?>
        <div class="wrap rc-admin-wrap">
            <h1><?php echo $recipe_id > 0 ? 'Edit Recipe' : 'Add New Recipe'; ?></h1>
            <div class="rc-admin-container">
                <form id="rc-recipe-form" class="rc-recipe-form">
                    <input type="hidden" id="rc-recipe-id" value="<?php echo $recipe_id; ?>">
                    
                    <div class="rc-form-row">
                        <div class="rc-form-group">
                            <label for="rc-title">Recipe Title *</label>
                            <input type="text" id="rc-title" name="title" value="<?php echo $recipe ? esc_attr($recipe->title) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="rc-form-row">
                        <div class="rc-form-group">
                            <label for="rc-description">Description</label>
                            <textarea id="rc-description" name="description" rows="4"><?php echo $recipe ? esc_textarea($recipe->description) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="rc-form-row">
                        <div class="rc-form-group">
                            <label for="rc-category">Category</label>
                            <input type="text" id="rc-category" name="category" value="<?php echo $recipe ? esc_attr($recipe->category) : ''; ?>" placeholder="e.g., Italian, Dessert, Main Course">
                        </div>
                        <div class="rc-form-group">
                            <label for="rc-difficulty">Difficulty</label>
                            <select id="rc-difficulty" name="difficulty">
                                <option value="easy" <?php echo $recipe && $recipe->difficulty === 'easy' ? 'selected' : ''; ?>>Easy</option>
                                <option value="medium" <?php echo $recipe && $recipe->difficulty === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="hard" <?php echo $recipe && $recipe->difficulty === 'hard' ? 'selected' : ''; ?>>Hard</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="rc-form-row">
                        <div class="rc-form-group">
                            <label for="rc-prep-time">Prep Time (minutes)</label>
                            <input type="number" id="rc-prep-time" name="prep_time" value="<?php echo $recipe ? intval($recipe->prep_time) : '0'; ?>" min="0">
                        </div>
                        <div class="rc-form-group">
                            <label for="rc-cook-time">Cook Time (minutes)</label>
                            <input type="number" id="rc-cook-time" name="cook_time" value="<?php echo $recipe ? intval($recipe->cook_time) : '0'; ?>" min="0">
                        </div>
                        <div class="rc-form-group">
                            <label for="rc-servings">Servings</label>
                            <input type="number" id="rc-servings" name="servings" value="<?php echo $recipe ? intval($recipe->servings) : '1'; ?>" min="1">
                        </div>
                    </div>
                    
                    <div class="rc-form-row">
                        <div class="rc-form-group">
                            <label for="rc-image-url">Image URL</label>
                            <input type="url" id="rc-image-url" name="image_url" value="<?php echo $recipe ? esc_url($recipe->image_url) : ''; ?>" placeholder="https://example.com/image.jpg">
                        </div>
                    </div>
                    
                    <div class="rc-form-row">
                        <div class="rc-form-group">
                            <label for="rc-ingredients">Ingredients *</label>
                            <textarea id="rc-ingredients" name="ingredients" rows="8" required placeholder="Enter ingredients, one per line or separated by commas"><?php echo $recipe ? esc_textarea($recipe->ingredients) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="rc-form-row">
                        <div class="rc-form-group">
                            <label for="rc-instructions">Instructions *</label>
                            <textarea id="rc-instructions" name="instructions" rows="10" required placeholder="Enter step-by-step instructions"><?php echo $recipe ? esc_textarea($recipe->instructions) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <?php if ($recipe_id > 0): ?>
                    <div class="rc-form-row">
                        <div class="rc-form-group">
                            <label for="rc-status">Status</label>
                    <select id="rc-status" name="status">
                        <option value="published" <?php echo $recipe && $recipe->status === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="pending" <?php echo $recipe && $recipe->status === 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                        <option value="draft" <?php echo $recipe && $recipe->status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="rc-form-actions">
                        <button type="submit" class="button button-primary"><?php echo $recipe_id > 0 ? 'Update Recipe' : 'Add Recipe'; ?></button>
                        <a href="<?php echo admin_url('admin.php?page=recipe-collector'); ?>" class="button">Cancel</a>
                    </div>
                    
                    <div id="rc-form-message" class="rc-form-message"></div>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle save recipe
     */
    public function handle_save_recipe() {
        check_ajax_referer('rc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $recipe_id = isset($_POST['recipe_id']) ? intval($_POST['recipe_id']) : 0;
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'ingredients' => sanitize_textarea_field($_POST['ingredients']),
            'instructions' => sanitize_textarea_field($_POST['instructions']),
            'prep_time' => intval($_POST['prep_time']),
            'cook_time' => intval($_POST['cook_time']),
            'servings' => intval($_POST['servings']),
            'difficulty' => sanitize_text_field($_POST['difficulty']),
            'category' => sanitize_text_field($_POST['category']),
            'image_url' => esc_url_raw($_POST['image_url'])
        );
        
        if (isset($_POST['status'])) {
            $data['status'] = sanitize_text_field($_POST['status']);
        }
        
        if ($recipe_id > 0) {
            $result = RC_Database::update_recipe($recipe_id, $data);
        } else {
            $result = RC_Database::insert_recipe($data);
        }
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => $recipe_id > 0 ? 'Recipe updated successfully' : 'Recipe added successfully',
                'redirect' => admin_url('admin.php?page=recipe-collector')
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save recipe'));
        }
    }
    
    /**
     * Handle delete recipe
     */
    public function handle_delete_recipe() {
        check_ajax_referer('rc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $recipe_id = intval($_POST['recipe_id']);
        $result = RC_Database::delete_recipe($recipe_id);
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Recipe deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete recipe'));
        }
    }
}

