// js/ui.js
class UI {
    static showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        } text-white z-50`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    static formatTime(time) {
        return new Date(`2000-01-01T${time}`).toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    static calculateFreshness(prepTime, duration) {
        const now = new Date();
        const prepDateTime = new Date();
        const [hours, minutes] = prepTime.split(':');
        prepDateTime.setHours(hours, minutes, 0);
        
        const endTime = new Date(prepDateTime.getTime() + duration * 60000);
        
        if (now < prepDateTime) {
            return {
                status: 'Upcoming',
                countdown: this.formatCountdown(prepDateTime - now)
            };
        } else if (now <= endTime) {
            return {
                status: 'Fresh',
                countdown: this.formatCountdown(endTime - now)
            };
        }
        return {
            status: 'Ready',
            countdown: null
        };
    }

    static formatCountdown(ms) {
        const minutes = Math.floor(ms / 60000);
        const seconds = Math.floor((ms % 60000) / 1000);
        return `${minutes}m ${seconds}s`;
    }
}
