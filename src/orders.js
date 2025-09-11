// Orders page functionality
import { getCurrentUser } from './app-auth.js';

let currentUser = null;
let allOrders = []; // Store all orders for filtering

export function initOrders() {
    // Orders page loaded
    
    // Wait for authentication and then load orders
    waitForAuthAndLoadOrders();
}

async function waitForAuthAndLoadOrders() {
    const maxAttempts = 100; // 10 seconds max wait
    let attempts = 0;
    
    console.log("🔍 Waiting for authentication...");
    
    while (attempts < maxAttempts) {
        currentUser = getCurrentUser();
        console.log(`🔍 Attempt ${attempts + 1}: currentUser =`, currentUser ? 'authenticated' : 'null');
        
        if (currentUser) {
            console.log("✅ User authenticated, loading orders");
            await loadOrders();
            return;
        }
        
        // Wait 100ms before trying again
        await new Promise(resolve => setTimeout(resolve, 100));
        attempts++;
    }
    
    // If we get here, authentication timed out
    console.log("❌ Authentication timeout, showing login prompt");
    showLoginPrompt();
}

async function loadOrders() {
    try {
        const container = document.getElementById('ordersContainer');
        if (!container) {
            console.error('Orders container not found');
            return;
        }
        
        // Show loading state
        container.innerHTML = `
            <div class="loading">
                <div class="loading-spinner"></div>
                Loading your orders...
            </div>
        `;
        
        // Fetch orders from server
        const response = await fetch(`get_orders.php`);
        const data = await response.json();
        
        if (data.success) {
            console.log('📦 Orders loaded:', data.orders);
            allOrders = data.orders; // Store all orders
            displayOrders(data.orders);
            initializeSearchAndFilter(); // Initialize search functionality
        } else {
            console.error('❌ Failed to load orders:', data.error);
            showErrorState(data.error || 'Failed to load orders');
        }
        
    } catch (error) {
        console.error('Error loading orders:', error);
        showErrorState('Failed to load orders. Please try again.');
    }
}

function displayOrders(orders) {
    const container = document.getElementById('ordersContainer');
    
    if (orders.length === 0) {
        showEmptyState();
        return;
    }
    
    const ordersHTML = orders.map(order => {
        console.log('🖼️ Processing order image:', order.memory_image_url);
        const statusClass = `status-${order.status.toLowerCase()}`;
        const orderNumber = order.stripe_session_id ? order.stripe_session_id.slice(-8) : 'N/A';
        const statusInfo = getStatusInfo(order.status);
        const orderDate = new Date(order.created_at);
        const lastUpdated = order.updated_at ? new Date(order.updated_at) : orderDate;
        
        return `
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <h2 class="order-title">${order.memory_title || 'Untitled Memory'}</h2>
                        <p style="margin: 4px 0 0 0; color: #6b7280; font-size: 14px;">
                            Order #${orderNumber} • ${orderDate.toLocaleDateString('en-US', { 
                                year: 'numeric', 
                                month: 'short', 
                                day: 'numeric' 
                            })}
                        </p>
                        ${lastUpdated.getTime() !== orderDate.getTime() ? 
                            `<p style="margin: 2px 0 0 0; color: #9ca3af; font-size: 12px;">
                                Last updated: ${lastUpdated.toLocaleDateString('en-US', { 
                                    month: 'short', 
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                })}
                            </p>` : ''
                        }
                    </div>
                    <div style="text-align: right;">
                        <span class="order-status ${statusClass}">
                            <span class="status-icon">${statusInfo.icon}</span>
                            ${statusInfo.label}
                        </span>
                        ${statusInfo.description ? 
                            `<p style="margin: 4px 0 0 0; color: #6b7280; font-size: 11px; text-align: right;">
                                ${statusInfo.description}
                            </p>` : ''
                        }
                    </div>
                </div>
                
                ${getOrderProgress(order.status)}
                
                <div class="order-details">
                    <img src="${order.memory_image_url}" 
                         alt="MemoryWave" class="order-image"
                         onclick="showImageModal('${order.memory_image_url}', '${order.memory_title || 'Untitled Memory'}')"
                         style="cursor: pointer;"
                         onload="console.log('✅ Image loaded:', '${order.memory_image_url}')"
                         onerror="console.error('❌ Image failed to load:', '${order.memory_image_url}'); this.style.border='2px solid red'; this.alt='Image failed to load';">
                    
                    <div class="order-info">
                        <h4>${order.product_name}</h4>
                        <p>Quantity: ${order.quantity}</p>
                        <p>Unit Price: $${parseFloat(order.unit_price).toFixed(2)}</p>
                        ${order.printful_order_id ? `<p>Printful Order: ${order.printful_order_id}</p>` : ''}
                    </div>
                    
                    <div class="order-price">
                        $${parseFloat(order.total_price).toFixed(2)}
                    </div>
                </div>
                
                <div class="order-meta">
                    <span>Ordered: ${new Date(order.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    })}</span>
                    <span>Product: ${order.product_name}</span>
                    ${['pending', 'paid'].includes(order.status) ? 
                        `<button onclick="cancelOrder(${order.id})" class="cancel-btn">
                            Cancel Order
                        </button>` : ''
                    }
                </div>
            </div>
        `;
    }).join('');
    
    container.innerHTML = ordersHTML;
}

