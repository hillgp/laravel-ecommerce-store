/**
 * Laravel E-commerce Store - Components JavaScript
 * Specialized components for the store interface
 */

class StoreComponents {
    constructor() {
        this.components = new Map();
        this.init();
    }

    init() {
        this.initializeProductComponents();
        this.initializeCartComponents();
        this.initializeCheckoutComponents();
        this.initializeCustomerComponents();
        this.initializeAdminComponents();
    }

    /**
     * Initialize product-related components
     */
    initializeProductComponents() {
        this.initializeProductGallery();
        this.initializeProductVariants();
        this.initializeProductReviews();
        this.initializeProductRecommendations();
        this.initializeProductComparison();
        this.initializeProductWishlist();
    }

    /**
     * Initialize cart-related components
     */
    initializeCartComponents() {
        this.initializeCartDrawer();
        this.initializeCartSummary();
        this.initializeCartItem();
        this.initializeCouponSystem();
        this.initializeShippingCalculator();
    }

    /**
     * Initialize checkout-related components
     */
    initializeCheckoutComponents() {
        this.initializeCheckoutSteps();
        this.initializeAddressForm();
        this.initializePaymentForm();
        this.initializeOrderSummary();
    }

    /**
     * Initialize customer-related components
     */
    initializeCustomerComponents() {
        this.initializeCustomerProfile();
        this.initializeOrderHistory();
        this.initializeAddressBook();
        this.initializeWishlistManager();
    }

    /**
     * Initialize admin-related components
     */
    initializeAdminComponents() {
        this.initializeAdminDashboard();
        this.initializeProductManager();
        this.initializeOrderManager();
        this.initializeCustomerManager();
    }

    /**
     * Product Gallery Component
     */
    initializeProductGallery() {
        const galleries = document.querySelectorAll('.product-gallery');
        galleries.forEach(gallery => {
            const component = new ProductGallery(gallery);
            this.components.set(gallery, component);
        });
    }

    /**
     * Product Variants Component
     */
    initializeProductVariants() {
        const variantSelectors = document.querySelectorAll('.product-variants');
        variantSelectors.forEach(selector => {
            const component = new ProductVariants(selector);
            this.components.set(selector, component);
        });
    }

    /**
     * Product Reviews Component
     */
    initializeProductReviews() {
        const reviewSections = document.querySelectorAll('.product-reviews');
        reviewSections.forEach(section => {
            const component = new ProductReviews(section);
            this.components.set(section, component);
        });
    }

    /**
     * Product Recommendations Component
     */
    initializeProductRecommendations() {
        const recommendationSections = document.querySelectorAll('.product-recommendations');
        recommendationSections.forEach(section => {
            const component = new ProductRecommendations(section);
            this.components.set(section, component);
        });
    }

