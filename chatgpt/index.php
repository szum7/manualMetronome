<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0" />
    <title>Clock Sync (mobile)</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="app">
        <h1>Clock Sync</h1>
        <div class="muted">Client ID will be generated & shown below</div>

        <div style="margin-top:12px" class="field">
            <div class="row">
                <div class="big">Client ID</div>
                <div id="clientId" style="margin-left:auto; font-weight:700"></div>
            </div>
            <div class="row" style="margin-top:8px">
                <div class="muted">Synch ID</div><input id="synchId"
                    style="margin-left:auto;width:120px;padding:8px;border-radius:8px;background:transparent;border:1px solid rgba(255,255,255,0.04);color:var(--card)"
                    placeholder="e.g. 1" />
            </div>
        </div>

        <div style="margin-top:12px" class="clock">
            <div class="line">
                <div class="muted">Unadjusted clock</div>
                <div id="unadj" class="big">--:--:--.------</div>
            </div>
            <div class="line">
                <div class="muted">Adjusted clock</div>
                <div id="adj" class="big">--:--:--.------</div>
            </div>
        </div>

        <div class="circle" aria-hidden="true">
            <div id="blink" class="blink"></div>
        </div>

        <button id="pressBtn" class="btn">Press (send timestamp)</button>
        <div style="margin-top:8px" class="muted">When computing finishes, the server will schedule the blink ~5s into
            the future and send back the start timestamp.</div>

        <div style="margin-top:12px" class="field">
            <div class="muted">Server status</div>
            <pre id="log" style="white-space:pre-wrap;max-height:120px;overflow:auto;margin-top:6px"></pre>
        </div>
    </div>

    <script>
        // Configuration: adjust to your server endpoints
        const API_SAVE = 'save_timestamp.php';
        const API_GET = 'get_sync_result.php';
        //const API_COMPUTE = 'compute_offsets.php'; // admin call — run from server or curl

        // Utility: high-resolution epoch time in microseconds
        function epochUsec() {
            // performance.timeOrigin + performance.now() gives sub-millisecond precision epoch
            const ms = performance.timeOrigin + performance.now();
            return Math.round(ms * 1000); // microseconds
        }

        // format microsecond epoch to readable string (with microsecond precision)
        function formatUsec(usec) {
            const ms = Math.floor(usec / 1000);
            const date = new Date(ms);
            const hh = String(date.getHours()).padStart(2, '0');
            const mm = String(date.getMinutes()).padStart(2, '0');
            const ss = String(date.getSeconds()).padStart(2, '0');
            const msec = String(date.getMilliseconds()).padStart(3, '0');
            const remUsec = String(usec % 1000000).padStart(6, '0');
            // we show hh:mm:ss.milliseconds+micro
            return `${hh}:${mm}:${ss}.${remUsec}`;
        }

        // small logger
        function log(msg) {
            const el = document.getElementById('log');
            el.textContent = new Date().toLocaleTimeString() + ' - ' + msg + '\n' + el.textContent
        }

        // client id (persisted in localStorage)
        let clientId = localStorage.getItem('cs_clientId');
        if (!clientId) {
            clientId = 'c_' + Math.random().toString(36).slice(2, 10);
            localStorage.setItem('cs_clientId', clientId);
        }
        document.getElementById('clientId').textContent = clientId;

        // DOM refs
        const unadjEl = document.getElementById('unadj');
        const adjEl = document.getElementById('adj');
        const blinkEl = document.getElementById('blink');
        const btn = document.getElementById('pressBtn');
        const synchIdInput = document.getElementById('synchId');

        // client's offset (signed microseconds). null until server provides
        let offset_usec = null;
        let blink_start_usec = null;
        let ref_client_id = null;
        let activeSynchid = null;

        // unadjusted clock use performance-based time origin to display precise time
        const navTimeOriginUsec = Math.round(performance.timeOrigin * 1000);

        function clientNowUsec() {
            return navTimeOriginUsec + Math.round(performance.now() * 1000);
        }

        // UI clock update
        function rafTick() {
            const now_usecs = clientNowUsec();
            unadjEl.textContent = formatUsec(now_usecs);
            if (offset_usec !== null) {
                // adjusted clock = local_time - offset (since offset = client_ts - ref_ts)
                const adj_usecs = now_usecs - offset_usec;
                adjEl.textContent = formatUsec(adj_usecs);
            } else {
                adjEl.textContent = '--:--:--.------';
            }
            requestAnimationFrame(rafTick);
        }
        requestAnimationFrame(rafTick);

        // blink loop driven by adjusted clock and blink_start_usec (server time in usec)
        // 40 bpm -> period 60/40 = 1.5 seconds = 1,500,000 usec
        const BEAT_PERIOD_USEC = 1500000;
        let lastBlinkState = false;

        function blinkTick() {
            if (blink_start_usec && offset_usec !== null) {
                // compute current reference time = adjusted clock (i.e., local - offset)
                const ref_now = clientNowUsec() - offset_usec;
                if (ref_now >= blink_start_usec) {
                    const since = ref_now - blink_start_usec;
                    // find beat index (floor)
                    const beatIndex = Math.floor(since / BEAT_PERIOD_USEC);
                    const beatProgress = (since % BEAT_PERIOD_USEC) / BEAT_PERIOD_USEC; // 0..1
                    // Make blink visible near beat onset: we toggle visible when beatProgress < duty (e.g., 0.18)
                    const duty = 0.18; // how long the pulse stays on fraction of period
                    const on = beatProgress < duty;
                    if (on !== lastBlinkState) {
                        blinkEl.style.opacity = on ? '1' : '0';
                        lastBlinkState = on;
                    }
                }
            }
            requestAnimationFrame(blinkTick);
        }
        requestAnimationFrame(blinkTick);

        // POST helper
        async function postJson(url, payload) {
            const r = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            return r.json();
        }

        btn.addEventListener('click', async () => {
            const synchid = parseInt(synchIdInput.value || '0', 10);
            if (!synchid) {
                alert('Enter a synch ID (integer)');
                return;
            }
            activeSynchid = synchid;
            // compute a very high precision timestamp using performance.timeOrigin + performance.now()
            const ts_usec = epochUsec();
            log('Sending timestamp: ' + ts_usec + ' for synch ' + synchid);
            try {
                const res = await postJson(API_SAVE, {
                    synchid: synchid,
                    clientId: clientId,
                    ts_usec: ts_usec
                });
                if (res.ok) {
                    log('Timestamp saved. Now polling for result...');
                    //pollForResult();
                } else {
                    log('Save error: ' + JSON.stringify(res));
                }
            } catch (e) {
                log('Network error: ' + e.message);
            }
        });

        function waitForSyncResult() {
            fetch(`get_sync_result.php?clientId=${clientId}&synchId=${synchId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.blinkStart) {
                        console.log("Blink start received:", data.blinkStart);
                        offset = data.offset;
                        scheduleBlink(data.blinkStart);
                    } else {
                        // No blinkStart yet — reconnect
                        waitForSyncResult();
                    }
                })
                .catch(err => {
                    console.error("Error getting sync result:", err);
                    setTimeout(waitForSyncResult, 2000);
                });
        }

        // Call once after submitting timestamp
        waitForSyncResult();

        // safe timeout that supports very large ms values and returns a handle you can cancel
        function safeSetTimeout(fn, ms) {
            const MAX = 2147483647; // ~24.85 days, max for setTimeout
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

        // scheduleBlink: serverBlinkUsec is server epoch microseconds (BIGINT style)
        // Requires offset_usec to be set (signed microseconds). Uses clientNowUsec() from your code.
        function scheduleBlink(serverBlinkUsec) {
            // ensure integer
            blink_start_usec = Number(serverBlinkUsec);

            // clear any previously scheduled wake
            if (window._blinkStartTimerHandle) {
                try {
                    window._blinkStartTimerHandle.clear();
                } catch (e) {
                    /*ignore*/ }
                window._blinkStartTimerHandle = null;
            }

            log('Received blink_start_usec: ' + blink_start_usec);

            // If offset not yet available, wait briefly and retry (very common race)
            if (offset_usec === null) {
                log('Offset not yet available — will retry scheduling in 100ms');
                setTimeout(() => scheduleBlink(serverBlinkUsec), 100);
                return;
            }

            // compute time until start in microseconds using adjusted/reference clock
            // ref_now = client's adjusted (reference) time = clientNowUsec() - offset_usec
            const ref_now = clientNowUsec() - offset_usec; // microseconds
            const delta_usec = blink_start_usec - ref_now; // microseconds until start as measured by this client
            const delta_ms = Math.max(0, Math.ceil(delta_usec / 1000)); // milliseconds (never negative)

            // human readable
            log('Blink starts in ~' + (delta_ms / 1000).toFixed(3) + ' s (delta_ms=' + delta_ms + ')');

            // small lead so we wake a little before the exact start and let rAF align the first frame.
            const LEAD_MS = 20; // adjust lower/higher for your environment (5..50ms typical)
            const wake_ms = Math.max(0, delta_ms - LEAD_MS);

            // Use safeSetTimeout to handle crazy large timeouts (defensive)
            window._blinkStartTimerHandle = safeSetTimeout(() => {
                // Force blinkTick to re-evaluate immediately and not rely solely on the next scheduled rAF
                // reset lastBlinkState so the first computed 'on' value will apply
                lastBlinkState = null;

                // Snap a rAF to kick the animation loop
                requestAnimationFrame(() => {
                    // one extra frame to make sure blinkTick runs right away
                    // blinkTick uses blink_start_usec and offset_usec to compute current pulse
                    // If the start time is already past, blinkTick will pick up the right beat index.
                });
                log('Woke for blink start (lead ' + LEAD_MS + 'ms).');
            }, wake_ms);

            // If start already passed (delta_ms === 0), we still set blink_start_usec so blinkTick will immediately show the correct beat.
            if (delta_ms === 0) {
                lastBlinkState = null;
                requestAnimationFrame(() => {});
            }
        }
    </script>
</body>

</html>