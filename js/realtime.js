/**
 * Real-Time Notification System
 * Uses Server-Sent Events (SSE) with fallback to polling
 */

class RealtimeNotifications {
    constructor(options = {}) {
        this.options = {
            sseEndpoint: '/api/sse.php',
            pollingEndpoint: '/api/notifications.php',
            pollingInterval: 30000, // 30 seconds
            reconnectDelay: 5000,
            maxReconnectAttempts: 5,
            ...options
        };

        this.eventSource = null;
        this.pollingTimer = null;
        this.reconnectAttempts = 0;
        this.isConnected = false;
        this.listeners = new Map();
        this.lastEventId = null;

        this.init();
    }

    init() {
        // Check if SSE is supported
        if (typeof EventSource !== 'undefined') {
            this.connectSSE();
        } else {
            console.warn('SSE not supported, falling back to polling');
            this.startPolling();
        }

        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pause();
            } else {
                this.resume();
            }
        });

        // Handle online/offline
        window.addEventListener('online', () => this.resume());
        window.addEventListener('offline', () => this.pause());
    }

    connectSSE() {
        try {
            const url = new URL(this.options.sseEndpoint, window.location.origin);
            if (this.lastEventId) {
                url.searchParams.set('lastEventId', this.lastEventId);
            }

            this.eventSource = new EventSource(url.toString());

            this.eventSource.onopen = () => {
                console.log('SSE connection established');
                this.isConnected = true;
                this.reconnectAttempts = 0;
                this.emit('connected');
            };

            this.eventSource.onmessage = (event) => {
                this.handleMessage(event);
            };

            this.eventSource.onerror = (error) => {
                console.error('SSE error:', error);
                this.isConnected = false;
                this.eventSource.close();
                this.emit('disconnected');
                this.handleReconnect();
            };

            // Listen for custom event types
            ['notification', 'status_update', 'message'].forEach(eventType => {
                this.eventSource.addEventListener(eventType, (event) => {
                    this.handleMessage(event, eventType);
                });
            });

        } catch (error) {
            console.error('Failed to connect SSE:', error);
            this.startPolling();
        }
    }

    handleMessage(event, eventType = 'message') {
        try {
            const data = JSON.parse(event.data);
            this.lastEventId = event.lastEventId || data.id;

            // Emit to specific listeners
            this.emit(eventType, data);
            this.emit('data', { type: eventType, data });

            // Show notification if configured
            if (data.notification && this.options.showNotifications) {
                this.showNotification(data.notification);
            }

        } catch (error) {
            console.error('Failed to parse SSE message:', error);
        }
    }

    handleReconnect() {
        if (this.reconnectAttempts >= this.options.maxReconnectAttempts) {
            console.warn('Max reconnect attempts reached, falling back to polling');
            this.startPolling();
            return;
        }

        this.reconnectAttempts++;
        const delay = this.options.reconnectDelay * this.reconnectAttempts;

        console.log(`Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts})`);
        
        setTimeout(() => {
            if (!this.isConnected) {
                this.connectSSE();
            }
        }, delay);
    }

    startPolling() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
        }

        // Initial poll
        this.poll();

        // Setup interval
        this.pollingTimer = setInterval(() => {
            this.poll();
        }, this.options.pollingInterval);
    }

    async poll() {
        try {
            const url = new URL(this.options.pollingEndpoint, window.location.origin);
            if (this.lastEventId) {
                url.searchParams.set('since', this.lastEventId);
            }

            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`Polling failed: ${response.status}`);
            }

            const data = await response.json();

            if (data.events && Array.isArray(data.events)) {
                data.events.forEach(event => {
                    this.lastEventId = event.id;
                    this.emit(event.type || 'notification', event.data);
                });
            }

        } catch (error) {
            console.error('Polling error:', error);
            this.emit('error', error);
        }
    }

    pause() {
        if (this.eventSource) {
            this.eventSource.close();
            this.isConnected = false;
        }
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
            this.pollingTimer = null;
        }
    }

    resume() {
        if (!this.isConnected && !this.pollingTimer) {
            if (typeof EventSource !== 'undefined') {
                this.connectSSE();
            } else {
                this.startPolling();
            }
        }
    }

    disconnect() {
        this.pause();
        this.listeners.clear();
    }

    on(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, []);
        }
        this.listeners.get(event).push(callback);
        return () => this.off(event, callback);
    }

    off(event, callback) {
        const callbacks = this.listeners.get(event);
        if (callbacks) {
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        }
    }

    emit(event, data) {
        const callbacks = this.listeners.get(event);
        if (callbacks) {
            callbacks.forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error('Listener error:', error);
                }
            });
        }
    }

    showNotification(notification) {
        // Check notification permission
        if ('Notification' in window) {
            if (Notification.permission === 'granted') {
                new Notification(notification.title, {
                    body: notification.body,
                    icon: notification.icon || '/favicon.ico',
                    tag: notification.tag,
                    requireInteraction: notification.requireInteraction || false
                });
            } else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        this.showNotification(notification);
                    }
                });
            }
        }

        // Show in-app notification
        this.showInAppNotification(notification);
    }

    showInAppNotification(notification) {
        const container = this.getNotificationContainer();
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${notification.type || 'info'}`;
        alert.setAttribute('role', 'alert');
        alert.innerHTML = `
            <div class="d-flex align-items-start gap-3">
                ${notification.icon ? `<i class="${notification.icon} fa-lg"></i>` : ''}
                <div class="flex-grow-1">
                    ${notification.title ? `<strong>${this.escapeHtml(notification.title)}</strong><br>` : ''}
                    ${this.escapeHtml(notification.body)}
                </div>
                <button type="button" class="btn-close" aria-label="Close"></button>
            </div>
        `;

        container.appendChild(alert);

        // Close button
        alert.querySelector('.btn-close').addEventListener('click', () => {
            alert.style.animation = 'slideOutUp 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        });

        // Auto-remove after delay
        if (notification.duration !== 0) {
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.animation = 'slideOutUp 0.3s ease';
                    setTimeout(() => alert.remove(), 300);
                }
            }, notification.duration || 5000);
        }
    }

    getNotificationContainer() {
        let container = document.getElementById('notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 1rem;
                right: 1rem;
                z-index: 9999;
                max-width: 400px;
                width: 100%;
            `;
            document.body.appendChild(container);
        }
        return container;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize realtime notifications when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.realtimeNotifications = new RealtimeNotifications({
            showNotifications: true
        });
    });
} else {
    window.realtimeNotifications = new RealtimeNotifications({
        showNotifications: true
    });
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RealtimeNotifications;
}
