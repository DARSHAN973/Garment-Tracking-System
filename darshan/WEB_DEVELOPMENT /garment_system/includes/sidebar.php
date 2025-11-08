<!-- Sidebar -->
<div id="sidebar" class="fixed left-0 top-16 h-full w-64 bg-gray-900 text-white shadow-xl custom-scrollbar overflow-y-auto z-40">
  <div class="p-6">
    
    <!-- Main Navigation -->
    <div class="mb-8">
      <h3 class="text-lg font-semibold text-gray-300 mb-4 uppercase tracking-wider">Main Menu</h3>
      <nav class="space-y-2">
        
        <a href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../'; ?>dashboard.php" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group nav-link"
           onclick="showLoading('Loading Dashboard...', 'Fetching system overview and analytics')">
          <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
          </svg>
          <span class="font-medium">Dashboard</span>
        </a>

        <a href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../'; ?>masters/styles.php" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group nav-link"
           onclick="showLoading('Loading Styles...', 'Fetching style master data')">
          <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
          </svg>
          <span class="font-medium">Styles</span>
        </a>

        <a href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../'; ?>ob/ob_list.php" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group nav-link"
           onclick="showLoading('Loading Operation Breakdown...', 'Fetching operation breakdown data')">
          <svg class="w-5 h-5 text-gray-400 group-hover:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
          </svg>
          <span class="font-medium">Operation Breakdown</span>
        </a>

        <a href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../'; ?>tcr/tcr_list.php" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group nav-link"
           onclick="showLoading('Loading Thread Consumption...', 'Fetching thread consumption reports')">
          <svg class="w-5 h-5 text-gray-400 group-hover:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
          </svg>
          <span class="font-medium">Thread Consumption</span>
        </a>

        <a href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../'; ?>method/method_list.php" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group nav-link"
           onclick="showLoading('Loading Method Analysis...', 'Fetching method study data')">
          <svg class="w-5 h-5 text-gray-400 group-hover:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
          <span class="font-medium">Method Analysis</span>
        </a>

        <a href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../'; ?>capacity/capacity_analysis.php" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group nav-link"
           onclick="showLoading('Loading Capacity Analysis...', 'Fetching capacity planning data')">
          <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
          </svg>
          <span class="font-medium">Capacity Analysis</span>
        </a>
      </nav>
    </div>

    <!-- Master Data Section -->
    <div class="border-t border-gray-700 pt-6">
      <h3 class="text-lg font-semibold text-gray-300 mb-4 uppercase tracking-wider">Master Data</h3>
      <nav class="space-y-2">
        
        <a href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../'; ?>masters/machine_types.php" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group nav-link"
           onclick="showLoading('Loading Machine Types...', 'Fetching machine type master data')">
          <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 7.172V5L8 4z"></path>
          </svg>
          <span class="font-medium">Machine Types</span>
        </a>

        <a href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../'; ?>masters/operations.php" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group nav-link"
           onclick="showLoading('Loading Operations...', 'Fetching operation master data')">
          <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h8m2-10.2A2 2 0 0118 3.8V13a2 2 0 01-2 2h-5L9 17.25 7 15H4a2 2 0 01-2-2V3.8A2 2 0 014 3.8h14z"></path>
          </svg>
          <span class="font-medium">Operations</span>
        </a>

        <a href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../'; ?>masters/thread_factors.php" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group nav-link"
           onclick="showLoading('Loading Thread Factors...', 'Fetching thread consumption factors')">
          <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
          </svg>
          <span class="font-medium">Thread Factors</span>
        </a>

        <a href="<?php echo dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../'; ?>masters/gsd_elements.php" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition-colors group nav-link"
           onclick="showLoading('Loading GSD Elements...', 'Fetching motion elements library')">
          <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span class="font-medium">GSD Elements</span>
        </a>
      </nav>
    </div>

  </div>
</div>
