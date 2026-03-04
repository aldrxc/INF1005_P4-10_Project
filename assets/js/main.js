/* main.js — Global JavaScript for MerchVault */

document.addEventListener('DOMContentLoaded', function () {

    // -----------------------------------------------
    // Auto-dismiss Bootstrap flash alerts after 4s
    // -----------------------------------------------
    document.querySelectorAll('.flash-message').forEach(function (alert) {
        setTimeout(function () {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 4000);
    });

    // -----------------------------------------------
    // Back-to-top button
    // -----------------------------------------------
    var backToTopBtn = document.getElementById('backToTop');
    if (backToTopBtn) {
        window.addEventListener('scroll', function () {
            backToTopBtn.style.display = window.scrollY > 400 ? 'block' : 'none';
        });
        backToTopBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // -----------------------------------------------
    // Active nav link highlighting
    // -----------------------------------------------
    var currentPath = window.location.pathname;
    document.querySelectorAll('#mainNav .nav-link').forEach(function (link) {
        var href = link.getAttribute('href');
        if (href && href !== '/' && currentPath.startsWith(href)) {
            link.classList.add('active');
            link.setAttribute('aria-current', 'page');
        } else if (href === '/index.php' && currentPath === '/') {
            link.classList.add('active');
        }
    });

    // -----------------------------------------------
    // Collapse mobile navbar on nav-link click
    // -----------------------------------------------
    var navCollapse = document.getElementById('navbarMain');
    if (navCollapse) {
        document.querySelectorAll('#navbarMain .nav-link:not(.dropdown-toggle)').forEach(function (link) {
            link.addEventListener('click', function () {
                var bsCollapse = bootstrap.Collapse.getInstance(navCollapse);
                if (bsCollapse) bsCollapse.hide();
            });
        });
    }

    // -----------------------------------------------
    // Scroll-triggered fade-in for sections
    // -----------------------------------------------
    if ('IntersectionObserver' in window) {
        var fadeObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    fadeObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.fade-in-section').forEach(function (el) {
            fadeObserver.observe(el);
        });
    } else {
        // Fallback: show all immediately
        document.querySelectorAll('.fade-in-section').forEach(function (el) {
            el.classList.add('visible');
        });
    }

    // -----------------------------------------------
    // Update cart badge count (shared utility)
    // Called by listing.js and cart.js after AJAX
    // -----------------------------------------------
    window.updateCartBadge = function (count) {
        var badge = document.getElementById('cartBadge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = '';
        } else {
            badge.style.display = 'none';
        }
    };

});
