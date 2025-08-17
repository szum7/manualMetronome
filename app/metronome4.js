class Metronome4 {
    #audioCtx;
    #bpm;
    #startUsec;
    #circles;
    #currentBeat = 1;
    #isMuted = false;
    #running = false;
    #rafId;
    #tickPitch;
    #tickType;

    constructor(containerId, tickPitch, tickType) {
        this.#tickPitch = tickPitch;
        this.#tickType = tickType;

        const container = document.getElementById(containerId);
        if (!container) throw new Error("Container not found");
        this.#circles = Array.from(container.querySelectorAll(".circle"));
        if (this.#circles.length !== 4) {
            throw new Error("Need exactly 4 circles inside the container");
        }
    }

    start(startTimeUsec, bpm) {
        if (this.#running) return;

        this.#bpm = bpm;
        this.#startUsec = startTimeUsec;
        this.#running = true;
        this.#schedule();
    }

    startNow(bpm) {
        this.#bpm = bpm;
        this.start(this.#getEpochUsec());
    }

    stop() {
        this.#running = false;
        cancelAnimationFrame(this.#rafId);
        this.#clearCircles();
    }

    mute() { this.#isMuted = true; }
    unmute() { this.#isMuted = false; }

    setOffsetUsec(offset) {
        this.#startUsec += offset;
    }

    setTickPitch(value) {
        this.#tickPitch = value;
    }
    
    setTickType(value) {
        this.#tickType = value;
    }

    #getEpochUsec() {
        const ms = performance.timeOrigin + performance.now();
        return Math.round(ms * 1000);
    }

    #schedule = () => {
        if (!this.#running) return;
        const nowUsec = Math.round((performance.timeOrigin + performance.now()) * 1000);

        const beatPeriodUsec = (60 / this.#bpm) * 1_000_000;
        const elapsed = nowUsec - this.#startUsec;

        if (elapsed >= 0) {
            const beatIndex = Math.floor(elapsed / beatPeriodUsec) % 4;
            if (beatIndex !== this.#currentBeat) {
                this.#currentBeat = beatIndex;
                this.#highlightCircle(beatIndex);
                if (!this.#isMuted) this.#beep(beatIndex);
            }
        }

        this.#rafId = requestAnimationFrame(this.#schedule);
    };

    #highlightCircle(index) {
        this.#clearCircles();
        const circle = this.#circles[index];
        if (index === 0) {
            circle.classList.add("active", "downbeat");
        } else {
            circle.classList.add("active");
        }
    }

    #clearCircles() {
        this.#circles.forEach(c => c.classList.remove("active", "downbeat"));
    }

    #beep(beatIndex) {
        if (!this.#audioCtx) {
            this.#audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        const osc = this.#audioCtx.createOscillator();
        const gainNode = this.#audioCtx.createGain();
        
        osc.type = this.#tickType;
        osc.frequency.value = (beatIndex === 0 ? (this.#tickPitch + 300) : this.#tickPitch);

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
