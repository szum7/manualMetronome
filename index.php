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

        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-pill">
                Client: <span id="clientId">...</span>
            </div>
            <div class="top-pill">
                Sync ID: <input id="syncId" placeholder="e.g. 1" value="123" />
            </div>
            <div class="top-pill">Beep:
                <select id="beepFrequency">
                    <option value="220">Low</option>
                    <option value="500" selected>Medium</option>
                    <option value="1050">High</option>
                </select>
            </div>
            <div class="top-pill">Type:
                <select id="beepType">
                    <option value="sine" selected>sine</option>
                    <option value="square">square</option>
                    <option value="triangle">triangle</option>
                    <option value="sawtooth">sawtooth</option>
                </select>
            </div>
            <div class="top-pill">
                Offset: <span id="offsetDisplay">unset</span>
            </div>
            <div class="top-pill">
                Ref: <span id="refClientDisplay">unset</span>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" data-tab="setup">Setup</button>
            <button class="tab-btn" data-tab="metronome">Metronome</button>
        </div>

        <div class="page-width">

            <!-- Content -->
            <div class="content">
                <!-- Setup -->
                <div id="setup" class="tab-content active">
                    <div class="card">
                        <div>
                            <button class="btn" id="postBtn">Send my Clock</button>
                        </div>
                        <div class="clock-column">
                            <div class="clock-line">
                                <div class="clock-label">Unadjusted:</div>
                                <div class="clock-display" id="unadj">--:--:--.------</div>
                            </div>
                            <div class="clock-line">
                                <div class="clock-label">Adjusted:</div>
                                <div class="clock-display" id="adj">--:--:--.------</div>
                            </div>
                        </div>
                        <div>
                            <button class="btn secondary" id="getBtn">Get Offset</button>
                        </div>
                        <div class="circle-wrap">
                            <div class="circle" id="circle">
                                <div class="pulse" id="blink"></div>
                                <!-- <div class="mute-ind" id="muteInd">🔇</div> -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Metronome -->
                <div id="metronome" class="tab-content" style="display:none">
                    <div class="card">
                        <div class="tempo-row">
                            <button class="btn fix" id="setMetronome">Set</button>
                            <div class="tempo-input-wrap">
                                <input type="text" id="tempo" value="120" />
                                <span class="tempo-label">bpm</span>
                            </div>
                        </div>
                        <div style="margin-top:8px">
                            <button class="btn secondary" id="joinMetronome">Join</button>
                            <button class="btn ghost" id="stopMetronome">Stop</button>
                        </div>
                        <div id="metronome-visual" class="metronome-visual">
                            <div class="beat-circle" id="beat-1"></div>
                            <div class="beat-circle" id="beat-2"></div>
                            <div class="beat-circle" id="beat-3"></div>
                            <div class="beat-circle" id="beat-4"></div>
                        </div>
                        <div class="mt-bpm"><span id="mtBpm">0</span> bpm</div>
                    </div>
                </div>
            </div>

            <div class="instructions">
                <p class="title"><b>Instructions</b></p>
                <ol>
                    <li>Set a <strong>sync id</strong> on the top. This will be your room key.</li>
                    <li>Press 'Send my Clock' <u>at the same time</u> with all the clients.</li>
                    <li>Press 'Get Offset'.</li>
                    <li>You should now have a synchronized clock with all the other clients.</li>
                    <li>You can now use the Metronome tab.</li>
                </ol>
                <ul>
                    <li>On <b>page refresh</b> you may only need to press 'Get Offset' again.</li>
                    <li>'Set' will set a tempo for the room.</li>
                    <li>'Join' will start your metronome synched with all clients in the room.</li>
                    <li>'Stop' will stop your metronome. (Not others'.)</li>
                </ul>
                <div>
                    <button class="btn" id="deleteBtn">CLEAR DATABASE</button>
                </div>
            </div>

        </div>

        <!-- Bottom Log -->
        <pre id="log" class="bottom-log"></pre>
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
        const EL_REF_CLIENT_DISPLAY = document.getElementById("refClientDisplay");
        const EL_OFFSET_DISPLAY = document.getElementById("offsetDisplay");
        const EL_BPM_DISPLAY = document.getElementById("mtBpm");
        const EL_DELETE_BTN = document.getElementById("deleteBtn");

        // Globals
        const _navTimeOriginUsec = Math.round(performance.timeOrigin * 1000);
        let _offsetUsec = null;
        let _clientId = null;
        let _blinkStartUsec = null;
        const BEAT_PERIOD_USEC = 1500000;
        let _lastBlinkState = false;
        let _isMuted = false;
        let _isTestMuted = false;
        let _audioCtx;

        // RUN
        log("START.");
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

                    log(`SETUP: Resetted all syncId=${syncId} rows.`);
                    log(`SETUP: Timestamp saved: ${syncId}, ${_clientId}, ${formatUsec(tsUsec)}`);

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

                    log(`SETUP: Blink start received: ${(res.blink_start_usec_formatted)}`);

                    _offsetUsec = res.offset_usec;
                    EL_OFFSET_DISPLAY.textContent = _offsetUsec;
                    EL_REF_CLIENT_DISPLAY.textContent = res.ref_client_id;
                    _isTestMuted = false;

                    scheduleBlink(res.blink_start_usec);
                } else {
                    log(`Error: ${JSON.stringify(res)}`);
                }
            } catch (e) {
                log('Network error: ' + e.message);
            }
        });

        EL_DELETE_BTN.addEventListener('click', async () => {

            try {
                const res = await postJson("delete_data.php", {});
                if (res.success === true) {

                    log(`DATABASE CLEARED.`);

                } else {
                    log(`Error: ${JSON.stringify(res)}`);
                }
            } catch (e) {
                log('Network error: ' + e.message);
            }
        });

        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {

                // Resets
                _isTestMuted = true;
                stopMetronomeLocal();

                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
                document.getElementById(btn.dataset.tab).style.display = 'block';
            });
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
            if (!EL_UNADJ_TIME) {
                return;
            }
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
            _isTestMuted = !_isTestMuted;
            updateMuteVisual();
        });

        function updateMuteVisual() {
            if (_isTestMuted) {
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
                        if (_isTestMuted === false) {
                            EL_BLINK_CIRCLE.style.opacity = on ? '1' : '0';
                            if (on === true) {
                                playBeep();
                            }
                        }
                        _lastBlinkState = on;
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
        
        function getBeepType() {
            return document.getElementById("beepType").value;
        }

        function playBeep(frequency) {
            if (!_audioCtx) initAudio();

            frequency = (typeof frequency === 'undefined') ? parseFloat(document.getElementById("beepFrequency").value) : frequency;
            //const frequency = parseFloat(document.getElementById("beepFrequency").value);

            const osc = _audioCtx.createOscillator();
            const gain = _audioCtx.createGain();

            osc.type = getBeepType(); // "sine", "square", "triangle", "sawtooth"
            osc.frequency.value = frequency; // frequency from dropdown
            gain.gain.value = 0.1;

            osc.connect(gain);
            gain.connect(_audioCtx.destination);

            osc.start();
            osc.stop(_audioCtx.currentTime + 0.1); // 0.1s beep
        }
        
        let _metronomeTimerHandle = null;
        let _metronomeRunning = false;
        let _metronomeBeatIndex = 0; // counts beats since the metronome start (0 => first beat)
        let METRONOME_LEAD_MS = 20; // wake slightly earlier for rAF alignment

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

            let _currentBeat = 1;

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

                        // Visual: toggle or pulse your circle here if needed (blinkTick may already handle visuals)
                        // e.g., show immediate pulse:
                        // blinkEl.style.opacity = '1'; set timeout to fade... (your blinkTick may manage this)
                        updateMetronomeVisual(_currentBeat);
                        _currentBeat = _currentBeat === 4 ? 1 : _currentBeat + 1;

                        // Audio
                        if (!_isMuted) {
                            // accent the first beat
                            let freq = document.getElementById('beepFrequency')?.value ?? 880;
                            if (_currentBeat === 2) {
                                playBeep(parseFloat(freq) + 400);
                            } else {
                                playBeep(parseFloat(freq));
                            }
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

        function updateMetronomeVisual(currentBeat) {
            for (let i = 1; i <= 4; i++) {
                const circle = document.getElementById(`beat-${i}`);
                if (!circle) continue;

                circle.classList.remove('active', 'first-beat');

                if (i === currentBeat) {
                    if (i === 1) {
                        circle.classList.add('active', 'first-beat'); // different style for beat 1
                    } else {
                        circle.classList.add('active');
                    }
                }
            }
        }





        document.getElementById("setMetronome").addEventListener("click", async () => {

            stopMetronomeLocal();

            if (_offsetUsec == null) {
                log("Need to sync first!");
                return;
            }

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
                log(`Set Sync ID=${res.sync_id} => ${res.tempo} bpm`);
            } else {
                log(`Error: ${JSON.stringify(res)}`);
            }
        });

        document.getElementById("joinMetronome").addEventListener("click", async () => {

            stopMetronomeLocal();

            if (_offsetUsec == null) {
                log("Need to sync first!");
                return;
            }

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

                EL_BPM_DISPLAY.textContent = res.tempo_bpm;

                log(`Joined Sync ID=${res.sync_id}, ${res.tempo_bpm} bpm`);

            } else {
                log(`Error: ${JSON.stringify(res)}`);
            }
        });

        document.getElementById("stopMetronome").addEventListener("click", () => {
            stopMetronomeLocal();
            log("Stopped metronome.")
        });
    </script>
</body>

</html>