<?php
/**
 * REST API Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RC_API {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        $namespace = 'recipe-collector/v1';
        
        // Get all recipes
        register_rest_route($namespace, '/recipes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_recipes'),
            'permission_callback' => '__return_true'
        ));
        
        // Get single recipe
        register_rest_route($namespace, '/recipes/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_recipe'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Create recipe (requires authentication)
        register_rest_route($namespace, '/recipes', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_recipe'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        // Update recipe (requires authentication)
        register_rest_route($namespace, '/recipes/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_recipe'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Delete recipe (requires authentication)
        register_rest_route($namespace, '/recipes/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_recipe'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Add favorite (requires authentication)
        register_rest_route($namespace, '/recipes/(?P<id>\d+)/favorite', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_favorite'),
            'permission_callback' => array($this, 'check_user_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Remove favorite (requires authentication)
        register_rest_route($namespace, '/recipes/(?P<id>\d+)/favorite', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'remove_favorite'),
            'permission_callback' => array($this, 'check_user_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Add rating (requires authentication)
        register_rest_route($namespace, '/recipes/(?P<id>\d+)/rating', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_rating'),
            'permission_callback' => array($this, 'check_user_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Get user favorites (requires authentication)
        register_rest_route($namespace, '/favorites', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_favorites'),
            'permission_callback' => array($this, 'check_user_permission')
        ));
        
        // Get user's own recipes (requires authentication)
        register_rest_route($namespace, '/my-recipes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_my_recipes'),
            'permission_callback' => array($this, 'check_user_permission')
        ));
    }
    
    /**
     * Check if user is admin
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }
    
    /**
     * Check if user is logged in
     */
    public function check_user_permission() {
        return is_user_logged_in();
    }
    
    /**
     * Get recipes
     */
    public function get_recipes($request) {
        $params = $request->get_query_params();
        
        $args = array(
            'per_page' => isset($params['per_page']) ? intval($params['per_page']) : 10,
            'page' => isset($params['page']) ? intval($params['page']) : 1,
            'category' => isset($params['category']) ? sanitize_text_field($params['category']) : '',
            'search' => isset($params['search']) ? sanitize_text_field($params['search']) : '',
            'orderby' => isset($params['orderby']) ? sanitize_text_field($params['orderby']) : 'created_at',
            'order' => isset($params['order']) ? sanitize_text_field($params['order']) : 'DESC'
        );
        
        $recipes = RC_Database::get_recipes($args);
        
        // Format response
        $formatted_recipes = array();
        foreach ($recipes as $recipe) {
            $formatted_recipes[] = $this->format_recipe($recipe);
        }
        
        return new WP_REST_Response($formatted_recipes, 200);
    }
    
    /**
     * Get single recipe
     */
    public function get_recipe($request) {
        $recipe_id = intval($request['id']);
        $recipe = RC_Database::get_recipe($recipe_id);
        
        if (!$recipe) {
            return new WP_Error('not_found', 'Recipe not found', array('status' => 404));
        }
        
        return new WP_REST_Response($this->format_recipe($recipe), 200);
    }
    
    /**
     * Create recipe
     */
    public function create_recipe($request) {
        $data = $request->get_json_params();
        
        $recipe_data = array(
            'title' => sanitize_text_field($data['title']),
            'description' => sanitize_textarea_field($data['description']),
            'ingredients' => sanitize_textarea_field($data['ingredients']),
            'instructions' => sanitize_textarea_field($data['instructions']),
            'prep_time' => intval($data['prep_time']),
            'cook_time' => intval($data['cook_time']),
            'servings' => intval($data['servings']),
            'difficulty' => sanitize_text_field($data['difficulty']),
            'category' => sanitize_text_field($data['category']),
            'image_url' => esc_url_raw($data['image_url']),
            'created_by' => get_current_user_id()
        );
        
        $recipe_id = RC_Database::insert_recipe($recipe_data);
        
        if ($recipe_id) {
            $recipe = RC_Database::get_recipe($recipe_id);
            return new WP_REST_Response($this->format_recipe($recipe), 201);
        }
        
        return new WP_Error('creation_failed', 'Failed to create recipe', array('status' => 500));
    }
    
    /**
     * Update recipe
     */
    public function update_recipe($request) {
        $recipe_id = intval($request['id']);
        $data = $request->get_json_params();
        
        $recipe_data = array();
        if (isset($data['title'])) $recipe_data['title'] = sanitize_text_field($data['title']);
        if (isset($data['description'])) $recipe_data['description'] = sanitize_textarea_field($data['description']);
        if (isset($data['ingredients'])) $recipe_data['ingredients'] = sanitize_textarea_field($data['ingredients']);
        if (isset($data['instructions'])) $recipe_data['instructions'] = sanitize_textarea_field($data['instructions']);
        if (isset($data['prep_time'])) $recipe_data['prep_time'] = intval($data['prep_time']);
        if (isset($data['cook_time'])) $recipe_data['cook_time'] = intval($data['cook_time']);
        if (isset($data['servings'])) $recipe_data['servings'] = intval($data['servings']);
        if (isset($data['difficulty'])) $recipe_data['difficulty'] = sanitize_text_field($data['difficulty']);
        if (isset($data['category'])) $recipe_data['category'] = sanitize_text_field($data['category']);
        if (isset($data['image_url'])) $recipe_data['image_url'] = esc_url_raw($data['image_url']);
        if (isset($data['status'])) $recipe_data['status'] = sanitize_text_field($data['status']);
        
        $result = RC_Database::update_recipe($recipe_id, $recipe_data);
        
        if ($result !== false) {
            $recipe = RC_Database::get_recipe($recipe_id);
            return new WP_REST_Response($this->format_recipe($recipe), 200);
        }
        
        return new WP_Error('update_failed', 'Failed to update recipe', array('status' => 500));
    }
    
    /**
     * Delete recipe
     */
    public function delete_recipe($request) {
        $recipe_id = intval($request['id']);
        $result = RC_Database::delete_recipe($recipe_id);
        
        if ($result !== false) {
            return new WP_REST_Response(array('message' => 'Recipe deleted successfully'), 200);
        }
        
        return new WP_Error('delete_failed', 'Failed to delete recipe', array('status' => 500));
    }
    
    /**
     * Add favorite
     */
    public function add_favorite($request) {
        $recipe_id = intval($request['id']);
        $user_id = get_current_user_id();
        
        $result = RC_Database::add_favorite($user_id, $recipe_id);
        
        if ($result !== false) {
            return new WP_REST_Response(array('message' => 'Recipe added to favorites'), 200);
        }
        
        return new WP_Error('add_failed', 'Failed to add favorite', array('status' => 500));
    }
    
    /**
     * Remove favorite
     */
    public function remove_favorite($request) {
        $recipe_id = intval($request['id']);
        $user_id = get_current_user_id();
        
        $result = RC_Database::remove_favorite($user_id, $recipe_id);
        
        if ($result !== false) {
            return new WP_REST_Response(array('message' => 'Recipe removed from favorites'), 200);
        }
        
        return new WP_Error('remove_failed', 'Failed to remove favorite', array('status' => 500));
    }
    
    /**
     * Add rating
     */
    public function add_rating($request) {
        $recipe_id = intval($request['id']);
        $user_id = get_current_user_id();
        $data = $request->get_json_params();
        $rating = intval($data['rating']);
        
        if ($rating < 1 || $rating > 5) {
            return new WP_Error('invalid_rating', 'Rating must be between 1 and 5', array('status' => 400));
        }
        
        $result = RC_Database::add_rating($user_id, $recipe_id, $rating);
        
        if ($result !== false) {
            return new WP_REST_Response(array('message' => 'Rating added successfully'), 200);
        }
        
        return new WP_Error('rating_failed', 'Failed to add rating', array('status' => 500));
    }
    
    /**
     * Get user favorites
     */
    public function get_favorites($request) {
        $user_id = get_current_user_id();
        $favorites = RC_Database::get_user_favorites($user_id);
        
        $formatted_favorites = array();
        foreach ($favorites as $recipe) {
            $formatted_favorites[] = $this->format_recipe($recipe);
        }
        
        return new WP_REST_Response($formatted_favorites, 200);
    }
    
    /**
     * Get user's own recipes
     */
    public function get_my_recipes($request) {
        $user_id = get_current_user_id();
        $params = $request->get_query_params();
        
        $args = array(
            'per_page' => isset($params['per_page']) ? intval($params['per_page']) : 20,
            'page' => isset($params['page']) ? intval($params['page']) : 1,
            'status' => isset($params['status']) ? sanitize_text_field($params['status']) : 'all',
            'orderby' => isset($params['orderby']) ? sanitize_text_field($params['orderby']) : 'created_at',
            'order' => isset($params['order']) ? sanitize_text_field($params['order']) : 'DESC'
        );
        
        $recipes = RC_Database::get_user_recipes($user_id, $args);
        
        $formatted_recipes = array();
        foreach ($recipes as $recipe) {
            $formatted_recipes[] = $this->format_recipe($recipe);
        }
        
        return new WP_REST_Response($formatted_recipes, 200);
    }
    
    /**
     * Format recipe for API response
     */
    private function format_recipe($recipe) {
        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        $is_favorited = $user_id > 0 ? RC_Database::is_favorited($user_id, $recipe->id) : false;
        $rating = RC_Database::get_recipe_rating($recipe->id);
        
        return array(
            'id' => intval($recipe->id),
            'title' => $recipe->title,
            'description' => $recipe->description,
            'ingredients' => $recipe->ingredients,
            'instructions' => $recipe->instructions,
            'prep_time' => intval($recipe->prep_time),
            'cook_time' => intval($recipe->cook_time),
            'servings' => intval($recipe->servings),
            'difficulty' => $recipe->difficulty,
            'category' => $recipe->category,
            'image_url' => $recipe->image_url,
            'created_by' => intval($recipe->created_by),
            'created_at' => $recipe->created_at,
            'updated_at' => $recipe->updated_at,
            'status' => $recipe->status,
            'is_favorited' => $is_favorited,
            'rating' => $rating ? round(floatval($rating), 2) : 0
        );
    }
}

