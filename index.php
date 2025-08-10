<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0" />
    <title>MS Metronome</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="app">
        <h1>Clock Sync</h1>

        <div class="field">
            <div class="row">
                <div class="big">Client ID</div>
                <div id="clientId"></div>
            </div>
            <div class="row" style="margin-top:8px">
                <div class="muted">Synch ID</div>
                <input id="synchId" placeholder="e.g. 1" value="123" />
            </div>
            <div class="row" style="margin-top:8px">
                <div class="muted">Beep frequency</div>
                <select id="beepFrequency">
                    <option value="220">Low</option>
                    <option value="500" selected>Medium</option>
                    <option value="1050">High</option>
                </select>
            </div>
        </div>

        <div class="muted">Press at the same time.</div>
        <button id="postBtn" class="btn secondary">Send Synch</button>

        <div class="clock">
            <div class="line">
                <div class="muted">Unadjusted</div>
                <div id="unadj" class="big">--:--:--.------</div>
            </div>
            <div class="line">
                <div class="muted">Adjusted</div>
                <div id="adj" class="big">--:--:--.------</div>
            </div>
        </div>

        <div class="muted">Press within a 5 sec time window.</div>
        <button id="getBtn" class="btn primary">Get</button>

        <div class="circle" aria-hidden="true">
            <div id="blink" class="blink"></div>
        </div>

        <div class="field">
            <div class="muted">Server log</div>
            <pre id="log"></pre>
        </div>
    </div>

    <script>
        // Endpoints
        const API_SAVE = 'save_timestamp.php';
        const API_GET = 'get_sync_result.php';

        // Elements
        const unadjEl = document.getElementById('unadj');
        const adjEl = document.getElementById('adj');
        const blinkEl = document.getElementById('blink');
        const synchIdInput = document.getElementById('synchId');
        const postBtn = document.getElementById('postBtn');
        const getBtn = document.getElementById('getBtn');

        // Globals
        const navTimeOriginUsec = Math.round(performance.timeOrigin * 1000);
        let activeSynchid = null;
        let offset_usec = null;
        let clientId = null;
        let blink_start_usec = null;
        const BEAT_PERIOD_USEC = 1500000;
        let lastBlinkState = false;

        // RUN
        clientId = generateClientId();
        requestAnimationFrame(rafTick);
        requestAnimationFrame(blinkTick);

        // Functions
        postBtn.addEventListener('click', async () => { // SEND timers

            const synchId = parseInt(synchIdInput.value || '0', 10);
            if (!synchId) {
                alert('Enter a synch ID (integer)');
                return;
            }

            playBeep();

            activeSynchid = synchId;
            const tsUsec = getEpochUsec();

            try {
                const res = await postJson(API_SAVE, {
                    synch_id: synchId,
                    client_id: clientId,
                    ts_usec: tsUsec
                });
                if (res.success === true) {

                    log(`Resetted all syncId=${synchId} rows.`);
                    log(`Timestamp saved: ${synchId}, ${clientId}, ${formatUsec(tsUsec)}`);

                } else {
                    log(`Error: ${JSON.stringify(res)}`);
                }
            } catch (e) {
                log(`Network error: ${e.message}`);
            }
        });

        getBtn.addEventListener('click', async () => { // GET Offset and Blink start

            const synchId = parseInt(synchIdInput.value || '0', 10);
            if (!synchId) {
                alert('Enter a synch ID (integer)');
                return;
            }

            activeSynchid = synchId;

            try {
                const res = await postJson(API_GET, {
                    synch_id: synchId,
                    client_id: clientId
                });
                if (res.success === true) {
                    
                    log(`Blink start received: ${(res.blink_start_usec_formatted)}`);

                    offset_usec = res.offset_usec;

                    scheduleBlink(res.blink_start_usec);
                } else {
                    log(`Error: ${JSON.stringify(res)}`);
                }
            } catch (e) {
                log('Network error: ' + e.message);
            }
        });

        async function postJson(url, payload) {
            console.log(JSON.stringify(payload));
            const r = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            return r.json();
        }

        function generateClientId() {
            let clientId = localStorage.getItem('cs_clientId');
            if (!clientId) {
                clientId = 'c_' + Math.random().toString(36).slice(2, 10);
                localStorage.setItem('cs_clientId', clientId);
            }
            document.getElementById('clientId').textContent = clientId;
            return clientId;
        }

        function rafTick() {
            const now_usecs = clientNowUsec();
            unadjEl.textContent = formatUsec(now_usecs);
            if (offset_usec !== null) {
                const adj_usecs = now_usecs - offset_usec;
                adjEl.textContent = formatUsec(adj_usecs);
            } else {
                adjEl.textContent = '--:--:--.------';
            }
            requestAnimationFrame(rafTick);
        }

        let isMuted = false;
        const blinkCircle = document.querySelector('#blink');
        blinkCircle.style.cursor = 'pointer';  // indicate it’s clickable
        blinkCircle.addEventListener('click', () => {
            isMuted = !isMuted;
            updateMuteVisual();
        });
        function updateMuteVisual() {
            if (isMuted) {
                blinkCircle.style.background = '#fff';
                blinkCircle.title = "Muted (click to unmute)";
            } else {
                blinkCircle.style.background = '#0b84ff';
                blinkCircle.title = "Unmuted (click to mute)";
            }
        }

        function blinkTick() {
            if (blink_start_usec && offset_usec !== null) {
                const ref_now = clientNowUsec() - offset_usec;
                if (ref_now >= blink_start_usec) {
                    const since = ref_now - blink_start_usec;
                    const beatIndex = Math.floor(since / BEAT_PERIOD_USEC);
                    const beatProgress = (since % BEAT_PERIOD_USEC) / BEAT_PERIOD_USEC;
                    const duty = 0.18;
                    const on = beatProgress < duty;
                    if (on !== lastBlinkState) {
                        blinkEl.style.opacity = on ? '1' : '0';
                        lastBlinkState = on;
                        if (on === true && isMuted === false) {
                            playBeep();
                        }
                    }
                }
            }
            requestAnimationFrame(blinkTick);
        }

        function clientNowUsec() {
            return navTimeOriginUsec + Math.round(performance.now() * 1000);
        }

        function getEpochUsec() {
            const ms = performance.timeOrigin + performance.now();
            return Math.round(ms * 1000);
        }

        function log(msg) {
            const el = document.getElementById('log');
            //el.textContent = new Date().toLocaleTimeString() + ' - ' + msg + '\n' + el.textContent;
            el.textContent = getFormattedTime() + ' - ' + msg + '\n' + el.textContent;
        }

        function getFormattedTime() {
            const now = new Date();
            const h = now.getHours().toString().padStart(2, '0');
            const m = now.getMinutes().toString().padStart(2, '0');
            const s = now.getSeconds().toString().padStart(2, '0');
            let ms = now.getMilliseconds().toString().padStart(4, '0');
            ms = ms + '0';
            return `${h}:${m}:${s}.${ms}`;
        }

        function formatUsec(usec) {
            const ms = Math.floor(usec / 1000);
            const date = new Date(ms);
            const hh = String(date.getHours()).padStart(2, '0');
            const mm = String(date.getMinutes()).padStart(2, '0');
            const ss = String(date.getSeconds()).padStart(2, '0');
            const msec = String(date.getMilliseconds()).padStart(3, '0');
            const remUsec = String(usec % 1000000).padStart(6, '0');
            return `${hh}:${mm}:${ss}.${remUsec}`;
        }

        function safeSetTimeout(fn, ms) {
            const MAX = 2147483647;
            let cancelled = false;
            let id = null;

            function clear() {
                cancelled = true;
                if (id !== null) clearTimeout(id);
            }

            function schedule(remaining) {
                if (cancelled) return;
                if (remaining <= MAX) {
                    id = setTimeout(() => {
                        if (!cancelled) fn();
                    }, remaining);
                } else {
                    id = setTimeout(() => schedule(remaining - MAX), MAX);
                }
            }

            schedule(ms);
            return {
                clear
            };
        }

        function scheduleBlink(serverBlinkUsec) {
            blink_start_usec = Number(serverBlinkUsec);

            if (window._blinkStartTimerHandle) {
                try {
                    window._blinkStartTimerHandle.clear();
                } catch (e) {

                }
                window._blinkStartTimerHandle = null;
            }

            if (offset_usec === null) {
                log('Offset not yet available — will retry scheduling in 100ms');
                setTimeout(() => scheduleBlink(serverBlinkUsec), 100);
                return;
            }

            const ref_now = clientNowUsec() - offset_usec;
            const delta_usec = blink_start_usec - ref_now;
            const delta_ms = Math.max(0, Math.ceil(delta_usec / 1000));

            //log('Blink starts in ~' + (delta_ms / 1000).toFixed(3) + ' s (delta_ms=' + delta_ms + ')');

            const LEAD_MS = 20;
            const wake_ms = Math.max(0, delta_ms - LEAD_MS);

            window._blinkStartTimerHandle = safeSetTimeout(() => {
                lastBlinkState = null;
                requestAnimationFrame(() => {});
                //log('Woke for blink start (lead ' + LEAD_MS + 'ms).');
            }, wake_ms);

            if (delta_ms === 0) {
                lastBlinkState = null;
                requestAnimationFrame(() => {});
            }
        }

        let audioCtx;

        function initAudio() {
            audioCtx = new(window.AudioContext || window.webkitAudioContext)();
        }

        function playBeep() {
            if (!audioCtx) initAudio();

            const frequency = parseFloat(document.getElementById("beepFrequency").value);

            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();

            osc.type = "sine"; // "sine", "square", "triangle", "sawtooth"
            osc.frequency.value = frequency; // frequency from dropdown
            gain.gain.value = 0.1;

            osc.connect(gain);
            gain.connect(audioCtx.destination);

            osc.start();
            osc.stop(audioCtx.currentTime + 0.1); // 0.1s beep
        }
    </script>
</body>

</html>