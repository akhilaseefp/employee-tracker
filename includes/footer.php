<?php
// Page footer with JavaScript includes and closing HTML tags
// Shared across all pages for consistent look and feel.

// Close the main content div from header.php
?>
            </main>

            <footer class="bg-light border-top py-3 mt-4">
                <div class="container-fluid px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Demo application.
                        </small>
                        <small class="text-muted">
                            Version 1.0.0
                        </small>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="<?php echo url('assets/js/app.js'); ?>" defer></script>

    <!-- Sidebar toggle script -->
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }
    </script>
</body>
</html>