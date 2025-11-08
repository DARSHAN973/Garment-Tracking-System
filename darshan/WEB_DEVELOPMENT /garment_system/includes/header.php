
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Garment Production System</title>
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  
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
  
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#eff6ff',
              500: '#3b82f6',
              600: '#2563eb',
              700: '#1d4ed8',
              900: '#1e3a8a'
            }
          }
        }
      }
    }
  </script>
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
