// Custom JavaScript for Employee Work Tracker
// Initializes Bootstrap components and handles UI interactions

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize Bootstrap popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-close temporary flash alerts after 5 seconds (never close permanent content like saved feedback)
    const alertList = [].slice.call(document.querySelectorAll('.alert.alert-dismissible:not(.alert-permanent)'));
    alertList.forEach(function (alertEl) {
        setTimeout(function () {
            try {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
                if (bsAlert) {
                    bsAlert.close();
                }
            } catch (e) {
                // Ignore if already dismissed
            }
        }, 5000);
    });

    // Date validation for task dates
    const dateInputs = document.querySelectorAll('input[type="date"][min]');
    dateInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const minDate = this.getAttribute('min');
            const selectedDate = this.value;
            if (selectedDate && selectedDate < minDate) {
                this.value = minDate;
            }
        });
    });

    // Handle form submission with CSRF protection
    const csrfForms = document.querySelectorAll('form.csrf-protected');
    csrfForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            // CSRF token is handled server-side, but we can add client-side validation
            // for empty required fields to prevent unnecessary server round trips
        });
    });

    // Confirmation modals for dangerous actions
    const confirmActions = document.querySelectorAll('[data-confirm]');
    confirmActions.forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm'))) {
                e.preventDefault();
                return false;
            }
        });
    });

    // Handle responsive table containers
    const tableWrappers = document.querySelectorAll('.table-responsive');
    tableWrappers.forEach(function(wrapper) {
        // Add custom scrollbar styling if needed
    });

    // Focus first input in modals when shown
    const modals = document.querySelectorAll('.modal');
    modals.forEach(function(modal) {
        modal.addEventListener('shown.bs.modal', function () {
            const firstInput = this.querySelector('input[autofocus]');
            if (firstInput) {
                firstInput.focus();
            }
        });
    });

    // Handle dynamic row additions/removals (if any)
    // This would be for more complex forms with repeatable sections

    // Initialize any custom date pickers or enhanced selects
    // For now relying on native date inputs and Bootstrap selects

    // Add smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});