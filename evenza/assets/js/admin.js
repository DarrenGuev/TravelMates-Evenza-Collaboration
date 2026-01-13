
function formatCurrency(n) { 
    return parseFloat(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); 
}


function refreshDashboardData() {
    fetch('../../admin/process/fetch/getDashboardStats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const stats = data.data;
                
                const trEl = document.getElementById('totalRevenue');
                const tsEl = document.getElementById('ticketsSold');
                const aeEl = document.getElementById('activeEvents');
                const nuEl = document.getElementById('newUsers');
                const trendEl = document.getElementById('revenueTrend');

                if (trEl) trEl.textContent = formatCurrency(stats.totalRevenue);
                if (tsEl) tsEl.textContent = stats.totalTicketsSold.toLocaleString('en-PH');
                if (aeEl) aeEl.textContent = stats.activeEvents;
                if (nuEl) nuEl.textContent = stats.newUsers;
                if (trendEl) {
                    trendEl.textContent = stats.revenueTrend.toFixed(1) + '%';
                    const trendIndicator = trendEl.closest('.trend-indicator');
                    if (trendIndicator) {
                        if (stats.revenueTrend >= 0) {
                            trendIndicator.classList.add('trend-up');
                            trendIndicator.classList.remove('trend-down');
                            trendIndicator.querySelector('span').textContent = '↗';
                        } else {
                            trendIndicator.classList.add('trend-down');
                            trendIndicator.classList.remove('trend-up');
                            trendIndicator.querySelector('span').textContent = '↘';
                        }
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error fetching dashboard stats:', error);
        });
}

function initSidebarToggle() {
    const toggleButton = document.getElementById('adminSidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    if (!toggleButton || !sidebar) return;
    toggleButton.addEventListener('click', () => {
        sidebar.classList.toggle('d-none');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initSidebarToggle();
});
