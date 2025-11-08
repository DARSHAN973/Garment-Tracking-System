/**
 * Comprehensive Loading Screen System
 * Handles all types of loading states across the application
 */

// Initialize loading immediately when script loads
(function() {
    // Create loading elements immediately
    createGlobalLoader();
    createPageLoader();
    
    // Show initial page load indicator
    showPageLoader();
    
    // Hide page loader after DOM is ready and a short delay
    const hidePageLoaderWhenReady = () => {
        if (document.readyState === 'complete') {
            setTimeout(() => {
                hidePageLoader();
                hideLoading();
            }, 300);
        } else {
            setTimeout(hidePageLoaderWhenReady, 100);
        }
    };
    hidePageLoaderWhenReady();
})();

function createGlobalLoader() {
    if (document.getElementById('globalLoader')) return;
    
    const loader = document.createElement('div');
    loader.id = 'globalLoader';
    loader.className = 'loading-overlay';
    loader.innerHTML = `
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Processing...</div>
            <div class="loading-subtext">Please wait while we process your request</div>
        </div>
    `;
    document.body.appendChild(loader);
}

function createPageLoader() {
    if (document.getElementById('pageLoader')) return;
    
    const loader = document.createElement('div');
    loader.id = 'pageLoader';
    loader.className = 'page-loading show'; // Show by default
    document.body.appendChild(loader);
}

// Global functions for easy access
window.showLoading = function(text = 'Processing...', subtext = 'Please wait while we process your request') {
    const loader = document.getElementById('globalLoader');
    if (loader) {
        const textEl = loader.querySelector('.loading-text');
        const subtextEl = loader.querySelector('.loading-subtext');
        if (textEl) textEl.textContent = text;
        if (subtextEl) subtextEl.textContent = subtext;
        loader.classList.add('show');
    }
};

window.hideLoading = function() {
    const loader = document.getElementById('globalLoader');
    if (loader) {
        loader.classList.remove('show');
    }
};

function showPageLoader() {
    const loader = document.getElementById('pageLoader');
    if (loader) {
        loader.classList.add('show');
    }
}

function hidePageLoader() {
    const loader = document.getElementById('pageLoader');
    if (loader) {
        loader.classList.remove('show');
    }
}

// Enhanced Loading Manager Class
class LoadingManager {
    constructor() {
        this.isLoading = false;
        this.init();
    }

    init() {
        this.attachAllEventListeners();
        this.setupMutationObserver();
    }