    /**
     * Product Comparison Component
     */
    initializeProductComparison() {
        const comparisonButtons = document.querySelectorAll('.product-compare-btn');
        comparisonButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleProductComparison(button);
            });
        });
    }

    /**
     * Product Wishlist Component
     */
    initializeProductWishlist() {
        const wishlistButtons = document.querySelectorAll('.product-wishlist-btn');
        wishlistButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleWishlist(button);
            });
        });
    }

    /**
     * Cart Drawer Component
     */
    initializeCartDrawer() {
        const cartDrawer = document.querySelector('.cart-drawer');
        if (cartDrawer) {
            const component = new CartDrawer(cartDrawer);
            this.components.set(cartDrawer, component);
        }
    }

    /**
     * Cart Summary Component
     */
    initializeCartSummary() {
        const cartSummaries = document.querySelectorAll('.cart-summary');
        cartSummaries.forEach(summary => {
            const component = new CartSummary(summary);
            this.components.set(summary, component);
        });
    }

    /**
     * Cart Item Component
     */
    initializeCartItem() {
        const cartItems = document.querySelectorAll('.cart-item');
        cartItems.forEach(item => {
            const component = new CartItem(item);
            this.components.set(item, component);
        });
    }

    /**
     * Coupon System Component
     */
    initializeCouponSystem() {
        const couponForms = document.querySelectorAll('.coupon-form');
        couponForms.forEach(form => {
            const component = new CouponSystem(form);
            this.components.set(form, component);
        });
    }

    /**
     * Shipping Calculator Component
     */
    initializeShippingCalculator() {
        const calculators = document.querySelectorAll('.shipping-calculator');
        calculators.forEach(calculator => {
            const component = new ShippingCalculator(calculator);
            this.components.set(calculator, component);
        });
    }

    /**
     * Checkout Steps Component
     */
    initializeCheckoutSteps() {
        const checkoutSteps = document.querySelector('.checkout-steps');
        if (checkoutSteps) {
            const component = new CheckoutSteps(checkoutSteps);
            this.components.set(checkoutSteps, component);
        }
    }

    /**
     * Address Form Component
     */
    initializeAddressForm() {
        const addressForms = document.querySelectorAll('.address-form');
        addressForms.forEach(form => {
            const component = new AddressForm(form);
            this.components.set(form, component);
        });
    }

    /**
     * Payment Form Component
     */
    initializePaymentForm() {
        const paymentForms = document.querySelectorAll('.payment-form');
        paymentForms.forEach(form => {
            const component = new PaymentForm(form);
            this.components.set(form, component);
        });
    }

    /**
     * Order Summary Component
     */
    initializeOrderSummary() {
        const orderSummaries = document.querySelectorAll('.order-summary');
        orderSummaries.forEach(summary => {
            const component = new OrderSummary(summary);
            this.components.set(summary, component);
        });
    }

    /**
     * Customer Profile Component
     */
    initializeCustomerProfile() {
        const profileForms = document.querySelectorAll('.customer-profile-form');
        profileForms.forEach(form => {
            const component = new CustomerProfile(form);
            this.components.set(form, component);
        });
    }

    /**
     * Order History Component
     */
    initializeOrderHistory() {
        const orderHistories = document.querySelectorAll('.order-history');
        orderHistories.forEach(history => {
            const component = new OrderHistory(history);
            this.components.set(history, component);
        });
    }

    /**
     * Address Book Component
     */
    initializeAddressBook() {
        const addressBooks = document.querySelectorAll('.address-book');
        addressBooks.forEach(book => {
            const component = new AddressBook(book);
            this.components.set(book, component);
        });
    }

    /**
     * Wishlist Manager Component
     */
    initializeWishlistManager() {
        const wishlists = document.querySelectorAll('.wishlist-manager');
        wishlists.forEach(wishlist => {
            const component = new WishlistManager(wishlist);
            this.components.set(wishlist, component);
        });
    }

    /**
     * Admin Dashboard Component
     */
    initializeAdminDashboard() {
        const dashboards = document.querySelectorAll('.admin-dashboard');
        dashboards.forEach(dashboard => {
            const component = new AdminDashboard(dashboard);
            this.components.set(dashboard, component);
        });
    }

    /**
     * Product Manager Component
     */
    initializeProductManager() {
        const productManagers = document.querySelectorAll('.product-manager');
        productManagers.forEach(manager => {
            const component = new ProductManager(manager);
            this.components.set(manager, component);
        });
    }

    /**
     * Order Manager Component
     */
    initializeOrderManager() {
        const orderManagers = document.querySelectorAll('.order-manager');
        orderManagers.forEach(manager => {
            const component = new OrderManager(manager);
            this.components.set(manager, component);
        });
    }

    /**
     * Customer Manager Component
     */
    initializeCustomerManager() {
        const customerManagers = document.querySelectorAll('.customer-manager');
        customerManagers.forEach(manager => {
            const component = new CustomerManager(manager);
            this.components.set(manager, component);
        });
    }

    /**
     * Toggle product comparison
     */
    toggleProductComparison(button) {
        const productId = button.dataset.productId;
        const isActive = button.classList.contains('active');

        if (isActive) {
            this.removeFromComparison(productId);
            button.classList.remove('active');
            button.innerHTML = '<i class="fas fa-balance-scale"></i> Comparar';
        } else {
            this.addToComparison(productId);
            button.classList.add('active');
            button.innerHTML = '<i class="fas fa-check"></i> Comparando';
        }
    }

    /**
     * Add product to comparison
     */
    addToComparison(productId) {
        this.makeRequest('/products/compare/add', 'POST', { product_id: productId })
        .then(response => {
            if (response.success) {
                this.updateComparisonDisplay(response.comparison);
                this.showNotification('Produto adicionado à comparação!', 'success');
            } else {
                this.showNotification(response.message || 'Erro ao adicionar produto', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao adicionar produto à comparação', 'danger');
        });
    }

    /**
     * Remove product from comparison
     */
    removeFromComparison(productId) {
        this.makeRequest('/products/compare/remove', 'POST', { product_id: productId })
        .then(response => {
            if (response.success) {
                this.updateComparisonDisplay(response.comparison);
            }
        })
        .catch(error => {
            console.error('Error removing from comparison:', error);
        });
    }

    /**
     * Update comparison display
     */
    updateComparisonDisplay(comparison) {
        const comparisonContainer = document.querySelector('.product-comparison');
        if (comparisonContainer) {
            // Update comparison UI
            console.log('Updating comparison display:', comparison);
        }
    }

    /**
     * Toggle wishlist
     */
    toggleWishlist(button) {
        const productId = button.dataset.productId;
        const isActive = button.classList.contains('active');

        if (isActive) {
            this.removeFromWishlist(productId);
            button.classList.remove('active');
            button.innerHTML = '<i class="far fa-heart"></i>';
        } else {
            this.addToWishlist(productId);
            button.classList.add('active');
            button.innerHTML = '<i class="fas fa-heart"></i>';
        }
    }

    /**
     * Add to wishlist
     */
    addToWishlist(productId) {
        this.makeRequest('/wishlist/add', 'POST', { product_id: productId })
        .then(response => {
            if (response.success) {
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
     * Remove from wishlist
     */
    removeFromWishlist(productId) {
        this.makeRequest('/wishlist/remove', 'POST', { product_id: productId })
        .then(response => {
            if (response.success) {
                this.showNotification('Produto removido da lista de desejos!', 'success');
            }
        })
        .catch(error => {
            console.error('Error removing from wishlist:', error);
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
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
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
     * Show notification
     */
    showNotification(message, type = 'info', duration = 5000) {
        if (window.StoreApp && window.StoreApp.showNotification) {
            window.StoreApp.showNotification(message, type, duration);
        } else {
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    }
}

/**
 * Product Gallery Component
 */
class ProductGallery {
    constructor(element) {
        this.element = element;
        this.mainImage = element.querySelector('.main-image img');
        this.thumbnails = element.querySelectorAll('.thumbnail');
        this.currentIndex = 0;
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupZoom();
        this.setupLightbox();
    }

    bindEvents() {
        this.thumbnails.forEach((thumbnail, index) => {
            thumbnail.addEventListener('click', () => {
                this.switchImage(index);
            });

            thumbnail.addEventListener('mouseenter', () => {
                this.previewImage(index);
            });
        });

        // Keyboard navigation
        this.element.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                this.previousImage();
            } else if (e.key === 'ArrowRight') {
                this.nextImage();
            }
        });
    }

    switchImage(index) {
        if (index < 0 || index >= this.thumbnails.length) return;

        // Update main image
        const thumbnail = this.thumbnails[index];
        if (this.mainImage && thumbnail) {
            this.mainImage.src = thumbnail.src;
            this.mainImage.alt = thumbnail.alt;
        }

        // Update active thumbnail
        this.thumbnails.forEach((thumb, i) => {
            thumb.classList.toggle('active', i === index);
        });

        this.currentIndex = index;
    }

    previewImage(index) {
        if (index < 0 || index >= this.thumbnails.length) return;

        const thumbnail = this.thumbnails[index];
        if (this.mainImage && thumbnail) {
            this.mainImage.src = thumbnail.src;
        }
    }

    nextImage() {
        const nextIndex = (this.currentIndex + 1) % this.thumbnails.length;
        this.switchImage(nextIndex);
    }

    previousImage() {
        const prevIndex = (this.currentIndex - 1 + this.thumbnails.length) % this.thumbnails.length;
        this.switchImage(prevIndex);
    }

    setupZoom() {
        if (!this.mainImage) return;

        let zoomLevel = 1;
        const maxZoom = 3;
        const minZoom = 1;

        this.mainImage.addEventListener('wheel', (e) => {
            e.preventDefault();

            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            zoomLevel = Math.min(maxZoom, Math.max(minZoom, zoomLevel + delta));

            this.mainImage.style.transform = `scale(${zoomLevel})`;
        });

        // Reset zoom on double click
        this.mainImage.addEventListener('dblclick', () => {
            zoomLevel = 1;
            this.mainImage.style.transform = 'scale(1)';
        });
    }

    setupLightbox() {
        this.mainImage.addEventListener('click', () => {
            this.openLightbox();
        });
    }

    openLightbox() {
        const lightbox = document.createElement('div');
        lightbox.className = 'lightbox';
        lightbox.innerHTML = `
            <div class="lightbox-content">
                <button class="lightbox-close">&times;</button>
                <button class="lightbox-prev"><</button>
                <button class="lightbox-next">></button>
                <img src="${this.mainImage.src}" alt="${this.mainImage.alt}">
                <div class="lightbox-caption">${this.mainImage.alt}</div>
            </div>
        `;

        document.body.appendChild(lightbox);

        // Bind lightbox events
        lightbox.querySelector('.lightbox-close').addEventListener('click', () => {
            this.closeLightbox(lightbox);
        });

        lightbox.querySelector('.lightbox-prev').addEventListener('click', () => {
            this.previousImage();
            this.updateLightboxImage(lightbox);
        });

        lightbox.querySelector('.lightbox-next').addEventListener('click', () => {
            this.nextImage();
            this.updateLightboxImage(lightbox);
        });

        // Close on backdrop click
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) {
                this.closeLightbox(lightbox);
            }
        });

        // Close on escape key
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                this.closeLightbox(lightbox);
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
    }

    updateLightboxImage(lightbox) {
        const lightboxImg = lightbox.querySelector('img');
        const thumbnail = this.thumbnails[this.currentIndex];
        lightboxImg.src = thumbnail.src;
        lightboxImg.alt = thumbnail.alt;
        lightbox.querySelector('.lightbox-caption').textContent = thumbnail.alt;
    }

    closeLightbox(lightbox) {
        lightbox.remove();
    }
}

/**
 * Product Variants Component
 */
class ProductVariants {
    constructor(element) {
        this.element = element;
        this.variantSelects = element.querySelectorAll('select');
        this.variantOptions = element.querySelectorAll('.variant-option');
        this.priceDisplay = element.querySelector('.variant-price');
        this.stockDisplay = element.querySelector('.variant-stock');
        this.skuDisplay = element.querySelector('.variant-sku');
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateVariantInfo();
    }

    bindEvents() {
        this.variantSelects.forEach(select => {
            select.addEventListener('change', () => {
                this.onVariantChange();
            });
        });

        this.variantOptions.forEach(option => {
            option.addEventListener('click', () => {
                this.onVariantOptionClick(option);
            });
        });
    }

    onVariantChange() {
        const selectedOptions = {};
        this.variantSelects.forEach(select => {
            selectedOptions[select.name] = select.value;
        });

        this.updateVariantInfo(selectedOptions);
        this.checkVariantAvailability(selectedOptions);
    }

    onVariantOptionClick(option) {
        const group = option.dataset.group;
        const value = option.dataset.value;

        // Update active state in group
        const groupOptions = this.element.querySelectorAll(`[data-group="${group}"]`);
        groupOptions.forEach(opt => opt.classList.remove('active'));
        option.classList.add('active');

        // Update select value
        const select = this.element.querySelector(`select[name="${group}"]`);
        if (select) {
            select.value = value;
            select.dispatchEvent(new Event('change'));
        }
    }

    updateVariantInfo(selectedOptions = null) {
        const variantData = this.getVariantData(selectedOptions);

        if (variantData) {
            this.updatePrice(variantData.price);
            this.updateStock(variantData.stock);
            this.updateSku(variantData.sku);
            this.updateImages(variantData.images);
        }
    }

    getVariantData(selectedOptions = null) {
        // Get variant data from element data attributes or API
        const variants = this.element.dataset.variants;
        if (!variants) return null;

        try {
            const variantsData = JSON.parse(variants);
            if (selectedOptions) {
                return variantsData.find(variant => {
                    return Object.keys(selectedOptions).every(key => {
                        return variant[key] === selectedOptions[key];
                    });
                });
            }
            return variantsData[0]; // Return first variant as default
        } catch (e) {
            console.error('Error parsing variant data:', e);
            return null;
        }
    }

    updatePrice(price) {
        if (this.priceDisplay) {
            this.priceDisplay.textContent = this.formatCurrency(price);
        }
    }

    updateStock(stock) {
        if (this.stockDisplay) {
            const stockText = stock > 0 ? `${stock} em estoque` : 'Fora de estoque';
            this.stockDisplay.textContent = stockText;
            this.stockDisplay.className = stock > 0 ? 'text-success' : 'text-danger';
        }
    }

    updateSku(sku) {
        if (this.skuDisplay) {
            this.skuDisplay.textContent = sku;
        }
    }

    updateImages(images) {
        // Update product gallery images if available
        if (images && images.length > 0) {
            const gallery = document.querySelector('.product-gallery');
            if (gallery) {
                // Update gallery images
                console.log('Updating gallery images:', images);
            }
        }
    }

    checkVariantAvailability(selectedOptions) {
        // Check if the selected variant combination is available
        const variantData = this.getVariantData(selectedOptions);

        if (variantData && variantData.available) {
            this.enableVariantOptions();
        } else {
            this.disableVariantOptions();
        }
    }

    enableVariantOptions() {
        this.variantSelects.forEach(select => {
            select.disabled = false;
        });
    }

    disableVariantOptions() {
        this.variantSelects.forEach(select => {
            select.disabled = true;
        });
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(amount);
    }
}

/**
 * Product Reviews Component
 */
class ProductReviews {
    constructor(element) {
        this.element = element;
        this.productId = element.dataset.productId;
        this.reviewsContainer = element.querySelector('.reviews-list');
        this.reviewForm = element.querySelector('.review-form');
        this.ratingFilter = element.querySelector('.rating-filter');
        this.sortSelect = element.querySelector('.sort-reviews');
        this.loadMoreBtn = element.querySelector('.load-more-reviews');
        this.currentPage = 1;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadReviews();
    }

    bindEvents() {
        if (this.reviewForm) {
            this.reviewForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitReview();
            });
        }

        if (this.ratingFilter) {
            this.ratingFilter.addEventListener('change', () => {
                this.filterReviews();
            });
        }

        if (this.sortSelect) {
            this.sortSelect.addEventListener('change', () => {
                this.sortReviews();
            });
        }

        if (this.loadMoreBtn) {
            this.loadMoreBtn.addEventListener('click', () => {
                this.loadMoreReviews();
            });
        }
    }

    loadReviews() {
        this.makeRequest(`/products/${this.productId}/reviews`, 'GET', {
            page: this.currentPage,
            filter: this.ratingFilter ? this.ratingFilter.value : null,
            sort: this.sortSelect ? this.sortSelect.value : null
        })
        .then(response => {
            if (response.success) {
                this.renderReviews(response.reviews);
                this.updatePagination(response.pagination);
            }
        })
        .catch(error => {
            console.error('Error loading reviews:', error);
        });
    }

    renderReviews(reviews) {
        if (!this.reviewsContainer) return;

        const reviewsHtml = reviews.map(review => this.createReviewHtml(review)).join('');
        this.reviewsContainer.innerHTML = reviewsHtml;
    }

    createReviewHtml(review) {
        return `
            <div class="review-item" data-review-id="${review.id}">
                <div class="review-header">
                    <div class="review-rating">
                        ${this.createStarRating(review.rating)}
                    </div>
                    <div class="review-meta">
                        <span class="review-author">${review.customer_name}</span>
                        <span class="review-date">${this.formatDate(review.created_at)}</span>
                    </div>
                </div>
                <div class="review-content">
                    <p>${review.comment}</p>
                </div>
                ${review.images ? `
                <div class="review-images">
                    ${review.images.map(image => `<img src="${image}" alt="Review image">`).join('')}
                </div>
                ` : ''}
                <div class="review-actions">
                    <button class="btn btn-sm btn-outline-primary review-helpful" data-review-id="${review.id}" data-action="helpful">
                        Útil (${review.helpful_count})
                    </button>
                    <button class="btn btn-sm btn-outline-secondary review-helpful" data-review-id="${review.id}" data-action="not_helpful">
                        Não útil (${review.not_helpful_count})
                    </button>
                </div>
            </div>
        `;
    }

    createStarRating(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += `<span class="star ${i <= rating ? 'filled' : 'empty'}">★</span>`;
        }
        return stars;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    }

    submitReview() {
        const formData = new FormData(this.reviewForm);

        this.makeRequest(`/products/${this.productId}/reviews`, 'POST', Object.fromEntries(formData))
        .then(response => {
            if (response.success) {
                this.showNotification('Avaliação enviada com sucesso!', 'success');
                this.reviewForm.reset();
                this.loadReviews(); // Reload reviews
            } else {
                this.showNotification(response.message || 'Erro ao enviar avaliação', 'danger');
            }
        })
        .catch(error => {
            this.showNotification('Erro ao enviar avaliação', 'danger');
        });
    }

    filterReviews() {
        this.currentPage = 1;
        this.loadReviews();
    }

    sortReviews() {
        this.currentPage = 1;
        this.loadReviews();
    }

    loadMoreReviews() {
        this.currentPage++;
        this.loadReviews();
    }

    updatePagination(pagination) {
        if (this.loadMoreBtn) {
            this.loadMoreBtn.style.display = pagination.has_more ? 'block' : 'none';
        }
    }

    makeRequest(url, method = 'GET', data = {}) {
        const config = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
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

    showNotification(message, type = 'info', duration = 5000) {
        if (window.StoreApp && window.StoreApp.showNotification) {
            window.StoreApp.showNotification(message, type, duration);
        } else {
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    }
}

/**
 * Product Recommendations Component
 */
class ProductRecommendations {
    constructor(element) {
        this.element = element;
        this.productId = element.dataset.productId;
        this.type = element.dataset.type || 'related';
        this.limit = element.dataset.limit || 4;
        this.init();
    }

    init() {
        this.loadRecommendations();
    }

    loadRecommendations() {
        this.makeRequest(`/products/${this.productId}/recommendations`, 'GET', {
            type: this.type,
            limit: this.limit
        })
        .then(response => {
            if (response.success) {
                this.renderRecommendations(response.products);
            }
        })
        .catch(error => {
            console.error('Error loading recommendations:', error);
        });
    }

    renderRecommendations(products) {
        const recommendationsHtml = products.map(product => this.createProductHtml(product)).join('');
        this.element.innerHTML = recommendationsHtml;
    }

    createProductHtml(product) {
        return `
            <div class="product-card">
                <div class="product-image">
                    <img src="${product.image}" alt="${product.name}">
                    ${product.discount ? `<span class="badge badge-discount">${product.discount}% OFF</span>` : ''}
                </div>
                <div class="product-info">
                    <h3 class="product-title">${product.name}</h3>
                    <div class="product-price">
                        ${product.discount_price ? `<span class="old-price">${this.formatCurrency(product.price)}</span>` : ''}
                        <span class="current-price">${this.formatCurrency(product.discount_price || product.price)}</span>
                    </div>
                    <div class="product-rating">
                        ${this.createStarRating(product.rating)}
                        <span class="rating-count">(${product.review_count})</span>
                    </div>
                </div>
                <div class="product-actions">
                    <button class="btn btn-primary btn-add-to-cart" data-product-id="${product.id}">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                    <button class="btn btn-outline-secondary btn-add-to-wishlist" data-product-id="${product.id}">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        `;
    }

    createStarRating(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += `<span class="star ${i <= rating ? 'filled' : 'empty'}">★</span>`;
        }
        return stars;
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(amount);
    }

    makeRequest(url, method = 'GET', data = {}) {
        const config = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
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
}

// Additional component classes would continue here...
// CartDrawer, CartSummary, CartItem, CouponSystem, etc.

// Initialize components when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.StoreComponents = new StoreComponents();
});