/* main.js - global javascript for MerchVault */
$(function () {

    // -----------------------------------------------
    // auto-dismiss bootstrap flash alerts after 4s
    // -----------------------------------------------
    $('.flash-message').each(function () {
        var alertEl = this;
        setTimeout(function () {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
            bsAlert.close();
        }, 4000);
    });

    // -----------------------------------------------
    // back-to-top button
    // -----------------------------------------------
    var $backToTopBtn = $('#backToTop');
    if ($backToTopBtn.length) {
        $(window).on('scroll', function () {
            if ($(window).scrollTop() > 400) {
                $backToTopBtn.fadeIn();
            } else {
                $backToTopBtn.fadeOut();
            }
        });

        $backToTopBtn.on('click', function () {
            $('html, body').animate({ scrollTop: 0 }, 'smooth');
        });
    }

    // -----------------------------------------------
    // active nav link highlighting
    // -----------------------------------------------
    var currentPath = window.location.pathname;
    $('#mainNav .nav-link').each(function () {
        var href = $(this).attr('href');
        if (href && href !== '/' && currentPath.startsWith(href)) {
            $(this).addClass('active').attr('aria-current', 'page');
        } else if (href === '/index.php' && currentPath === '/') {
            $(this).addClass('active');
        }
    });

    // -----------------------------------------------
    // collapse mobile navbar on nav-link click
    // -----------------------------------------------
    var $navCollapse = $('#navbarMain');
    if ($navCollapse.length) {
        $('#navbarMain .nav-link:not(.dropdown-toggle)').on('click', function () {
            var bsCollapse = bootstrap.Collapse.getInstance($navCollapse[0]);
            if (bsCollapse) bsCollapse.hide();
        });
    }

    // -----------------------------------------------
    // scroll-triggered fade-in for sections
    // -----------------------------------------------
    if ('IntersectionObserver' in window) {
        var fadeObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    $(entry.target).addClass('visible');
                    fadeObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        $('.fade-in-section').each(function () {
            fadeObserver.observe(this);
        });
    } else {
        // fallback: show all immediately
        $('.fade-in-section').addClass('visible');
    }

    // -----------------------------------------------
    // update cart badge count (shared utility)
    // called by listing.js and cart.js after AJAX
    // -----------------------------------------------
    window.updateCartBadge = function (count) {
        var $badge = $('#cartBadge');
        if (!$badge.length) return;
        if (count > 0) {
            $badge.text(count).show();
        } else {
            $badge.hide();
        }
    };

});