// Dashboard functionality
const API_BASE_URL = 'http://localhost/whenfresh/api';
let currentShop = null;

// Check authentication
document.addEventListener('DOMContentLoaded', () => {
    const token = localStorage.getItem('token');
    if (!token) {
        window.location.href = './index.html';
        return;
    }

    initializeDashboard();
    setupEventListeners();
});

async function initializeDashboard() {
    currentShop = JSON.parse(localStorage.getItem('user'));
    document.getElementById('shopName').textContent = currentShop.name;
    await fetchItems();
    updateStats();
}

function setupEventListeners() {
    document.getElementById('logoutBtn').addEventListener('click', handleLogout);
    document.getElementById('addItemBtn').addEventListener('click', showAddItemModal);
    document.getElementById('closeItemModal').addEventListener('click', closeItemModal);
    document.getElementById('itemForm').addEventListener('submit', handleItemSubmit);
}

// Items Management
async function fetchItems() {
    try {
        const response = await fetch(`${API_BASE_URL}/shops/${currentShop.id}/items`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        const items = await response.json();
        renderItems(items);
        updateStats(items);
    } catch (error) {
        console.error('Error fetching items:', error);
    }
}

function renderItems(items) {
    const tableBody = document.getElementById('itemsTable');
    tableBody.innerHTML = '';

    items.forEach(item => {
        const row = document.createElement('tr');
        const freshness = calculateFreshness(item.prep_start_time, item.prep_duration);
        
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${item.item_name}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-500">${formatTime(item.prep_start_time)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-500">${item.prep_duration} mins</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getFreshnessClass(freshness.status)}">
                    ${freshness.status}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick="editItem(${item.item_id})" class="text-swedish-blue hover:text-blue-700 mr-3">Edit</button>
                <button onclick="deleteItem(${item.item_id})" class="text-red-600 hover:text-red-700">Delete</button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

function updateStats(items) {
    const totalItems = items.length;
    const freshItems = items.filter(item => calculateFreshness(item.prep_start_time, item.prep_duration).status === 'Fresh').length;
    const upcomingItems = items.filter(item => calculateFreshness(item.prep_start_time, item.prep_duration).status === 'Upcoming').length;

    document.getElementById('totalItems').textContent = totalItems;
    document.getElementById('freshItems').textContent = freshItems;
    document.getElementById('upcomingItems').textContent = upcomingItems;
}

// Modal Management
function showAddItemModal() {
    document.getElementById('modalTitle').textContent = 'Add New Item';
    document.getElementById('itemForm').reset();
    document.getElementById('itemModal').classList.remove('hidden');
}

function closeItemModal() {
    document.getElementById('itemModal').classList.add('hidden');
}

async function handleItemSubmit(e) {
    e.preventDefault();
    e.preventDefault();
    const formData = new FormData(e.target);
    const itemData = {
        shop_id: currentShop.id,
        item_name: formData.get('item_name'),
        prep_start_time: formData.get('prep_start_time'),
        prep_duration: parseInt(formData.get('prep_duration'))
    };

    try {
        const method = formData.get('item_id') ? 'PUT' : 'POST';
        const endpoint = formData.get('item_id') 
            ? `${API_BASE_URL}/items/${formData.get('item_id')}`
            : `${API_BASE_URL}/items`;

        const response = await fetch(endpoint, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify(itemData)
        });

        if (response.ok) {
            closeItemModal();
            await fetchItems();
            showNotification(
                formData.get('item_id') ? 'Item updated successfully' : 'Item added successfully',
                'success'
            );
        } else {
            const error = await response.json();
            showNotification(error.error, 'error');
        }
    } catch (error) {
        showNotification('Failed to save item. Please try again.', 'error');
    }
}

async function editItem(itemId) {
    try {
        const response = await fetch(`${API_BASE_URL}/items/${itemId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        const item = await response.json();

        document.getElementById('modalTitle').textContent = 'Edit Item';
        const form = document.getElementById('itemForm');
        form.elements['item_name'].value = item.item_name;
        form.elements['prep_start_time'].value = item.prep_start_time;
        form.elements['prep_duration'].value = item.prep_duration;
        form.elements['item_id'].value = item.item_id;

        document.getElementById('itemModal').classList.remove('hidden');
    } catch (error) {
        showNotification('Failed to load item details', 'error');
    }
}

async function deleteItem(itemId) {
    if (!confirm('Are you sure you want to delete this item?')) return;

    try {
        const response = await fetch(`${API_BASE_URL}/items/${itemId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });

        if (response.ok) {
            await fetchItems();
            showNotification('Item deleted successfully', 'success');
        } else {
            const error = await response.json();
            showNotification(error.error, 'error');
        }
    } catch (error) {
        showNotification('Failed to delete item', 'error');
    }
}

// Authentication
function handleLogout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = './index.html';
}

// Notifications
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    } text-white`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

// Real-time updates
setInterval(fetchItems, 60000); // Update every minute        