// Api urls
const API_GET_CLIENT = "get_client.php";
const API_SET_AS_SERVER = "save_as_server.php";

// Elements
const consoleLog = document.getElementById("consoleLog");
const syncIdEntry = document.getElementById("syncIdEntry");
const mainApp = document.getElementById("mainApp");
const EL_CIRCLE = document.getElementById("outer-circle");

// Globals
let _sync_id = null;
let _client_id = null;
let _offsetUsec = null;
let _server_timestamp_usec = null;

// RUN
generateClientId();

// Functions
document.getElementById("proceedBtn").addEventListener("click", async () => {

    const syncId = document.getElementById("initialSyncId").value.trim();
    setSyncId(syncId);

    syncIdEntry.classList.add("hidden");
    mainApp.classList.remove("hidden");

    try {
        const res = await postJson(API_GET_CLIENT, {
            sync_id: _sync_id,
            client_id: _client_id
        });
        console.log(res);
        if (res.success === true) {
            if (res.found === true) {

                setOffsetUsec(res.offset_usec);
                _server_timestamp_usec = res.server_timestamp_usec;

                if (res.is_ref === true) {
                    setToServerOrClient("server");
                    requestAnimationFrame(animate);
                } else {
                    setToServerOrClient("client");
                }

                startCustomBlinking();

            } else {

            }
        } else {
            log(`Error: ${JSON.stringify(res)}`);
        }
    } catch (e) {
        log('Network error: ' + e.message);
    }
});

document.getElementById("setRefPointBtn").addEventListener("click", async () => {
    let ts = getEpochUsec();
    try {
        const res = await postJson(API_SET_AS_SERVER, {
            sync_id: _sync_id,
            client_id: _client_id,
            server_timestamp_usec: ts
        });
        if (res.success === true) {
            log(`Room: ${_sync_id}, timestamp: ${ts}`);
        } else {
            log(`Error: ${JSON.stringify(res)}`);
        }
    } catch (e) {
        log('Network error: ' + e.message);
    }
});

function generateClientId() {
    let id = localStorage.getItem('cs_clientId');
    if (!id) {
        id = 'c_' + Math.random().toString(36).slice(2, 10);
        localStorage.setItem('cs_clientId', clientId);
    }
    setClientId(id);
}

function updateSyncIdLabel(value) {
    document.getElementById("syncIdLabel").textContent = value;
}

function updateClientIdLabel(value) {
    document.getElementById("clientId").textContent = value;
}

function setClientId(value) {
    _client_id = value;
    updateClientIdLabel(value);
}

function setSyncId(value) {
    _sync_id = value;
    updateSyncIdLabel(value);
}

function setOffsetUsec(value) {
    _offsetUsec = value;
    document.getElementById("offset").textContent = value;
}

function getEpochUsec() {
    const ms = performance.timeOrigin + performance.now();
    return Math.round(ms * 1000);
}

// Change Sync ID button
document.getElementById("changeSyncIdBtn").addEventListener("click", () => {
    mainApp.classList.add("hidden");
    syncIdEntry.classList.remove("hidden");
});

