

// RUN
generateClientId();

function setCustomOffsetUsec(value) {
    _customOffsetUsec = value;
    EL_OFFSET_LABEL.textContent = formatUsecToSec(value);
}

// Functions
EL_SET_SYNC_ID_BTN.addEventListener("click", async () => {

    const syncId = document.getElementById("initialSyncId").value.trim();
    setSyncId(syncId);

    EL_SCREEN_SYNC_ID.classList.add("hidden");
    EL_MAIN_APP.classList.remove("hidden");

    try {
        const response = await postJson(API_GET_CLIENT, {
            sync_id: _syncId,
            client_id: _clientId
        });
        console.log(response);
        if (response.success === true) {
            if (response.found === true) {

                //setOffsetUsec(response.offset_usec);

                if (response.is_ref === true) {
                    setToServerOrClient("server");
                    requestAnimationFrame(animate);
                } else {
                    if (!!response.server) {
                        setToServerOrClient("client");
                        setCustomOffsetUsec(response.offset_usec);
                        _serverTimestampUsec = response.server.server_timestamp_usec;
                    } else {

                    }
                }

                startCustomBlinking();

            } else {

            }
        } else {
            log(`Error: ${JSON.stringify(response)}`);
        }
    } catch (e) {
        log('Network error: ' + e.message);
    }
});

document.getElementById("setRefPointBtn").addEventListener("click", async () => {
    let ts = getEpochUsec();
    try {
        const response = await postJson(API_SET_AS_SERVER, {
            sync_id: _syncId,
            client_id: _clientId,
            server_timestamp_usec: ts
        });
        if (response.success === true) {
            log(`Room: ${_syncId}, timestamp: ${ts}`);
            requestAnimationFrame(animate);
        } else {
            log(`Error: ${JSON.stringify(response)}`);
        }
    } catch (e) {
        log('Network error: ' + e.message);
    }
});

document.getElementById("clientSaveOffset").addEventListener("click", async () => {

    try {
        const response = await postJson(API_SAVE_CLIENT_OFFSET, {
            sync_id: _syncId,
            client_id: _clientId,
            offset_usec: _customOffsetUsec
        });
        console.log(response);
        if (response.success === true) {
            log("Offset saved.");
        } else {
            log(`Error: ${JSON.stringify(response)}`);
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
    _clientId = value;
    updateClientIdLabel(value);
}

function setSyncId(value) {
    _syncId = value;
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









EL_CIRCLE_SERVER.addEventListener('click', () => {
    _isServerMuted = !_isServerMuted;
});

EL_CIRCLE_CLIENT.addEventListener('click', () => {
    _isClientMuted = !_isClientMuted;
});


function animate() {
    const now = getEpochUsec();
    //const firstBeatTime = _serverTimestampUsec + _offsetUsec;
    const elapsed = now - _serverTimestampUsec;

    if (elapsed >= 0) {
        const beatNumber = Math.floor(elapsed / _beatPeriodUsec);
        if (beatNumber !== _serverLastBeatN) {
            _serverLastBeatN = beatNumber;
            blinkOnce(EL_SERVER_BLINKER, _isServerMuted);
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

function startCustomBlinking() {
    if (_customBlinkStart !== null) return;
    _customBlinkStart = getEpochUsec();
    _clientLastBeatN = -1;
    requestAnimationFrame(animateBlink);
}

function animateBlink() {
    const now = getEpochUsec();
    const firstBeatTime = _customBlinkStart + _customOffsetUsec;
    const elapsed = now - firstBeatTime;

    if (elapsed >= 0) {
        const beatNumber = Math.floor(elapsed / _beatPeriodUsec);
        if (beatNumber !== _clientLastBeatN) {
            _clientLastBeatN = beatNumber;
            blinkOnce(EL_CLIENT_BLINKER, _isClientMuted);
        }
    }

    requestAnimationFrame(animateBlink);
}

function updateOffset(delta) {
    _customOffsetUsec = Math.max(0, _customOffsetUsec + delta);
    EL_OFFSET_LABEL.textContent = formatUsecToSec(_customOffsetUsec);
}

document.querySelectorAll('.pill-buttons button').forEach(btn => {
    btn.addEventListener('click', () => {
        const delta = parseInt(btn.dataset.delta, 10);
        updateOffset(delta);
    });
});

function formatUsecToSec(usec) {
    const seconds = usec / 1_000_000;
    return seconds.toFixed(3).padStart(6, '0'); // ensures 00.000 style
}

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
    EL_CONSOLE.appendChild(entry);
    EL_CONSOLE.scrollTop = EL_CONSOLE.scrollHeight;
}