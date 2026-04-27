(function($) {
    'use strict';
    
    var SCFS = {
        // Variabile globale
        lastScrollTop: 0,
        scrollTimeout: null,
        scrollAnimationFrame: null, // ACEASTA LIPSEA!
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
        
        // 3. Comportament la scroll
        setupScrollBehavior: function() {
            var self = this;
            var $window = $(window);
            var scrollSpeed = 0;
            var lastTimestamp = 0;
            var lastPosition = 0;
            
            function calculateScrollSpeed(currentPos, currentTime) {
                if (lastPosition === 0) {
                    lastPosition = currentPos;
                    lastTimestamp = currentTime;
                    return 0;
                }
                
                var timeDiff = Math.max(16, currentTime - lastTimestamp);
                var posDiff = Math.abs(currentPos - lastPosition);
                var speed = posDiff / timeDiff;
                
                lastPosition = currentPos;
                lastTimestamp = currentTime;
                
                return speed;
            }
            
            function applyTransparentEffect(speed) {
                var $containers = $('.sfb-container');
                var normalizedSpeed = Math.min(1, speed / 0.8);
                
                var opacityValue;
                if (normalizedSpeed < 0.1) {
                    opacityValue = 1;
                } else if (normalizedSpeed < 0.3) {
                    opacityValue = 0.85;
                } else if (normalizedSpeed < 0.6) {
                    opacityValue = 0.65;
                } else {
                    opacityValue = 0.4;
                }
                
                var scaleValue = 1 - (normalizedSpeed * 0.08);
                scaleValue = Math.max(0.94, Math.min(1, scaleValue));
                
                $containers.each(function() {
                    var $container = $(this);
                    
                    if (speed > 0.05) {
                        $container.css({
                            'transition': 'opacity 0.15s cubic-bezier(0.4, 0, 0.2, 1), transform 0.15s cubic-bezier(0.4, 0, 0.2, 1)',
                            'opacity': opacityValue,
                            'transform': 'scale(' + scaleValue + ')'
                        });
                        $container.addClass('sfb-scrolling');
                    }
                    
                    if ($container.hasClass('active')) {
                        $container.removeClass('active');
                    }
                    
                    var $cta = $container.find('.sfb-cta');
                    if ($cta.hasClass('active')) {
                        $cta.removeClass('active');
                    }
                });
            }
            
            function resetEffect() {
                $('.sfb-container').each(function() {
                    var $container = $(this);
                    $container.css({
                        'opacity': '1',
                        'transform': 'scale(1)',
                        'transition': 'opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1), transform 0.4s cubic-bezier(0.4, 0, 0.2, 1)'
                    });
                    $container.removeClass('sfb-scrolling');
                });
                
                setTimeout(function() {
                    $('.sfb-container').css({
                        'transition': '',
                        'transform': ''
                    });
                }, 400);
            }
            
            $window.on('scroll', function() {
                var currentPos = $window.scrollTop();
                var currentTime = Date.now();
                
                scrollSpeed = calculateScrollSpeed(currentPos, currentTime);
                
                if (self.scrollAnimationFrame) {
                    cancelAnimationFrame(self.scrollAnimationFrame);
                }
                
                self.scrollAnimationFrame = requestAnimationFrame(function() {
                    applyTransparentEffect(scrollSpeed);
                });
                
                if (scrollSpeed > 0.05) {
                    $('.sfb-custom-message').addClass('sfb-custom-message-scroll');
                }
                
                clearTimeout(self.scrollTimeout);
                
                self.scrollTimeout = setTimeout(function() {
                    var finalPos = $window.scrollTop();
                    var finalTime = Date.now();
                    var finalSpeed = calculateScrollSpeed(finalPos, finalTime);
                    
                    if (finalSpeed < 0.03) {
                        resetEffect();
                        
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
        },
        
        // 4. Setup hover pulsation (opțional)
        setupHoverPulsation: function() {
            $('.sfb-item').on('mouseenter', function(e) {
                $(this).addClass('sfb-pulsating');
                setTimeout(function() {
                    $(this).removeClass('sfb-pulsating');
                }.bind(this), 300);
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
