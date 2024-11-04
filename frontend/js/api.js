// js/api.js
class API {
    static async request(endpoint, options = {}) {
        const token = Auth.getToken();
        const headers = {
            'Content-Type': 'application/json',
            ...(token && { 'Authorization': `Bearer ${token}` }),
            ...options.headers
        };

        try {
            const response = await fetch(`${config.API_BASE_URL}${endpoint}`, {
                ...options,
                headers
            });
            const data = await response.json();
            
            if (response.ok) {
                return { success: true, data };
            }
            return { success: false, error: data.error };
        } catch (error) {
            return { success: false, error: 'Request failed' };
        }
    }

    static getNearbyShops(lat, lng, radius = 5) {
        return this.request(`/shops/nearby?lat=${lat}&lng=${lng}&radius=${radius}`);
    }

    static getShopItems(shopId) {
        return this.request(`/shops/${shopId}/items`);
    }

    static createItem(itemData) {
        return this.request('/items', {
            method: 'POST',
            body: JSON.stringify(itemData)
        });
    }

    static updateItem(itemId, itemData) {
        return this.request(`/items/${itemId}`, {
            method: 'PUT',
            body: JSON.stringify(itemData)
        });
    }

    static deleteItem(itemId) {
        return this.request(`/items/${itemId}`, {
            method: 'DELETE'
        });
    }
}
