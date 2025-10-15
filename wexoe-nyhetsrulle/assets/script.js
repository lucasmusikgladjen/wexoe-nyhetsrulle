/**
 * Wexoe Nyhetsrulle JavaScript
 */

(function($) {
    'use strict';
    
    class WexoeNewsTicker {
        constructor(element) {
            this.$element = $(element);
            this.$container = this.$element.find('.wexoe-news-container');
            this.$list = this.$element.find('.wexoe-news-list');
            this.$items = this.$element.find('.wexoe-news-item');
            this.$navigation = this.$element.find('.wexoe-news-navigation');
            this.$dots = this.$element.find('.wexoe-news-dots');
            
            this.autoRotate = this.$element.data('auto-rotate') === 'true';
            this.rotateInterval = parseInt(this.$element.data('rotate-interval')) || 5000;
            this.currentIndex = 0;
            this.totalItems = this.$items.length;
            this.visibleItems = 4;
            this.rotateTimer = null;
            this.isPaused = false;
            
            this.init();
        }
        
        init() {
            if (this.totalItems === 0) return;
            
            this.setupNavigation();
            this.setupDots();
            this.setupAutoRotate();
            this.setupHoverPause();
            this.setupKeyboardNavigation();
            this.updateVisibility();
            this.setupLiveUpdate();
        }
        
        setupNavigation() {
            const self = this;
            
            // Create navigation if not exists
            if (this.$navigation.length === 0) {
                const navHTML = `
                    <div class="wexoe-news-navigation">
                        <button class="wexoe-news-nav prev" aria-label="Föregående">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M15 18l-6-6 6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="wexoe-news-dots"></div>
                        <button class="wexoe-news-nav next" aria-label="Nästa">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M9 18l6-6-6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                `;
                this.$container.after(navHTML);
                this.$navigation = this.$element.find('.wexoe-news-navigation');
                this.$dots = this.$element.find('.wexoe-news-dots');
            }
            
            // Previous button
            this.$navigation.on('click', '.prev', function(e) {
                e.preventDefault();
                self.navigate('prev');
            });
            
            // Next button
            this.$navigation.on('click', '.next', function(e) {
                e.preventDefault();
                self.navigate('next');
            });
        }
        
        setupDots() {
            if (this.totalItems <= this.visibleItems) {
                this.$navigation.hide();
                return;
            }
            
            // Create dots for pagination
            const numPages = Math.ceil(this.totalItems / this.visibleItems);
            let dotsHTML = '';
            
            for (let i = 0; i < numPages; i++) {
                dotsHTML += `<span class="wexoe-news-dot" data-index="${i}"></span>`;
            }
            
            this.$dots.html(dotsHTML);
            this.updateDots();
            
            // Dot click handler
            const self = this;
            this.$dots.on('click', '.wexoe-news-dot', function() {
                const index = $(this).data('index');
                self.goToPage(index);
            });
        }
        
        updateDots() {
            const currentPage = Math.floor(this.currentIndex / this.visibleItems);
            this.$dots.find('.wexoe-news-dot')
                .removeClass('active')
                .eq(currentPage)
                .addClass('active');
        }
        
        setupAutoRotate() {
            if (!this.autoRotate || this.totalItems <= this.visibleItems) return;
            
            const self = this;
            this.startRotation();
        }
        
        startRotation() {
            if (!this.autoRotate || this.isPaused) return;
            
            const self = this;
            this.stopRotation();
            
            this.rotateTimer = setInterval(function() {
                self.navigate('next');
            }, this.rotateInterval);
        }
        
        stopRotation() {
            if (this.rotateTimer) {
                clearInterval(this.rotateTimer);
                this.rotateTimer = null;
            }
        }
        
        setupHoverPause() {
            const self = this;
            
            this.$element.on('mouseenter', function() {
                self.isPaused = true;
                self.stopRotation();
            });
            
            this.$element.on('mouseleave', function() {
                self.isPaused = false;
                self.startRotation();
            });
        }
        
        setupKeyboardNavigation() {
            const self = this;
            
            this.$element.attr('tabindex', '0');
            
            this.$element.on('keydown', function(e) {
                switch(e.key) {
                    case 'ArrowLeft':
                        e.preventDefault();
                        self.navigate('prev');
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        self.navigate('next');
                        break;
                    case ' ':
                        e.preventDefault();
                        self.isPaused = !self.isPaused;
                        if (self.isPaused) {
                            self.stopRotation();
                        } else {
                            self.startRotation();
                        }
                        break;
                }
            });
        }
        
        navigate(direction) {
            if (direction === 'next') {
                this.currentIndex += this.visibleItems;
                if (this.currentIndex >= this.totalItems) {
                    this.currentIndex = 0;
                }
            } else {
                this.currentIndex -= this.visibleItems;
                if (this.currentIndex < 0) {
                    this.currentIndex = Math.max(0, this.totalItems - this.visibleItems);
                }
            }
            
            this.updateVisibility();
            this.updateDots();
            
            // Reset rotation timer
            if (this.autoRotate && !this.isPaused) {
                this.startRotation();
            }
        }
        
        goToPage(pageIndex) {
            this.currentIndex = pageIndex * this.visibleItems;
            this.updateVisibility();
            this.updateDots();
            
            // Reset rotation timer
            if (this.autoRotate && !this.isPaused) {
                this.startRotation();
            }
        }
        
        updateVisibility() {
            const self = this;
            const startIndex = this.currentIndex;
            const endIndex = Math.min(startIndex + this.visibleItems, this.totalItems);
            
            // Hide all items first with animation
            this.$items.each(function(index) {
                const $item = $(this);
                
                if (index < startIndex || index >= endIndex) {
                    $item.addClass('fade-out');
                    setTimeout(function() {
                        $item.hide();
                    }, 300);
                } else {
                    $item.removeClass('fade-out').addClass('fade-in').show();
                    setTimeout(function() {
                        $item.removeClass('fade-in');
                    }, 300);
                }
            });
            
            // Update navigation buttons state
            this.updateNavigationState();
        }
        
        updateNavigationState() {
            const $prevBtn = this.$navigation.find('.prev');
            const $nextBtn = this.$navigation.find('.next');
            
            // Update button states
            $prevBtn.prop('disabled', this.currentIndex === 0);
            $nextBtn.prop('disabled', this.currentIndex + this.visibleItems >= this.totalItems);
        }
        
        setupLiveUpdate() {
            const self = this;
            
            // Check for new news every 30 seconds
            setInterval(function() {
                self.checkForUpdates();
            }, 30000);
        }
        
        checkForUpdates() {
            const self = this;
            
            $.ajax({
                url: wexoe_news_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wexoe_get_news',
                    nonce: wexoe_news_ajax.nonce,
                    count: 1,
                    offset: 0
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        const latestNews = response.data[0];
                        const $firstItem = self.$items.first();
                        
                        // Check if this is new news
                        if ($firstItem.data('news-id') != latestNews.id) {
                            self.addNewItem(latestNews);
                        }
                    }
                }
            });
        }
        
        addNewItem(newsData) {
            // Create new item HTML
            const priorityClass = newsData.priority || '';
            const badgeHTML = newsData.priority === 'urgent' 
                ? '<span class="wexoe-news-badge urgent">Brådskande</span>'
                : newsData.priority === 'high'
                ? '<span class="wexoe-news-badge high">Viktigt</span>'
                : '';
            
            const targetAttr = newsData.is_external ? 'target="_blank" rel="noopener"' : '';
            
            const itemHTML = `
                <article class="wexoe-news-item ${priorityClass} new-item" data-news-id="${newsData.id}">
                    <div class="wexoe-news-date">
                        <span class="day">${newsData.date_formatted.split(' ')[0]}</span>
                        <span class="month">${newsData.date_formatted.split(' ')[1]}</span>
                    </div>
                    <div class="wexoe-news-content">
                        <h4 class="wexoe-news-title">
                            <a href="${newsData.link}" ${targetAttr}>${newsData.title}</a>
                        </h4>
                        ${newsData.excerpt ? `<p class="wexoe-news-excerpt">${newsData.excerpt}</p>` : ''}
                        <div class="wexoe-news-meta">
                            <span class="wexoe-news-time">${newsData.time_ago}</span>
                            ${badgeHTML}
                        </div>
                    </div>
                    <div class="wexoe-news-arrow">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M9 18l6-6-6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </article>
            `;
            
            // Add new item with animation
            const $newItem = $(itemHTML);
            $newItem.hide().prependTo(this.$list).slideDown(300);
            
            // Remove oldest item if we exceed limit
            if (this.$items.length >= 10) {
                this.$items.last().slideUp(300, function() {
                    $(this).remove();
                });
            }
            
            // Update references
            this.$items = this.$element.find('.wexoe-news-item');
            this.totalItems = this.$items.length;
            
            // Show notification
            this.showNotification('Ny nyhet publicerad!');
        }
        
        showNotification(message) {
            const notificationHTML = `
                <div class="wexoe-news-notification">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>${message}</span>
                </div>
            `;
            
            const $notification = $(notificationHTML);
            $notification.appendTo('body').fadeIn(300);
            
            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        $('.wexoe-news-ticker').each(function() {
            new WexoeNewsTicker(this);
        });
    });
    
    // Reinitialize on Enfold AJAX page load
    $(window).on('avia_ajax_loaded', function() {
        $('.wexoe-news-ticker').each(function() {
            if (!$(this).data('wexoe-initialized')) {
                new WexoeNewsTicker(this);
                $(this).data('wexoe-initialized', true);
            }
        });
    });
    
})(jQuery);