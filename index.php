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
        let _activeSyncId = null;
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

            const syncId = parseInt(EL_SYNC_ID.value || '0', 10);
            if (!syncId) {
                alert('Enter a sync ID (integer)');
                return;
            }

            playBeep();

            _activeSyncId = syncId;
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

            const syncId = parseInt(EL_SYNC_ID.value || '0', 10);
            if (!syncId) {
                alert('Enter a sync ID (integer)');
                return;
            }

            _activeSyncId = syncId;

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
                            playBeep();
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
    </script>
</body>

</html>