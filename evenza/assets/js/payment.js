document.addEventListener('DOMContentLoaded', function() {
    console.log('Payment script loaded');
    
    if (typeof paypal === 'undefined') {
        console.error('PayPal SDK not loaded!');
        showPaymentError('PayPal is not available. Please refresh the page or try again later.');
        return;
    }
    
    const paypalContainer = document.getElementById('paypal-button-container');
    if (!paypalContainer) {
        console.log('PayPal button container not found - payment may already be completed');
        return;
    }
    
    const eventId = document.getElementById('paypal-event-id')?.value || 0;
    const packageId = document.getElementById('paypal-package-id')?.value || 0;
    const amount = document.getElementById('paypal-amount')?.value || 0;
    
    console.log('Payment data:', { eventId, packageId, amount });
    
    if (amount <= 0) {
        showPaymentError('Invalid payment amount. Please go back and try again.');
        return;
    }
    
    paypal.Buttons({
        style: {
            layout: 'vertical',
            color: 'gold',
            shape: 'rect',
            label: 'paypal',
            height: 50
        },
        
        createOrder: function(data, actions) {
            console.log('Creating PayPal order...');
            showPaymentStatus('Creating your order...', 'processing');
            
            return fetch('../process/paypal/paypal-create-order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    eventId: parseInt(eventId),
                    packageId: parseInt(packageId),
                    amount: parseFloat(amount)
                })
            })
            .then(function(response) {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(function(text) {
                        console.error('Non-JSON response:', text);
                        throw new Error('Server returned an invalid response. Please try again.');
                    });
                }
                
                if (!response.ok) {
                    return response.json().then(function(err) {
                        throw new Error(err.error || 'Failed to create order');
                    }).catch(function(parseError) {
                        console.error('JSON parse error:', parseError);
                        throw new Error('Failed to process order creation. Please try again.');
                    });
                }
                return response.json().catch(function(parseError) {
                    console.error('JSON parse error:', parseError);
                    throw new Error('Invalid response from server. Please try again.');
                });
            })
            .then(function(orderData) {
                console.log('Order created:', orderData.id);
                hidePaymentStatus();
                return orderData.id;
            })
            .catch(function(error) {
                console.error('Create order error:', error);
                showPaymentError('Failed to create order: ' + error.message);
                throw error;
            });
        },
        
        onApprove: function(data, actions) {
            console.log('Payment approved, capturing...', data);
            showPaymentStatus('Processing your payment...', 'processing');
            
            return fetch('../process/paypal/paypal-capture-order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    orderId: data.orderID
                })
            })
            .then(function(response) {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(function(text) {
                        console.error('Non-JSON response:', text);
                        throw new Error('Server returned an invalid response. Please try again.');
                    });
                }
                
                if (!response.ok) {
                    return response.json().then(function(err) {
                        throw new Error(err.error || 'Failed to capture payment');
                    }).catch(function(parseError) {
                        console.error('JSON parse error:', parseError);
                        throw new Error('Failed to process payment response. Please try again.');
                    });
                }
                return response.json().catch(function(parseError) {
                    console.error('JSON parse error:', parseError);
                    throw new Error('Invalid response from server. Please try again.');
                });
            })
            .then(function(captureData) {
                console.log('=== PAYPAL CAPTURE RESPONSE ===');
                console.log('Full response:', JSON.stringify(captureData, null, 2));
                console.log('Status:', captureData.status);
                console.log('Transaction ID:', captureData.transactionId);
                console.log('Success Token:', captureData.successToken);
                console.log('Redirect URL from server:', captureData.redirectUrl);
                console.log('================================');
                
                if (captureData.status === 'COMPLETED') {
                    showPaymentStatus('Payment successful! Redirecting...', 'success');
                    
                    setTimeout(function() {
                        // ALWAYS construct the redirect URL from scratch with the correct full path
                        // Never trust the server's redirectUrl - construct it manually
                        const protocol = window.location.protocol; // http: or https:
                        const host = window.location.host; // localhost
                        const basePath = '/TravelMates-Evenza-Collaboration/evenza';
                        
                        // Get success token and transaction ID from response
                        const successToken = captureData.successToken || '';
                        const txId = captureData.transactionId || '';
                        
                        // Validate that we have the required data
                        if (!successToken) {
                            console.error('ERROR: Success token is missing from response!');
                            console.error('Response data:', captureData);
                            alert('Error: Missing payment confirmation token. Please contact support.');
                            return;
                        }
                        
                        // Build query parameters
                        const params = [];
                        if (successToken) {
                            params.push('success=' + encodeURIComponent(successToken));
                        }
                        if (txId) {
                            params.push('tx=' + encodeURIComponent(txId));
                        }
                        
                        // Construct the COMPLETE absolute URL
                        let redirectUrl = protocol + '//' + host + basePath + '/user/pages/confirmation.php';
                        if (params.length > 0) {
                            redirectUrl += '?' + params.join('&');
                        }
                        
                        console.log('=== PAYPAL REDIRECT DEBUG ===');
                        console.log('Protocol:', protocol);
                        console.log('Host:', host);
                        console.log('Base Path:', basePath);
                        console.log('Success Token (raw):', successToken);
                        console.log('Success Token (length):', successToken.length);
                        console.log('Transaction ID:', txId);
                        console.log('Query Params:', params.join('&'));
                        console.log('FINAL REDIRECT URL:', redirectUrl);
                        console.log('Current page URL:', window.location.href);
                        console.log('Server redirectUrl (ignored):', captureData.redirectUrl);
                        console.log('============================');
                        
                        // Final safety check - if URL is still wrong, force fix it
                        if (redirectUrl.indexOf('/TravelMates-Evenza-Collaboration/evenza') === -1) {
                            console.error('CRITICAL ERROR: URL construction failed!');
                            // Emergency fallback - construct manually
                            redirectUrl = protocol + '//' + host + '/TravelMates-Evenza-Collaboration/evenza/user/pages/confirmation.php';
                            if (params.length > 0) {
                                redirectUrl += '?' + params.join('&');
                            }
                            console.error('Emergency fixed URL:', redirectUrl);
                        }
                        
                        // Use replace to prevent back button issues
                        console.log('Executing redirect to:', redirectUrl);
                        window.location.replace(redirectUrl);
                    }, 1000);
                } else {
                    throw new Error('Payment was not completed');
                }
            })
            .catch(function(error) {
                console.error('Capture error:', error);
                showPaymentError('Payment processing failed: ' + error.message);
            });
        },
        
        onCancel: function(data) {
            console.log('Payment cancelled by user');
            showPaymentStatus('Payment was cancelled. You can try again when ready.', 'cancelled');
            
            setTimeout(function() {
                hidePaymentStatus();
            }, 5000);
        },
        
        onError: function(err) {
            console.error('PayPal error:', err);
            showPaymentError('An error occurred with PayPal. Please try again.');
        }
        
    }).render('#paypal-button-container')
    .then(function() {
        console.log('PayPal buttons rendered successfully');
    })
    .catch(function(error) {
        console.error('Failed to render PayPal buttons:', error);
        showPaymentError('Failed to load PayPal. Please refresh the page.');
    });
});

