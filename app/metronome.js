class Metronome {
    #element;
    #bpm;
    #startTimeUsec;
    #offsetUsec = 0;
    #beatPeriodUsec;
    #lastBeatNumber = -1;
    #running = false;
    #muted = true;
    #audioCtx;
    #tickPitch;
    #tickType;

    // Constructor
    constructor(element, bpm, tickPitch, tickType) {
        this.#element = element;
        if (!this.#element) {
            throw new Error(`Element with ID "${element}" not found.`);
        }
        this.#bpm = bpm;
        this.#tickPitch = tickPitch;
        this.#tickType = tickType;
        this.#beatPeriodUsec = (60 / bpm) * 1_000_000;
    }
    
    // Public methods
    start(startTimeUsec) {
        if (this.#running) return;

        this.#startTimeUsec = startTimeUsec;
        this.#running = true;
        this.#lastBeatNumber = -1;
        requestAnimationFrame(() => this.#animate());
    }

    startNow() {
        this.start(this.#getEpochUsec());
    }

    stop() {
        this.#running = false;
    }

    mute() { this.#muted = true; }
    unmute() { this.#muted = false; }
    toggleMute() { this.#muted = !this.#muted; }

    // Setters
    setOffset(usec) {
        this.#offsetUsec = Math.max(0, usec);
    }

    setTickPitch(value) {
        this.#tickPitch = value;
    }
    
    setTickType(value) {
        this.#tickType = value;
    }

    // Getters
    getOffset() { return this.#offsetUsec; }
    getStartTimeUsec() { return this.#startTimeUsec; }
    
    // Private methods
    #getEpochUsec() {
        const ms = performance.timeOrigin + performance.now();
        return Math.round(ms * 1000);
    }

    #animate() {
        if (!this.#running) return;

        const now = this.#getEpochUsec();
        const firstBeatTime = this.#startTimeUsec + this.#offsetUsec;
        const elapsed = now - firstBeatTime;

        if (elapsed >= 0) {
            const beatNumber = Math.floor(elapsed / this.#beatPeriodUsec);
            if (beatNumber !== this.#lastBeatNumber) {
                this.#lastBeatNumber = beatNumber;
                this.#tick();
            }
        }

        requestAnimationFrame(() => this.#animate());
    }
    
    #tick() {
        this.#blink();
        if (!this.#muted) {
            this.#beep();
        }
    }
    
    #blink() {
        this.#element.style.opacity = 1;
        setTimeout(() => {
            this.#element.style.opacity = 0;
        }, 150);
    }
    
    #beep() {
        if (!this.#audioCtx) {
            this.#audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        const osc = this.#audioCtx.createOscillator();
        const gainNode = this.#audioCtx.createGain();

        osc.type = this.#tickType;
        osc.frequency.value = this.#tickPitch;

        let maxVolume = 0.3;

        // Sets the volume
        gainNode.gain.setValueAtTime(maxVolume, this.#audioCtx.currentTime);
        // Makes the sound naturally fade out
        gainNode.gain.exponentialRampToValueAtTime(0.0001, this.#audioCtx.currentTime + 0.1);

        osc.connect(gainNode).connect(this.#audioCtx.destination);
        osc.start();
        osc.stop(this.#audioCtx.currentTime + 0.1);
    }
}
