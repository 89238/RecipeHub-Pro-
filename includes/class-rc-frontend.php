<?php
/**
 * Frontend Interface Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RC_Frontend {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_shortcode('rc_recipes', array($this, 'recipes_list'));
        add_shortcode('rc_recipe', array($this, 'recipe_detail'));
        add_shortcode('rc_favorites', array($this, 'favorites_list'));
        add_shortcode('rc_submit_recipe', array($this, 'submit_recipe_form'));
        add_shortcode('rc_my_recipes', array($this, 'my_recipes_list'));
        add_action('wp_ajax_rc_submit_recipe', array($this, 'handle_submit_recipe'));
        add_action('wp_ajax_nopriv_rc_submit_recipe', array($this, 'handle_submit_recipe'));
    }
    
    /**
     * Recipes list shortcode
     */
    public function recipes_list($atts) {
        $atts = shortcode_atts(array(
            'per_page' => '12',
            'category' => '',
            'show_search' => 'true'
        ), $atts);
        
        ob_start();
        ?>
        <div class="rc-recipes-container">
            <?php if ($atts['show_search'] === 'true'): ?>
            <div class="rc-search-filters">
                <div class="rc-search-box">
                    <input type="text" id="rc-frontend-search" placeholder="Search recipes...">
                    <button id="rc-search-button" class="rc-btn rc-btn-primary">Search</button>
                </div>
                <div class="rc-filter-box">
                    <select id="rc-category-filter">
                        <option value="">All Categories</option>
                        <!-- Categories will be loaded via JavaScript -->
                    </select>
                    <select id="rc-sort-filter">
                        <option value="created_at:DESC">Newest First</option>
                        <option value="created_at:ASC">Oldest First</option>
                        <option value="title:ASC">Title A-Z</option>
                        <option value="title:DESC">Title Z-A</option>
                    </select>
                </div>
            </div>
            <?php endif; ?>
            
            <div id="rc-recipes-grid" class="rc-recipes-grid">
                <!-- Recipes will be loaded here via JavaScript -->
            </div>
            
            <div id="rc-loading" class="rc-loading" style="display: none;">
                <p>Loading recipes...</p>
            </div>
            
            <div id="rc-pagination" class="rc-pagination">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Recipe detail shortcode
     */
    public function recipe_detail($atts) {
        $atts = shortcode_atts(array(
            'id' => '0'
        ), $atts);
        
        $recipe_id = intval($atts['id']);
        
        ob_start();
        ?>
        <div class="rc-recipe-detail" data-recipe-id="<?php echo $recipe_id; ?>">
            <div id="rc-recipe-content">
                <!-- Recipe content will be loaded here via JavaScript -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Favorites list shortcode
     */
    public function favorites_list($atts) {
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wp_login_url() . '">login</a> to view your favorites.</p>';
        }
        
        ob_start();
        ?>
        <div class="rc-favorites-container">
            <h2>My Favorite Recipes</h2>
            <div id="rc-favorites-grid" class="rc-recipes-grid">
                <!-- Favorites will be loaded here via JavaScript -->
            </div>
            <div id="rc-favorites-empty" class="rc-empty-state" style="display: none;">
                <p>You haven't favorited any recipes yet.</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Submit recipe form shortcode
     */
    public function submit_recipe_form($atts) {
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to submit a recipe.</p>';
        }
        
        ob_start();
        ?>
        <div class="rc-submit-recipe-container">
            <h2>Submit Your Recipe</h2>
            <form id="rc-submit-recipe-form" class="rc-submit-recipe-form">
                <div class="rc-form-row">
                    <div class="rc-form-group full-width">
                        <label for="rc-submit-title">Recipe Title *</label>
                        <input type="text" id="rc-submit-title" name="title" required>
                    </div>
                </div>
                
                <div class="rc-form-row">
                    <div class="rc-form-group full-width">
                        <label for="rc-submit-description">Description</label>
                        <textarea id="rc-submit-description" name="description" rows="4" placeholder="Brief description of your recipe"></textarea>
                    </div>
                </div>
                
                <div class="rc-form-row">
                    <div class="rc-form-group">
                        <label for="rc-submit-category">Category</label>
                        <input type="text" id="rc-submit-category" name="category" placeholder="e.g., Italian, Dessert, Main Course">
                    </div>
                    <div class="rc-form-group">
                        <label for="rc-submit-difficulty">Difficulty</label>
                        <select id="rc-submit-difficulty" name="difficulty">
                            <option value="easy">Easy</option>
                            <option value="medium" selected>Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                </div>
                
                <div class="rc-form-row">
                    <div class="rc-form-group">
                        <label for="rc-submit-prep-time">Prep Time (minutes)</label>
                        <input type="number" id="rc-submit-prep-time" name="prep_time" value="0" min="0">
                    </div>
                    <div class="rc-form-group">
                        <label for="rc-submit-cook-time">Cook Time (minutes)</label>
                        <input type="number" id="rc-submit-cook-time" name="cook_time" value="0" min="0">
                    </div>
                    <div class="rc-form-group">
                        <label for="rc-submit-servings">Servings</label>
                        <input type="number" id="rc-submit-servings" name="servings" value="1" min="1">
                    </div>
                </div>
                
                <div class="rc-form-row">
                    <div class="rc-form-group full-width">
                        <label for="rc-submit-image-url">Image URL</label>
                        <input type="url" id="rc-submit-image-url" name="image_url" placeholder="https://example.com/image.jpg">
                        <small>Enter a URL to an image of your recipe</small>
                    </div>
                </div>
                
                <div class="rc-form-row">
                    <div class="rc-form-group full-width">
                        <label for="rc-submit-ingredients">Ingredients *</label>
                        <textarea id="rc-submit-ingredients" name="ingredients" rows="8" required placeholder="Enter ingredients, one per line or separated by commas"></textarea>
                        <small>Enter each ingredient on a new line</small>
                    </div>
                </div>
                
                <div class="rc-form-row">
                    <div class="rc-form-group full-width">
                        <label for="rc-submit-instructions">Instructions *</label>
                        <textarea id="rc-submit-instructions" name="instructions" rows="10" required placeholder="Enter step-by-step instructions"></textarea>
                        <small>Enter each step on a new line</small>
                    </div>
                </div>
                
                <div class="rc-form-actions">
                    <button type="submit" class="rc-btn rc-btn-primary">Submit Recipe</button>
                    <button type="reset" class="rc-btn rc-btn-secondary">Clear Form</button>
                </div>
                
                <div id="rc-submit-message" class="rc-form-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle recipe submission
     */
    public function handle_submit_recipe() {
        check_ajax_referer('rc_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in to submit a recipe.'));
        }
        
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
            'image_url' => esc_url_raw($_POST['image_url']),
            'created_by' => get_current_user_id(),
            'status' => 'published' // User submissions are published immediately and added to collection
        );
        
        // Validation
        if (empty($data['title']) || empty($data['ingredients']) || empty($data['instructions'])) {
            wp_send_json_error(array('message' => 'Title, ingredients, and instructions are required.'));
        }
        
        $recipe_id = RC_Database::insert_recipe($data);
        
        if ($recipe_id) {
            wp_send_json_success(array(
                'message' => 'Recipe submitted successfully! Your recipe has been added to the collection.',
                'recipe_id' => $recipe_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to submit recipe. Please try again.'));
        }
    }
}

