<!-- Setup Instructions Modal -->
<div class="modal fade" id="setupModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-gear me-2"></i>Gmail Setup Instructions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Follow these steps to configure Gmail integration using the Gmail API.
                </div>
                
                <h6 class="fw-bold">1. Create Google Cloud Project</h6>
                <ol class="mb-4">
                    <li>Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>.</li>
                    <li>Create a new project.</li>
                    <li>Go to "APIs & Services" > "Library".</li>
                    <li>Search for "Gmail API" and enable it.</li>
                </ol>

                <h6 class="fw-bold">2. Create OAuth Credentials</h6>
                <ol class="mb-4">
                    <li>Go to "APIs & Services" > "Credentials".</li>
                    <li>Click "Create Credentials" > "OAuth client ID".</li>
                    <li>Select "Web application".</li>
                    <li>Add Authorized Redirect URI: <code><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_URL; ?>/integrations/gmail/googleCallback.php</code></li>
                    <li>Copy the <strong>Client ID</strong> and <strong>Client Secret</strong>.</li>
                </ol>

                <h6 class="fw-bold">3. Configure .env File</h6>
                <p>Add these variables to your <code>.env</code> file:</p>
                <pre class="bg-dark text-light p-3 rounded"><code># Gmail API Configuration
GOOGLE_CLIENT_ID=your_client_id_here
GOOGLE_CLIENT_SECRET=your_client_secret_here
EMAIL_FROM=your-email@gmail.com
EMAIL_FROM_NAME=TravelMates Hotel</code></pre>

                <h6 class="fw-bold">4. Connect Account</h6>
                <p>After saving the .env file, refresh this page and click the "Connect Gmail Account" button that appears.</p>

                <h6 class="fw-bold mt-4">5. Database Setup</h6>
                <p>Run these SQL queries in phpMyAdmin:</p>
                <pre class="bg-dark text-light p-3 rounded"><code>CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    direction ENUM('inbound', 'outbound') NOT NULL,
    from_email VARCHAR(255),
    to_email VARCHAR(255),
    subject VARCHAR(500),
    body TEXT,
    status VARCHAR(50),
    message_id VARCHAR(255),
    error_message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    folder VARCHAR(100) DEFAULT 'INBOX',
    email_date DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_direction (direction),
    INDEX idx_status (status),
    INDEX idx_is_read (is_read)
);

CREATE TABLE IF NOT EXISTS email_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_email_id INT,
    reply_body TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (original_email_id) REFERENCES email_logs(id) ON DELETE CASCADE
);</code></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
