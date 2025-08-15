// RUN
generateClientId();

function setUserRole(value) {
    _user.is_ref = value;
    EL.header.roleLabel.textContent = value === true ? "Server" : "Client";
}

function setUserOffset(value) {
    _user.offset_usec = value;
    EL.header.offsetLabel.textContent = formatUsecToSec(value);
}

// Functions
EL.initPage.okBtn.addEventListener("click", async () => {

    const syncId = EL.initPage.syncIdInput.value.trim();
    setSyncId(syncId);

    hide(EL.initPage.page);
    show(EL.mainApp);

    try {

        const response = await postJson(API_GET_CLIENT, {
            sync_id: _user.sync_id,
            client_id: _user.client_id
        });

        console.log(response);

        // Successful call
        if (response.success === true) {

            // User has already been in the room
            if (response.found === true) {

                setUserRole(response.is_ref);
                setUserOffset(response.offset_usec);
                _user.server.client_id = response.server.client_id;
                _user.server.server_timestamp_usec = response.server.server_timestamp_usec;

                // User is Server
                if (response.is_ref === true) {

                    showSettingServerContent();

                    let startTime = calculateStartTimeUsec(
                        _user.server_timestamp_usec,
                        30,
                        getEpochUsec()
                    );
                    _metronomeServer.start(startTime);
                    _metronomeServer.unmute();

                }
                // User is Client
                else {

                    // Reference was found for the Client
                    if (!!response.server) {

                        showSettingClientContent();
                        
                        let startTime = calculateStartTimeUsec(
                            _user.server.server_timestamp_usec,
                            30,
                            getEpochUsec()
                        );
                        _metronomeClient.start(startTime);
                        _metronomeClient.unmute();

                    } 
                    // Reference not found / Should not be possible
                    else {
                        log("Client found without a Server.");
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

EL.setupTab.server.setReferenceBtn.addEventListener("click", async () => {
    let ts = getEpochUsec();
    try {
        const response = await postJson(API_SET_AS_SERVER, {
            sync_id: _user.sync_id,
            client_id: _user.client_id,
            server_timestamp_usec: ts
        });
        if (response.success === true) {
            log(`Room: ${_user.sync_id}, timestamp: ${ts}`);
            requestAnimationFrame(animate);
        } else {
            log(`Error: ${JSON.stringify(response)}`);
        }
    } catch (e) {
        log('Network error: ' + e.message);
    }
});

EL.setupTab.client.saveOffsetBtn.addEventListener("click", async () => {

    try {
        const response = await postJson(API_SAVE_CLIENT_OFFSET, {
            sync_id: _user.sync_id,
            client_id: _user.client_id,
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
    EL.header.syncIdLabel.textContent = value;
}

function updateClientIdLabel(value) {
    EL.header.clientIdLabel.textContent = value;
}

function setClientId(value) {
    _user.client_id = value;
    updateClientIdLabel(value);
}

function setSyncId(value) {
    _user.sync_id = value;
    updateSyncIdLabel(value);
}

function setOffsetUsec(valueUsec) {
    _offsetUsec = valueUsec;
    document.getElementById("offset").textContent = valueUsec;
}

function getEpochUsec() {
    const ms = performance.timeOrigin + performance.now();
    return Math.round(ms * 1000);
}





function setCustomOffsetUsec(value) {
    _customOffsetUsec = value;
    EL.header.offsetLabel.textContent = formatUsecToSec(value);
}






function initAudio() {
    _audioCtx = new (window.AudioContext || window.webkitAudioContext)();
}

function getBeepType() {
    return EL.header.typeSelect.value;
}

function getBeepFrequency() {
    return EL.header.pitchSelect.value;
}


function getEpochUsec() {
    const ms = performance.timeOrigin + performance.now();
    return Math.round(ms * 1000);
}

function updateOffset(delta) {
    _customOffsetUsec = Math.max(0, _customOffsetUsec + delta);
    EL.header.offsetLabel.textContent = formatUsecToSec(_customOffsetUsec);
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
    EL.console.appendChild(entry);
    EL.console.scrollTop = EL.console.scrollHeight;
}

function calculateStartTimeUsec(referenceStartUsec, bpm, nowUsec, offsetUsec = 0) {
    // TODO offsetUsec doesn't make sense i think and can be removed
    const beatPeriodUsec = (60 / bpm) * 1_000_000;
    const adjustedStart = referenceStartUsec + offsetUsec;
    const elapsed = nowUsec - adjustedStart;

    if (elapsed < 0) {
        // The start is in the future
        return adjustedStart;
    }

    const beatsElapsed = Math.floor(elapsed / beatPeriodUsec);
    return adjustedStart + (beatsElapsed + 1) * beatPeriodUsec;
}