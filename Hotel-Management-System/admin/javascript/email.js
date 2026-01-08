// Handle View Email Modal
const viewEmailModal = document.getElementById('viewEmailModal');
if (viewEmailModal) {
    viewEmailModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const emailId = button.getAttribute('data-email-id');
        const from = button.getAttribute('data-email-from');
        const to = button.getAttribute('data-email-to');
        const subject = button.getAttribute('data-email-subject');
        const date = button.getAttribute('data-email-date');
        const direction = button.getAttribute('data-email-direction');

        document.getElementById('viewEmailSubject').textContent = subject || '(No Subject)';
        document.getElementById('viewEmailFrom').textContent = from;
        document.getElementById('viewEmailTo').textContent = to;
        document.getElementById('viewEmailDate').textContent = date;
        document.getElementById('viewEmailBody').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2 text-muted">Loading email content...</p></div>';

        // Fetch email body via AJAX
        fetch('../integrations/gmail/api/get.php?id=' + emailId + '&source=database')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.email) {
                    let body = data.email.body || '';
                    if (body.trim() === '') {
                        document.getElementById('viewEmailBody').innerHTML = '<em class="text-muted">No content</em>';
                    } else {
                        // Check if body contains HTML
                        if (body.includes('<') && body.includes('>')) {
                            document.getElementById('viewEmailBody').innerHTML = body;
                        } else {
                            document.getElementById('viewEmailBody').innerHTML = body.replace(/\n/g, '<br>');
                        }
                    }
                } else {
                    document.getElementById('viewEmailBody').innerHTML = '<em class="text-muted">Failed to load content</em>';
                }
            })
            .catch(err => {
                console.error('Error fetching email:', err);
                document.getElementById('viewEmailBody').innerHTML = '<em class="text-muted">Error loading content</em>';
            });

        // Show/hide reply button based on direction
        const replyBtn = document.getElementById('replyBtn');
        const footer = document.getElementById('viewEmailFooter');

        if (direction === 'inbound') {
            replyBtn.style.display = 'block';
            replyBtn.setAttribute('data-email-id', emailId);
            replyBtn.setAttribute('data-email-from', from);
            replyBtn.setAttribute('data-email-subject', subject);
        } else {
            replyBtn.style.display = 'none';
        }

        // Mark as read via AJAX
        fetch('../integrations/gmail/api/mark_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: emailId })
        });

        // Remove unread styling from clicked item
        button.classList.remove('unread');
        const newBadge = button.querySelector('.badge.bg-primary');
        if (newBadge) newBadge.remove();
    });
}

// Handle Reply Modal
const replyBtn = document.getElementById('replyBtn');
if (replyBtn) {
    replyBtn.addEventListener('click', function () {
        const emailId = this.getAttribute('data-email-id');
        const from = this.getAttribute('data-email-from');
        const subject = this.getAttribute('data-email-subject');

        document.getElementById('replyEmailId').value = emailId;
        document.getElementById('replyToAddress').value = from;
        document.getElementById('replySubject').value = 'Re: ' + (subject || '');
    });
}