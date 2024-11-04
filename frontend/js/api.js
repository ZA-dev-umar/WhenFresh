class API {
    static async request(endpoint, options = {}) {
        const token = Auth.getToken();
        
        try {
            const response = await fetch(`${API_BASE_URL}${endpoint}`, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    ...(token && { 'Authorization': `Bearer ${token}` }),
                    ...options.headers
                }
            });

            const data = await response.json();
            return {
                success: response.ok,
                data: data.data,
                message: data.message,
                status: response.status
            };
        } catch (error) {
            console.error('API Error:', error);
            return {
                success: false,
                message: 'Network error occurred',
                status: 500
            };
        }
    }

    static get(endpoint) {
        return this.request(endpoint);
    }

    static post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    static put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    static delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE'
        });
    }
}