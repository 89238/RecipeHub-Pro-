# Recipe Collector - WordPress Plugin

A comprehensive recipe management system for WordPress with user registration, admin interface, and REST API endpoints.

## Features

✅ **User Registration & Login** - Custom registration and login system with SQL database interaction  
✅ **Backend Development** - PHP backend with WordPress REST API endpoints  
✅ **Frontend Development** - HTML, CSS, JavaScript with jQuery for interactive user interface  
✅ **Admin Interface** - Complete admin panel to add, edit, and delete recipes  
✅ **User Interface** - Beautiful frontend to browse, search, and view recipes  
✅ **REST API** - Full RESTful API for recipe management  
✅ **Favorites System** - Users can favorite recipes  
✅ **Rating System** - Users can rate recipes (1-5 stars)  
✅ **Search & Filter** - Search recipes by title/description and filter by category  

## Installation

1. **Upload the Plugin**
   - Copy the `recipe-collector` folder to your WordPress `wp-content/plugins/` directory
   - Or upload it via WordPress admin: Plugins → Add New → Upload Plugin

2. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Recipe Collector" and click "Activate"
   - The plugin will automatically create the necessary database tables

3. **Configure Pages**
   - Create a new page for recipes listing (e.g., "Recipes")
   - Add the shortcode: `[rc_recipes]`
   - Create a page for registration: `[rc_register]`
   - Create a page for login: `[rc_login]`
   - Create a page for favorites: `[rc_favorites]`

## Usage

### Admin Interface

1. **Access Admin Panel**
   - Go to WordPress Admin → Recipe Collector
   - You'll see "All Recipes" and "Add New" menu items

2. **Add a Recipe**
   - Click "Add New" in the Recipe Collector menu
   - Fill in the recipe details:
     - Title (required)
     - Description
     - Category
     - Difficulty (Easy/Medium/Hard)
     - Prep Time and Cook Time (in minutes)
     - Servings
     - Image URL
     - Ingredients (required)
     - Instructions (required)
   - Click "Add Recipe"

3. **Edit/Delete Recipes**
   - Go to "All Recipes" to see all recipes
   - Click "Edit" to modify a recipe
   - Click "Delete" to remove a recipe

### User Interface

1. **View Recipes**
   - Visit the page where you added `[rc_recipes]` shortcode
   - Browse all recipes in a grid layout
   - Use search and filters to find specific recipes

2. **View Recipe Details**
   - Click on any recipe card to view full details
   - See ingredients, instructions, and recipe metadata

3. **User Features** (requires login)
   - **Favorite Recipes**: Click the "Favorite" button on any recipe
   - **Rate Recipes**: Click on stars to rate a recipe (1-5 stars)
   - **View Favorites**: Visit the favorites page to see all your favorited recipes

### Shortcodes

- `[rc_recipes]` - Display recipes grid with search and filters
  - Parameters: `per_page`, `category`, `show_search`
  - Example: `[rc_recipes per_page="12" show_search="true"]`

- `[rc_recipe id="1"]` - Display single recipe detail
  - Example: `[rc_recipe id="5"]`

- `[rc_register]` - Display registration form

- `[rc_login]` - Display login form

- `[rc_favorites]` - Display user's favorite recipes (requires login)

## REST API Endpoints

All endpoints are prefixed with: `/wp-json/recipe-collector/v1/`

### Public Endpoints (No Authentication Required)

- `GET /recipes` - Get all recipes
  - Query parameters: `per_page`, `page`, `category`, `search`, `orderby`, `order`
  - Example: `/wp-json/recipe-collector/v1/recipes?per_page=10&page=1`

- `GET /recipes/{id}` - Get single recipe
  - Example: `/wp-json/recipe-collector/v1/recipes/5`

### Authenticated Endpoints (Requires Login)

- `POST /recipes/{id}/favorite` - Add recipe to favorites
- `DELETE /recipes/{id}/favorite` - Remove recipe from favorites
- `POST /recipes/{id}/rating` - Rate a recipe (body: `{"rating": 5}`)
- `GET /favorites` - Get user's favorite recipes

### Admin Endpoints (Requires Admin Permission)

- `POST /recipes` - Create new recipe
- `PUT /recipes/{id}` - Update recipe
- `DELETE /recipes/{id}` - Delete recipe

## Database Structure

The plugin creates three custom tables:

1. **wp_rc_recipes** - Stores recipe data
2. **wp_rc_favorites** - Stores user favorite recipes
3. **wp_rc_ratings** - Stores recipe ratings

## Technology Stack

- **Backend**: PHP (WordPress)
- **Database**: MySQL/MariaDB (via WordPress)
- **Frontend**: HTML, CSS, JavaScript (jQuery)
- **API**: WordPress REST API

## File Structure

```
recipe-collector/
├── recipe-collector.php          # Main plugin file
├── includes/
│   ├── class-rc-database.php     # Database operations
│   ├── class-rc-api.php          # REST API endpoints
│   ├── class-rc-admin.php        # Admin interface
│   ├── class-rc-frontend.php     # Frontend shortcodes
│   └── class-rc-auth.php         # Authentication
├── assets/
│   ├── css/
│   │   ├── style.css             # Frontend styles
│   │   └── admin.css             # Admin styles
│   └── js/
│       ├── script.js             # Frontend JavaScript
│       └── admin.js               # Admin JavaScript
└── README.md                      # This file
```

## Development Decisions

### Why WordPress?
- WordPress provides a solid foundation with built-in user management
- Easy to extend with custom functionality
- Large community and extensive documentation

### Why Custom Database Tables?
- Better performance for recipe-specific data
- Allows for complex queries and relationships
- Maintains data integrity with proper schema

### Why REST API?
- Enables future mobile app development
- Allows integration with other systems
- Follows modern web development practices
- Provides flexibility for frontend frameworks

### Why jQuery?
- Lightweight and widely supported
- Easy AJAX handling
- Good compatibility with WordPress
- Simple DOM manipulation

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Security Features

- Nonce verification for all AJAX requests
- Capability checks for admin functions
- SQL injection prevention using prepared statements
- XSS prevention through WordPress sanitization functions
- CSRF protection via WordPress nonces

## Support

For issues or questions, please contact the plugin author or create an issue on GitHub.

## License

GPL v2 or later

## Author

Your Name  
GitHub: https://github.com/yourusername

## Version

1.0.0

