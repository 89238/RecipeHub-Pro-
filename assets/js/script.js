/**
 * Recipe Collector Frontend JavaScript
 */

(function($) {
    'use strict';
    
    let currentPage = 1;
    let currentCategory = '';
    let currentSearch = '';
    let currentSort = 'created_at:DESC';
    
    $(document).ready(function() {
        // Initialize recipes list
        if ($('.rc-recipes-container').length) {
            loadRecipes();
            setupEventListeners();
        }
        
        // Initialize recipe detail
        if ($('.rc-recipe-detail').length) {
            const recipeId = $('.rc-recipe-detail').data('recipe-id');
            if (recipeId > 0) {
                loadRecipeDetail(recipeId);
            }
        }
        
        // Initialize favorites
        if ($('.rc-favorites-container').length) {
            loadFavorites();
        }
        
        // Initialize my recipes
        if ($('.rc-my-recipes-container').length) {
            loadMyRecipes();
            setupMyRecipesListeners();
        }
        
        // Initialize submit recipe form
        if ($('#rc-submit-recipe-form').length) {
            $('#rc-submit-recipe-form').on('submit', function(e) {
                e.preventDefault();
                handleSubmitRecipe();
            });
        }
        
        // Registration form
        $('#rc-register-form').on('submit', function(e) {
            e.preventDefault();
            handleRegister();
        });
        
        // Login form
        $('#rc-login-form').on('submit', function(e) {
            e.preventDefault();
            handleLogin();
        });
    });
    
    function setupEventListeners() {
        // Search
        $('#rc-search-button').on('click', function() {
            currentSearch = $('#rc-frontend-search').val();
            currentPage = 1;
            loadRecipes();
        });
        
        $('#rc-frontend-search').on('keypress', function(e) {
            if (e.which === 13) {
                currentSearch = $(this).val();
                currentPage = 1;
                loadRecipes();
            }
        });
        
        // Category filter
        $('#rc-category-filter').on('change', function() {
            currentCategory = $(this).val();
            currentPage = 1;
            loadRecipes();
        });
        
        // Sort filter
        $('#rc-sort-filter').on('change', function() {
            currentSort = $(this).val();
            currentPage = 1;
            loadRecipes();
        });
    }
    
    function loadRecipes() {
        const $grid = $('#rc-recipes-grid');
        const $loading = $('#rc-loading');
        
        $grid.html('');
        $loading.show();
        
        const sortParts = currentSort.split(':');
        const params = {
            per_page: 12,
            page: currentPage,
            search: currentSearch,
            category: currentCategory,
            orderby: sortParts[0],
            order: sortParts[1]
        };
        
        $.ajax({
            url: rcAjax.rest_url + 'recipes',
            method: 'GET',
            data: params,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', rcAjax.rest_nonce);
            },
            success: function(response) {
                $loading.hide();
                
                if (response.length === 0) {
                    $grid.html('<div class="rc-empty-state"><p>No recipes found.</p></div>');
                    return;
                }
                
                response.forEach(function(recipe) {
                    $grid.append(createRecipeCard(recipe));
                });
                
                // Setup favorite buttons
                $('.rc-btn-favorite').on('click', function(e) {
                    e.stopPropagation();
                    const recipeId = $(this).data('recipe-id');
                    toggleFavorite(recipeId, $(this));
                });
                
                // Setup card clicks
                $('.rc-recipe-card').on('click', function() {
                    const recipeId = $(this).data('recipe-id');
                    window.location.href = '?recipe_id=' + recipeId;
                });
            },
            error: function() {
                $loading.hide();
                $grid.html('<div class="rc-empty-state"><p>Error loading recipes. Please try again.</p></div>');
            }
        });
    }
    
    
    function loadRecipeDetail(recipeId) {
        const $content = $('#rc-recipe-content');
        $content.html('<div class="rc-loading"><p>Loading recipe...</p></div>');
        
        $.ajax({
            url: rcAjax.rest_url + 'recipes/' + recipeId,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', rcAjax.rest_nonce);
            },
            success: function(recipe) {
                $content.html(createRecipeDetail(recipe));
                
                // Setup favorite button
                $('.rc-btn-favorite').on('click', function() {
                    const id = $(this).data('recipe-id');
                    toggleFavorite(id, $(this));
                });
                
                // Setup rating
                $('.rc-rating-star').on('click', function() {
                    const rating = $(this).data('rating');
                    addRating(recipeId, rating);
                });
            },
            error: function() {
                $content.html('<div class="rc-empty-state"><p>Recipe not found.</p></div>');
            }
        });
    }
    
    function createRecipeDetail(recipe) {
        const imageHtml = recipe.image_url 
            ? `<img src="${recipe.image_url}" alt="${recipe.title}" class="rc-recipe-header-image">`
            : '';
        
        const favoriteClass = recipe.is_favorited ? 'active' : '';
        const favoriteText = recipe.is_favorited ? '❤️ Favorited' : '🤍 Add to Favorites';
        
        const ingredients = recipe.ingredients.split('\n').filter(i => i.trim());
        const instructions = recipe.instructions.split('\n').filter(i => i.trim());
        
        let ingredientsHtml = '<ul>';
        ingredients.forEach(function(ing) {
            ingredientsHtml += `<li>${ing.trim()}</li>`;
        });
        ingredientsHtml += '</ul>';
        
        let instructionsHtml = '<ol>';
        instructions.forEach(function(inst, index) {
            instructionsHtml += `<li>${inst.trim()}</li>`;
        });
        instructionsHtml += '</ol>';
        
        return `
            <div class="rc-recipe-header">
                ${imageHtml}
                <h1>${recipe.title}</h1>
                <div class="rc-recipe-header-meta">
                    <span>⏱️ Prep: ${recipe.prep_time} min | Cook: ${recipe.cook_time} min</span>
                    <span>👥 ${recipe.servings} servings</span>
                    <span>📊 ${recipe.difficulty}</span>
                    ${recipe.category ? `<span>📁 ${recipe.category}</span>` : ''}
                    ${recipe.rating > 0 ? `<span>⭐ ${recipe.rating.toFixed(1)}</span>` : ''}
                </div>
                ${isUserLoggedIn() ? `
                    <div class="rc-recipe-actions">
                        <button class="rc-btn rc-btn-favorite ${favoriteClass}" data-recipe-id="${recipe.id}">${favoriteText}</button>
                    </div>
                    <div class="rc-rating">
                        <span>Rate this recipe:</span>
                        <div class="rc-rating-stars">
                            ${[1,2,3,4,5].map(i => `<span class="rc-rating-star" data-rating="${i}">⭐</span>`).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
            ${recipe.description ? `<div class="rc-recipe-section"><p>${recipe.description}</p></div>` : ''}
            <div class="rc-recipe-section">
                <h2>Ingredients</h2>
                ${ingredientsHtml}
            </div>
            <div class="rc-recipe-section">
                <h2>Instructions</h2>
                ${instructionsHtml}
            </div>
        `;
    }
    
    function toggleFavorite(recipeId, $button) {
        if (!isUserLoggedIn()) {
            alert('Please login to favorite recipes.');
            return;
        }
        
        const isFavorited = $button.hasClass('active');
        const method = isFavorited ? 'DELETE' : 'POST';
        
        $.ajax({
            url: rcAjax.rest_url + 'recipes/' + recipeId + '/favorite',
            method: method,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', rcAjax.rest_nonce);
            },
            success: function() {
                $button.toggleClass('active');
                $button.text(isFavorited ? '🤍 Favorite' : '❤️ Favorited');
            },
            error: function() {
                alert('Error updating favorite. Please try again.');
            }
        });
    }
    
    function addRating(recipeId, rating) {
        if (!isUserLoggedIn()) {
            alert('Please login to rate recipes.');
            return;
        }
        
        $.ajax({
            url: rcAjax.rest_url + 'recipes/' + recipeId + '/rating',
            method: 'POST',
            data: JSON.stringify({ rating: rating }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', rcAjax.rest_nonce);
            },
            success: function() {
                alert('Rating submitted!');
                loadRecipeDetail(recipeId);
            },
            error: function() {
                alert('Error submitting rating. Please try again.');
            }
        });
    }
    
    function loadFavorites() {
        const $grid = $('#rc-favorites-grid');
        const $empty = $('#rc-favorites-empty');
        
        $grid.html('');
        
        $.ajax({
            url: rcAjax.rest_url + 'favorites',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', rcAjax.rest_nonce);
            },
            success: function(response) {
                if (response.length === 0) {
                    $empty.show();
                    return;
                }
                
                $empty.hide();
                response.forEach(function(recipe) {
                    $grid.append(createRecipeCard(recipe));
                });
            },
            error: function() {
                $empty.show();
            }
        });
    }
    
    function handleRegister() {
        const $form = $('#rc-register-form');
        const $message = $form.find('.rc-form-message');
        
        $.ajax({
            url: rcAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'rc_register',
                nonce: rcAjax.nonce,
                username: $('#rc-username').val(),
                email: $('#rc-email').val(),
                password: $('#rc-password').val(),
                confirm_password: $('#rc-confirm-password').val()
            },
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
    
    function handleLogin() {
        const $form = $('#rc-login-form');
        const $message = $form.find('.rc-form-message');
        
        $.ajax({
            url: rcAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'rc_login',
                nonce: rcAjax.nonce,
                username: $('#rc-login-username').val(),
                password: $('#rc-login-password').val(),
                remember: $('#rc-login-form input[name="remember"]').is(':checked') ? 1 : 0
            },
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
    
    function isUserLoggedIn() {
        // Check if user is logged in (you may need to adjust this based on your setup)
        return typeof rcAjax !== 'undefined' && rcAjax.user_id && rcAjax.user_id > 0;
    }
    
    function loadMyRecipes(status = 'all') {
        const $grid = $('#rc-my-recipes-grid');
        const $empty = $('#rc-my-recipes-empty');
        const $loading = $('#rc-my-recipes-loading');
        
        $grid.html('');
        $loading.show();
        $empty.hide();
        
        $.ajax({
            url: rcAjax.rest_url + 'my-recipes',
            method: 'GET',
            data: {
                per_page: 20,
                page: 1,
                status: status
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', rcAjax.rest_nonce);
            },
            success: function(response) {
                $loading.hide();
                
                if (response.length === 0) {
                    $empty.show();
                    return;
                }
                
                $empty.hide();
                response.forEach(function(recipe) {
                    $grid.append(createRecipeCard(recipe, true));
                });
                
                // Setup favorite buttons
                $('.rc-btn-favorite').on('click', function(e) {
                    e.stopPropagation();
                    const recipeId = $(this).data('recipe-id');
                    toggleFavorite(recipeId, $(this));
                });
            },
            error: function() {
                $loading.hide();
                $empty.show();
            }
        });
    }
    
    function setupMyRecipesListeners() {
        $('.rc-status-tab').on('click', function() {
            $('.rc-status-tab').removeClass('active');
            $(this).addClass('active');
            const status = $(this).data('status');
            loadMyRecipes(status);
        });
    }
    
    function createRecipeCard(recipe, showStatus = false) {
        const imageHtml = recipe.image_url 
            ? `<img src="${recipe.image_url}" alt="${recipe.title}" class="rc-recipe-image">`
            : '<div class="rc-recipe-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">No Image</div>';
        
        const favoriteClass = recipe.is_favorited ? 'active' : '';
        const favoriteText = recipe.is_favorited ? '❤️ Favorited' : '🤍 Favorite';
        
        let statusBadge = '';
        if (showStatus && recipe.status) {
            if (recipe.status === 'pending') {
                statusBadge = '<span class="rc-status-badge rc-status-pending">Pending</span>';
            } else if (recipe.status === 'published') {
                statusBadge = '<span class="rc-status-badge rc-status-published">Published</span>';
            } else if (recipe.status === 'draft') {
                statusBadge = '<span class="rc-status-badge rc-status-draft">Draft</span>';
            }
        }
        
        return `
            <div class="rc-recipe-card" data-recipe-id="${recipe.id}">
                ${imageHtml}
                <div class="rc-recipe-content">
                    <h3 class="rc-recipe-title">
                        ${recipe.title}
                        ${statusBadge}
                    </h3>
                    <p class="rc-recipe-description">${recipe.description || ''}</p>
                    <div class="rc-recipe-meta">
                        <span>⏱️ ${recipe.prep_time + recipe.cook_time} min</span>
                        <span>👥 ${recipe.servings} servings</span>
                        <span>📊 ${recipe.difficulty}</span>
                    </div>
                    ${recipe.rating > 0 ? `<div class="rc-rating-value">⭐ ${recipe.rating.toFixed(1)}</div>` : ''}
                    <div class="rc-recipe-actions">
                        <button class="rc-btn rc-btn-primary">View Recipe</button>
                        ${isUserLoggedIn() ? `<button class="rc-btn rc-btn-favorite ${favoriteClass}" data-recipe-id="${recipe.id}">${favoriteText}</button>` : ''}
                    </div>
                </div>
            </div>
        `;
    }
    
    function handleSubmitRecipe() {
        const $form = $('#rc-submit-recipe-form');
        const $message = $('#rc-submit-message');
        const $submitBtn = $form.find('button[type="submit"]');
        
        // Disable submit button
        $submitBtn.prop('disabled', true).text('Submitting...');
        $message.hide();
        
        $.ajax({
            url: rcAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'rc_submit_recipe',
                nonce: rcAjax.nonce,
                title: $('#rc-submit-title').val(),
                description: $('#rc-submit-description').val(),
                ingredients: $('#rc-submit-ingredients').val(),
                instructions: $('#rc-submit-instructions').val(),
                prep_time: $('#rc-submit-prep-time').val(),
                cook_time: $('#rc-submit-cook-time').val(),
                servings: $('#rc-submit-servings').val(),
                difficulty: $('#rc-submit-difficulty').val(),
                category: $('#rc-submit-category').val(),
                image_url: $('#rc-submit-image-url').val()
            },
            success: function(response) {
                if (response.success) {
                    $message.removeClass('error').addClass('success').text(response.data.message).show();
                    $form[0].reset();
                    
                    // Redirect to my recipes page after 2 seconds
                    setTimeout(function() {
                        // Try to find my recipes page URL, or redirect to recipes page
                        const myRecipesUrl = window.location.href.split('?')[0] + '?view=my-recipes';
                        window.location.href = myRecipesUrl;
                    }, 2000);
                } else {
                    $message.removeClass('success').addClass('error').text(response.data.message).show();
                    $submitBtn.prop('disabled', false).text('Submit Recipe');
                }
            },
            error: function() {
                $message.removeClass('success').addClass('error').text('An error occurred. Please try again.').show();
                $submitBtn.prop('disabled', false).text('Submit Recipe');
            }
        });
    }
    
})(jQuery);

