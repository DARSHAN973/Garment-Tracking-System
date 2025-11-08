<?php 
require_once 'config/app_config.php';
require_once 'auth/session_check.php';
include './includes/header.php'; 
?>

<!-- Main Content -->
<div class="min-h-screen bg-gray-50">
    <?php include './includes/sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Welcome Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Welcome to Garment Production System</h1>
                <p class="text-xl text-gray-600">Select a module to get started with production planning</p>
            </div>

            <!-- Module Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <!-- Styles Module -->
                <a href="masters/styles.php" class="group">
                    <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-300 p-8 border-l-4 border-blue-500 hover:border-blue-600">
                        <div class="flex flex-col items-center text-center">
                            <div class="bg-blue-100 rounded-full p-4 mb-4 group-hover:bg-blue-200 transition-colors">
                                <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Styles</h3>
                            <p class="text-gray-600">Manage garment style details and specifications</p>
                        </div>
                    </div>
                </a>

                <!-- Operation Breakdown -->
                <a href="ob/ob_list.php" class="group">
                    <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-300 p-8 border-l-4 border-green-500 hover:border-green-600">
                        <div class="flex flex-col items-center text-center">
                            <div class="bg-green-100 rounded-full p-4 mb-4 group-hover:bg-green-200 transition-colors">
                                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Operation Breakdown</h3>
                            <p class="text-gray-600">Plan operations, efficiency & calculate targets</p>
                        </div>
                    </div>
                </a>

                <!-- Thread Consumption -->
                <a href="tcr/tcr_list.php" class="group">
                    <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-300 p-8 border-l-4 border-purple-500 hover:border-purple-600">
                        <div class="flex flex-col items-center text-center">
                            <div class="bg-purple-100 rounded-full p-4 mb-4 group-hover:bg-purple-200 transition-colors">
                                <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Thread Consumption</h3>
                            <p class="text-gray-600">Calculate thread usage per operation</p>
                        </div>
                    </div>
                </a>

                <!-- Method Analysis -->
                <a href="method/method_list.php" class="group">
                    <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-300 p-8 border-l-4 border-yellow-500 hover:border-yellow-600">
                        <div class="flex flex-col items-center text-center">
                            <div class="bg-yellow-100 rounded-full p-4 mb-4 group-hover:bg-yellow-200 transition-colors">
                                <svg class="w-12 h-12 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Method Analysis</h3>
                            <p class="text-gray-600">Breakdown motion elements & calculate SMV</p>
                        </div>
                    </div>
                </a>

                <!-- Masters -->
                <a href="masters/machine_types.php" class="group">
                    <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-300 p-8 border-l-4 border-indigo-500 hover:border-indigo-600">
                        <div class="flex flex-col items-center text-center">
                            <div class="bg-indigo-100 rounded-full p-4 mb-4 group-hover:bg-indigo-200 transition-colors">
                                <svg class="w-12 h-12 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Master Data</h3>
                            <p class="text-gray-600">Manage machines, operations, factors & GSD</p>
                        </div>
                    </div>
                </a>

                <!-- Import Excel -->
                <a href="imports/excel_upload.php" class="group">
                    <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-300 p-8 border-l-4 border-red-500 hover:border-red-600">
                        <div class="flex flex-col items-center text-center">
                            <div class="bg-red-100 rounded-full p-4 mb-4 group-hover:bg-red-200 transition-colors">
                                <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Import Data</h3>
                            <p class="text-gray-600">Import Excel sheets to system database</p>
                        </div>
                    </div>
                </a>

            </div>
        </div>
        
        <!-- Export Data Section -->
        <div class="bg-gray-50 p-8 rounded-xl">
            <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Export Data to Excel</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <!-- Export Master Data -->
                <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-300 p-6 border-l-4 border-green-500">
                    <div class="flex flex-col">
                        <div class="flex items-center mb-4">
                            <div class="bg-green-100 rounded-full p-3 mr-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Master Data</h3>
                        </div>
                        <div class="space-y-2">
                            <a href="exports/export.php?type=operations" class="block w-full bg-blue-500 hover:bg-blue-600 text-white text-center py-2 px-4 rounded-lg transition-colors">
                                Operations
                            </a>
                            <a href="exports/export.php?type=machine_types" class="block w-full bg-blue-500 hover:bg-blue-600 text-white text-center py-2 px-4 rounded-lg transition-colors">
                                Machine Types
                            </a>
                            <a href="exports/export.php?type=styles" class="block w-full bg-blue-500 hover:bg-blue-600 text-white text-center py-2 px-4 rounded-lg transition-colors">
                                Styles
                            </a>
                            <a href="exports/export.php?type=gsd_elements" class="block w-full bg-blue-500 hover:bg-blue-600 text-white text-center py-2 px-4 rounded-lg transition-colors">
                                GSD Elements
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Export Transaction Data -->
                <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-300 p-6 border-l-4 border-purple-500">
                    <div class="flex flex-col">
                        <div class="flex items-center mb-4">
                            <div class="bg-purple-100 rounded-full p-3 mr-4">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Transaction Data</h3>
                        </div>
                        <div class="space-y-2">
                            <a href="exports/export.php?type=ob_reports" class="block w-full bg-indigo-500 hover:bg-indigo-600 text-white text-center py-2 px-4 rounded-lg transition-colors">
                                OB Reports
                            </a>
                            <a href="exports/export.php?type=tcr_reports" class="block w-full bg-indigo-500 hover:bg-indigo-600 text-white text-center py-2 px-4 rounded-lg transition-colors">
                                TCR Reports
                            </a>
                            <a href="exports/export.php?type=method_analysis" class="block w-full bg-indigo-500 hover:bg-indigo-600 text-white text-center py-2 px-4 rounded-lg transition-colors">
                                Method Analysis
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Export System Data -->
                <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-300 p-6 border-l-4 border-orange-500">
                    <div class="flex flex-col">
                        <div class="flex items-center mb-4">
                            <div class="bg-orange-100 rounded-full p-3 mr-4">
                                <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">System Data</h3>
                        </div>
                        <div class="space-y-2">
                            <a href="exports/export.php?type=activity_logs" class="block w-full bg-gray-500 hover:bg-gray-600 text-white text-center py-2 px-4 rounded-lg transition-colors">
                                Activity Logs
                            </a>
                            <a href="exports/export.php?type=all_data" class="block w-full bg-red-500 hover:bg-red-600 text-white text-center py-2 px-4 rounded-lg transition-colors font-semibold">
                                Complete Backup
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include './includes/footer.php'; ?>