<!-- Footer -->
<footer class="bg-white border-t border-gray-200 py-6 mt-16">
  <div class="max-w-7xl mx-auto px-6">
    <div class="text-center text-gray-600">
      <p class="text-sm">
        © <?php echo date("Y"); ?> Garment Production System. 
        Built for efficient production planning and tracking.
      </p>
    </div>
  </div>
</footer>

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Custom JavaScript -->
<script>
// Global JavaScript functions for the application
$(document).ready(function() {
    // Initialize tooltips and other interactive elements
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert-auto-hide').fadeOut('slow');
    }, 5000);
    
    // Form validation helper
    window.validateForm = function(formId) {
        let isValid = true;
        $('#' + formId + ' [required]').each(function() {
            if ($(this).val() === '') {
                isValid = false;
                $(this).addClass('border-red-500');
            } else {
                $(this).removeClass('border-red-500');
            }
        });
        return isValid;
    };
    
    // Number formatting helper
    window.formatNumber = function(num, decimals = 2) {
        return parseFloat(num).toFixed(decimals);
    };
    
    // Show loading state
    window.showLoading = function(buttonId) {
        $('#' + buttonId).prop('disabled', true).html('<svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Loading...');
    };
    
    // Hide loading state
    window.hideLoading = function(buttonId, originalText) {
        $('#' + buttonId).prop('disabled', false).html(originalText);
    };
});

// SweetAlert2 helper functions
window.showAlert = function(type, title, text) {
    Swal.fire({
        icon: type,
        title: title,
        text: text,
        confirmButtonColor: '#3b82f6'
    });
};

window.showConfirm = function(title, text, callback) {
    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed && callback) {
            callback();
        }
    });
};
</script>

</body>
</html>
