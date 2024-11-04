// Configuration
const API_BASE_URL = 'http://localhost/whenfresh/api';
let map;
let markers = [];

// Initialize Mapbox
mapboxgl.accessToken = 'YOUR_MAPBOX_TOKEN';

// DOM Elements
const loginModal = document.getElementById('loginModal');
const shopLoginBtn = document.getElementById('shopLoginBtn');
const closeLoginModal = document.getElementById('closeLoginModal');
const loginForm = document.getElementById('loginForm');
const productsGrid = document.getElementById('productsGrid');

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    initializeMap();
    fetchNearbyShops();
    setupEventListeners();
});

function setupEventListeners() {
    shopLoginBtn.addEventListener('click', () => loginModal.classList.remove('hidden'));
    closeLoginModal.addEventListener('click', () => loginModal.classList.add('hidden'));
    loginForm.addEventListener('submit', handleLogin);
}

// Map Functions
function initializeMap() {
    map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/light-v10',
        center: [-74.0060, 40.7128],
        zoom: 12
    });

    map.addControl(new mapboxgl.NavigationControl());
    map.addControl(new mapboxgl.GeolocateControl({
        positionOptions: {
            enableHighAccuracy: true
        },
        trackUserLocation: true
    }));
}

// API Functions
async function fetchNearbyShops() {
    try {
        const response = await fetch(`${API_BASE_URL}/shops/nearby?lat=40.7128&lng=-74.0060&radius=5`);
        const shops = await response.json();
        
        shops.forEach(shop => {
            addShopMarker(shop);
            fetchShopItems(shop.shop_id);
        });
    } catch (error) {
        console.error('Error fetching shops:', error);
    }
}

async function fetchShopItems(shopId) {
    try {
        const response = await fetch(`${API_BASE_URL}/shops/${shopId}/items`);
        const items = await response.json();
        items.forEach(item => addProductCard(item));
    } catch (error) {
        console.error('Error fetching items:', error);
    }
}

function addShopMarker(shop) {
    const [lng, lat] = shop.location.split(',');
    
    const marker = new mapboxgl.Marker()
        .setLngLat([parseFloat(lng), parseFloat(lat)])
        .setPopup(new mapboxgl.Popup().setHTML(`
            <div class="p-2">
                <h3 class="font-semibold">${shop.name}</h3>
                <p class="text-sm text-gray-600">${shop.address}</p>
            </div>
        `))
        .addTo(map);
    
    markers.push(marker);
}

function addProductCard(item) {
    const freshness = calculateFreshness(item.prep_start_time, item.prep_duration);
    const card = document.createElement('div');
    card.className = 'bg-white rounded-lg shadow-lg overflow-hidden';
    card.innerHTML = `
        <div class="p-6">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-xl font-semibold text-gray-800">${item.item_name}</h3>
                <span class="px-3 py-1 rounded-full text-sm ${getFreshnessClass(freshness.status)}">
                    ${freshness.status}
                </span>
            </div>
            <p class="text-gray-600 mb-4">${item.shop_name}</p>
            <div class="space-y-2">
                <p class="text-sm text-gray-500">
                    <span class="font-medium">Prep Time:</span> ${formatTime(item.prep_start_time)}
                </p>
                <p class="text-sm text-gray-500">
                    <span class="font-medium">Duration:</span> ${item.prep_duration} minutes
                </p>
                ${freshness.countdown ? `
                <div class="fresh-countdown text-sm font-medium text-swedish-blue">
                    Ready in: ${freshness.countdown}
                </div>
                ` : ''}
            </div>
        </div>
    `;
    productsGrid.appendChild(card);
}

// Utility Functions
function calculateFreshness(prepTime, duration) {
    const now = new Date();
    const prepDateTime = new Date();
    const [hours, minutes] = prepTime.split(':');
    prepDateTime.setHours(hours, minutes, 0);
    
    const endTime = new Date(prepDateTime.getTime() + duration * 60000);
    
    if (now < prepDateTime) {
        const diff = prepDateTime - now;
        return {
            status: 'Upcoming',
            countdown: formatCountdown(diff)
        };
    } else if (now <= endTime) {
        const diff = endTime - now;
        return {
            status: 'Fresh',
            countdown: formatCountdown(diff)
        };
    } else {
        return {
            status: 'Ready',
            countdown: null
        };
    }
}

function formatCountdown(ms) {
    const minutes = Math.floor(ms / 60000);
    const seconds = Math.floor((ms % 60000) / 1000);
    return `${minutes}m ${seconds}s`;
}

function formatTime(time) {
    return new Date(`2000-01-01T${time}`).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getFreshnessClass(status) {
    switch (status) {
        case 'Fresh':
            return 'bg-green-100 text-green-800';
        case 'Upcoming':
            return 'bg-blue-100 text-blue-800';
        case 'Ready':
            return 'bg-yellow-100 text-yellow-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

// Authentication Functions
async function handleLogin(e) {
    e.preventDefault();
    const email = loginForm.querySelector('input[type="email"]').value;
    const password = loginForm.querySelector('input[type="password"]').value;

    try {
        const response = await fetch(`${API_BASE_URL}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (response.ok) {
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            window.location.href = './dashboard.html';
        } else {
            showError(data.error);
        }
    } catch (error) {
        showError('Login failed. Please try again.');
    }
}

// UI Utility Functions
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4';
    errorDiv.innerHTML = `
        <span class="block sm:inline">${message}</span>
        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
            <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
            </svg>
        </span>
    `;
    loginForm.insertBefore(errorDiv, loginForm.firstChild);
    setTimeout(() => errorDiv.remove(), 5000);
}

// Real-time Updates
function initializeRealTimeUpdates() {
    setInterval(updateFreshness, 1000);
}

function updateFreshness() {
    const products = document.querySelectorAll('.fresh-countdown');
    products.forEach(product => {
        const prepTime = product.dataset.prepTime;
        const duration = parseInt(product.dataset.duration);
        const freshness = calculateFreshness(prepTime, duration);
        if (freshness.countdown) {
            product.textContent = `Ready in: ${freshness.countdown}`;
        } else {
            product.remove();
        }
    });
}

// Initialize everything
initializeRealTimeUpdates();