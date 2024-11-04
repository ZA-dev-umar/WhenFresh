// js/auth.js
class Auth {
    static isAuthenticated() {
        return localStorage.getItem('token') !== null;
    }

    static getToken() {
        return localStorage.getItem('token');
    }
    static getUser() {
        const userString = localStorage.getItem('user');
    console.log('User data:', userString); // Add this log statement
    if (!userString) {
        console.warn("User data not found in local storage.");
        return null; // Handle case where user data is absent
    }
    
        try {
            return JSON.parse(userString);
        } catch (error) {
            console.error("Error parsing user data:", error);
            return null; // Handle invalid JSON
        }
    }
    

    static setSession(token, user) {
        if (token && user) {
            localStorage.setItem('token', token);
            localStorage.setItem('user', JSON.stringify(user));
        } else {
            console.warn("Attempted to set session with invalid token or user data.");
        }
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
            
            console.log("Login response data:", data); // Log to inspect response
            console.log("Token:", data.token); // Log to inspect token
            console.log("User:", data.user); // Log to inspect user
            
            if (response.ok && data.token && data.user) {
                this.setSession(data.token, data.user);
                console.log("Token set:", localStorage.getItem('token')); // Log to inspect token storage
                return { success: true };
            }
            return { success: false, error: data.error || "Unexpected response format" };
        } catch (error) {
            console.error("Login failed:", error);
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
console.log('token', Auth.getToken());
console.log('user', Auth.getUser());