    // Comprehensive event listeners for ALL possible interactions
    attachAllEventListeners() {
        // 1. ALL LINK CLICKS (navigation, tabs, etc.)
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link && this.shouldShowLoadingForLink(link)) {
                const linkText = link.textContent.trim();
                showLoading(`Loading ${linkText}...`, 'Please wait while we load the content');
            }
        });

        // 2. ALL FORM SUBMISSIONS
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.tagName === 'FORM') {
                this.handleFormSubmission(form);
            }
        });

        // 3. ALL BUTTON CLICKS
        document.addEventListener('click', (e) => {
            const button = e.target.closest('button');
            if (button && !button.disabled) {
                this.handleButtonClick(button);
            }
        });

        // 4. SIDEBAR NAVIGATION
        document.addEventListener('click', (e) => {
            const sidebarLink = e.target.closest('.sidebar a, #sidebar a, nav a');
            if (sidebarLink) {
                const linkText = sidebarLink.textContent.trim();
                showLoading(`Loading ${linkText}...`, 'Switching to the selected section');
            }
        });

        // 5. MODAL OPERATIONS
        document.addEventListener('click', (e) => {
            if (e.target.matches('[onclick*="Modal"], [onclick*="modal"]') || 
                e.target.closest('[onclick*="Modal"], [onclick*="modal"]')) {
                showLoading('Loading Form...', 'Preparing the form interface');
                setTimeout(hideLoading, 500); // Hide after modal opens
            }
        });

        // 6. SEARCH OPERATIONS
        document.addEventListener('input', (e) => {
            if (e.target.matches('input[name="search"]')) {
                // Show loading for search as user types (debounced)
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    if (e.target.value.length > 2) {
                        showLoading('Searching...', 'Looking for matching results');
                        setTimeout(hideLoading, 800);
                    }
                }, 500);
            }
        });

        // 7. PAGINATION CLICKS
        document.addEventListener('click', (e) => {
            if (e.target.closest('[aria-label="Pagination"] a, .pagination a')) {
                showLoading('Loading Page...', 'Fetching the requested page');
            }
        });

        // 8. TABLE OPERATIONS
        document.addEventListener('click', (e) => {
            if (e.target.matches('.table-action, [class*="edit"], [class*="delete"], [class*="view"]')) {
                const action = e.target.textContent.trim() || e.target.title || 'Action';
                showLoading(`Processing ${action}...`, 'Performing the requested operation');
            }
        });
    }

    shouldShowLoadingForLink(link) {
        // Skip certain links
        if (!link.href || 
            link.href.includes('#') || 
            link.href.includes('javascript:') ||
            link.href.includes('mailto:') ||
            link.href.includes('tel:') ||
            link.hostname !== window.location.hostname ||
            link.hasAttribute('data-no-loading') ||
            link.target === '_blank') {
            return false;
        }
        return true;
    }

    handleFormSubmission(form) {
        const action = form.querySelector('input[name="action"]')?.value || '';
        const method = form.method.toUpperCase();
        
        let loadingText = 'Processing...';
        let subText = 'Please wait while we process your request';

        // Determine loading message based on action
        switch(action.toLowerCase()) {
            case 'create':
                loadingText = 'Creating Record...';
                subText = 'Adding new item to the system';
                break;
            case 'update':
                loadingText = 'Updating Record...';
                subText = 'Saving your changes';
                break;
            case 'delete':
                loadingText = 'Deleting Record...';
                subText = 'Removing item from the system';
                break;
            case 'search':
                loadingText = 'Searching...';
                subText = 'Finding matching results';
                break;
            default:
                if (method === 'POST') {
                    loadingText = 'Submitting Form...';
                    subText = 'Processing your submission';
                } else {
                    loadingText = 'Loading Results...';
                    subText = 'Fetching the requested data';
                }
        }

        showLoading(loadingText, subText);

        // Add loading state to submit button
        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn) {
            this.buttonLoading(submitBtn, true);
        }
    }

    handleButtonClick(button) {
        const buttonText = button.textContent.trim();
        const onclick = button.getAttribute('onclick') || '';
        
        // Skip certain buttons
        if (button.type === 'button' && !onclick) return;
        
        if (onclick.includes('delete') || onclick.includes('Delete')) {
            showLoading('Preparing Delete...', 'Loading delete confirmation');
            setTimeout(hideLoading, 500);
        } else if (onclick.includes('edit') || onclick.includes('Edit')) {
            showLoading('Loading Editor...', 'Preparing the edit form');
            setTimeout(hideLoading, 500);
        } else if (onclick.includes('create') || onclick.includes('Create') || onclick.includes('Add')) {
            showLoading('Loading Form...', 'Preparing the creation form');
            setTimeout(hideLoading, 500);
        }
    }

    buttonLoading(button, loading = true) {
        if (loading) {
            button.classList.add('loading');
            button.disabled = true;
            if (!button.hasAttribute('data-original-text')) {
                button.setAttribute('data-original-text', button.innerHTML);
            }
        } else {
            button.classList.remove('loading');
            button.disabled = false;
            const originalText = button.getAttribute('data-original-text');
            if (originalText) {
                button.innerHTML = originalText;
                button.removeAttribute('data-original-text');
            }
        }
    }

    // Monitor DOM changes to attach listeners to new elements
    setupMutationObserver() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            // Re-attach listeners to new elements
                            this.attachToNewElements(node);
                        }
                    });
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    attachToNewElements(element) {
        // Attach listeners to dynamically added forms, buttons, links
        const forms = element.querySelectorAll('form');
        const buttons = element.querySelectorAll('button');
        const links = element.querySelectorAll('a');

        forms.forEach(form => {
            form.addEventListener('submit', (e) => this.handleFormSubmission(e.target));
        });

        buttons.forEach(button => {
            button.addEventListener('click', (e) => this.handleButtonClick(e.target));
        });

        links.forEach(link => {
            if (this.shouldShowLoadingForLink(link)) {
                link.addEventListener('click', () => {
                    const linkText = link.textContent.trim();
                    showLoading(`Loading ${linkText}...`, 'Please wait while we load the content');
                });
            }
        });
    }
}

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.loadingManager = new LoadingManager();
    
    // Hide page loader after a short delay to ensure page is loaded
    setTimeout(() => {
        hidePageLoader();
    }, 500);
});

// Hide page loader on window load
window.addEventListener('load', () => {
    setTimeout(() => {
        hidePageLoader();
        hideLoading(); // Hide any remaining loaders
    }, 300);
});

// Show page loader on page unload/navigation
window.addEventListener('beforeunload', () => {
    showPageLoader();
});

// Additional utility functions
window.tableLoading = function(table, loading = true) {
    if (loading) {
        table.classList.add('table-loading');
    } else {
        table.classList.remove('table-loading');
    }
};

window.buttonLoading = function(button, loading = true) {
    if (window.loadingManager) {
        window.loadingManager.buttonLoading(button, loading);
    }
};