function showEmptyState() {
    const container = document.getElementById('ordersContainer');
    container.innerHTML = `
        <div class="order-card empty-state">
            <h3>No Orders Yet</h3>
            <p>You haven't placed any print orders yet.</p>
            <p>Create a MemoryWave and order a beautiful print to get started!</p>
            <a href="app.php" class="create-memory-btn" style="display: inline-block; background: #2a4df5; color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 500; margin-top: 16px;">
                Create MemoryWave
            </a>
        </div>
    `;
}

function showErrorState(errorMessage) {
    const container = document.getElementById('ordersContainer');
    container.innerHTML = `
        <div class="order-card" style="text-align: center; color: #dc2626;">
            <h3>Error Loading Orders</h3>
            <p>${errorMessage}</p>
            <button onclick="loadOrders()" style="background: #2a4df5; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; margin-top: 12px;">
                Try Again
            </button>
        </div>
    `;
}

function showLoginPrompt() {
    const container = document.getElementById('ordersContainer');
    container.innerHTML = `
        <div class="order-card empty-state">
            <h3>Please Sign In</h3>
            <p>You need to be signed in to view your orders.</p>
            <a href="login.php" style="display: inline-block; background: #2a4df5; color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 500; margin-top: 16px;">
                Sign In
            </a>
        </div>
    `;
}

// Global function for canceling orders
window.cancelOrder = async function(orderId) {
    const result = await Swal.fire({
        title: 'Cancel Order?',
        text: 'Are you sure you want to cancel this order? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, cancel it!',
        cancelButtonText: 'Keep order'
    });
    
    if (!result.isConfirmed) {
        return;
    }
    
    try {
        const response = await fetch('cancel_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_id=${orderId}&user_id=${currentUser.uid}&reason=User requested cancellation`
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Order Cancelled!',
                text: 'Your order has been cancelled successfully.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#22c55e'
            });
            await loadOrders(); // Reload orders to show updated status
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Cancellation Failed',
                text: 'Error cancelling order: ' + (data.error || 'Unknown error'),
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc2626'
            });
        }
        
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Error cancelling order. Please try again.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#dc2626'
        });
    }
};

// Image modal functions
window.showImageModal = function(imageUrl, title) {
    document.getElementById('modalImage').src = imageUrl;
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('imageModal').style.display = 'flex';
};

window.closeImageModal = function() {
    document.getElementById('imageModal').style.display = 'none';
};

// Close modal on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeImageModal();
    }
});

// Status information helper function
function getStatusInfo(status) {
    const statusMap = {
        'pending': {
            icon: '⏳',
            label: 'Pending',
            description: 'Payment processing'
        },
        'paid': {
            icon: '✅',
            label: 'Paid',
            description: 'Payment confirmed'
        },
        'processing': {
            icon: '🔄',
            label: 'Processing',
            description: 'Preparing your order'
        },
        'shipped': {
            icon: '📦',
            label: 'Shipped',
            description: 'On its way to you'
        },
        'delivered': {
            icon: '🎉',
            label: 'Delivered',
            description: 'Enjoy your memory!'
        },
        'cancelled': {
            icon: '❌',
            label: 'Cancelled',
            description: 'Order cancelled'
        },
        'refunded': {
            icon: '💰',
            label: 'Refunded',
            description: 'Refund processed'
        }
    };
    
    return statusMap[status.toLowerCase()] || {
        icon: '❓',
        label: status.charAt(0).toUpperCase() + status.slice(1),
        description: ''
    };
}

