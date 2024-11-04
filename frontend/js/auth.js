// js/auth.js
class Auth {
    static isAuthenticated() {
        return localStorage.getItem('token') !== null;
    }

    static getToken() {
        return localStorage.getItem('token');
    }

    static getUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    }

    static setSession(token, user) {
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
    }

    static clearSession() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
    }

    static async login(email, password) {
        try {
            const response = await fetch(`${config.API_BASE_URL}/auth/login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });
            const data = await response.json();
            
            if (response.ok) {
                this.setSession(data.token, data.user);
                return { success: true };
            }
            return { success: false, error: data.error };
        } catch (error) {
            return { success: false, error: 'Login failed' };
        }
    }

    static async register(shopData) {
        try {
            const response = await fetch(`${config.API_BASE_URL}/auth/register`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(shopData)
            });
            const data = await response.json();
            
            if (response.ok) {
                this.setSession(data.token, data.user);
                return { success: true };
            }
            return { success: false, error: data.error };
        } catch (error) {
            return { success: false, error: 'Registration failed' };
        }
    }
}
