// dashboard.js

// State Management
let currentUser = null;
let activeTab = 'shops';

// DOM Elements
const elements = {
    ownerName: document.getElementById('ownerName'),
    logoutBtn: document.getElementById('logoutBtn'),
    addShopBtn: document.getElementById('addShopBtn'),
    shopModal: document.getElementById('shopModal'),
    closeShopModal: document.getElementById('closeShopModal'),
    shopForm: document.getElementById('shopForm'),
    getLocationBtn: document.getElementById('getLocationBtn'),
    shopsGrid: document.getElementById('shopsGrid'),
    shopsTab: document.getElementById('shopsTab'),
    itemsTab: document.getElementById('itemsTab'),
    shopsSection: document.getElementById('shopsSection'),
    itemsSection: document.getElementById('itemsSection'),
    totalShops: document.getElementById('totalShops'),
    activeItems: document.getElementById('activeItems'),
    upcomingItems: document.getElementById('upcomingItems')
};

// Initialize Dashboard
// Define the initializeDashboard function
function initializeDashboard(user) {
    currentUser = user;
    elements.ownerName.textContent = user.name || "User";

    setupEventListeners(); // Set up event listeners for various buttons and actions
    loadInitialData();     // Load the initial data for shops and items
}

// Initialize Dashboard
document.addEventListener("DOMContentLoaded", function() {
    try {
        const user = Auth.getUser();
        if (!user) throw new Error("No user data found");

        console.log("Dashboard initialized with user:", user);
        initializeDashboard(user); // Pass user to initializeDashboard
    } catch (error) {
        console.error("Dashboard initialization error:", error);
        // Redirect to login page or show an error message as necessary
        window.location.href = './index.html'; // Redirect to login if no user data
    }
});

if (localStorage.getItem('token')) {
  console.log('token', localStorage.getItem('token'));
}

if (localStorage.getItem('user')) {
  console.log('user', JSON.parse(localStorage.getItem('user')));
}// Initialize Dashboard
document.addEventListener("DOMContentLoaded", function() {
  try {
    const user = Auth.getUser();
    if (!user) throw new Error("No user data found");

    console.log("Dashboard initialized with user:", user);
    initializeDashboard(user); // Pass user to initializeDashboard

    // Log token and user data here, after initialization
    if (localStorage.getItem('token')) {
      console.log('token', localStorage.getItem('token'));
    }

    if (localStorage.getItem('user')) {
      console.log('user', JSON.parse(localStorage.getItem('user')));
    }
  } catch (error) {
    console.error("Dashboard initialization error:", error);
    // Redirect to login page or show an error message as necessary
    window.location.href = './index.html'; // Redirect to login if no user data
  }
});
// Event Listeners
function setupEventListeners() {
    // Tab switching
    elements.shopsTab.addEventListener('click', () => switchTab('shops'));
    elements.itemsTab.addEventListener('click', () => switchTab('items'));

    // Shop modal
    elements.addShopBtn.addEventListener('click', () => elements.shopModal.classList.remove('hidden'));
    elements.closeShopModal.addEventListener('click', closeShopModal);
    elements.shopForm.addEventListener('submit', handleShopSubmit);
    elements.getLocationBtn.addEventListener('click', getCurrentLocation);

    // Logout
    elements.logoutBtn.addEventListener('click', () => Auth.logout());
}

// Initial Data Loading
async function loadInitialData() {
    UI.showLoading();
    try {
        await loadShops();
        updateStats();
    } catch (error) {
        console.error('Initial data loading error:', error);
        UI.showNotification('Failed to load initial data', 'error');
    } finally {
        UI.hideLoading();
    }
}

// Tab Management
function switchTab(tab) {
    activeTab = tab;
    if (tab === 'shops') {
        elements.shopsTab.className = 'px-3 py-2 text-sm font-medium rounded-md bg-swedish-blue text-white';
        elements.itemsTab.className = 'px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700';
        elements.shopsSection.classList.remove('hidden');
        elements.itemsSection.classList.add('hidden');
        loadShops();
    } else {
        elements.itemsTab.className = 'px-3 py-2 text-sm font-medium rounded-md bg-swedish-blue text-white';
        elements.shopsTab.className = 'px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700';
        elements.itemsSection.classList.remove('hidden');
        elements.shopsSection.classList.add('hidden');
        loadAllItems();
    }
}

// Shop Management
async function loadShops() {
    UI.showLoading();
    try {
        const response = await API.get('/shops');
        if (response.success) {
            renderShops(response.data || []);
            updateStats();
        } else {
            UI.showNotification(response.message || 'Failed to load shops', 'error');
        }
    } catch (error) {
        console.error('Load shops error:', error);
        UI.showNotification('Failed to load shops', 'error');
    } finally {
        UI.hideLoading();
    }
}

function renderShops(shops) {
    elements.shopsGrid.innerHTML = '';
    
    if (shops.length === 0) {
        elements.shopsGrid.innerHTML = `
            <div class="col-span-full text-center py-8 text-gray-500">
                No shops found. Click "Add New Shop" to create one.
            </div>
        `;
        return;
    }
    
    shops.forEach(shop => {
        const shopCard = document.createElement('div');
        shopCard.className = 'bg-white rounded-lg shadow-lg p-6';
        shopCard.innerHTML = `
            <h3 class="text-xl font-semibold text-gray-800 mb-2">${shop.name}</h3>
            <p class="text-gray-600 mb-4">${shop.address}</p>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">${shop.contact_info}</span>
                <div class="space-x-2">
                    <button onclick="window.manageShop(${shop.shop_id})" 
                            class="text-swedish-blue hover:text-blue-700">
                        Manage
                    </button>
                    <button onclick="window.deleteShop(${shop.shop_id})"
                            class="text-red-600 hover:text-red-700">
                        Delete
                    </button>
                </div>
            </div>
        `;
        elements.shopsGrid.appendChild(shopCard);
    });
}

