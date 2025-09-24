/**
 * Laravel E-commerce Store - Main JavaScript
 * Modern ES6+ JavaScript with no dependencies
 */

class StoreApp {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeComponents();
        this.setupAjax();
        this.setupNotifications();
    }

    /**
     * Bind global event listeners
     */
    bindEvents() {
        // DOM Content Loaded
        document.addEventListener('DOMContentLoaded', () => {
            this.onDOMReady();
        });

        // Product interactions
        this.bindProductEvents();

        // Cart interactions
        this.bindCartEvents();

        // Form interactions
        this.bindFormEvents();

        // Modal interactions
        this.bindModalEvents();

        // Search functionality
        this.bindSearchEvents();

        // Filter functionality
        this.bindFilterEvents();

        // Review functionality
        this.bindReviewEvents();

        // Wishlist functionality
        this.bindWishlistEvents();

        // Comparison functionality
        this.bindComparisonEvents();
    }

    /**
     * Initialize components when DOM is ready
     */
    onDOMReady() {
        this.initializeTooltips();
        this.initializePopovers();
        this.initializeDropdowns();
        this.initializeTabs();
        this.initializeAlerts();
        this.initializeModals();
        this.initializeCarousels();
        this.initializeQuantitySelectors();
        this.initializeImageGalleries();
        this.initializeStarRatings();
        this.initializeProgressBars();
        this.initializeLazyLoading();
    }

    /**
     * Setup AJAX configuration
     */
    setupAjax() {
        // Set up CSRF token for all AJAX requests
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            window.Store = window.Store || {};
            window.Store.csrfToken = token.getAttribute('content');
        }

        // Configure axios if available
        if (typeof axios !== 'undefined') {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = window.Store.csrfToken;
            axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        }
    }

    /**
     * Setup notification system
     */
    setupNotifications() {
        window.Store = window.Store || {};
        window.Store.notifications = {
            show: (message, type = 'info', duration = 5000) => {
                this.showNotification(message, type, duration);
            },
            success: (message, duration) => {
                this.showNotification(message, 'success', duration);
            },
            error: (message, duration) => {
                this.showNotification(message, 'danger', duration);
            },
            warning: (message, duration) => {
                this.showNotification(message, 'warning', duration);
            },
            info: (message, duration) => {
                this.showNotification(message, 'info', duration);
            }
        };
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info', duration = 5000) {
        const notification = this.createNotificationElement(message, type);
        document.body.appendChild(notification);

        // Trigger animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);

        // Auto hide
        if (duration > 0) {
            setTimeout(() => {
                this.hideNotification(notification);
            }, duration);
        }

        return notification;
    }

    /**
     * Create notification element
     */
    createNotificationElement(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade`;
        notification.setAttribute('role', 'alert');
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        // Add styles
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            zIndex: '9999',
            minWidth: '300px',
            maxWidth: '500px',
            transform: 'translateX(100%)',
            transition: 'transform 0.3s ease-in-out'
        });

        // Bind close event
        const closeBtn = notification.querySelector('.btn-close');
        closeBtn.addEventListener('click', () => {
            this.hideNotification(notification);
        });

        return notification;
    }

    /**
     * Hide notification
     */
    hideNotification(notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }

    /**
     * Bind product-related events
     */
    bindProductEvents() {
        // Product image gallery
        document.addEventListener('click', (e) => {
            if (e.target.matches('.product-gallery-item') || e.target.closest('.product-gallery-item')) {
                e.preventDefault();
                const item = e.target.closest('.product-gallery-item');
                this.switchProductImage(item);
            }

            // Add to cart
            if (e.target.matches('.btn-add-to-cart') || e.target.closest('.btn-add-to-cart')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-add-to-cart');
                this.addToCart(btn);
            }

            // Add to wishlist
            if (e.target.matches('.btn-add-to-wishlist') || e.target.closest('.btn-add-to-wishlist')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-add-to-wishlist');
                this.addToWishlist(btn);
            }

            // Quick view
            if (e.target.matches('.btn-quick-view') || e.target.closest('.btn-quick-view')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-quick-view');
                this.quickView(btn);
            }
        });

        // Product variant selection
        document.addEventListener('change', (e) => {
            if (e.target.matches('.product-variant-select')) {
                this.onVariantChange(e.target);
            }
        });
    }

    /**
     * Bind cart-related events
     */
    bindCartEvents() {
        document.addEventListener('click', (e) => {
            // Update cart item quantity
            if (e.target.matches('.cart-quantity-btn') || e.target.closest('.cart-quantity-btn')) {
                e.preventDefault();
                const btn = e.target.closest('.cart-quantity-btn');
                this.updateCartQuantity(btn);
            }

            // Remove cart item
            if (e.target.matches('.cart-remove-btn') || e.target.closest('.cart-remove-btn')) {
                e.preventDefault();
                const btn = e.target.closest('.cart-remove-btn');
                this.removeCartItem(btn);
            }

            // Apply coupon
            if (e.target.matches('.btn-apply-coupon') || e.target.closest('.btn-apply-coupon')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-apply-coupon');
                this.applyCoupon(btn);
            }

            // Update shipping method
            if (e.target.matches('.shipping-method-select')) {
                this.onShippingMethodChange(e.target);
            }
        });

        // Cart quantity input changes
        document.addEventListener('change', (e) => {
            if (e.target.matches('.cart-quantity-input')) {
                this.onCartQuantityChange(e.target);
            }
        });
    }

    /**
     * Bind form-related events
     */
    bindFormEvents() {
        document.addEventListener('submit', (e) => {
            // Contact form
            if (e.target.matches('.contact-form')) {
                e.preventDefault();
                this.submitContactForm(e.target);
            }

            // Newsletter form
            if (e.target.matches('.newsletter-form')) {
                e.preventDefault();
                this.submitNewsletterForm(e.target);
            }

            // Review form
            if (e.target.matches('.review-form')) {
                e.preventDefault();
                this.submitReviewForm(e.target);
            }
        });

        // Form validation
        document.addEventListener('blur', (e) => {
            if (e.target.matches('.form-control[required]')) {
                this.validateField(e.target);
            }
        }, true);

        document.addEventListener('input', (e) => {
            if (e.target.matches('.form-control[required]')) {
                this.clearFieldError(e.target);
            }
        });
    }

    /**
     * Bind modal-related events
     */
    bindModalEvents() {
        document.addEventListener('click', (e) => {
            // Open modal
            if (e.target.matches('[data-bs-toggle="modal"]') || e.target.closest('[data-bs-toggle="modal"]')) {
                e.preventDefault();
                const trigger = e.target.closest('[data-bs-toggle="modal"]');
                const targetId = trigger.getAttribute('data-bs-target');
                this.openModal(targetId);
            }

            // Close modal
            if (e.target.matches('.btn-close-modal') || e.target.closest('.btn-close-modal')) {
                e.preventDefault();
                const modal = e.target.closest('.modal');
                this.closeModal(modal);
            }

            // Close modal on backdrop click
            if (e.target.matches('.modal')) {
                this.closeModal(e.target);
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modal = document.querySelector('.modal.show');
                if (modal) {
                    this.closeModal(modal);
                }
            }
        });
    }

    /**
     * Bind search-related events
     */
    bindSearchEvents() {
        let searchTimeout;

        document.addEventListener('input', (e) => {
            if (e.target.matches('.search-input')) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performSearch(e.target.value);
                }, 300);
            }
        });

        document.addEventListener('click', (e) => {
            if (e.target.matches('.search-suggestion')) {
                e.preventDefault();
                const suggestion = e.target;
                this.selectSearchSuggestion(suggestion);
            }
        });
    }

    /**
     * Bind filter-related events
     */
    bindFilterEvents() {
        document.addEventListener('change', (e) => {
            if (e.target.matches('.filter-checkbox') || e.target.matches('.filter-radio')) {
                this.applyFilters();
            }

            if (e.target.matches('.sort-select')) {
                this.applySorting(e.target.value);
            }
        });

        document.addEventListener('click', (e) => {
            if (e.target.matches('.filter-clear')) {
                e.preventDefault();
                this.clearFilters();
            }
        });
    }

    /**
     * Bind review-related events
     */
    bindReviewEvents() {
        document.addEventListener('click', (e) => {
            // Rate product
            if (e.target.matches('.star-rating .star')) {
                const star = e.target;
                const rating = parseInt(star.getAttribute('data-rating'));
                this.rateProduct(star.closest('.star-rating'), rating);
            }

            // Mark review as helpful
            if (e.target.matches('.review-helpful-btn')) {
                e.preventDefault();
                const btn = e.target;
                this.markReviewHelpful(btn);
            }
        });
    }

    /**
     * Bind wishlist-related events
     */
    bindWishlistEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('.wishlist-remove-btn') || e.target.closest('.wishlist-remove-btn')) {
                e.preventDefault();
                const btn = e.target.closest('.wishlist-remove-btn');
                this.removeFromWishlist(btn);
            }
        });
    }

    /**
     * Bind comparison-related events
     */
    bindComparisonEvents() {
        document.addEventListener('click', (e) => {
            // Add to comparison
            if (e.target.matches('.btn-add-to-comparison') || e.target.closest('.btn-add-to-comparison')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-add-to-comparison');
                this.addToComparison(btn);
            }

            // Remove from comparison
            if (e.target.matches('.comparison-remove-btn') || e.target.closest('.comparison-remove-btn')) {
                e.preventDefault();
                const btn = e.target.closest('.comparison-remove-btn');
                this.removeFromComparison(btn);
            }

            // Toggle comparison
            if (e.target.matches('.btn-toggle-comparison') || e.target.closest('.btn-toggle-comparison')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-toggle-comparison');
                this.toggleComparison(btn);
            }

            // Clear comparison
            if (e.target.matches('.btn-clear-comparison') || e.target.closest('.btn-clear-comparison')) {
                e.preventDefault();
                this.clearComparison();
            }

            // Share comparison
            if (e.target.matches('.btn-share-comparison') || e.target.closest('.btn-share-comparison')) {
                e.preventDefault();
                this.shareComparison();
            }
        });

        // Update comparison count on page load
        this.updateComparisonCount();
    }

    /**
     * Initialize tooltips
     */
    initializeTooltips() {
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => {
            tooltip.addEventListener('mouseenter', (e) => {
                this.showTooltip(e.target);
            });

            tooltip.addEventListener('mouseleave', (e) => {
                this.hideTooltip(e.target);
            });
        });
    }

    /**
     * Initialize popovers
     */
    initializePopovers() {
        const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
        popovers.forEach(popover => {
            popover.addEventListener('click', (e) => {
                e.preventDefault();
                this.togglePopover(e.target);
            });
        });
    }

    /**
     * Initialize dropdowns
     */
    initializeDropdowns() {
        const dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            const menu = dropdown.querySelector('.dropdown-menu');

            if (toggle && menu) {
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleDropdown(dropdown);
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!dropdown.contains(e.target)) {
                        this.closeDropdown(dropdown);
                    }
                });
            }
        });
    }

    /**
     * Initialize tabs
     */
    initializeTabs() {
        const tabs = document.querySelectorAll('[data-bs-toggle="tab"]');
        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchTab(e.target);
            });
        });
    }

    /**
     * Initialize alerts
     */
    initializeAlerts() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.closeAlert(alert);
                });
            }
        });
    }

    /**
     * Initialize modals
     */
    initializeModals() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            // Focus management
            modal.addEventListener('shown.bs.modal', () => {
                const focusable = modal.querySelector('[autofocus], input, button, [tabindex]:not([tabindex="-1"])');
                if (focusable) {
                    focusable.focus();
                }
            });
        });
    }

    /**
     * Initialize carousels
     */
    initializeCarousels() {
        const carousels = document.querySelectorAll('.carousel');
        carousels.forEach(carousel => {
            this.initializeCarousel(carousel);
        });
    }

    /**
     * Initialize quantity selectors
     */
    initializeQuantitySelectors() {
        const selectors = document.querySelectorAll('.quantity-selector');
        selectors.forEach(selector => {
            const input = selector.querySelector('input');
            const minusBtn = selector.querySelector('.minus');
            const plusBtn = selector.querySelector('.plus');

            if (minusBtn) {
                minusBtn.addEventListener('click', () => {
                    this.changeQuantity(input, -1);
                });
            }

            if (plusBtn) {
                plusBtn.addEventListener('click', () => {
                    this.changeQuantity(input, 1);
                });
            }
        });
    }

    /**
     * Initialize image galleries
     */
    initializeImageGalleries() {
        const galleries = document.querySelectorAll('.image-gallery');
        galleries.forEach(gallery => {
            this.initializeImageGallery(gallery);
        });
    }

    /**
     * Initialize star ratings
     */
    initializeStarRatings() {
        const ratings = document.querySelectorAll('.star-rating');
        ratings.forEach(rating => {
            this.initializeStarRating(rating);
        });
    }

    /**
     * Initialize progress bars
     */
    initializeProgressBars() {
        const progressBars = document.querySelectorAll('.progress-bar');
        progressBars.forEach(bar => {
            this.initializeProgressBar(bar);
        });
    }

    /**
     * Initialize lazy loading
     */
    initializeLazyLoading() {
        if ('IntersectionObserver' in window) {
            const lazyImages = document.querySelectorAll('img[data-src]');
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            lazyImages.forEach(img => imageObserver.observe(img));
        }
    }

    /**
     * Initialize components
     */
    initializeComponents() {
        // Initialize any additional components
        this.initializeCustomComponents();
    }

    /**
     * Initialize custom components
     */
    initializeCustomComponents() {
        // Custom component initialization
    }

    /**
     * Switch product image
     */
    switchProductImage(clickedItem) {
        const gallery = clickedItem.closest('.product-gallery');
        const mainImage = gallery.querySelector('.main-image img');
        const clickedImage = clickedItem.querySelector('img');

        if (mainImage && clickedImage) {
            // Update main image
            mainImage.src = clickedImage.src;
            mainImage.alt = clickedImage.alt;

            // Update active state
            gallery.querySelectorAll('.product-gallery-item').forEach(item => {
                item.classList.remove('active');
            });
            clickedItem.classList.add('active');
        }
    }

    /**
     * Add product to cart
     */
    addToCart(button) {
        const productId = button.dataset.productId;
        const quantity = button.dataset.quantity || 1;
        const variantId = button.dataset.variantId;

        if (!productId) {
            this.showNotification('Produto não encontrado', 'danger');
            return;
        }

        button.disabled = true;
        button.innerHTML = '<span class="spinner"></span> Adicionando...';

        this.makeRequest('/cart/add', 'POST', {
            product_id: productId,
            quantity: quantity,
            variant_id: variantId
        })
        .then(response => {
            if (response.success) {
                this.updateCartDisplay(response.cart);
                this.showNotification('Produto adicionado ao carrinho!', 'success');
            } else {
                this.showNotification(response.message || 'Erro ao adicionar produto', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao adicionar produto ao carrinho', 'danger');
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = 'Adicionar ao Carrinho';
        });
    }

    /**
     * Add product to wishlist
     */
    addToWishlist(button) {
        const productId = button.dataset.productId;

        if (!productId) {
            this.showNotification('Produto não encontrado', 'danger');
            return;
        }

        this.makeRequest('/wishlist/add', 'POST', {
            product_id: productId
        })
        .then(response => {
            if (response.success) {
                button.classList.add('active');
                this.showNotification('Produto adicionado à lista de desejos!', 'success');
            } else {
                this.showNotification(response.message || 'Erro ao adicionar produto', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao adicionar produto à lista de desejos', 'danger');
        });
    }

    /**
     * Quick view product
     */
    quickView(button) {
        const productId = button.dataset.productId;

        if (!productId) {
            this.showNotification('Produto não encontrado', 'danger');
            return;
        }

        this.makeRequest(`/products/${productId}/quick-view`, 'GET')
        .then(response => {
            if (response.success) {
                this.showQuickViewModal(response.product);
            } else {
                this.showNotification('Erro ao carregar produto', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao carregar produto', 'danger');
        });
    }

    /**
     * Update cart quantity
     */
    updateCartQuantity(button) {
        const cartItemId = button.dataset.cartItemId;
        const action = button.dataset.action; // 'increase' or 'decrease'
        const quantity = parseInt(button.dataset.quantity || 1);

        if (!cartItemId) {
            this.showNotification('Item do carrinho não encontrado', 'danger');
            return;
        }

        this.makeRequest('/cart/update-quantity', 'POST', {
            cart_item_id: cartItemId,
            action: action,
            quantity: quantity
        })
        .then(response => {
            if (response.success) {
                this.updateCartDisplay(response.cart);
                this.showNotification('Carrinho atualizado!', 'success');
            } else {
                this.showNotification(response.message || 'Erro ao atualizar carrinho', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao atualizar carrinho', 'danger');
        });
    }

    /**
     * Remove cart item
     */
    removeCartItem(button) {
        const cartItemId = button.dataset.cartItemId;

        if (!cartItemId) {
            this.showNotification('Item do carrinho não encontrado', 'danger');
            return;
        }

        if (!confirm('Tem certeza que deseja remover este item do carrinho?')) {
            return;
        }

        this.makeRequest('/cart/remove-item', 'POST', {
            cart_item_id: cartItemId
        })
        .then(response => {
            if (response.success) {
                this.updateCartDisplay(response.cart);
                this.showNotification('Item removido do carrinho!', 'success');
            } else {
                this.showNotification(response.message || 'Erro ao remover item', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao remover item do carrinho', 'danger');
        });
    }

    /**
     * Apply coupon
     */
    applyCoupon(button) {
        const couponCode = button.closest('.coupon-form').querySelector('input[name="coupon_code"]').value;

        if (!couponCode.trim()) {
            this.showNotification('Por favor, digite um código de cupom', 'warning');
            return;
        }

        button.disabled = true;
        button.innerHTML = '<span class="spinner"></span> Aplicando...';

        this.makeRequest('/cart/apply-coupon', 'POST', {
            coupon_code: couponCode
        })
        .then(response => {
            if (response.success) {
                this.updateCartDisplay(response.cart);
                this.showNotification('Cupom aplicado com sucesso!', 'success');
            } else {
                this.showNotification(response.message || 'Erro ao aplicar cupom', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao aplicar cupom', 'danger');
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = 'Aplicar Cupom';
        });
    }

    /**
     * Update cart display
     */
    updateCartDisplay(cart) {
        // Update cart count
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(el => {
            el.textContent = cart.item_count;
        });

        // Update cart total
        const cartTotalElements = document.querySelectorAll('.cart-total-amount');
        cartTotalElements.forEach(el => {
            el.textContent = this.formatCurrency(cart.total_amount);
        });

        // Update cart items
        const cartItemsContainer = document.querySelector('.cart-items');
        if (cartItemsContainer) {
            this.updateCartItems(cartItemsContainer, cart.items);
        }
    }

    /**
     * Update cart items
     */
    updateCartItems(container, items) {
        // Implementation for updating cart items display
        console.log('Updating cart items:', items);
    }

    /**
     * Perform search
     */
    performSearch(query) {
        if (query.length < 2) {
            this.hideSearchSuggestions();
            return;
        }

        this.makeRequest('/search/suggestions', 'GET', { q: query })
        .then(response => {
            if (response.success) {
                this.showSearchSuggestions(response.suggestions);
            }
        })
        .catch(error => {
            console.error('Search error:', error);
        });
    }

    /**
     * Show search suggestions
     */
    showSearchSuggestions(suggestions) {
        // Implementation for showing search suggestions
        console.log('Search suggestions:', suggestions);
    }

    /**
     * Hide search suggestions
     */
    hideSearchSuggestions() {
        // Implementation for hiding search suggestions
    }

    /**
     * Select search suggestion
     */
    selectSearchSuggestion(suggestion) {
        const query = suggestion.textContent.trim();
        document.querySelector('.search-input').value = query;
        this.hideSearchSuggestions();
        // Redirect to search results
        window.location.href = `/search?q=${encodeURIComponent(query)}`;
    }

    /**
     * Apply filters
     */
    applyFilters() {
        const filters = this.getActiveFilters();
        const url = new URL(window.location);

        // Update URL parameters
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                url.searchParams.set(key, filters[key]);
            } else {
                url.searchParams.delete(key);
            }
        });

        // Reset page
        url.searchParams.set('page', '1');

        // Navigate to filtered results
        window.location.href = url.toString();
    }

    /**
     * Get active filters
     */
    getActiveFilters() {
        const filters = {};
        const checkboxes = document.querySelectorAll('.filter-checkbox:checked');
        const radios = document.querySelectorAll('.filter-radio:checked');

        checkboxes.forEach(cb => {
            if (!filters[cb.name]) {
                filters[cb.name] = [];
            }
            filters[cb.name].push(cb.value);
        });

        radios.forEach(radio => {
            filters[radio.name] = radio.value;
        });

        return filters;
    }

    /**
     * Apply sorting
     */
    applySorting(sortBy) {
        const url = new URL(window.location);
        url.searchParams.set('sort', sortBy);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }

    /**
     * Clear filters
     */
    clearFilters() {
        const checkboxes = document.querySelectorAll('.filter-checkbox:checked');
        checkboxes.forEach(cb => {
            cb.checked = false;
        });

        const url = new URL(window.location);
        url.searchParams.delete('category');
        url.searchParams.delete('brand');
        url.searchParams.delete('price_min');
        url.searchParams.delete('price_max');
        url.searchParams.delete('rating');
        url.searchParams.set('page', '1');

        window.location.href = url.toString();
    }

    /**
     * Rate product
     */
    rateProduct(ratingContainer, rating) {
        if (!this.isAuthenticated()) {
            this.showNotification('Você precisa estar logado para avaliar produtos', 'warning');
            return;
        }

        const productId = ratingContainer.dataset.productId;

        this.makeRequest('/products/rate', 'POST', {
            product_id: productId,
            rating: rating
        })
        .then(response => {
            if (response.success) {
                this.updateStarRating(ratingContainer, rating);
                this.showNotification('Avaliação enviada com sucesso!', 'success');
            } else {
                this.showNotification(response.message || 'Erro ao enviar avaliação', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao enviar avaliação', 'danger');
        });
    }

    /**
     * Mark review as helpful
     */
    markReviewHelpful(button) {
        const reviewId = button.dataset.reviewId;
        const action = button.dataset.action; // 'helpful' or 'not_helpful'

        this.makeRequest('/reviews/mark-helpful', 'POST', {
            review_id: reviewId,
            action: action
        })
        .then(response => {
            if (response.success) {
                this.updateReviewHelpfulCount(button, response.helpful_count, response.not_helpful_count);
                this.showNotification('Obrigado pelo seu feedback!', 'success');
            } else {
                this.showNotification(response.message || 'Erro ao processar feedback', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao processar feedback', 'danger');
        });
    }

    /**
     * Remove from wishlist
     */
    removeFromWishlist(button) {
        const productId = button.dataset.productId;

        if (!confirm('Tem certeza que deseja remover este item da lista de desejos?')) {
            return;
        }

        this.makeRequest('/wishlist/remove', 'POST', {
            product_id: productId
        })
        .then(response => {
            if (response.success) {
                const wishlistItem = button.closest('.wishlist-item');
                wishlistItem.remove();
                this.showNotification('Item removido da lista de desejos!', 'success');
            } else {
                this.showNotification(response.message || 'Erro ao remover item', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao remover item da lista de desejos', 'danger');
        });
    }

    /**
     * Add product to comparison
     */
    addToComparison(button) {
        const productId = button.dataset.productId;

        if (!productId) {
            this.showNotification('Produto não encontrado', 'danger');
            return;
        }

        // Show loading state
        const originalText = button.textContent;
        button.disabled = true;
        button.innerHTML = '<span class="spinner"></span> Adicionando...';

        this.makeRequest(`/comparacao/adicionar/${productId}`, 'POST', {
            notes: button.dataset.notes || ''
        })
        .then(response => {
            if (response.success) {
                this.updateComparisonCount();
                this.updateComparisonButtons(productId, true);
                this.showNotification('Produto adicionado à comparação!', 'success');
            } else {
                this.showNotification(response.message || 'Erro ao adicionar produto', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao adicionar produto à comparação', 'danger');
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }

    /**
     * Remove product from comparison
     */
    removeFromComparison(button) {
        const productId = button.dataset.productId;

        if (!productId) {
            this.showNotification('Produto não encontrado', 'danger');
            return;
        }

        if (!confirm('Tem certeza que deseja remover este produto da comparação?')) {
            return;
        }

        this.makeRequest(`/comparacao/remover/${productId}`, 'POST')
        .then(response => {
            if (response.success) {
                this.updateComparisonCount();
                this.updateComparisonButtons(productId, false);
                this.showNotification('Produto removido da comparação!', 'success');

                // If on comparison page, reload to update display
                if (window.location.pathname.includes('/comparacao')) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            } else {
                this.showNotification(response.message || 'Erro ao remover produto', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao remover produto da comparação', 'danger');
        });
    }

    /**
     * Toggle product in comparison
     */
    toggleComparison(button) {
        const productId = button.dataset.productId;

        if (!productId) {
            this.showNotification('Produto não encontrado', 'danger');
            return;
        }

        const isInComparison = button.classList.contains('active') || button.dataset.inComparison === 'true';

        if (isInComparison) {
            this.removeFromComparison(button);
        } else {
            this.addToComparison(button);
        }
    }

    /**
     * Clear comparison
     */
    clearComparison() {
        if (!confirm('Tem certeza que deseja limpar toda a comparação?')) {
            return;
        }

        this.makeRequest('/comparacao/limpar', 'POST')
        .then(response => {
            if (response.success) {
                this.updateComparisonCount();
                this.updateAllComparisonButtons(false);
                this.showNotification('Comparação limpa com sucesso!', 'success');

                // If on comparison page, redirect to products
                if (window.location.pathname.includes('/comparacao')) {
                    setTimeout(() => {
                        window.location.href = '/produtos';
                    }, 1000);
                }
            } else {
                this.showNotification(response.message || 'Erro ao limpar comparação', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao limpar comparação', 'danger');
        });
    }

    /**
     * Share comparison
     */
    shareComparison() {
        this.makeRequest('/comparacao/compartilhar', 'POST')
        .then(response => {
            if (response.success) {
                this.showShareModal(response.data.share_url);
            } else {
                this.showNotification(response.message || 'Erro ao gerar link de compartilhamento', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao gerar link de compartilhamento', 'danger');
        });
    }

    /**
     * Update comparison count display
     */
    updateComparisonCount() {
        this.makeRequest('/comparacao/produtos', 'GET')
        .then(response => {
            if (response.success) {
                const count = response.meta.count;
                const canAddMore = response.meta.can_add_more;

                // Update count badges
                const countElements = document.querySelectorAll('.comparison-count');
                countElements.forEach(el => {
                    el.textContent = count;
                    el.style.display = count > 0 ? 'inline' : 'none';
                });

                // Update comparison buttons state
                if (count >= 4) {
                    document.querySelectorAll('.btn-add-to-comparison').forEach(btn => {
                        if (!btn.classList.contains('active') && btn.dataset.inComparison !== 'true') {
                            btn.disabled = true;
                            btn.textContent = 'Limite atingido';
                            btn.classList.add('disabled');
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error updating comparison count:', error);
        });
    }

    /**
     * Update comparison buttons for a specific product
     */
    updateComparisonButtons(productId, isInComparison) {
        const buttons = document.querySelectorAll(`[data-product-id="${productId}"]`);
        buttons.forEach(btn => {
            if (btn.classList.contains('btn-add-to-comparison') ||
                btn.classList.contains('btn-toggle-comparison')) {

                btn.classList.toggle('active', isInComparison);
                btn.dataset.inComparison = isInComparison;

                if (btn.classList.contains('btn-toggle-comparison')) {
                    btn.innerHTML = isInComparison ?
                        '<i class="fas fa-chart-bar"></i> Remover da Comparação' :
                        '<i class="far fa-chart-bar"></i> Comparar';
                }
            }
        });
    }

    /**
     * Update all comparison buttons
     */
    updateAllComparisonButtons(isInComparison) {
        document.querySelectorAll('.btn-add-to-comparison, .btn-toggle-comparison').forEach(btn => {
            btn.classList.toggle('active', isInComparison);
            btn.dataset.inComparison = isInComparison;

            if (btn.classList.contains('btn-toggle-comparison')) {
                btn.innerHTML = isInComparison ?
                    '<i class="fas fa-chart-bar"></i> Remover da Comparação' :
                    '<i class="far fa-chart-bar"></i> Comparar';
            }
        });
    }

    /**
     * Show share modal
     */
    showShareModal(shareUrl) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('comparisonShareModal');
        if (!modal) {
            modal = this.createShareModal();
            document.body.appendChild(modal);
        }

        // Update modal content
        const urlInput = modal.querySelector('#comparisonShareUrl');
        if (urlInput) {
            urlInput.value = shareUrl;
        }

        // Show modal
        modal.classList.add('show');
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
    }

    /**
     * Create share modal
     */
    createShareModal() {
        const modal = document.createElement('div');
        modal.id = 'comparisonShareModal';
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Compartilhar Comparação</h5>
                        <button type="button" class="btn-close" onclick="window.StoreApp.closeShareModal()"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Link para compartilhar:</label>
                            <div class="input-group">
                                <input type="text" id="comparisonShareUrl" class="form-control" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="window.StoreApp.copyShareUrl()">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <small>Copie o link acima e compartilhe com outras pessoas para que elas possam ver sua comparação de produtos.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="window.StoreApp.closeShareModal()">Fechar</button>
                    </div>
                </div>
            </div>
        `;

        // Close modal when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeShareModal();
            }
        });

        return modal;
    }

    /**
     * Close share modal
     */
    closeShareModal() {
        const modal = document.getElementById('comparisonShareModal');
        if (modal) {
            modal.classList.remove('show');
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    }

    /**
     * Copy share URL to clipboard
     */
    copyShareUrl() {
        const urlInput = document.getElementById('comparisonShareUrl');
        if (urlInput) {
            urlInput.select();
            urlInput.setSelectionRange(0, 99999);

            try {
                document.execCommand('copy');
                this.showNotification('Link copiado para a área de transferência!', 'success');
            } catch (err) {
                this.showNotification('Erro ao copiar link', 'danger');
            }
        }
    }

    /**
     * Show tooltip
     */
    showTooltip(element) {
        const tooltip = element.querySelector('.tooltip-text') || this.createTooltip(element);
        tooltip.style.visibility = 'visible';
        tooltip.style.opacity = '1';
    }

    /**
     * Hide tooltip
     */
    hideTooltip(element) {
        const tooltip = element.querySelector('.tooltip-text');
        if (tooltip) {
            tooltip.style.visibility = 'hidden';
            tooltip.style.opacity = '0';
        }
    }

    /**
     * Create tooltip
     */
    createTooltip(element) {
        const text = element.getAttribute('data-bs-title') || element.getAttribute('title');
        if (!text) return null;

        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip-text';
        tooltip.textContent = text;
        element.appendChild(tooltip);

        return tooltip;
    }

    /**
     * Toggle popover
     */
    togglePopover(element) {
        const existingPopover = element.querySelector('.popover');
        if (existingPopover) {
            this.hidePopover(element);
        } else {
            this.showPopover(element);
        }
    }

    /**
     * Show popover
     */
    showPopover(element) {
        this.hidePopover(element); // Hide any existing popover first

        const content = element.getAttribute('data-bs-content');
        if (!content) return;

        const popover = document.createElement('div');
        popover.className = 'popover';
        popover.innerHTML = `
            <div class="popover-arrow"></div>
            <div class="popover-body">${content}</div>
        `;

        element.appendChild(popover);

        // Position popover
        this.positionPopover(element, popover);
    }

    /**
     * Hide popover
     */
    hidePopover(element) {
        const popover = element.querySelector('.popover');
        if (popover) {
            popover.remove();
        }
    }

    /**
     * Position popover
     */
    positionPopover(element, popover) {
        const rect = element.getBoundingClientRect();
        const popoverRect = popover.getBoundingClientRect();

        // Default to top position
        let top = rect.top - popoverRect.height - 10;
        let left = rect.left + (rect.width / 2) - (popoverRect.width / 2);

        // Adjust if popover goes off screen
        if (top < 10) {
            top = rect.bottom + 10;
            popover.classList.add('popover-bottom');
        }

        if (left < 10) {
            left = 10;
        } else if (left + popoverRect.width > window.innerWidth - 10) {
            left = window.innerWidth - popoverRect.width - 10;
        }

        popover.style.position = 'fixed';
        popover.style.top = top + 'px';
        popover.style.left = left + 'px';
        popover.style.zIndex = '1070';
    }

    /**
     * Toggle dropdown
     */
    toggleDropdown(dropdown) {
        const isOpen = dropdown.classList.contains('show');
        this.closeAllDropdowns();

        if (!isOpen) {
            dropdown.classList.add('show');
        }
    }

    /**
     * Close dropdown
     */
    closeDropdown(dropdown) {
        dropdown.classList.remove('show');
    }

    /**
     * Close all dropdowns
     */
    closeAllDropdowns() {
        document.querySelectorAll('.dropdown.show').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }

    /**
     * Switch tab
     */
    switchTab(tab) {
        const targetId = tab.getAttribute('data-bs-target');
        const tabContainer = tab.closest('.nav-tabs');
        const tabContent = document.querySelector(targetId);

        if (!tabContainer || !tabContent) return;

        // Remove active class from all tabs
        tabContainer.querySelectorAll('.nav-link').forEach(t => {
            t.classList.remove('active');
        });

        // Add active class to clicked tab
        tab.classList.add('active');

        // Hide all tab panes
        const tabPanes = tabContent.parentNode.querySelectorAll('.tab-pane');
        tabPanes.forEach(pane => {
            pane.classList.remove('active');
        });

        // Show target tab pane
        tabContent.classList.add('active');
    }

    /**
     * Close alert
     */
    closeAlert(alert) {
        alert.style.transition = 'opacity 0.3s ease-in-out';
        alert.style.opacity = '0';

        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 300);
    }

    /**
     * Open modal
     */
    openModal(modalId) {
        const modal = document.querySelector(modalId);
        if (modal) {
            modal.classList.add('show');
            modal.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }

    /**
     * Close modal
     */
    closeModal(modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    /**
     * Initialize carousel
     */
    initializeCarousel(carousel) {
        const slides = carousel.querySelectorAll('.carousel-slide');
        const indicators = carousel.querySelectorAll('.carousel-indicator');
        const prevBtn = carousel.querySelector('.carousel-prev');
        const nextBtn = carousel.querySelector('.carousel-next');

        if (slides.length === 0) return;

        let currentSlide = 0;

        const showSlide = (index) => {
            slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });

            indicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', i === index);
            });

            currentSlide = index;
        };

        const nextSlide = () => {
            const next = (currentSlide + 1) % slides.length;
            showSlide(next);
        };

        const prevSlide = () => {
            const prev = (currentSlide - 1 + slides.length) % slides.length;
            showSlide(prev);
        };

        if (prevBtn) {
            prevBtn.addEventListener('click', prevSlide);
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', nextSlide);
        }

        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => showSlide(index));
        });

        // Auto-play
        if (carousel.dataset.autoplay !== 'false') {
            setInterval(nextSlide, 5000);
        }
    }

    /**
     * Change quantity
     */
    changeQuantity(input, change) {
        const currentValue = parseInt(input.value) || 0;
        const newValue = Math.max(1, currentValue + change);
        input.value = newValue;
        input.dispatchEvent(new Event('change'));
    }

    /**
     * On cart quantity change
     */
    onCartQuantityChange(input) {
        const cartItemId = input.dataset.cartItemId;
        const quantity = parseInt(input.value);

        if (quantity < 1) {
            input.value = 1;
            return;
        }

        this.makeRequest('/cart/update-quantity', 'POST', {
            cart_item_id: cartItemId,
            quantity: quantity
        })
        .then(response => {
            if (response.success) {
                this.updateCartDisplay(response.cart);
            } else {
                this.showNotification(response.message || 'Erro ao atualizar quantidade', 'danger');
                input.value = input.defaultValue; // Reset to original value
            }
        })
        .catch(error => {
            this.showNotification('Erro ao atualizar quantidade', 'danger');
            input.value = input.defaultValue; // Reset to original value
        });
    }

    /**
     * On variant change
     */
    onVariantChange(select) {
        const productId = select.dataset.productId;
        const variantId = select.value;

        this.makeRequest(`/products/${productId}/variant/${variantId}`, 'GET')
        .then(response => {
            if (response.success) {
                this.updateProductVariant(response.variant);
            }
        })
        .catch(error => {
            console.error('Error loading variant:', error);
        });
    }

    /**
     * On shipping method change
     */
    onShippingMethodChange(select) {
        const shippingMethod = select.value;

        this.makeRequest('/cart/update-shipping', 'POST', {
            shipping_method: shippingMethod
        })
        .then(response => {
            if (response.success) {
                this.updateCartDisplay(response.cart);
                this.showNotification('Método de envio atualizado!', 'success');
            } else {
                this.showNotification(response.message || 'Erro ao atualizar envio', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao atualizar método de envio', 'danger');
        });
    }

    /**
     * Update product variant
     */
    updateProductVariant(variant) {
        // Update price
        const priceElement = document.querySelector('.product-price');
        if (priceElement) {
            priceElement.textContent = this.formatCurrency(variant.price);
        }

        // Update stock
        const stockElement = document.querySelector('.product-stock');
        if (stockElement) {
            stockElement.textContent = variant.stock_quantity > 0 ? `${variant.stock_quantity} em estoque` : 'Fora de estoque';
        }

        // Update SKU
        const skuElement = document.querySelector('.product-sku');
        if (skuElement) {
            skuElement.textContent = variant.sku;
        }

        // Update image
        const imageElement = document.querySelector('.product-image img');
        if (imageElement && variant.image) {
            imageElement.src = variant.image;
        }
    }

    /**
     * Update star rating
     */
    updateStarRating(container, rating) {
        const stars = container.querySelectorAll('.star');
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('empty');
                star.classList.add('filled');
            } else {
                star.classList.remove('filled');
                star.classList.add('empty');
            }
        });
    }

    /**
     * Update review helpful count
     */
    updateReviewHelpfulCount(button, helpfulCount, notHelpfulCount) {
        const helpfulElement = button.closest('.review-actions').querySelector('.helpful-count');
        const notHelpfulElement = button.closest('.review-actions').querySelector('.not-helpful-count');

        if (helpfulElement) {
            helpfulElement.textContent = helpfulCount;
        }

        if (notHelpfulElement) {
            notHelpfulElement.textContent = notHelpfulCount;
        }

        button.disabled = true;
        button.textContent = 'Voto registrado';
    }

    /**
     * Show quick view modal
     */
    showQuickViewModal(product) {
        // Implementation for showing quick view modal
        console.log('Quick view product:', product);
    }

    /**
     * Initialize star rating
     */
    initializeStarRating(rating) {
        const stars = rating.querySelectorAll('.star');
        const readonly = rating.hasAttribute('data-readonly');

        if (readonly) return;

        stars.forEach((star, index) => {
            star.addEventListener('click', () => {
                const ratingValue = index + 1;
                this.updateStarRating(rating, ratingValue);
                rating.dispatchEvent(new CustomEvent('ratingChanged', {
                    detail: { rating: ratingValue }
                }));
            });

            star.addEventListener('mouseenter', () => {
                this.updateStarRating(rating, index + 1);
            });
        });

        rating.addEventListener('mouseleave', () => {
            // Reset to actual rating
            const currentRating = parseInt(rating.dataset.rating || 0);
            this.updateStarRating(rating, currentRating);
        });
    }

    /**
     * Initialize progress bar
     */
    initializeProgressBar(bar) {
        const value = parseInt(bar.dataset.value || 0);
        const max = parseInt(bar.dataset.max || 100);

        const percentage = Math.min(100, Math.max(0, (value / max) * 100));
        bar.style.width = percentage + '%';

        if (bar.dataset.showLabel !== 'false') {
            bar.textContent = Math.round(percentage) + '%';
        }
    }

    /**
     * Initialize image gallery
     */
    initializeImageGallery(gallery) {
        const mainImage = gallery.querySelector('.main-image img');
        const thumbnails = gallery.querySelectorAll('.thumbnail');

        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', () => {
                if (mainImage) {
                    mainImage.src = thumbnail.src;
                    mainImage.alt = thumbnail.alt;
                }

                thumbnails.forEach(t => t.classList.remove('active'));
                thumbnail.classList.add('active');
            });
        });
    }

    /**
     * Validate field
     */
    validateField(field) {
        const value = field.value.trim();
        const type = field.getAttribute('type');
        const required = field.hasAttribute('required');

        let isValid = true;
        let message = '';

        // Required validation
        if (required && !value) {
            isValid = false;
            message = 'Este campo é obrigatório';
        }

        // Type validation
        if (isValid && value) {
            switch (type) {
                case 'email':
                    if (!this.isValidEmail(value)) {
                        isValid = false;
                        message = 'Por favor, digite um email válido';
                    }
                    break;
                case 'number':
                    if (isNaN(value)) {
                        isValid = false;
                        message = 'Por favor, digite um número válido';
                    }
                    break;
            }
        }

        this.setFieldValidation(field, isValid, message);
        return isValid;
    }

    /**
     * Set field validation
     */
    setFieldValidation(field, isValid, message) {
        field.classList.toggle('is-valid', isValid);
        field.classList.toggle('is-invalid', !isValid);

        const feedbackElement = field.parentNode.querySelector('.invalid-feedback');
        if (feedbackElement) {
            feedbackElement.textContent = message;
            feedbackElement.style.display = message ? 'block' : 'none';
        }
    }

    /**
     * Clear field error
     */
    clearFieldError(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');

        const feedbackElement = field.parentNode.querySelector('.invalid-feedback');
        if (feedbackElement) {
            feedbackElement.style.display = 'none';
        }
    }

    /**
     * Submit contact form
     */
    submitContactForm(form) {
        const formData = new FormData(form);

        this.makeRequest('/contact', 'POST', Object.fromEntries(formData))
        .then(response => {
            if (response.success) {
                this.showNotification('Mensagem enviada com sucesso!', 'success');
                form.reset();
            } else {
                this.showNotification(response.message || 'Erro ao enviar mensagem', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao enviar mensagem', 'danger');
        });
    }

    /**
     * Submit newsletter form
     */
    submitNewsletterForm(form) {
        const formData = new FormData(form);

        this.makeRequest('/newsletter/subscribe', 'POST', Object.fromEntries(formData))
        .then(response => {
            if (response.success) {
                this.showNotification('Inscrição realizada com sucesso!', 'success');
                form.reset();
            } else {
                this.showNotification(response.message || 'Erro ao se inscrever', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao se inscrever na newsletter', 'danger');
        });
    }

    /**
     * Submit review form
     */
    submitReviewForm(form) {
        const formData = new FormData(form);

        this.makeRequest('/reviews', 'POST', Object.fromEntries(formData))
        .then(response => {
            if (response.success) {
                this.showNotification('Avaliação enviada com sucesso!', 'success');
                form.reset();
                // Reload page or update reviews section
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                this.showNotification(response.message || 'Erro ao enviar avaliação', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao enviar avaliação', 'danger');
        });
    }

    /**
     * Make HTTP request
     */
    makeRequest(url, method = 'GET', data = {}) {
        const config = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.Store.csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (method !== 'GET' && data) {
            config.body = JSON.stringify(data);
        }

        return fetch(url, config)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        });
    }

    /**
     * Check if user is authenticated
     */
    isAuthenticated() {
        return !!document.querySelector('meta[name="authenticated-user"]');
    }

    /**
     * Format currency
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(amount);
    }

    /**
     * Validate email
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Throttle function
     */
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    window.StoreApp = new StoreApp();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = StoreApp;
}