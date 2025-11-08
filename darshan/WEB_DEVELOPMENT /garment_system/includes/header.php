
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Garment Production System</title>
  
  <!-- Favicon -->
  <link rel="icon" href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../'; ?>favicon.svg" type="image/svg+xml">
  
  <!-- Tailwind CSS -->
  <link href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../'; ?>assets/css/tailwind.css" rel="stylesheet">
  <!-- <script src="https://cdn.tailwindcss.com"></script> -->
  
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  
  <!-- Button Border Styles -->
  <link href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../'; ?>assets/css/button-borders.css" rel="stylesheet">
  
  <!-- Loading Screen Styles -->
  <link href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../'; ?>assets/css/loading.css" rel="stylesheet">
  
  <!-- Immediate Loading Script - Runs before DOM ready -->
  <script>
    // Show loading immediately for any user interaction
    (function() {
      // Create loading elements immediately
      function createLoaders() {
        // Global loader
        if (!document.getElementById('globalLoader')) {
          const globalLoader = document.createElement('div');
          globalLoader.id = 'globalLoader';
          globalLoader.className = 'loading-overlay';
          globalLoader.innerHTML = `
            <div class="loading-content">
              <div class="loading-spinner"></div>
              <div class="loading-text">Loading...</div>
              <div class="loading-subtext">Please wait while we process your request</div>
            </div>
          `;
          document.body.appendChild(globalLoader);
        }
        
        // Page loader
        if (!document.getElementById('pageLoader')) {
          const pageLoader = document.createElement('div');
          pageLoader.id = 'pageLoader';
          pageLoader.className = 'page-loading';
          document.body.appendChild(pageLoader);
        }
      }
      
      // Global functions
      window.showLoading = function(text = 'Loading...', subtext = 'Please wait while we process your request') {
        createLoaders();
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
      
      window.showPageLoader = function() {
        createLoaders();
        const loader = document.getElementById('pageLoader');
        if (loader) {
          loader.classList.add('show');
        }
      };
      
      window.hidePageLoader = function() {
        const loader = document.getElementById('pageLoader');
        if (loader) {
          loader.classList.remove('show');
        }
      };
      
      // Immediate event listeners
      document.addEventListener('click', function(e) {
        const target = e.target;
        
        // Links
        const link = target.closest('a');
        if (link && link.href && link.hostname === window.location.hostname && 
            !link.href.includes('#') && !link.href.includes('javascript:') && 
            !link.hasAttribute('data-no-loading')) {
          showLoading('Loading...', 'Please wait while we load the content');
        }
        
        // Buttons with specific actions
        const button = target.closest('button');
        if (button) {
          const onclick = button.getAttribute('onclick') || '';
          const buttonText = button.textContent.trim();
          
          if (onclick.includes('Modal') || onclick.includes('modal')) {
            showLoading('Opening Form...', 'Preparing the interface');
            setTimeout(hideLoading, 500);
          } else if (onclick.includes('delete') || onclick.includes('Delete')) {
            showLoading('Loading...', 'Preparing delete confirmation');
            setTimeout(hideLoading, 300);
          } else if (onclick.includes('edit') || onclick.includes('Edit')) {
            showLoading('Loading Editor...', 'Preparing the edit form');
            setTimeout(hideLoading, 500);
          } else if (button.type === 'submit') {
            showLoading('Submitting...', 'Processing your request');
          }
        }
        
        // Sidebar links
        const sidebarLink = target.closest('#sidebar a, .sidebar a, nav a');
        if (sidebarLink) {
          const linkText = sidebarLink.textContent.trim();
          showLoading(`Loading ${linkText}...`, 'Switching to the selected section');
        }
      });
      
      // Form submissions
      document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.tagName === 'FORM') {
          const action = form.querySelector('input[name="action"]')?.value || '';
          let loadingText = 'Processing...';
          
          switch(action.toLowerCase()) {
            case 'create':
              loadingText = 'Creating Record...';
              break;
            case 'update':
              loadingText = 'Updating Record...';
              break;
            case 'delete':
              loadingText = 'Deleting Record...';
              break;
            case 'search':
              loadingText = 'Searching...';
              break;
          }
          
          showLoading(loadingText, 'Please wait while we process your request');
        }
      });
      
      // Show page loader on navigation
      window.addEventListener('beforeunload', function() {
        showPageLoader();
      });
      
      // Hide loaders when page loads
      window.addEventListener('load', function() {
        setTimeout(function() {
          hidePageLoader();
          hideLoading();
        }, 200);
      });
      
      // Create loaders when DOM is ready
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createLoaders);
      } else {
        createLoaders();
      }
    })();
  </script>
  
  <!-- Custom Styles -->
  <style>
    /* Custom scrollbar for sidebar */
    .custom-scrollbar::-webkit-scrollbar {
      width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
      background: #1f2937;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: #4b5563;
      border-radius: 3px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background: #6b7280;
    }
  </style>
</head>
<body class="bg-gray-50 pt-16">

<!-- Top Navigation Bar -->
<nav class="bg-white shadow-lg border-b border-gray-200 fixed w-full z-50 top-0">
  <div class="px-6 py-4">
    <div class="flex justify-between items-center">
      <!-- Logo -->
      <div class="flex items-center space-x-4">
        <a href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../'; ?>dashboard.php" class="flex items-center space-x-2">
          <div class="bg-primary-500 rounded-lg p-2">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
            </svg>
          </div>
          <span class="text-xl font-bold text-gray-800">Garment Production System</span>
        </a>
      </div>

      <!-- User Menu -->
      <div class="flex items-center space-x-4">
        <?php if (isset($_SESSION['username'])): ?>
        <div class="flex items-center space-x-3">
          <div class="flex items-center space-x-2 text-sm text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            <span class="font-medium"><?php echo $_SESSION['username']; ?></span>
            <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full"><?php echo $_SESSION['role'] ?? 'User'; ?></span>
          </div>
          <a href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../'; ?>auth/logout.php" 
             class="inline-flex items-center space-x-1 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            <span>Logout</span>
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
