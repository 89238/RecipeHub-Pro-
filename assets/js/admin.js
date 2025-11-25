/**
 * Recipe Collector Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Load recipes list on admin page
        if ($('#rc-recipes-list').length) {
            loadRecipesList();
        }
        
        // Recipe form submission
        $('#rc-recipe-form').on('submit', function(e) {
            e.preventDefault();
            saveRecipe();
        });
        
        // Search functionality
        $('#rc-search-btn').on('click', function() {
            loadRecipesList();
        });
        
        $('#rc-search-input').on('keypress', function(e) {
            if (e.which === 13) {
                loadRecipesList();
            }
        });
        
        // Status filter
        $('#rc-status-filter').on('change', function() {
            loadRecipesList();
        });
    });
    
    function loadRecipesList() {
        const $list = $('#rc-recipes-list');
        const search = $('#rc-search-input').val();
        const status = $('#rc-status-filter').val() || 'all';
        
        $list.html('<p>Loading recipes...</p>');
        
        const params = {
            per_page: 50,
            page: 1,
            status: status
        };
        
        if (search) {
            params.search = search;
        }
        
        $.ajax({
            url: rcAdminAjax.rest_url + 'recipes',
            method: 'GET',
            data: params,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', rcAdminAjax.rest_nonce);
            },
            success: function(response) {
                if (response.length === 0) {
                    $list.html('<p>No recipes found.</p>');
                    return;
                }
                
                let html = '';
                response.forEach(function(recipe) {
                    html += createRecipeListItem(recipe);
                });
                $list.html(html);
                
                // Setup delete buttons
                $('.rc-delete-recipe').on('click', function() {
                    if (confirm('Are you sure you want to delete this recipe?')) {
                        deleteRecipe($(this).data('recipe-id'));
                    }
                });
            },
            error: function() {
                $list.html('<p>Error loading recipes.</p>');
            }
        });
    }
    
    function createRecipeListItem(recipe) {
        const imageHtml = recipe.image_url 
            ? `<img src="${recipe.image_url}" alt="${recipe.title}" class="rc-recipe-item-image">`
            : '<div class="rc-recipe-item-image" style="background: #ddd; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;">No Image</div>';
        
        // Status badge
        let statusBadge = '';
        if (recipe.status === 'pending') {
            statusBadge = '<span class="rc-status-badge rc-status-pending">Pending Approval</span>';
        } else if (recipe.status === 'published') {
            statusBadge = '<span class="rc-status-badge rc-status-published">Published</span>';
        } else if (recipe.status === 'draft') {
            statusBadge = '<span class="rc-status-badge rc-status-draft">Draft</span>';
        }
        
        return `
            <div class="rc-recipe-item">
                ${imageHtml}
                <div class="rc-recipe-item-content">
                    <div class="rc-recipe-item-title">
                        ${recipe.title}
                        ${statusBadge}
                    </div>
                    <div class="rc-recipe-item-meta">
                        Category: ${recipe.category || 'N/A'} | 
                        Difficulty: ${recipe.difficulty} | 
                        Created: ${recipe.created_at}
                    </div>
                </div>
                <div class="rc-recipe-item-actions">
                    <a href="?page=recipe-collector-add&edit=${recipe.id}" class="button">Edit</a>
                    <button class="button rc-delete-recipe" data-recipe-id="${recipe.id}">Delete</button>
                </div>
            </div>
        `;
    }
    
    function saveRecipe() {
        const $form = $('#rc-recipe-form');
        const $message = $('#rc-form-message');
        const recipeId = $('#rc-recipe-id').val();
        
        const data = {
            action: 'rc_admin_save_recipe',
            nonce: rcAdminAjax.nonce,
            recipe_id: recipeId,
            title: $('#rc-title').val(),
            description: $('#rc-description').val(),
            ingredients: $('#rc-ingredients').val(),
            instructions: $('#rc-instructions').val(),
            prep_time: $('#rc-prep-time').val(),
            cook_time: $('#rc-cook-time').val(),
            servings: $('#rc-servings').val(),
            difficulty: $('#rc-difficulty').val(),
            category: $('#rc-category').val(),
            image_url: $('#rc-image-url').val()
        };
        
        if ($('#rc-status').length) {
            data.status = $('#rc-status').val();
        }
        
        $.ajax({
            url: rcAdminAjax.ajaxurl,
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $message.removeClass('error').addClass('success').text(response.data.message).show();
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1500);
                } else {
                    $message.removeClass('success').addClass('error').text(response.data.message).show();
                }
            },
            error: function() {
                $message.removeClass('success').addClass('error').text('An error occurred. Please try again.').show();
            }
        });
    }
    
    function deleteRecipe(recipeId) {
        $.ajax({
            url: rcAdminAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'rc_admin_delete_recipe',
                nonce: rcAdminAjax.nonce,
                recipe_id: recipeId
            },
            success: function(response) {
                if (response.success) {
                    loadRecipesList();
                } else {
                    alert('Error deleting recipe: ' + response.data.message);
                }
            },
            error: function() {
                alert('An error occurred while deleting the recipe.');
            }
        });
    }
    
})(jQuery);

