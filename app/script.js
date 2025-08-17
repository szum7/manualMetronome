// RUN
generateClientId();

// Functions
function setClientId(value) {
    _user.client_id = value;
    EL.header.clientIdLabel.textContent = value;
}

function updateClient(response) {
    setUserRole(response.is_ref);
    setUserOffset(response.offset_usec);
    _user.server.client_id = response.server.client_id;
    _user.server.timestamp_usec = response.server.timestamp_usec;
}

function isSuccessfulCall(response) { return response.success === true; }
function isUserNew(response) { return response.found !== true; }
function isServerSet(response) { return !!response.server.client_id; }
function isServerSetNow() { return !!_user.server.client_id; }

function hideAllPage() {
    document.querySelectorAll(".hidable").forEach(b => hide(b));
}

EL.header.refreshBtn.addEventListener("click", () => {
    window.location.reload();
});

// Init page: Set Sync ID
EL.initPage.continueBtn.addEventListener("click", async () => {

    setSyncId(EL.initPage.syncIdInput.value.trim());

    try {

        const response = await postJsonAsync(API_GET_CLIENT, {
            sync_id: _user.sync_id,
            client_id: _user.client_id
        });

        console.log(response);

        if (isSuccessfulCall(response)) {

            setUserFull(response);

            if (isUserNew(response)) { // User's first time in the room

                hideAllPage();
                show(EL.chooseTypePage.page);

            }
            // User has already been in the room
            else {

                hideAllPage();
                show(EL.knownUserPage.page);
                EL.knownUserPage.ufpTypeLabel.textContent = _user.is_ref ? "server" : "client";

            }
        } else {
            log(`Error: ${JSON.stringify(response)}`);
        }
    } catch (e) {
        log('Network error: ' + e.message);
    }
});

EL.knownUserPage.continueBtn.addEventListener("click", async () => {

    if (_client.is_ref === true) { // Server

        hideAllPage();
        show(EL.mainApp.page);
        show(EL.setupTab.server.content);

        let startTime = calculateStartTimeUsec(
            _user.timestamp_usec,
            30,
            getEpochUsec()
        );
        _metronomeServer.start(startTime);
        //_metronomeServer.unmute();
        
        EL.mainApp.tabMetronome.click(); // Set to the Metronome tab

    } else { // Client

        let isServer = isServerSetNow();
        let data;

        if (!isServer) {
            data = await checkForServerInRoom();
        }

        // Server found
        if (isServer === true || data.result === true) {

            hideAllPage();
            show(EL.setupTab.client.content);

            let startTime = calculateStartTimeUsec(
                _user.server.timestamp_usec,
                30,
                getEpochUsec(),
                _user.offset_usec // TODO test needed? Works?
            );
            _metronomeServer.start(startTime);

            EL.mainApp.tabMetronome.click();

        } 
        // No server found
        else {

            goTo("wait_for_server_page");

        }
    }
});

function goTo(page) {
    if (page === "known_user") {

    } else if (page === "choose_type") {

    } else if (page === "wait_for_server_page") {
        hideAllPage();
        show(EL.waitForServerPage.page);
    } else if (page === "") {

    }
}

function setUserFull(r) {
    _user.sync_id = r.sync_id;
    _user.client_id = r.client_id;
    _user.offset_usec = r.offset_usec;
    _user.timestamp_usec = r.timestamp_usec;
    _user.is_ref = r.is_ref;
    _user.server.client_id = r.server.client_id;
    _user.server.timestamp_usec = r.server.timestamp_usec;
}

EL.chooseTypePage.continueBtn.addEventListener("click", async () => {

    let value = EL.chooseTypePage.scToggle.checked;

    if (value === true) { // Client

        hideAllPage();

        let isServer = isServerSetNow();
        let data;

        if (!isServer) {
            data = await checkForServerInRoom();
            console.log(data);
        }

        // Server found
        if (isServer === true || data.result === true) {
            show(EL.mainApp.page);
            show(EL.setupTab.client.content);
        } 
        // No server found
        else {
            show(EL.waitForServerPage.page);
        }

    } else { // Server

        hideAllPage();
        show(EL.mainApp.page);
        show(EL.setupTab.server.content);

        let startTime = getEpochUsec();
        _metronomeServer.start(startTime);
        _metronomeServer.unmute();

    }
});

// Setup/Server Save Reference
EL.setupTab.server.setReferenceBtn.addEventListener("click", async () => {
    try {
        const response = await postJsonAsync(API_SET_AS_SERVER, {
            sync_id: _user.sync_id,
            client_id: _user.client_id,
            timestamp_usec: _metronomeServer.getStartTimeUsec()
        });
        if (response.success === true) {
            log(`Server timestamp saved.`);
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
        const response = await postJsonAsync(API_SAVE_CLIENT_OFFSET, {
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

async function checkForServerInRoom() {
    try {

        const response = await postJsonAsync(API_GET_CLIENT, {
            sync_id: _user.sync_id,
            client_id: _user.client_id
        });

        if (response.success === true) {

            log(response);

            if (isUserNew(response)) {
                return { result: false, response: null };
            }

            return { result: true, response: response };

        } else {
            log(`Error: ${JSON.stringify(response)}`);
        }

    } catch (e) {
        log('Network error: ' + e.message);
    } finally {
        return { result: false, response: null };
    }
}

// Setup/Client Try Again
EL.waitForServerPage.checkBtn.addEventListener("click", async () => {

    let data = await checkForServerInRoom();

    if (data.result === true) {
        updateClient(data.response);
        show(EL.mainApp.page);
        show(EL.setupTab.client.content);
    }
});

document.getElementById("clearDb").addEventListener("click", async () => {
    if (confirm("Are you sure you want to clear the Database?") == false) {
        return;
    }
    try {
        const response = await postJsonAsync("clear_db.php", {});
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
        localStorage.setItem('cs_clientId', id);
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

async function postJsonAsync(url, payload) {
    const r = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    });
    return r.json();
}

function postJson(url, payload) {
    console.log(JSON.stringify(payload));
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
        .then(response => response.json());
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