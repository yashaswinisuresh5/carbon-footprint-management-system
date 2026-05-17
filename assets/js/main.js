// assets/js/main.js
// Global Client-side Controller for Carbon Footprint Management System

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Loader Fadeout
    const loader = document.querySelector('.loader-overlay');
    if (loader) {
        window.addEventListener('load', () => {
            setTimeout(() => {
                loader.style.opacity = '0';
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 500);
            }, 400); // Small delay for premium feel
        });
        
        // Safety timeout if load event is delayed
        setTimeout(() => {
            if (loader.style.display !== 'none') {
                loader.style.opacity = '0';
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 500);
            }
        }, 3000);
    }

    // 2. Initialize Dark Mode Toggle (Using Cookies to avoid localStorage strictly)
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('change', function() {
            const isDark = this.checked;
            if (isDark) {
                document.documentElement.setAttribute('data-theme', 'dark');
                setCookie('theme', 'dark', 30);
            } else {
                document.documentElement.removeAttribute('data-theme');
                setCookie('theme', 'light', 30);
            }
            
            // Dispatch custom event to let active charts know they should refresh styles
            window.dispatchEvent(new CustomEvent('themechanged', { detail: { theme: isDark ? 'dark' : 'light' } }));
        });
    }

    // 3. Initialize Notification Dropdown
    const notifBtn = document.getElementById('notif-btn');
    const notifDropdown = document.getElementById('notif-dropdown');
    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.style.display = notifDropdown.style.display === 'block' ? 'none' : 'block';
        });
        
        document.addEventListener('click', () => {
            notifDropdown.style.display = 'none';
        });
        
        notifDropdown.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
});

// Toast Notification Manager
function showToast(title, text, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast-message ${type}`;
    
    let icon = 'fa-check-circle';
    if (type === 'error') icon = 'fa-exclamation-circle';
    if (type === 'warning') icon = 'fa-exclamation-triangle';
    
    toast.innerHTML = `
        <i class="fas ${icon}"></i>
        <div class="toast-body">
            <div class="toast-title">${title}</div>
            <div class="toast-text">${text}</div>
        </div>
    `;
    
    container.appendChild(toast);
    
    // Auto remove toast after 4s
    setTimeout(() => {
        toast.style.animation = 'slideInRight 0.3s ease reverse forwards';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}

// Cookie Helper Functions
function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        let date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}

function getCookie(name) {
    let nameEQ = name + "=";
    let ca = document.cookie.split(';');
    for(let i=0;i < ca.length;i++) {
        let c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}
