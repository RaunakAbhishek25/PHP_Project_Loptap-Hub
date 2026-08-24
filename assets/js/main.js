// ============================================================
// LAPTOPHUB - PREMIUM JAVASCRIPT
// ============================================================

document.addEventListener('DOMContentLoaded', function() {

    // ========== BACK TO TOP ==========
    const backToTop = document.createElement('button');
    backToTop.className = 'back-to-top';
    backToTop.innerHTML = '<i class="fas fa-chevron-up"></i>';
    document.body.appendChild(backToTop);

    window.addEventListener('scroll', function() {
        if (window.scrollY > 400) {
            backToTop.classList.add('show');
        } else {
            backToTop.classList.remove('show');
        }
    });

    backToTop.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // ========== TOAST NOTIFICATIONS ==========
    function showToast(message, type = 'success') {
        const colors = {
            success: '#22c55e',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#2563eb'
        };

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 m-4 p-4 text-white rounded-4 shadow-lg';
        toast.style.backgroundColor = colors[type] || colors.success;
        toast.style.zIndex = 9999;
        toast.style.minWidth = '280px';
        toast.style.maxWidth = '400px';
        toast.style.borderRadius = '16px';
        toast.style.boxShadow = '0 20px 60px rgba(0,0,0,0.2)';
        toast.style.animation = 'fadeInUp 0.4s ease-out';
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas ${icons[type] || icons.success} fa-lg me-3"></i>
                <span class="flex-grow-1 fw-semibold">${message}</span>
                <button type="button" class="btn-close btn-close-white" onclick="this.closest('.position-fixed').remove()"></button>
            </div>
        `;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.5s';
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }

    // ========== LAZY LOAD IMAGES ==========
    const lazyImages = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                img.classList.add('animate-fade-scale');
            }
        });
    });

    lazyImages.forEach(img => imageObserver.observe(img));

    // ========== COUNTER ANIMATION ==========
    const counters = document.querySelectorAll('.counter');
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = entry.target;
                const final = parseInt(target.dataset.target);
                let current = 0;
                const increment = final / 60;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= final) {
                        target.textContent = final + '+';
                        clearInterval(timer);
                    } else {
                        target.textContent = Math.floor(current) + '+';
                    }
                }, 20);
                counterObserver.unobserve(target);
            }
        });
    });

    counters.forEach(counter => counterObserver.observe(counter));

    // ========== SMOOTH SCROLL ==========
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ========== NAVBAR SCROLL EFFECT ==========
    const navbar = document.querySelector('.navbar');
    let lastScroll = 0;

    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        if (currentScroll > 50) {
            navbar.style.boxShadow = '0 4px 30px rgba(0,0,0,0.1)';
            navbar.style.background = 'rgba(255,255,255,0.95)';
            navbar.style.backdropFilter = 'blur(12px)';
        } else {
            navbar.style.boxShadow = '0 2px 15px rgba(0,0,0,0.06)';
            navbar.style.background = 'rgba(255,255,255,0.98)';
            navbar.style.backdropFilter = 'none';
        }
        lastScroll = currentScroll;
    });

    // ========== ADD TO CART FUNCTION ==========
    window.addToCart = function(productId, quantity = 1) {
        fetch('ajax/add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ laptop_id: productId, quantity: quantity })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('✅ Added to cart successfully!', 'success');
                updateCartCount();
            } else {
                showToast(data.message || 'Error adding to cart', 'error');
            }
        })
        .catch(error => {
            showToast('Error adding to cart', 'error');
        });
    };

    // ========== UPDATE CART COUNT ==========
    function updateCartCount() {
        fetch('ajax/cart_count.php')
            .then(response => response.json())
            .then(data => {
                const badges = document.querySelectorAll('.cart-badge');
                badges.forEach(badge => {
                    badge.textContent = data.count || 0;
                    if (data.count === 0) {
                        badge.style.display = 'none';
                    } else {
                        badge.style.display = 'inline-block';
                    }
                });
            })
            .catch(() => {});
    }

    // ========== SEARCH SUGGESTIONS ==========
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            if (query.length > 2) {
                searchTimeout = setTimeout(() => {
                    fetch(`ajax/search_suggestions.php?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            // Show suggestions
                            const container = document.querySelector('.search-suggestions');
                            if (container) {
                                container.innerHTML = data.map(item => 
                                    `<a href="product.php?id=${item.id}" class="dropdown-item">
                                        <i class="fas fa-search me-2"></i>${item.name}
                                     </a>`
                                ).join('');
                                container.style.display = 'block';
                            }
                        });
                }, 300);
            }
        });
    }

    // ========== QUICK VIEW ==========
    document.querySelectorAll('.quick-view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const modal = new bootstrap.Modal(document.getElementById('quickViewModal'));
            document.getElementById('quickViewBody').innerHTML = `
                <div class="text-center py-5">
                    <div class="loading-spinner mx-auto"></div>
                    <p class="mt-3 text-muted">Loading product...</p>
                </div>
            `;
            modal.show();

            fetch(`ajax/quick_view.php?id=${id}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('quickViewBody').innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('quickViewBody').innerHTML = `
                        <div class="alert alert-danger">Failed to load product</div>
                    `;
                });
        });
    });

    // ========== EXPOSE FUNCTIONS ==========
    window.showToast = showToast;
    window.updateCartCount = updateCartCount;

});