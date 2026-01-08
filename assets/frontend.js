(function($) {
    'use strict';
    
    var SCFS = {
        // Variabile globale
        lastScrollTop: 0,
        scrollTimeout: null,
        
        // Inițializare principală
        init: function() {
            this.setupFloatingButtons();
            this.initCustomMessage();
            this.setupScrollBehavior();
            this.ensureIconsVisible();
        },
        
        // 1. Floating buttons functionality
        setupFloatingButtons: function() {
            $('.sfb-cta').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $container = $(this).closest('.sfb-container');
                $container.toggleClass('active');
                $(this).toggleClass('active');
                
                // Aria attributes
                $(this).attr('aria-expanded', $container.hasClass('active'));
                $container.find('.sfb-popup').attr('aria-hidden', !$container.hasClass('active'));
                
                // Asigură că custom message este vizibil când meniul este deschis
                if ($container.hasClass('active')) {
                    $container.find('.sfb-custom-message')
                        .removeClass('sfb-custom-message-hidden sfb-custom-message-scroll')
                        .css({ 'opacity': '1', 'visibility': 'visible' });
                }
                
                // Focus management
                if ($container.hasClass('active')) {
                    setTimeout(function() {
                        $container.find('.sfb-item').first().focus();
                    }, 100);
                } else {
                    $(this).focus();
                }
            });
            
            // Close on click outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.sfb-container').length) {
                    $('.sfb-container').removeClass('active');
                    $('.sfb-cta').removeClass('active');
                }
            });
            
            // Close on escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.sfb-container').removeClass('active');
                    $('.sfb-cta').removeClass('active');
                }
            });
            
            // Hover pentru custom message - AFIȘARE/VANISH
            $('.sfb-cta').on('mouseenter', function() {
                var $message = $(this).closest('.sfb-container').find('.sfb-custom-message');
                $message.removeClass('sfb-custom-message-hidden sfb-custom-message-scroll')
                        .css({ 'opacity': '1', 'visibility': 'visible' });
            }).on('mouseleave', function() {
                var $container = $(this).closest('.sfb-container');
                if (!$container.hasClass('active')) {
                    // După un delay, ascunde mesajul dacă nu este activ
                    setTimeout(function() {
                        if (!$container.hasClass('active')) {
                            $container.find('.sfb-custom-message').addClass('sfb-custom-message-hidden');
                        }
                    }, 500);
                }
            });
            
            // Keyboard navigation pentru meniu
            $('.sfb-item').on('keydown', function(e) {
                if (e.key === 'Escape') {
                    var $container = $(this).closest('.sfb-container');
                    $container.removeClass('active');
                    $container.find('.sfb-cta').removeClass('active').focus();
                }
            });
        },
        
        // 2. Inițializare Custom Message
        initCustomMessage: function() {
            var $messages = $('.sfb-custom-message');
            
            if ($messages.length) {
                // Asigură că sunt vizibile inițial
                $messages.css({
                    'opacity': '1',
                    'visibility': 'visible'
                });
                
                // După 2 secunde, ascunde-le dacă nu sunt hover
                setTimeout(function() {
                    $('.sfb-container:not(.active) .sfb-custom-message').each(function() {
                        var $container = $(this).closest('.sfb-container');
                        if (!$container.hasClass('active')) {
                            $(this).addClass('sfb-custom-message-hidden');
                        }
                    });
                }, 2000);
            }
        },
        
        // 3. Comportament la scroll
        setupScrollBehavior: function() {
            var self = this;
            
            $(window).on('scroll', function() {
                var scrollTop = $(this).scrollTop();
                var $messages = $('.sfb-custom-message');
                
                // Face mesajele transparente când user-ul face scroll
                $messages.addClass('sfb-custom-message-scroll');
                
                // Clear previous timeout
                clearTimeout(self.scrollTimeout);
                
                // După ce s-a oprit scroll-ul, verifică dacă să le readuce
                self.scrollTimeout = setTimeout(function() {
                    // Dacă nu s-a mai făcut scroll în ultimele 500ms
                    var currentScrollTop = $(window).scrollTop();
                    if (currentScrollTop === scrollTop) {
                        // Verifică dacă mouse-ul este deasupra butonului
                        $('.sfb-cta:hover').each(function() {
                            $(this).closest('.sfb-container')
                                   .find('.sfb-custom-message')
                                   .removeClass('sfb-custom-message-scroll');
                        });
                        
                        // Dacă niciun buton nu are hover, ascunde-le
                        if ($('.sfb-cta:hover').length === 0) {
                            $('.sfb-container:not(.active) .sfb-custom-message').addClass('sfb-custom-message-hidden');
                        }
                    }
                }, 500);
                
                self.lastScrollTop = scrollTop;
            });
        },
        
        // 4. Asigură vizibilitatea iconițelor
        ensureIconsVisible: function() {
            // Forțează vizibilitatea pentru toate iconițele
            function forceIconsVisibility() {
                $('.sfb-item i, .scfs-inline-button i, .scfs-single-button i').css({
                    'opacity': '1',
                    'visibility': 'visible',
                    'display': 'inline-flex'
                });
            }
            
            // Execută imediat
            forceIconsVisibility();
            
            // Verifică periodic
            setInterval(forceIconsVisibility, 2000);
            
            // Încarcă Font Awesome dacă nu este prezent
            if (!$('link[href*="font-awesome"], link[href*="fontawesome"]').length) {
                var link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
                link.crossOrigin = 'anonymous';
                document.head.appendChild(link);
            }
        }
    };
    
    // Inițializare când DOM-ul este gata
    $(document).ready(function() {
        SCFS.init();
    });
    
})(jQuery);