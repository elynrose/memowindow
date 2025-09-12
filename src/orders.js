// Orders page functionality
import unifiedAuth from './unified-auth.js';

let currentUser = null;

export function initOrders() {
    // Orders page loaded
    
    // Wait for authentication and then load orders
    waitForAuthAndLoadOrders();
}

async function waitForAuthAndLoadOrders() {
    console.log("🔍 Waiting for authentication...");
    
    // Wait for unified auth to be ready
    await unifiedAuth.waitForAuth();
    
    currentUser = unifiedAuth.getCurrentUser();
    console.log("🔍 Current user:", currentUser ? 'authenticated' : 'null');
    
    if (currentUser) {
        console.log("✅ User authenticated, loading orders");
        await loadOrders();
    } else {
        console.log("❌ User not authenticated, showing login prompt");
        showLoginPrompt();
    }
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
        const response = await fetch(`get_orders.php?user_id=${currentUser.uid}`);
        const data = await response.json();
        
        if (data.success) {
            displayOrders(data.orders);
        } else {
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
        const statusClass = `status-${order.status.toLowerCase()}`;
        const orderNumber = order.stripe_session_id ? order.stripe_session_id.slice(-8) : 'N/A';
        
        return `
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <h2 class="order-title">${order.memory_title || 'Untitled Memory'}</h2>
                        <p style="margin: 4px 0 0 0; color: #6b7280; font-size: 14px;">
                            Order #${orderNumber}
                        </p>
                    </div>
                    <span class="order-status ${statusClass}">
                        ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                    </span>
                </div>
                
                <div class="order-details">
                    <img src="${order.memory_image_url}" 
                         alt="MemoryWave" class="order-image">
                    
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

// Orders functionality initialized
