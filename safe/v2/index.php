<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0" />
    <title>M-Sync Meti</title>
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
                <div class="muted">Sync ID</div>
                <input id="syncId" placeholder="e.g. 1" value="123" />
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
        <button id="postBtn" class="btn secondary">Send Sync</button>

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

        <div style="margin: 10px 0;">
            <label for="tempo">Tempo (BPM):</label>
            <input type="number" id="tempo" min="20" max="300" value="120" style="width:60px;">
            <button id="setMetronome">Set metronome</button>
            <button id="joinMetronome">Join metronome</button>
            <button id="stopMetronome">Stop</button>
        </div>

        <div id="metronomeStatus"></div>

        <div class="field">
            <div class="muted">Server log</div>
            <pre id="log"></pre>
        </div>
    </div>

    <script src="scripts.js"></script>
    <script>
        // Endpoints
        const API_SAVE = 'save_timestamp.php';
        const API_GET = 'get_sync_result.php';

        // Elements
        const EL_UNADJ_TIME = document.getElementById('unadj');
        const EL_ADJ_TIME = document.getElementById('adj');
        const EL_BLINK_CIRCLE = document.getElementById('blink');
        const EL_SYNC_ID = document.getElementById('syncId');
        const EL_POST_BTN = document.getElementById('postBtn');
        const EL_GET_BTN = document.getElementById('getBtn');

        // Globals
        const _navTimeOriginUsec = Math.round(performance.timeOrigin * 1000);
        let _offsetUsec = null;
        let _clientId = null;
        let _blinkStartUsec = null;
        const BEAT_PERIOD_USEC = 1500000;
        let _lastBlinkState = false;
        let _isMuted = false;
        let _audioCtx;

        // RUN
        _clientId = generateClientId();
        requestAnimationFrame(rafTick);
        requestAnimationFrame(blinkTick);

        // Functions
        EL_POST_BTN.addEventListener('click', async () => { // SEND timers

            let syncId = getSyncId();
            if (!syncId) {
                return;
            }

            playBeep();

            const tsUsec = getEpochUsec();

            try {
                const res = await postJson(API_SAVE, {
                    sync_id: syncId,
                    client_id: _clientId,
                    same_time_ts_usec: tsUsec
                });
                if (res.success === true) {

                    log(`Resetted all syncId=${syncId} rows.`);
                    log(`Timestamp saved: ${syncId}, ${_clientId}, ${formatUsec(tsUsec)}`);

                } else {
                    log(`Error: ${JSON.stringify(res)}`);
                }
            } catch (e) {
                log(`Network error: ${e.message}`);
            }
        });

        EL_GET_BTN.addEventListener('click', async () => { // GET Offset and Blink start

            let syncId = getSyncId();
            if (!syncId) {
                return;
            }

            try {
                const res = await postJson(API_GET, {
                    sync_id: syncId,
                    client_id: _clientId
                });
                if (res.success === true) {

                    log(`Blink start received: ${(res.blink_start_usec_formatted)}`);

                    _offsetUsec = res.offset_usec;

                    scheduleBlink(res.blink_start_usec);
                } else {
                    log(`Error: ${JSON.stringify(res)}`);
                }
            } catch (e) {
                log('Network error: ' + e.message);
            }
        });

        function getSyncId() {
            let id = parseInt(EL_SYNC_ID.value || '0', 10);
            if (!id) {
                alert('Enter a sync ID (integer)');
                return null;
            }
            return id;
        }

        function rafTick() {
            const now_usecs = clientNowUsec();
            EL_UNADJ_TIME.textContent = formatUsec(now_usecs);
            if (_offsetUsec !== null) {
                const adj_usecs = now_usecs - _offsetUsec;
                EL_ADJ_TIME.textContent = formatUsec(adj_usecs);
            } else {
                EL_ADJ_TIME.textContent = '--:--:--.------';
            }
            requestAnimationFrame(rafTick);
        }

        EL_BLINK_CIRCLE.addEventListener('click', () => {
            _isMuted = !_isMuted;
            updateMuteVisual();
        });

        function updateMuteVisual() {
            if (_isMuted) {
                EL_BLINK_CIRCLE.style.background = '#fff';
                EL_BLINK_CIRCLE.title = "Muted (click to unmute)";
            } else {
                EL_BLINK_CIRCLE.style.background = '#0b84ff';
                EL_BLINK_CIRCLE.title = "Unmuted (click to mute)";
            }
        }

        function blinkTick() {
            if (_blinkStartUsec && _offsetUsec !== null) {
                const ref_now = clientNowUsec() - _offsetUsec;
                if (ref_now >= _blinkStartUsec) {
                    const since = ref_now - _blinkStartUsec;
                    const beatIndex = Math.floor(since / BEAT_PERIOD_USEC);
                    const beatProgress = (since % BEAT_PERIOD_USEC) / BEAT_PERIOD_USEC;
                    const duty = 0.18;
                    const on = beatProgress < duty;
                    if (on !== _lastBlinkState) {
                        EL_BLINK_CIRCLE.style.opacity = on ? '1' : '0';
                        _lastBlinkState = on;
                        if (on === true && _isMuted === false) {
                            //playBeep();
                        }
                    }
                }
            }
            requestAnimationFrame(blinkTick);
        }

        function clientNowUsec() {
            return _navTimeOriginUsec + Math.round(performance.now() * 1000);
        }

        function scheduleBlink(serverBlinkUsec) {
            _blinkStartUsec = Number(serverBlinkUsec);

            if (window._blinkStartTimerHandle) {
                try {
                    window._blinkStartTimerHandle.clear();
                } catch (e) {

                }
                window._blinkStartTimerHandle = null;
            }

            if (_offsetUsec === null) {
                log('Offset not yet available — will retry scheduling in 100ms');
                setTimeout(() => scheduleBlink(serverBlinkUsec), 100);
                return;
            }

            const ref_now = clientNowUsec() - _offsetUsec;
            const delta_usec = _blinkStartUsec - ref_now;
            const delta_ms = Math.max(0, Math.ceil(delta_usec / 1000));

            const LEAD_MS = 20;
            const wake_ms = Math.max(0, delta_ms - LEAD_MS);

            window._blinkStartTimerHandle = safeSetTimeout(() => {
                _lastBlinkState = null;
                requestAnimationFrame(() => {});
            }, wake_ms);

            if (delta_ms === 0) {
                _lastBlinkState = null;
                requestAnimationFrame(() => {});
            }
        }

        function initAudio() {
            _audioCtx = new(window.AudioContext || window.webkitAudioContext)();
        }

        function playBeep() {
            if (!_audioCtx) initAudio();

            const frequency = parseFloat(document.getElementById("beepFrequency").value);

            const osc = _audioCtx.createOscillator();
            const gain = _audioCtx.createGain();

            osc.type = "sine"; // "sine", "square", "triangle", "sawtooth"
            osc.frequency.value = frequency; // frequency from dropdown
            gain.gain.value = 0.1;

            osc.connect(gain);
            gain.connect(_audioCtx.destination);

            osc.start();
            osc.stop(_audioCtx.currentTime + 0.1); // 0.1s beep
        }


        let _metronomeIntervalId = null;
        //let _metronomeRunning = false;

        // Short beep for normal ticks
        function playMetronomeBeep(frequency = 880) {
            if (!_audioCtx) initAudio();
            const osc = _audioCtx.createOscillator();
            const gain = _audioCtx.createGain();
            osc.type = "square";
            osc.frequency.value = frequency;
            gain.gain.value = 0.1;
            osc.connect(gain);
            gain.connect(_audioCtx.destination);
            osc.start();
            osc.stop(_audioCtx.currentTime + 0.05);
        }





        let _metronomeTimerHandle = null;
        let _metronomeRunning = false;
        let _metronomeBeatIndex = 0; // counts beats since the metronome start (0 => first beat)
        let METRONOME_LEAD_MS = 20; // wake slightly earlier for rAF alignment
        //let isMuted = false; // if true, playBeep won't sound

        function stopMetronomeLocal() {
            _metronomeRunning = false;
            _metronomeBeatIndex = 0;
            if (_metronomeTimerHandle && _metronomeTimerHandle.clear) {
                _metronomeTimerHandle.clear();
                _metronomeTimerHandle = null;
            }
            // optionally update UI here
        }
        
        function startMetronome(tempoBpm, startTimeUsec) {
            if (_metronomeRunning) return;
            if (!tempoBpm || tempoBpm <= 0) {
                console.warn('Invalid tempo', tempoBpm);
                return;
            }

            // require offset to be known
            if (typeof _offsetUsec === 'undefined' || _offsetUsec === null) {
                // race: offset not ready yet — retry shortly
                console.log('offset_usec not ready yet; retrying startMetronome in 100ms');
                setTimeout(() => startMetronome(tempoBpm, startTimeUsec), 100);
                return;
            }

            _metronomeRunning = true;
            _metronomeBeatIndex = 0;

            // microseconds per beat
            const periodUsec = Math.round(60000000 / tempoBpm); // 60,000,000 usec per minute / bpm

            // compute reference "now" (reference clock aligned to the chosen ref client)
            // ref_now_usec = client's adjusted clock = local client epoch (usec) - offset_usec
            const refNowUsec = clientNowUsec() - _offsetUsec;

            // determine the next beat's absolute reference time (usec)
            let nextBeatRefUsec;
            if (refNowUsec < startTimeUsec) {
                // Start is in the future — the first beat is at startTimeUsec
                _metronomeBeatIndex = 0; // first beat to play will be index 0
                nextBeatRefUsec = startTimeUsec;
            } else {
                // Start already passed — compute which beat is next
                const elapsedSinceStart = refNowUsec - startTimeUsec;
                const completedBeats = Math.floor(elapsedSinceStart / periodUsec);
                _metronomeBeatIndex = completedBeats + 1; // next beat index
                nextBeatRefUsec = startTimeUsec + (completedBeats + 1) * periodUsec;
            }

            // Helper to schedule each beat given its absolute reference time (usec)
            function scheduleBeat(beatRefUsec) {
                if (!_metronomeRunning) return;

                const refNow = clientNowUsec() - _offsetUsec;
                let deltaUsec = beatRefUsec - refNow; // microseconds until this beat on *reference* clock
                if (deltaUsec < 0) deltaUsec = 0;
                const deltaMs = Math.ceil(deltaUsec / 1000);

                // wake slightly earlier (lead) so requestAnimationFrame and audio kick in on time
                const wakeMs = Math.max(0, deltaMs - METRONOME_LEAD_MS);

                // clear previous timer if any
                if (_metronomeTimerHandle && _metronomeTimerHandle.clear) {
                    _metronomeTimerHandle.clear();
                    _metronomeTimerHandle = null;
                }

                // schedule a wake
                _metronomeTimerHandle = safeSetTimeout(() => {
                    // on wake, align to rAF and play the beat exactly once
                    requestAnimationFrame(() => {
                        if (!_metronomeRunning) return;

                        // Determine if this is the downbeat (1 of 4)
                        const isDownbeat = (_metronomeBeatIndex % 4) === 0;

                        // Visual: toggle or pulse your circle here if needed (blinkTick may already handle visuals)
                        // e.g., show immediate pulse:
                        // blinkEl.style.opacity = '1'; set timeout to fade... (your blinkTick may manage this)

                        // Audio
                        if (!_isMuted) {
                            // accent the first beat
                            if (isDownbeat) playBeep(parseFloat(document.getElementById('beepFrequency')?.value || 1760));
                            else playBeep(parseFloat(document.getElementById('beepFrequency')?.value || 880));
                        }

                        // Advance beat index and schedule next beat using exact reference time
                        _metronomeBeatIndex++;
                        const nextRef = beatRefUsec + periodUsec;
                        scheduleBeat(nextRef);
                    });
                }, wakeMs);
            }

            // Start scheduling the first beat
            scheduleBeat(nextBeatRefUsec);
        }

        // ---------- Example join logic (client side) ----------
        async function joinMetronomeForSyncId(syncId) {
            try {
                const res = await fetch(`get_metronome.php?sync_id=${encodeURIComponent(syncId)}`);
                if (!res.ok) throw new Error('No metronome set');
                const data = await res.json();
                // data should contain tempo_bpm and start_time_usec (microseconds)
                startMetronome(parseInt(data.tempo_bpm, 10), Number(data.start_time_usec));
                // update UI...
            } catch (err) {
                console.error('Join metronome failed:', err);
            }
        }







        document.getElementById("setMetronome").addEventListener("click", async () => {
            
            stopMetronomeLocal();

            let tempo = parseInt(document.getElementById("tempo").value);
            let syncId = getSyncId();

            if (!syncId || !tempo) {
                return;
            }

            let res;
            try {
                res = await postJson("set_metronome.php", {
                    sync_id: syncId,
                    tempo: tempo
                });
            } catch (e) {
                log(`Network error: ${e.message}`);
            }

            if (res.success === true) {
                document.getElementById("metronomeStatus").textContent =
                    `Metronome set at ${res.tempo} BPM, starts at ${formatUsec(res.start_time_usec)}`;
            } else {
                log(`Error: ${JSON.stringify(res)}`);
            }
        });

        document.getElementById("joinMetronome").addEventListener("click", async () => {

            stopMetronomeLocal();

            let syncId = getSyncId();
            if (!syncId) {
                return;
            }

            let res;
            try {
                res = await postJson("get_metronome.php", {
                    sync_id: syncId
                });
            } catch (e) {
                log(`Network error: ${e.message}`);
            }

            if (res.success === true) {

                //startMetronome(res.tempo_bpm, res.start_time_usec);
                startMetronome(parseInt(res.tempo_bpm, 10), Number(res.start_time_usec));

                document.getElementById("metronomeStatus").textContent =
                    `Joined metronome at ${res.tempo_bpm} BPM, starts at ${formatUsec(res.start_time_usec)}`;

            } else {
                log(`Error: ${JSON.stringify(res)}`);
            }
        });

        document.getElementById("stopMetronome").addEventListener("click", stopMetronomeLocal);
    </script>
</body>

</html>