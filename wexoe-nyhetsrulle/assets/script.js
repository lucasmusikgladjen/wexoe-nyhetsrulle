/**
 * Wexoe Nyhetsrulle JavaScript - Förenklad version
 */

(function($) {
    'use strict';
    
    class WexoeNewsTickerCompact {
        constructor(element) {
            this.$element = $(element);
            this.$items = this.$element.find('.wexoe-news-item');
            
            this.init();
        }
        
        init() {
            if (this.$items.length === 0) return;
            
            this.setupHoverEffects();
            this.setupAccessibility();
        }
        
        setupHoverEffects() {
            // Lägg till hover-effekt med fördröjning för bättre UX
            this.$items.on('mouseenter', function() {
                $(this).addClass('is-hovering');
            });
            
            this.$items.on('mouseleave', function() {
                $(this).removeClass('is-hovering');
            });
            
            // Featured-stil hanteras via CSS med klassen is-hovering
        }
        
        setupAccessibility() {
            // Gör items keyboard-navigerbara
            this.$items.each(function() {
                const $item = $(this);
                const $link = $item.find('.wexoe-news-title a');
                
                // Lägg till tabindex för hela item
                $item.attr('tabindex', '0');
                
                // Hantera klick på hela item
                $item.on('click', function(e) {
                    // Om klicket inte är på länken, trigga länk-klick
                    if (!$(e.target).is('a')) {
                        $link[0].click();
                    }
                });
                
                // Hantera Enter-tangent
                $item.on('keypress', function(e) {
                    if (e.which === 13) { // Enter key
                        e.preventDefault();
                        $link[0].click();
                    }
                });
            });
            
            // Lägg till aria-label för featured item
            this.$items.filter('.featured').attr('aria-label', 'Utvald nyhet');
        }
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        $('.wexoe-news-ticker-compact').each(function() {
            new WexoeNewsTickerCompact(this);
        });
    });
    
    // Reinitialize on Enfold AJAX page load
    $(window).on('avia_ajax_loaded', function() {
        $('.wexoe-news-ticker-compact').each(function() {
            if (!$(this).data('wexoe-initialized')) {
                new WexoeNewsTickerCompact(this);
                $(this).data('wexoe-initialized', true);
            }
        });
    });
    
})(jQuery);