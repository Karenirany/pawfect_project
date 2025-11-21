// js/chatbot.js
class PawsomeChatbot {
    constructor() {
        this.isOpen = false;
        this.isTyping = false;
        this.init();
    }

    init() {
        this.createChatbotHTML();
        this.bindEvents();
        this.loadChatHistory();
    }

    createChatbotHTML() {
        const chatbotHTML = `
            <div class="chatbot-wrapper">
                <button class="chatbot-toggle" id="chatbotToggle" aria-label="Open chat">
                    <i class="fas fa-paw"></i>
                    <span class="notification-dot" id="notificationDot"></span>
                </button>
                <div class="chatbot-container" id="chatbotContainer">
                    <div class="chatbot-header">
                        <div class="chatbot-title">
                            <i class="fas fa-dog"></i>
                            <h3>Pawsome Assistant</h3>
                            <span class="status-indicator online"></span>
                        </div>
                        <button class="chatbot-close" id="chatbotClose" aria-label="Close chat">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="chatbot-messages" id="chatbotMessages">
                        <div class="welcome-message">
                            <div class="message bot">
                                <div class="message-text">
                                    <strong>🐾 Welcome to Pawsome Assistant!</strong><br>
                                    I'm here to help you learn about dog adoption processes on our educational platform. 
                                    Ask me about adoption steps, available dogs, or how to use our website!
                                </div>
                                <div class="message-time">Just now</div>
                            </div>
                        </div>
                    </div>
                    <div class="chatbot-typing" id="chatbotTyping">
                        <div class="typing-indicator">
                            <span>Pawsome Assistant is typing</span>
                            <div class="typing-dots">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                    </div>
                    <div class="chatbot-input">
                        <form id="chatbotForm">
                            <div class="input-container">
                                <input type="text" id="chatbotInput" placeholder="Ask about adoption..." required>
                                <button type="submit" id="chatbotSend" aria-label="Send message">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                        <div class="chatbot-disclaimer">
                            <small>🤖 Educational AI Assistant - This is a learning demonstration</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', chatbotHTML);
        
        this.elements = {
            toggle: document.getElementById('chatbotToggle'),
            container: document.getElementById('chatbotContainer'),
            close: document.getElementById('chatbotClose'),
            messages: document.getElementById('chatbotMessages'),
            typing: document.getElementById('chatbotTyping'),
            form: document.getElementById('chatbotForm'),
            input: document.getElementById('chatbotInput'),
            send: document.getElementById('chatbotSend'),
            notificationDot: document.getElementById('notificationDot')
        };
    }

    bindEvents() {
        this.elements.toggle.addEventListener('click', () => this.toggleChat());
        this.elements.close.addEventListener('click', () => this.closeChat());
        this.elements.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Input auto-resize and enter key support
        this.elements.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.elements.form.dispatchEvent(new Event('submit'));
            }
        });
        
        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (this.isOpen && !e.target.closest('.chatbot-wrapper')) {
                this.closeChat();
            }
        });
        
        // Handle escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeChat();
            }
        });
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        const message = this.elements.input.value.trim();
        if (!message || this.isTyping) return;

        this.addMessage(message, 'user');
        this.elements.input.value = '';
        this.elements.send.disabled = true;
        
        this.showTyping();
        
        try {
            const response = await fetch('includes/chatbot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=send_message&message=${encodeURIComponent(message)}`
            });
            
            const data = await response.json();
            
            this.hideTyping();
            
            if (data.success) {
                this.addMessage(data.response, 'bot');
            } else {
                throw new Error('API call failed');
            }
        } catch (error) {
            this.hideTyping();
            this.addMessage('Sorry, I encountered an error. Please try again in a moment. 🐾', 'bot');
            console.error('Chatbot error:', error);
        } finally {
            this.elements.send.disabled = false;
            this.elements.input.focus();
        }
    }

    addMessage(text, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}`;
        
        const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        messageDiv.innerHTML = `
            <div class="message-avatar">
                ${sender === 'bot' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>'}
            </div>
            <div class="message-content">
                <div class="message-text">${this.formatMessage(text)}</div>
                <div class="message-time">${time}</div>
            </div>
        `;
        
        // Remove welcome message if it's the first user message
        const welcomeMessage = this.elements.messages.querySelector('.welcome-message');
        if (welcomeMessage && sender === 'user') {
            welcomeMessage.remove();
        }
        
        this.elements.messages.appendChild(messageDiv);
        this.scrollToBottom();
        this.saveChatHistory();
        
        // Show notification if chat is closed
        if (!this.isOpen && sender === 'bot') {
            this.showNotification();
        }
    }

    formatMessage(text) {
        // Convert line breaks and basic formatting
        return text
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>');
    }

    showTyping() {
        this.isTyping = true;
        this.elements.typing.style.display = 'block';
        this.scrollToBottom();
    }

    hideTyping() {
        this.isTyping = false;
        this.elements.typing.style.display = 'none';
    }

    scrollToBottom() {
        this.elements.messages.scrollTop = this.elements.messages.scrollHeight;
    }

    toggleChat() {
        this.isOpen = !this.isOpen;
        this.elements.container.classList.toggle('active', this.isOpen);
        this.hideNotification();
        
        if (this.isOpen) {
            this.elements.input.focus();
            this.scrollToBottom();
        }
    }

    closeChat() {
        this.isOpen = false;
        this.elements.container.classList.remove('active');
    }

    showNotification() {
        this.elements.notificationDot.style.display = 'block';
    }

    hideNotification() {
        this.elements.notificationDot.style.display = 'none';
    }

    saveChatHistory() {
        const messages = Array.from(this.elements.messages.querySelectorAll('.message')).map(msg => {
            return {
                sender: msg.classList.contains('bot') ? 'bot' : 'user',
                text: msg.querySelector('.message-text').innerHTML,
                time: msg.querySelector('.message-time').textContent
            };
        });
        
        localStorage.setItem('pawsomeChatHistory', JSON.stringify(messages));
    }

    loadChatHistory() {
        const saved = localStorage.getItem('pawsomeChatHistory');
        if (saved) {
            const messages = JSON.parse(saved);
            const welcomeMessage = this.elements.messages.querySelector('.welcome-message');
            if (welcomeMessage) {
                welcomeMessage.remove();
            }
            
            messages.forEach(msg => {
                this.addMessage(this.stripHTML(msg.text), msg.sender);
            });
        }
    }

    stripHTML(html) {
        const tmp = document.createElement('div');
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || '';
    }
}

// Initialize chatbot when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new PawsomeChatbot();
});