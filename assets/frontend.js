(function($) {
    'use strict';
    
    var SCFS = {
        // Variabile globale
        lastScrollTop: 0,
        scrollTimeout: null,
        scrollAnimationFrame: null,
        scrollStartTime: 0,
        scrollPositions: [],
        
        // Inițializare principală
        init: function() {
            this.setupFloatingButtons();
            this.initCustomMessage();
            this.setupScrollBehavior();
            this.ensureIconsVisible();
            this.setupHoverPulsation();
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
            
            // Hover pentru custom message
            $('.sfb-cta').on('mouseenter', function() {
                var $message = $(this).closest('.sfb-container').find('.sfb-custom-message');
                $message.removeClass('sfb-custom-message-hidden sfb-custom-message-scroll')
                        .css({ 'opacity': '1', 'visibility': 'visible' });
            }).on('mouseleave', function() {
                var $container = $(this).closest('.sfb-container');
                if (!$container.hasClass('active')) {
                    setTimeout(function() {
                        if (!$container.hasClass('active')) {
                            $container.find('.sfb-custom-message').addClass('sfb-custom-message-hidden');
                        }
                    }, 500);
                }
            });
            
            // Keyboard navigation
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
                $messages.css({
                    'opacity': '1',
                    'visibility': 'visible'
                });
                
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
        
        // 3. Comportament la scroll - Efect vizibil și gradual
        setupScrollBehavior: function() {
            var self = this;
            var $window = $(window);
            var scrollSpeed = 0;
            var lastTimestamp = 0;
            var lastPosition = 0;
            
            // Funcție pentru calcularea vitezei de scroll
            function calculateScrollSpeed(currentPos, currentTime) {
                if (lastPosition === 0) {
                    lastPosition = currentPos;
                    lastTimestamp = currentTime;
                    return 0;
                }
                
                var timeDiff = Math.max(16, currentTime - lastTimestamp); // minim 16ms pentru framerate
                var posDiff = Math.abs(currentPos - lastPosition);
                var speed = posDiff / timeDiff; // pixeli per milisecundă
                
                lastPosition = currentPos;
                lastTimestamp = currentTime;
                
                return speed;
            }
            
            // Funcție pentru aplicarea efectului de transparent (mai vizibil)
            function applyTransparentEffect(speed) {
                var $containers = $('.sfb-container');
                
                // Normalize speed: 0-1 (0 = oprit, 1 = scroll foarte rapid)
                // Praguri mai mici pentru a observa efectul mai devreme
                var normalizedSpeed = Math.min(1, speed / 0.8); // 0.8 pixeli/ms = 800 pixeli/secundă
                
                // Calculăm opacitatea - scade mai rapid pentru efect vizibil
                var opacityValue;
                if (normalizedSpeed < 0.1) {
                    opacityValue = 1; // complet opac
                } else if (normalizedSpeed < 0.3) {
                    opacityValue = 0.85; // ușor transparent
                } else if (normalizedSpeed < 0.6) {
                    opacityValue = 0.65; // mediu transparent
                } else {
                    opacityValue = 0.4; // foarte transparent
                }
                
                // Calculăm scala pentru efect de zoom out subtil
                var scaleValue = 1 - (normalizedSpeed * 0.08);
                scaleValue = Math.max(0.94, Math.min(1, scaleValue));
                
                $containers.each(function() {
                    var $container = $(this);
                    
                    // Asigură clasele necesare
                    if (!$container.hasClass('sfb-position-left')) {
                        $container.addClass('sfb-position-left');
                    }
                    if (!$container.hasClass('sfb-animation-slide')) {
                        $container.addClass('sfb-animation-slide');
                    }
                    if (!$container.hasClass('sfb-mobile-enabled')) {
                        $container.addClass('sfb-mobile-enabled');
                    }
                    if (!$container.hasClass('sfb-icons-only')) {
                        $container.addClass('sfb-icons-only');
                    }
                    
                    // Adaugă clasa de transparent
                    if (!$container.hasClass('sfb-transparent-icons')) {
                        $container.addClass('sfb-transparent-icons');
                    }
                    
                    // Aplică efectul vizual
                    if (speed > 0.05) { // Orice mișcare peste 50 pixeli/secundă
                        $container.css({
                            'transition': 'opacity 0.15s cubic-bezier(0.4, 0, 0.2, 1), transform 0.15s cubic-bezier(0.4, 0, 0.2, 1)',
                            'opacity': opacityValue,
                            'transform': 'scale(' + scaleValue + ')'
                        });
                        
                        // Adaugă o clasă temporară pentru debugging vizual
                        $container.addClass('sfb-scrolling');
                    }
                    
                    // Elimină clasele active în timpul scroll-ului
                    if ($container.hasClass('active')) {
                        $container.removeClass('active');
                    }
                    
                    // Elimină clasa active de pe sfb-cta
                    $container.find('.sfb-cta.active').removeClass('active');
                });
            }
            
            // Funcție pentru resetarea efectului
            function resetEffect() {
                $('.sfb-container').each(function() {
                    var $container = $(this);
                    $container.css({
                        'opacity': '1',
                        'transform': 'scale(1)',
                        'transition': 'opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1), transform 0.4s cubic-bezier(0.4, 0, 0.2, 1)'
                    });
                    
                    // Elimină clasa temporară
                    $container.removeClass('sfb-scrolling');
                });
                
                // Elimină stilurile inline după tranziție
                setTimeout(function() {
                    $('.sfb-container').css({
                        'transition': '',
                        'transform': ''
                    });
                }, 400);
            }
            
            // Event handler pentru scroll cu detectare viteză
            $window.on('scroll', function() {
                var currentPos = $window.scrollTop();
                var currentTime = Date.now();
                
                // Calculează viteza curentă
                scrollSpeed = calculateScrollSpeed(currentPos, currentTime);
                
                // Aplică efectul vizual
                if (self.scrollAnimationFrame) {
                    cancelAnimationFrame(self.scrollAnimationFrame);
                }
                
                self.scrollAnimationFrame = requestAnimationFrame(function() {
                    applyTransparentEffect(scrollSpeed);
                });
                
                // Gestionează custom message
                if (scrollSpeed > 0.05) {
                    $('.sfb-custom-message').addClass('sfb-custom-message-scroll');
                }
                
                // Clear previous timeout
                clearTimeout(self.scrollTimeout);
                
                // Reset după ce scroll-ul s-a oprit
                self.scrollTimeout = setTimeout(function() {
                    var finalPos = $window.scrollTop();
                    var finalTime = Date.now();
                    var finalSpeed = calculateScrollSpeed(finalPos, finalTime);
                    
                    // Dacă viteza este foarte mică, considerăm că s-a oprit
                    if (finalSpeed < 0.03) {
                        resetEffect();
                        
                        // Verifică hover pentru custom message
                        $('.sfb-cta:hover').each(function() {
                            $(this).closest('.sfb-container')
                                   .find('.sfb-custom-message')
                                   .removeClass('sfb-custom-message-scroll');
                        });
                        
                        if ($('.sfb-cta:hover').length === 0) {
                            $('.sfb-container:not(.active) .sfb-custom-message').addClass('sfb-custom-message-hidden');
                        }
                    }
                }, 150);
            });
            
            // Opțional: efect la începutul scroll-ului
            $window.on('scrollstart', function() {
                $('.sfb-container').addClass('sfb-scroll-start');
            }).on('scrollend', function() {
                $('.sfb-container').removeClass('sfb-scroll-start');
                resetEffect();
            });
        },
        
        // 4. Setup hover pulsation
        setupHoverPulsation: function() {
            if (!$('#sfb-pulsation-styles').length) {
                $('<style id="sfb-pulsation-styles">\
                    .sfb-item {\
                        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;\
                        position: relative !important;\
                        cursor: pointer !important;\
                    }\
                    .sfb-item:hover {\
                        animation: sfbPulse 0.3s ease-out !important;\
                    }\
                    @keyframes sfbPulse {\
                        0% { transform: scale(1); }\
                        50% { transform: scale(1.03); }\
                        100% { transform: scale(1); }\
                    }\
                    .sfb-transparent-icons .sfb-item:hover {\
                        transform: scale(1.02) !important;\
                        background: transparent !important;\
                        border-color: var(--e-global-color-secondary, #0073aa) !important;\
                    }\
                    .sfb-item:not(.sfb-transparent-icons .sfb-item):hover {\
                        transform: translateY(-2px) !important;\
                    }\
                    .sfb-show-names .sfb-item:hover {\
                        transform: translateX(4px) translateY(-2px) !important;\
                    }\
                    .sfb-item:hover i {\
                        transform: scale(1.05);\
                        transition: transform 0.2s ease;\
                    }\
                    .sfb-item:active {\
                        transform: scale(0.98) !important;\
                    }\
                </style>').appendTo('head');
            }
            
            $('.sfb-item').on('mouseenter', function(e) {
                var $this = $(this);
                $this.addClass('sfb-pulsating');
                setTimeout(function() {
                    $this.removeClass('sfb-pulsating');
                }, 300);
            });
        },
        
        // 5. Asigură vizibilitatea iconițelor
        ensureIconsVisible: function() {
            function forceIconsVisibility() {
                $('.sfb-item i, .scfs-inline-button i, .scfs-single-button i').css({
                    'opacity': '1',
                    'visibility': 'visible',
                    'display': 'inline-flex'
                });
            }
            
            forceIconsVisibility();
            setInterval(forceIconsVisibility, 2000);
            
            if (!$('link[href*="font-awesome"], link[href*="fontawesome"]').length) {
                var link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
                link.crossOrigin = 'anonymous';
                document.head.appendChild(link);
            }
        }
    };
    
    $(document).ready(function() {
        SCFS.init();
    });
    
})(jQuery);
