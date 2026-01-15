/**
 * Reproduce un sonido al escanear un código QR.
 * @param {string} type - Tipo de sonido: 'success' o 'error'
 */
function playScanSound(type = 'success') {
    const sound = new Audio(`assets/sounds/scan-${type}.mp3`);
    sound.volume = 0.5; // Ajustar volumen (0.0 a 1.0)
    
    // Intentar reproducir el sonido
    const playPromise = sound.play();
    
    // Manejar errores de reproducción (algunos navegadores requieren interacción del usuario primero)
    if (playPromise !== undefined) {
        playPromise.catch(error => {
            console.log('Error al reproducir sonido:', error);
            
            // Alternativa: usar Web Audio API si la reproducción falla
            if (error.name === 'NotAllowedError') {
                playBeep(type === 'success' ? 1200 : 300);
            }
        });
    }
}

/**
 * Genera un beep usando la Web Audio API.
 * Esta es una alternativa cuando no se pueden reproducir archivos de audio.
 * @param {number} frequency - Frecuencia del sonido en Hz
 * @param {number} duration - Duración en ms
 * @param {number} volume - Volumen entre 0.0 y 1.0
 * @param {string} type - Tipo de onda: 'sine', 'square', 'sawtooth', 'triangle'
 */
function playBeep(frequency = 880, duration = 200, volume = 0.2, type = 'sine') {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        
        oscillator.type = type;
        oscillator.frequency.value = frequency;
        gainNode.gain.value = volume;
        
        oscillator.start();
        
        setTimeout(() => {
            oscillator.stop();
        }, duration);
    } catch (e) {
        console.error("Error al reproducir sonido:", e);
    }
}

// Sonido de éxito: tono agudo doble
function playSuccessBeep() {
    playBeep(1200, 100, 0.2, 'sine');
    setTimeout(() => playBeep(1500, 100, 0.2, 'sine'), 100);
}

// Sonido de error: tono grave único
function playErrorBeep() {
    playBeep(300, 300, 0.2, 'triangle');
}