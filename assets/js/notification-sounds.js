// JMC Foodies - Notification Sound System
// Uses Web Audio API to generate sounds without external files

const NotifSound = (function() {
    let audioCtx = null;

    function getContext() {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        return audioCtx;
    }

    function playTone(frequency, duration, type, volume) {
        try {
            const ctx = getContext();
            const oscillator = ctx.createOscillator();
            const gainNode = ctx.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(ctx.destination);

            oscillator.type = type || 'sine';
            oscillator.frequency.setValueAtTime(frequency, ctx.currentTime);

            gainNode.gain.setValueAtTime(volume || 0.3, ctx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + duration);

            oscillator.start(ctx.currentTime);
            oscillator.stop(ctx.currentTime + duration);
        } catch (e) {
            // Audio not supported or blocked
        }
    }

    return {
        // Success sound: two ascending tones (ding-ding)
        success: function() {
            playTone(523, 0.15, 'sine', 0.25);  // C5
            setTimeout(function() {
                playTone(659, 0.2, 'sine', 0.25);  // E5
            }, 150);
        },

        // Error/danger sound: low descending tone
        error: function() {
            playTone(330, 0.15, 'square', 0.2);  // E4
            setTimeout(function() {
                playTone(262, 0.25, 'square', 0.2);  // C4
            }, 150);
        },

        // Warning sound: single attention tone
        warning: function() {
            playTone(440, 0.3, 'triangle', 0.25);  // A4
        },

        // Notification alert: three-note chime
        notification: function() {
            playTone(587, 0.12, 'sine', 0.2);  // D5
            setTimeout(function() {
                playTone(784, 0.12, 'sine', 0.2);  // G5
            }, 120);
            setTimeout(function() {
                playTone(988, 0.25, 'sine', 0.25);  // B5
            }, 240);
        }
    };
})();
