
// =====================================================
// SOLO LEVELING FITNESS - MAIN JAVASCRIPT
// =====================================================

// Global app object
const SoloLevelingApp = {
    // Initialize on page load
    init: function() {
        this.setupEventListeners();
        this.checkSystemMessages();
        this.animateElements();
    },
    
    // Set up event listeners
    setupEventListeners: function() {
        // Rank up modal close
        document.querySelectorAll('.rank-up-modal button').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.rank-up-modal').remove();
            });
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 1s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 1000);
            });
        }, 5000);
    },
    
    // Check for system messages
    checkSystemMessages: function() {
        // This could check for penalties, rank ups, etc.
        console.log('System initialized...');
    },
    
    // Animate elements on scroll
    animateElements: function() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('.card, .stat-card, .partner-card').forEach(el => {
            observer.observe(el);
        });
    },
    
    // Play sound (if enabled)
    playSound: function(soundName) {
        // This would play sounds based on user preference
        console.log('Playing sound:', soundName);
    },
    
    // Show system notification
    showNotification: function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
        notification.style.zIndex = '9999';
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            ${message}
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    SoloLevelingApp.init();
});

// =====================================================
// WORKOUT TRACKER FUNCTIONS
// =====================================================

const WorkoutTracker = {
    // Calculate XP for workout
    calculateXP: function(exercises) {
        const total = exercises.pushups + exercises.situps + exercises.squats + (exercises.running * 10);
        let xp = Math.floor(total / 10);
        
        // Perfect bonus
        if (exercises.pushups >= 60 && exercises.situps >= 60 && 
            exercises.squats >= 60 && exercises.running >= 10) {
            xp += 50;
        }
        
        return xp;
    },
    
    // Validate workout input
    validateInput: function(inputs) {
        for (let [key, value] of Object.entries(inputs)) {
            if (value < 0) {
                return { valid: false, message: `${key} cannot be negative` };
            }
        }
        return { valid: true };
    }
};

// =====================================================
// AFFILIATE TRACKING
// =====================================================

const AffiliateTracker = {
    // Track click via AJAX
    trackClick: function(partnerId) {
        fetch('/solo-leveling-fitness/pages/track_affiliate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'partner_id=' + partnerId
        })
        .then(response => response.json())
        .then(data => {
            console.log('Click tracked:', data);
        })
        .catch(error => {
            console.error('Error tracking click:', error);
        });
    }
};

// =====================================================
// COUNTDOWN TIMERS
// =====================================================

class CountdownTimer {
    constructor(elementId, targetDate) {
        this.element = document.getElementById(elementId);
        this.targetDate = new Date(targetDate);
        this.interval = null;
    }
    
    start() {
        this.interval = setInterval(() => {
            this.update();
        }, 1000);
    }
    
    update() {
        const now = new Date();
        const diff = this.targetDate - now;
        
        if (diff <= 0) {
            this.element.innerHTML = "Event started!";
            clearInterval(this.interval);
            return;
        }
        
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        this.element.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
    }
    
    stop() {
        if (this.interval) {
            clearInterval(this.interval);
        }
    }
}

// =====================================================
// RANK CALCULATIONS
// =====================================================

const RankCalculator = {
    ranks: ['E', 'D', 'C', 'B', 'A', 'S'],
    
    getRankFromStreak: function(streak) {
        if (streak >= 60) return 'S';
        if (streak >= 50) return 'A';
        if (streak >= 40) return 'B';
        if (streak >= 30) return 'C';
        if (streak >= 20) return 'D';
        return 'E';
    },
    
    getRankColor: function(rank) {
        const colors = {
            'E': '#808080',
            'D': '#00FF00',
            'C': '#0000FF',
            'B': '#800080',
            'A': '#FFA500',
            'S': '#FF0000'
        };
        return colors[rank] || '#FFFFFF';
    },
    
    getProgressToNextRank: function(currentRank, streak) {
        const requirements = {
            'E': 20,
            'D': 30,
            'C': 40,
            'B': 50,
            'A': 60,
            'S': 60
        };
        
        const needed = requirements[currentRank];
        const progress = Math.min((streak / needed) * 100, 100);
        
        return {
            current: streak,
            needed: needed,
            percentage: progress,
            nextRank: this.ranks[this.ranks.indexOf(currentRank) + 1] || 'MAX'
        };
    }
};

// =====================================================
// ANIMATION EFFECTS
// =====================================================

const Animations = {
    // Glow effect
    addGlow: function(element, color) {
        element.style.transition = 'box-shadow 0.3s';
        element.style.boxShadow = `0 0 20px ${color}`;
    },
    
    removeGlow: function(element) {
        element.style.boxShadow = 'none';
    },
    
    // Pulse animation
    pulse: function(element) {
        element.style.animation = 'pulse 1s infinite';
    },
    
    // Typewriter effect
    typewriter: function(element, text, speed = 50) {
        let i = 0;
        element.innerHTML = '';
        
        function type() {
            if (i < text.length) {
                element.innerHTML += text.charAt(i);
                i++;
                setTimeout(type, speed);
            }
        }
        
        type();
    }
};

// Export for use
window.SoloLevelingApp = SoloLevelingApp;
window.WorkoutTracker = WorkoutTracker;
window.AffiliateTracker = AffiliateTracker;
window.RankCalculator = RankCalculator;
window.Animations = Animations;