// Order progress indicator function
function getOrderProgress(status) {
    const steps = [
        { key: 'pending', label: 'Payment', icon: '💳' },
        { key: 'paid', label: 'Confirmed', icon: '✅' },
        { key: 'processing', label: 'Processing', icon: '🔄' },
        { key: 'shipped', label: 'Shipped', icon: '📦' },
        { key: 'delivered', label: 'Delivered', icon: '🎉' }
    ];
    
    const statusIndex = steps.findIndex(step => step.key === status.toLowerCase());
    
    // Don't show progress for cancelled or refunded orders
    if (['cancelled', 'refunded'].includes(status.toLowerCase())) {
        return '';
    }
    
    return `
        <div class="order-progress">
            <div class="progress-steps">
                ${steps.map((step, index) => {
                    let stepClass = '';
                    if (index < statusIndex) {
                        stepClass = 'completed';
                    } else if (index === statusIndex) {
                        stepClass = 'current';
                    }
                    
                    return `
                        <div class="progress-step ${stepClass}">
                            <div class="step-icon">${step.icon}</div>
                            <div class="step-label">${step.label}</div>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;
}

// Search and Filter functionality
function initializeSearchAndFilter() {
    const searchInput = document.getElementById('orderSearch');
    const statusFilter = document.getElementById('statusFilter');
    const dateFilter = document.getElementById('dateFilter');
    const clearFilters = document.getElementById('clearFilters');
    
    if (!searchInput || !statusFilter || !dateFilter || !clearFilters) {
        console.log('Search elements not found, skipping search initialization');
        return;
    }
    
    // Add event listeners
    searchInput.addEventListener('input', filterOrders);
    statusFilter.addEventListener('change', filterOrders);
    dateFilter.addEventListener('change', filterOrders);
    clearFilters.addEventListener('click', clearAllFilters);
    
    console.log('Search and filter functionality initialized');
}

function filterOrders() {
    const searchTerm = document.getElementById('orderSearch')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';
    const dateFilter = document.getElementById('dateFilter')?.value || '';
    
    let filteredOrders = allOrders.filter(order => {
        // Search filter
        const matchesSearch = !searchTerm || 
            order.stripe_session_id?.toLowerCase().includes(searchTerm) ||
            order.memory_title?.toLowerCase().includes(searchTerm) ||
            order.product_name?.toLowerCase().includes(searchTerm);
        
        // Status filter
        const matchesStatus = !statusFilter || order.status === statusFilter;
        
        // Date filter
        const matchesDate = !dateFilter || isDateInRange(order.created_at, dateFilter);
        
        return matchesSearch && matchesStatus && matchesDate;
    });
    
    displayOrders(filteredOrders);
    updateResultsCounter(filteredOrders.length, allOrders.length);
}

function isDateInRange(dateString, range) {
    const orderDate = new Date(dateString);
    const now = new Date();
    
    switch (range) {
        case 'today':
            return orderDate.toDateString() === now.toDateString();
        case 'week':
            const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
            return orderDate >= weekAgo;
        case 'month':
            const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
            return orderDate >= monthAgo;
        case 'quarter':
            const quarterAgo = new Date(now.getTime() - 90 * 24 * 60 * 60 * 1000);
            return orderDate >= quarterAgo;
        case 'year':
            const yearAgo = new Date(now.getTime() - 365 * 24 * 60 * 60 * 1000);
            return orderDate >= yearAgo;
        default:
            return true;
    }
}

function clearAllFilters() {
    document.getElementById('orderSearch').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('dateFilter').value = '';
    filterOrders();
}

function updateResultsCounter(filteredCount, totalCount) {
    const resultsCounter = document.getElementById('resultsCounter');
    const resultsText = document.getElementById('resultsText');
    
    if (resultsCounter && resultsText) {
        if (filteredCount < totalCount) {
            resultsText.textContent = `Showing ${filteredCount} of ${totalCount} orders`;
            resultsCounter.style.display = 'block';
        } else {
            resultsCounter.style.display = 'none';
        }
    }
}

// Orders functionality initialized
