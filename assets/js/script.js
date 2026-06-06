/**
 * Custom JavaScript for VendorBridge ERP
 */

 document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips or other Bootstrap components if needed
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
