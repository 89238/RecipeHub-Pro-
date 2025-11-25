<?php
/**
 * Database Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RC_Database {
    
    /**
     * Create database tables on plugin activation
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Recipes table
        $table_recipes = $wpdb->prefix . 'rc_recipes';
        $sql_recipes = "CREATE TABLE $table_recipes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            ingredients text NOT NULL,
            instructions text NOT NULL,
            prep_time int(11) DEFAULT 0,
            cook_time int(11) DEFAULT 0,
            servings int(11) DEFAULT 1,
            difficulty varchar(50) DEFAULT 'medium',
            category varchar(100) DEFAULT '',
            image_url varchar(500) DEFAULT '',
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'published',
            PRIMARY KEY (id),
            KEY created_by (created_by),
            KEY status (status)
        ) $charset_collate;";
        
        // Recipe favorites table
        $table_favorites = $wpdb->prefix . 'rc_favorites';
        $sql_favorites = "CREATE TABLE $table_favorites (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            recipe_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_recipe (user_id, recipe_id),
            KEY user_id (user_id),
            KEY recipe_id (recipe_id)
        ) $charset_collate;";
        
        // Recipe ratings table
        $table_ratings = $wpdb->prefix . 'rc_ratings';
        $sql_ratings = "CREATE TABLE $table_ratings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            recipe_id bigint(20) NOT NULL,
            rating int(1) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_recipe (user_id, recipe_id),
            KEY user_id (user_id),
            KEY recipe_id (recipe_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_recipes);
        dbDelta($sql_favorites);
        dbDelta($sql_ratings);
    }
    
    /**
     * Get recipes with filters
     */
    public static function get_recipes($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'per_page' => 10,
            'page' => 1,
            'status' => 'published',
            'category' => '',
            'search' => '',
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . 'rc_recipes';
        
        // If status is 'all', don't filter by status
        if ($args['status'] === 'all') {
            $where = array('1=1');
        } else {
            $where = array("status = '{$args['status']}'");
        }
        
        if (!empty($args['category'])) {
            $category = sanitize_text_field($args['category']);
            $where[] = "category = '{$category}'";
        }
        
        if (!empty($args['search'])) {
            $search = sanitize_text_field($args['search']);
            $where[] = "(title LIKE '%{$search}%' OR description LIKE '%{$search}%')";
        }
        
        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $sql = "SELECT * FROM $table_name WHERE $where_clause ORDER BY {$args['orderby']} {$args['order']} LIMIT {$args['per_page']} OFFSET $offset";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get single recipe
     */
    public static function get_recipe($recipe_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rc_recipes';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND status = 'published'",
            $recipe_id
        ));
    }
    
    /**
     * Insert recipe
     */
    public static function insert_recipe($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rc_recipes';
        
        $defaults = array(
            'title' => '',
            'description' => '',
            'ingredients' => '',
            'instructions' => '',
            'prep_time' => 0,
            'cook_time' => 0,
            'servings' => 1,
            'difficulty' => 'medium',
            'category' => '',
            'image_url' => '',
            'created_by' => get_current_user_id(),
            'status' => 'published'
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update recipe
     */
    public static function update_recipe($recipe_id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rc_recipes';
        
        return $wpdb->update(
            $table_name,
            $data,
            array('id' => $recipe_id)
        );
    }
    
    /**
     * Delete recipe
     */
    public static function delete_recipe($recipe_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rc_recipes';
        
        return $wpdb->delete($table_name, array('id' => $recipe_id));
    }
    
    /**
     * Add favorite
     */
    public static function add_favorite($user_id, $recipe_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rc_favorites';
        
        return $wpdb->insert($table_name, array(
            'user_id' => $user_id,
            'recipe_id' => $recipe_id
        ));
    }
    
    /**
     * Remove favorite
     */
    public static function remove_favorite($user_id, $recipe_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rc_favorites';
        
        return $wpdb->delete($table_name, array(
            'user_id' => $user_id,
            'recipe_id' => $recipe_id
        ));
    }
    
    /**
     * Check if recipe is favorited
     */
    public static function is_favorited($user_id, $recipe_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rc_favorites';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND recipe_id = %d",
            $user_id,
            $recipe_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Get user favorites
     */
    public static function get_user_favorites($user_id) {
        global $wpdb;
        $table_favorites = $wpdb->prefix . 'rc_favorites';
        $table_recipes = $wpdb->prefix . 'rc_recipes';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.* FROM $table_recipes r 
            INNER JOIN $table_favorites f ON r.id = f.recipe_id 
            WHERE f.user_id = %d AND r.status = 'published' 
            ORDER BY f.created_at DESC",
            $user_id
        ));
    }
    
    /**
     * Add rating
     */
    public static function add_rating($user_id, $recipe_id, $rating) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rc_ratings';
        
        return $wpdb->replace($table_name, array(
            'user_id' => $user_id,
            'recipe_id' => $recipe_id,
            'rating' => $rating
        ));
    }
    
    /**
     * Get recipe average rating
     */
    public static function get_recipe_rating($recipe_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rc_ratings';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(rating) FROM $table_name WHERE recipe_id = %d",
            $recipe_id
        ));
    }
    
    /**
     * Get user's own recipes
     */
    public static function get_user_recipes($user_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'status' => 'all',
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . 'rc_recipes';
        
        $where = array("created_by = %d");
        $params = array($user_id);
        
        if ($args['status'] !== 'all') {
            $where[] = "status = %s";
            $params[] = $args['status'];
        }
        
        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $sql = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE $where_clause ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
            array_merge($params, array($args['per_page'], $offset))
        );
        
        return $wpdb->get_results($sql);
    }
}

