<?php echo phpversion();  ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0" />
    <title>Clock Sync (mobile)</title>
    <style>
        :root {
            --accent: #0b84ff;
            --bg: #0f1720;
            --card: #fff;
            color: #e6eef8
        }

        html,
        body {
            height: 100%;
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial;
            color: var(--card)
        }

        body {
            background: linear-gradient(180deg, #071029 0%, #071122 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px
        }

        .app {
            width: 100%;
            max-width: 540px;
            background: rgba(255, 255, 255, 0.03);
            padding: 18px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(2, 6, 23, 0.6)
        }

        h1 {
            font-size: 20px;
            margin: 0 0 8px
        }

        .row {
            display: flex;
            gap: 8px;
            align-items: center
        }

        .big {
            font-size: 18px;
            font-weight: 600
        }

        .muted {
            opacity: 0.8;
            font-size: 13px
        }

        .clock {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 12px
        }

        .clock .line {
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .btn {
            display: inline-block;
            background: var(--accent);
            color: white;
            padding: 12px;
            border-radius: 12px;
            text-align: center;
            font-weight: 700;
            border: none;
            width: 100%
        }

        .btn:active {
            transform: translateY(1px)
        }

        .circle {
            width: 90px;
            height: 90px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 12px auto
        }

        .blink {
            width: 56px;
            height: 56px;
            border-radius: 999px;
            background: var(--accent);
            opacity: 0;
            transition: opacity 0.15s linear
        }

        .field {
            background: rgba(255, 255, 255, 0.02);
            padding: 10px;
            border-radius: 10px
        }

        small {
            font-size: 12px;
            color: #a8b3c6
        }

        @media (max-width:420px) {
            .circle {
                width: 78px;
                height: 78px
            }

            .blink {
                width: 48px;
                height: 48px
            }
        }
    </style>
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
        function log(msg) { const el = document.getElementById('log'); el.textContent = new Date().toLocaleTimeString() + ' - ' + msg + '\n' + el.textContent }

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
            const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            return r.json();
        }

        btn.addEventListener('click', async () => {
            const synchid = parseInt(synchIdInput.value || '0', 10);
            if (!synchid) { alert('Enter a synch ID (integer)'); return; }
            activeSynchid = synchid;
            // compute a very high precision timestamp using performance.timeOrigin + performance.now()
            const ts_usec = epochUsec();
            log('Sending timestamp: ' + ts_usec + ' for synch ' + synchid);
            try {
                const res = await postJson(API_SAVE, { synchid: synchid, clientId: clientId, ts_usec: ts_usec });
                if (res.ok) {
                    log('Timestamp saved. Now polling for result...');
                    pollForResult();
                } else {
                    log('Save error: ' + JSON.stringify(res));
                }
            } catch (e) { log('Network error: ' + e.message); }
        });

        let pollTimer = null;
        async function pollForResult() {
            if (!activeSynchid) return;
            try {
                const params = new URLSearchParams({ synchid: activeSynchid, clientId: clientId });
                const r = await fetch(API_GET + '?' + params.toString());
                const data = await r.json();
                if (data.ready) {
                    offset_usec = parseInt(data.offset_usec, 10);
                    blink_start_usec = data.blink_start_usec ? parseInt(data.blink_start_usec, 10) : null;
                    ref_client_id = data.ref_client_id;
                    log('Offset received (usec): ' + offset_usec + ' (ref: ' + ref_client_id + ')');
                    if (blink_start_usec) log('Blink start server timestamp (usec): ' + blink_start_usec + ' (server time)');
                    // stop polling
                } else {
                    // not ready yet — poll again soon
                    pollTimer = setTimeout(pollForResult, 800);
                }
            } catch (e) {
                log('Poll error: ' + e.message);
                pollTimer = setTimeout(pollForResult, 1500);
            }
        }

        // Helpful: expose compute endpoint example (commented out) - for admin to run when all clients submitted
        // fetch('compute_offsets.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({synchid:1})}).then(r=>r.json()).then(console.log)

    </script>
</body>

</html>