function showPaymentStatus(message, type) {
    let statusContainer = document.getElementById('statusMessages');
    
    if (!statusContainer) {
        const paymentSection = document.querySelector('.payment-button-section');
        if (paymentSection) {
            statusContainer = document.createElement('div');
            statusContainer.id = 'statusMessages';
            statusContainer.className = 'mt-4';
            paymentSection.parentNode.insertBefore(statusContainer, paymentSection.nextSibling);
        } else {
            return;
        }
    }
    
    let iconClass = '';
    let statusClass = '';
    
    switch(type) {
        case 'processing':
            iconClass = 'spinner-border spinner-border-sm';
            statusClass = 'status-processing';
            break;
        case 'success':
            iconClass = 'fas fa-check-circle';
            statusClass = 'status-success';
            break;
        case 'cancelled':
            iconClass = 'fas fa-info-circle';
            statusClass = 'status-info';
            break;
        default:
            iconClass = 'fas fa-info-circle';
            statusClass = 'status-info';
    }
    
    statusContainer.innerHTML = `
        <div class="status-message ${statusClass}">
            <div class="status-content d-flex align-items-center">
                <span class="${iconClass} me-3"></span>
                <div>
                    <p class="mb-0">${message}</p>
                </div>
            </div>
        </div>
    `;
    
    statusContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function showPaymentError(message) {
    let statusContainer = document.getElementById('statusMessages');
    
    if (!statusContainer) {
        const paymentSection = document.querySelector('.payment-button-section');
        if (paymentSection) {
            statusContainer = document.createElement('div');
            statusContainer.id = 'statusMessages';
            statusContainer.className = 'mt-4';
            paymentSection.parentNode.insertBefore(statusContainer, paymentSection.nextSibling);
        } else {
            if (typeof showCustomModal === 'function') {
                showCustomModal(message, 'error', 'Payment Error');
            } else {
                alert(message);
            }
            return;
        }
    }
    
    statusContainer.innerHTML = `
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            ${message}
        </div>
    `;
    
    statusContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function hidePaymentStatus() {
    const statusContainer = document.getElementById('statusMessages');
    if (statusContainer) {
        statusContainer.innerHTML = '';
    }
}