// Shop Form Handling
async function handleShopSubmit(e) {
    e.preventDefault();
    UI.showLoading();

    try {
        const formData = new FormData(e.target);
        const shopData = {
            name: formData.get('name'),
            location: `${formData.get('latitude')},${formData.get('longitude')}`,
            address: formData.get('address'),
            contact_info: formData.get('contact_info')
        };

        const response = await API.post('/shops', shopData);
        
        if (response.success) {
            UI.showNotification('Shop created successfully');
            closeShopModal();
            await loadShops();
        } else {
            UI.showNotification(response.message || 'Failed to create shop', 'error');
        }
    } catch (error) {
        console.error('Create shop error:', error);
        UI.showNotification('Failed to create shop', 'error');
    } finally {
        UI.hideLoading();
    }
}

function closeShopModal() {
    elements.shopModal.classList.add('hidden');
    elements.shopForm.reset();
}

// Item Management
async function loadAllItems() {
    UI.showLoading();
    try {
        const shopsResponse = await API.get('/shops');
        if (!shopsResponse.success) {
            throw new Error('Failed to load shops');
        }

        const shops = shopsResponse.data || [];
        const allItems = await Promise.all(
            shops.map(async shop => {
                const itemsResponse = await API.get(`/shops/${shop.shop_id}/items`);
                if (itemsResponse.success) {
                    return (itemsResponse.data || []).map(item => ({
                        ...item,
                        shop_name: shop.name
                    }));
                }
                return [];
            })
        );

        renderItems(allItems.flat());
        updateStats();
    } catch (error) {
        console.error('Load items error:', error);
        UI.showNotification('Failed to load items', 'error');
    } finally {
        UI.hideLoading();
    }
}

function renderItems(items) {
    elements.itemsSection.innerHTML = `
        <div class="bg-white rounded-lg shadow-lg">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">All Items</h2>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Shop</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Prep Time</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            ${renderItemRows(items)}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

function renderItemRows(items) {
    if (items.length === 0) {
        return `
            <tr>
                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                    No items found
                </td>
            </tr>
        `;
    }

    return items.map(item => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.shop_name}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.item_name}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${UI.formatTime(item.prep_start_time)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.prep_duration} mins</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${UI.getStatusClass(calculateItemStatus(item))}">
                    ${calculateItemStatus(item)}
                </span>
            </td>
        </tr>
    `).join('');
}

// Stats Management
function updateStats() {
    if (elements.totalShops) {
        const totalShops = document.querySelectorAll('#shopsGrid > div').length;
        elements.totalShops.textContent = totalShops;
    }

    const itemRows = document.querySelectorAll('#itemsSection tbody tr');
    if (itemRows.length && elements.activeItems && elements.upcomingItems) {
        const activeItems = Array.from(itemRows).filter(row => 
            row.querySelector('span')?.textContent.trim() === 'Fresh'
        ).length;

        const upcomingItems = Array.from(itemRows).filter(row => 
            row.querySelector('span')?.textContent.trim() === 'Upcoming'
        ).length;

        elements.activeItems.textContent = activeItems;
        elements.upcomingItems.textContent = upcomingItems;
    }
}

// Utility Functions
function getCurrentLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            position => {
                document.getElementsByName('latitude')[0].value = position.coords.latitude;
                document.getElementsByName('longitude')[0].value = position.coords.longitude;
            },
            error => {
                UI.showNotification('Failed to get location: ' + error.message, 'error');
            }
        );
    } else {
        UI.showNotification('Geolocation is not supported by your browser', 'error');
    }
}

function calculateItemStatus(item) {
    const now = new Date();
    const prepTime = new Date();
    const [hours, minutes] = item.prep_start_time.split(':');
    prepTime.setHours(hours, minutes, 0);
    
    const endTime = new Date(prepTime.getTime() + item.prep_duration * 60000);
    
    if (now < prepTime) return 'Upcoming';
    if (now <= endTime) return 'Fresh';
    return 'Ready';
}

// Shop Actions
async function deleteShop(shopId) {
    if (!confirm('Are you sure you want to delete this shop? This action cannot be undone.')) {
        return;
    }

    UI.showLoading();
    try {
        const response = await API.delete(`/shops/${shopId}`);
        if (response.success) {
            UI.showNotification('Shop deleted successfully');
            await loadShops();
        } else {
            UI.showNotification(response.message || 'Failed to delete shop', 'error');
        }
    } catch (error) {
        console.error('Delete shop error:', error);
        UI.showNotification('Failed to delete shop', 'error');
    } finally {
        UI.hideLoading();
    }
}

function manageShop(shopId) {
    window.location.href = `./shop-management.html?id=${shopId}`;
}

// Auto refresh
setInterval(() => {
    if (!document.hidden) {
        if (activeTab === 'shops') {
            loadShops();
        } else {
            loadAllItems();
        }
    }
}, 60000);

// Make functions available globally for onclick handlers
window.manageShop = manageShop;
window.deleteShop = deleteShop;