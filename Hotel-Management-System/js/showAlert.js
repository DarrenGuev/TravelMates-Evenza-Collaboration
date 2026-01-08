function showAlert(message, type = 'info', title = '') {
            const iconMap = {
                'success': 'bi-check-circle-fill',
                'danger': 'bi-x-circle-fill',
                'warning': 'bi-exclamation-triangle-fill',
                'info': 'bi-info-circle-fill'
            };

            const icon = iconMap[type] || iconMap['info'];
            const alertTitle = title ? `<strong>${title}</strong><br>` : '';
            
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" 
                     role="alert" 
                     style="z-index: 9999; min-width: 300px; max-width: 500px;">
                    <i class="bi ${icon} me-2"></i>
                    ${alertTitle}
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;

            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert.position-fixed');
            existingAlerts.forEach(alert => alert.remove());

            // Insert new alert
            document.body.insertAdjacentHTML('afterbegin', alertHtml);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alertElement = document.querySelector('.alert.position-fixed');
                if (alertElement) {
                    const bsAlert = new bootstrap.Alert(alertElement);
                    bsAlert.close();
                }
            }, 5000);
        }