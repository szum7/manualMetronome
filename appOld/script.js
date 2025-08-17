// RUN
generateClientId();

// Functions
function updateClient(response) {
    setUserRole(response.is_ref);
    setUserOffset(response.offset_usec);
    _user.server.client_id = response.server.client_id;
    _user.server.timestamp_usec = response.server.timestamp_usec;
}

function isSuccessfulCall(response) { return response.success === true; }
function isUserNew(response) { return response.found !== true; }
function isServerSet(response) { return !!response.server.client_id; }

// Init page: Set Sync ID
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
        
        if (isSuccessfulCall(response)) {
            
            if (isUserNew(response)) { // User's first time in the room
                
                if (isServerSet(response)) { // Reference was found for the room

                    show(EL.setupTab.client.content);
                    showSettingClientContent();

                } else {

                    show(EL.setupTab.client.contentNoRef);
                    showSettingServerContent();

                }
            } 
            // User has already been in the room
            else {

                updateClient(response);

                // User is Server
                if (response.is_ref === true) {

                    showSettingServerContent();

                    let startTime = calculateStartTimeUsec(
                        _user.timestamp_usec,
                        30,
                        getEpochUsec()
                    );
                    _metronomeServer.start(startTime);
                    _metronomeServer.unmute();

                }
                // User is Client
                else {

                    // Reference was found for the Room
                    if (!!response.server.client_id) {

                        showSettingClientContent();
                        show(EL.setupTab.client.content);
                        
                        let startTime = calculateStartTimeUsec(
                            _user.server.timestamp_usec,
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
            }
        } else {
            log(`Error: ${JSON.stringify(response)}`);
        }
    } catch (e) {
        log('Network error: ' + e.message);
    }
});

// Setup/Server Save Reference
EL.setupTab.server.setReferenceBtn.addEventListener("click", async () => {
    let ts = getEpochUsec();
    try {
        const response = await postJson(API_SET_AS_SERVER, {
            sync_id: _user.sync_id,
            client_id: _user.client_id,
            timestamp_usec: ts
        });
        if (response.success === true) {
            log(`Room: ${_user.sync_id}, timestamp: ${ts}`);
            _metronomeServer.start(ts);
        } else {
            log(`Error: ${JSON.stringify(response)}`);
        }
    } catch (e) {
        log('Network error: ' + e.message);
    }
});

// Setup/Client Save Offset
EL.setupTab.client.saveOffsetBtn.addEventListener("click", async () => {

    try {
        const response = await postJson(API_SAVE_CLIENT_OFFSET, {
            sync_id: _user.sync_id,
            client_id: _user.client_id,
            offset_usec: _metronomeClient.getOffset()
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

// Setup/Client Try Again
EL.setupTab.client.tryAgainBtn.addEventListener("click", async () => {
    try {
        const response = await postJson(API_GET_CLIENT, {
            sync_id: _user.sync_id,
            client_id: _user.client_id
        });
        if (response.success === true) {
            
            log(`Updated.`);

            if (!isUserNew(response)) {
                updateClient(response);
            }

            if (!!response.server.client_id) {
                hide(EL.setupTab.client.contentNoRef);
                show(EL.setupTab.client.content);
            }

        } else {
            log(`Error: ${JSON.stringify(response)}`);
        }
    } catch (e) {
        log('Network error: ' + e.message);
    }
});

document.getElementById("clearDb").addEventListener("click", async () => {
    if (confirm("Are you sure you want to clear the Database?") == false) {
        return;
    }
    try {
        const response = await postJson("clear_db.php", {});
        if (response.success === true) {
            log(`Database cleared.`);
        } else {
            log(`Error: ${JSON.stringify(response)}`);
        }
    } catch (e) {
        log('Network error: ' + e.message);
    }
});

// Setup/Client offset +/-
EL.setupTab.client.offsetAdjustPills.forEach(btn => {
    btn.addEventListener('click', () => {
        
        _metronomeClient.setOffset(_metronomeClient.getOffset() + parseInt(btn.dataset.delta));
        EL.header.offsetLabel.textContent = _metronomeClient.getOffset();

    });
});

function setClientId(value) {
    _user.client_id = value;
    EL.header.clientIdLabel.textContent = value;
}

function setSyncId(value) {
    _user.sync_id = value;
    EL.header.syncIdLabel.textContent = value;
}

function setUserRole(value) {
    _user.is_ref = value;
    EL.header.roleLabel.textContent = value === true ? "Server" : "Client";
}

function setUserOffset(value) {
    _user.offset_usec = value;
    EL.header.offsetLabel.textContent = formatUsecToSec(value);
}

function generateClientId() {
    let id = localStorage.getItem('cs_clientId');
    if (!id) {
        id = 'c_' + Math.random().toString(36).slice(2, 10);
        localStorage.setItem('cs_clientId', clientId);
    }
    setClientId(id);
}

function getEpochUsec() {
    const ms = performance.timeOrigin + performance.now();
    return Math.round(ms * 1000);
}

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