# Installation Guide - Recipe Collector Plugin

## Step-by-Step Installation

### 1. Upload Plugin to WordPress

**Option A: Via FTP/SFTP**
1. Connect to your WordPress server via FTP/SFTP
2. Navigate to `wp-content/plugins/` directory
3. Upload the entire `recipe-collector` folder
4. Ensure the folder structure is: `wp-content/plugins/recipe-collector/`

**Option B: Via WordPress Admin**
1. Zip the `recipe-collector` folder
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the zip file and click "Install Now"
5. Click "Activate Plugin"

### 2. Activate the Plugin

1. Go to WordPress Admin → Plugins
2. Find "Recipe Collector" in the list
3. Click "Activate"
4. The plugin will automatically create database tables on activation

### 3. Create Required Pages

Create the following pages in WordPress:

#### Page 1: Recipes Listing
- **Page Title**: Recipes
- **Page Slug**: recipes (or your preferred slug)
- **Content**: Add shortcode `[rc_recipes]`
- **Publish** the page

#### Page 2: Registration
- **Page Title**: Register
- **Page Slug**: register
- **Content**: Add shortcode `[rc_register]`
- **Publish** the page

#### Page 3: Login
- **Page Title**: Login
- **Page Slug**: login
- **Content**: Add shortcode `[rc_login]`
- **Publish** the page

#### Page 4: Favorites (Optional)
- **Page Title**: My Favorites
- **Page Slug**: favorites
- **Content**: Add shortcode `[rc_favorites]`
- **Publish** the page

### 4. Configure Permalinks

1. Go to WordPress Admin → Settings → Permalinks
2. Select "Post name" or any structure you prefer
3. Click "Save Changes"
4. This ensures REST API endpoints work correctly

### 5. Test the Installation

1. **Test Admin Interface**
   - Go to WordPress Admin → Recipe Collector
   - Click "Add New Recipe"
   - Fill in a test recipe and save
   - Verify it appears in "All Recipes"

2. **Test Frontend**
   - Visit your Recipes page
   - Verify recipes are displayed
   - Test search and filter functionality

3. **Test Registration**
   - Visit your Register page
   - Create a test account
   - Verify you can log in

4. **Test User Features**
   - Log in as a regular user
   - View a recipe
   - Try favoriting a recipe
   - Try rating a recipe
   - Check your favorites page

### 6. Verify Database Tables

You can verify the tables were created by checking your database:

```sql
SHOW TABLES LIKE 'wp_rc_%';
```

You should see:
- `wp_rc_recipes`
- `wp_rc_favorites`
- `wp_rc_ratings`

(Note: `wp_` may be different if you changed your WordPress table prefix)

## Troubleshooting

### Plugin Not Appearing
- Check that the plugin folder is in `wp-content/plugins/`
- Verify file permissions (folders: 755, files: 644)
- Check WordPress error logs

### Database Tables Not Created
- Deactivate and reactivate the plugin
- Check database user permissions
- Review WordPress debug logs

### Shortcodes Not Working
- Ensure pages are published (not draft)
- Check that shortcodes are correctly added
- Clear any caching plugins

### REST API Not Working
- Go to Settings → Permalinks and click "Save Changes"
- Check that REST API is enabled: Visit `/wp-json/`
- Verify .htaccess file is writable

### JavaScript Not Loading
- Check browser console for errors
- Verify jQuery is loaded (WordPress includes it by default)
- Clear browser cache

## Next Steps

1. Add some sample recipes via the admin panel
2. Customize the styling in `assets/css/style.css` if needed
3. Configure user roles and permissions as needed
4. Set up email notifications (optional)

## Support

If you encounter any issues during installation, please:
1. Check WordPress debug logs
2. Verify all requirements are met
3. Contact support with error messages