// Tab switching
document.querySelectorAll(".tab-button").forEach(btn => {
    btn.addEventListener("click", () => {
        document.querySelectorAll(".tab-button").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        const tab = btn.dataset.tab;
        document.querySelectorAll(".tab-content").forEach(tc => tc.classList.remove("active"));
        document.getElementById(tab + "Tab").classList.add("active");
    });
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

function log(message) {
    const now = new Date();
    const timestamp = now.toTimeString().split(" ")[0] + "." + now.getMilliseconds().toString().padStart(5, '0');
    const entry = document.createElement("div");
    entry.textContent = `${timestamp} - ${message}`;
    consoleLog.appendChild(entry);
    consoleLog.scrollTop = consoleLog.scrollHeight;
}

const EL_TOGGLE_SC = document.getElementById('toggle-sc');
const EL_CONTENT_SERVER = document.getElementById('content-server');
const EL_CONTENT_CLIENT = document.getElementById('content-client');

EL_TOGGLE_SC.addEventListener('change', () => {
    EL_CONTENT_SERVER.classList.toggle('hidden', EL_TOGGLE_SC.checked);
    EL_CONTENT_CLIENT.classList.toggle('hidden', !EL_TOGGLE_SC.checked);
    _isMuted = true;
});

function setToServerOrClient(name) {
    if (name === "server") {
        EL_TOGGLE_SC.checked = false;
        EL_CONTENT_SERVER.classList.toggle('hidden', false);
        EL_CONTENT_CLIENT.classList.toggle('hidden', true);
    } else if (name === "client") {
        EL_TOGGLE_SC.checked = true;
        EL_CONTENT_SERVER.classList.toggle('hidden', true);
        EL_CONTENT_CLIENT.classList.toggle('hidden', false);
    }
}

let _isMuted = false;
EL_CIRCLE.addEventListener('click', () => {
    _isMuted = !_isMuted;
});


let bpm = 30;

const beatPeriodUsec = (60 / bpm) * 1_000_000;
let lastBeatNumber = -1;
const innerCircle = document.getElementById('inner-circle');

function animate() {
    const now = getEpochUsec();
    const firstBeatTime = _server_timestamp_usec + _offsetUsec;
    const elapsed = now - firstBeatTime;

    if (elapsed >= 0) {
        const beatNumber = Math.floor(elapsed / beatPeriodUsec);
        if (beatNumber !== lastBeatNumber) {
            lastBeatNumber = beatNumber;
            blinkOnce(innerCircle, _isMuted);
        }
    }

    requestAnimationFrame(animate);
}

function blinkOnce(element, isMuted) {
    element.style.opacity = 1;
    if (!isMuted) {
        playBeep();
    }
    setTimeout(() => {
        element.style.opacity = 0;
    }, 150); // show for 150 ms
}

let _audioCtx;
function playBeep(frequency) {
    if (!_audioCtx) initAudio();

    frequency = (typeof frequency === 'undefined') ? getBeepFrequency() : frequency;

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

function initAudio() {
    _audioCtx = new (window.AudioContext || window.webkitAudioContext)();
}

function getBeepType() {
    return document.getElementById("typeSelect").value;
}

function getBeepFrequency() {
    return document.getElementById("pitchSelect").value;
}






function getEpochUsec() {
    const ms = performance.timeOrigin + performance.now();
    return Math.round(ms * 1000);
}

// Blink settings
let _customBlinkStart = null;     // usec
let _customOffsetUsec = 0;        // usec, always positive

let lastBeatNumber2 = -1;
let _isMuted2 = false;

const innerCircle2 = document.getElementById('inner-circle2');
const offsetLabel = document.getElementById('offset-label');

// Start blinking
function startCustomBlinking() {
    if (_customBlinkStart !== null) return; // already running
    _customBlinkStart = getEpochUsec();
    lastBeatNumber2 = -1;
    requestAnimationFrame(animateBlink);
}

// Blink animation (drift-free)
function animateBlink() {
    const now = getEpochUsec();
    const firstBeatTime = _customBlinkStart + _customOffsetUsec;
    const elapsed = now - firstBeatTime;

    if (elapsed >= 0) {
        const beatNumber = Math.floor(elapsed / beatPeriodUsec);
        if (beatNumber !== lastBeatNumber2) {
            lastBeatNumber2 = beatNumber;
            blinkOnce(innerCircle2, _isMuted2);
        }
    }

    requestAnimationFrame(animateBlink);
}

// Update offset and UI label
function updateOffset(delta) {
    _customOffsetUsec = Math.max(0, _customOffsetUsec + delta);
    offsetLabel.textContent = formatOffsetLabel(_customOffsetUsec) + ' s';
}

// Attach button click events
document.querySelectorAll('.pill-buttons button').forEach(btn => {
    btn.addEventListener('click', () => {
        const delta = parseInt(btn.dataset.delta, 10);
        updateOffset(delta);
    });
});

function formatOffsetLabel(usec) {
    const seconds = usec / 1_000_000;
    return seconds.toFixed(3).padStart(6, '0'); // ensures 00.000 style
}

// Init label
offsetLabel.textContent = formatOffsetLabel(_customOffsetUsec) + ' s';





