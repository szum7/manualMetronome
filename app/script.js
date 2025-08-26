// RUN
generateUserId();

// Functions
document.getElementById("setMetronomeBtn").addEventListener("click", async () => {

    let bpm = parseInt(document.getElementById("bpm").value);

    try {

        let response = await postJsonAsync(API_SET_METRONOME, {
            room_id: _user.room_id,
            user_id: _user.user_id,
            bpm: bpm
        });

        if (response.success === true) {

            log(`${_user.room_id} => ${bpm} bpm`);

        } else {
            log(`Error: ${JSON.stringify(response)}`);
        }
    } catch (e) {
        log('Network error: ' + e.message);
    }
});

document.getElementById("startMetronome").addEventListener("click", async () => {
    try {

        let response = await postJsonAsync(API_GET_METRONOME, {
            room_id: _user.room_id,
            user_id: _user.user_id
        });

        if (response.success === true) {

            if (response.found1 === true && 
                response.found3 === true && 
                response.found3 === true) {

                document.getElementById("roomBpm").textContent = response.bpm;

                let startTime = calculateStartTimeUsec2(
                    response.ref_start_usec,
                    30,
                    getEpochUsec(),
                    response.offset,
                    4
                );
                _metronome.start(startTime, response.bpm);

            } else {
                log(`Cannot start metronome.`);
            }

        } else {
            log(`Error: ${JSON.stringify(response)}`);
        }
    } catch (e) {
        log('Network error: ' + e.message);
    }
});

document.getElementById("stopMetronome").addEventListener("click", () => {
    _metronome.stop();
});

// Init page: Set Room ID
EL.initPage.continueBtn.addEventListener("click", async () => {
    try {

        setRoomId(EL.initPage.roomIdInput.value.trim());

        let response = await postJsonAsync(API_GET_USER, {
            room_id: _user.room_id,
            user_id: _user.user_id
        });
        
        if (response.success === true) {

            if (isUserNew(response)) { // User's first time in the room

                hideAllPage();
                show(EL.chooseTypePage.page);

            } else { // User has already been in the room

                setUserFull(response);

                hideAllPage();
                show(EL.knownUserPage.page);
                EL.knownUserPage.ufpTypeLabel.textContent = _user.is_ref ? "server" : "client";

            }

        } else {
            log(`Error: ${JSON.stringify(response)}`);
        }

    } catch (e) {
        log('Network error: ' + e.message);
        return;
    }
});

EL.knownUserPage.resetBtn.addEventListener("click", async () => {
    try {

        const response = await postJsonAsync(API_DELETE_USER, {
            room_id: _user.room_id,
            user_id: _user.user_id
        });

        if (isSuccessfulCall(response)) {

            hideAllPage();
            show(EL.chooseTypePage.page);

        } else {
            log(`Error: ${JSON.stringify(response)}`);
        }
    } catch (e) {
        log('Network error: ' + e.message);
    }
});

EL.knownUserPage.continueBtn.addEventListener("click", async () => {

    if (_user.is_ref === true) { // Server

        hideAllPage();
        show(EL.mainApp.page);
        show(EL.setupTab.server.content);

        let startTime = calculateStartTimeUsec(
            parseInt(_user.timestamp_usec),
            30,
            getEpochUsec()
        );
        _metronomeServer.start(startTime);

        EL.mainApp.tabMetronome.click(); // Set to the Metronome tab

    } else { // Client

        let isServerSet = isServerSetLocally();
        let data;

        if (!isServerSet) {
            data = await checkForServerInRoom();
        }

        // Server found
        if (isServerSet === true || data.result === true) {

            if (data) {
                _user.server.user_id = data.response.server.user_id;
                _user.server.timestamp_usec = data.response.server.timestamp_usec;
            }

            hideAllPage();
            show(EL.mainApp.page);
            show(EL.setupTab.client.content);

            let startTime = calculateStartTimeUsec(
                parseInt(_user.server.timestamp_usec),
                30,
                getEpochUsec(),
                _user.offset_usec // TODO test needed? Works?
            );
            _metronomeClient.start(startTime);

            EL.mainApp.tabMetronome.click();

        }
        // No server found
        else {

            goTo("wait_for_server_page");

        }
    }
});

EL.chooseTypePage.continueBtn.addEventListener("click", async () => {

    let value = EL.chooseTypePage.scToggle.checked;

    if (value === true) { // Client

        hideAllPage();

        let isServerSet = isServerSetLocally();
        let data;

        if (!isServerSet) {
            data = await checkForServerInRoom();
        }

        // Server found
        if (isServerSet === true || data.result === true) {

            if (data) {
                _user.server.user_id = data.response.server.user_id;
                _user.server.timestamp_usec = data.response.server.timestamp_usec;
            }

            show(EL.mainApp.page);
            show(EL.setupTab.client.content);

            let startTime = calculateStartTimeUsec(
                parseInt(_user.server.timestamp_usec),
                30,
                getEpochUsec(),
                0
            );

            _metronomeClient.start(startTime);
            _metronomeClient.unmute();
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
            room_id: _user.room_id,
            user_id: _user.user_id,
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
        let offset = _metronomeClient.getOffset();

        const response = await postJsonAsync(API_SAVE_CLIENT_OFFSET, {
            room_id: _user.room_id,
            user_id: _user.user_id,
            offset_usec: offset
        });
        if (response.success === true) {

            _user.offset_usec = offset;
            log("Offset saved.");

        } else {
            log(`Error: ${JSON.stringify(response)}`);
        }
    } catch (e) {
        log('Network error: ' + e.message);
    }
});

EL.header.refreshBtn.addEventListener("click", () => {
    window.location.reload();
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
    _user.room_id = r.room_id;
    _user.user_id = r.user_id;
    _user.offset_usec = r.offset_usec;
    _user.timestamp_usec = r.timestamp_usec;
    _user.is_ref = r.is_ref;
    _user.server.user_id = r.server.user_id;
    _user.server.timestamp_usec = r.server.timestamp_usec;
}

async function checkForServerInRoom() {
    try {

        const response = await postJsonAsync(API_GET_USER, {
            room_id: _user.room_id,
            user_id: _user.user_id
        });

        if (response.success === true) {

            if (response.server.user_id === null) {
                return { result: false, response: null };
            }

            return { result: true, response: response };

        } else {
            log(`Error: ${JSON.stringify(response)}`);
        }

    } catch (e) {
        log('Network error: ' + e.message);
    }

    return { result: false, response: null };
}

// Setup/User Try Again
EL.waitForServerPage.checkBtn.addEventListener("click", async () => {

    let data = await checkForServerInRoom();

    if (data.result === true) {

        _user.server.user_id = data.response.server.user_id;
        _user.server.timestamp_usec = data.response.server.timestamp_usec;

        hideAllPage();
        show(EL.mainApp.page);
        show(EL.setupTab.client.content);

        let startTime = calculateStartTimeUsec(
            _user.server.timestamp_usec,
            30,
            getEpochUsec(),
            _user.offset_usec // TODO test needed? Works?
        );
        _metronomeClient.start(startTime);
        _metronomeClient.unmute();

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

// Setup/User offset +/-
EL.setupTab.client.offsetAdjustPills.forEach(btn => {
    btn.addEventListener('click', () => {

        let value = parseInt(btn.dataset.delta);

        let offset = _metronomeClient.getOffset() + parseInt(btn.dataset.delta);

        if (value === 0) { // Reset btn
            offset = 0;
        }

        _metronomeClient.setOffset(offset);
        EL.setupTab.client.offsetLabel.textContent = formatUsecToSec(offset);

    });
});

function setUserId(value) {
    _user.user_id = value;
    EL.header.userIdLabel.textContent = value;
}

function setRoomId(value) {
    value = parseInt(value);
    _user.room_id = value;
    EL.header.roomIdLabel.textContent = value;
}

function setUserRole(value) {
    _user.is_ref = value;
    EL.header.roleLabel.textContent = value === true ? "Server" : "Client";
}

function setUserOffset(value) {
    _user.offset_usec = value;
    EL.header.offsetLabel.textContent = formatUsecToSec(value);
}

function generateUserId() {
    let id = localStorage.getItem('cs_clientId');
    if (!id) {
        id = 'c_' + Math.random().toString(36).slice(2, 10);
        localStorage.setItem('cs_clientId', id);
    }
    setUserId(id);
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

function log(message) {
    const now = new Date();
    const timestamp = now.toTimeString().split(" ")[0] + "." + now.getMilliseconds().toString().padStart(5, '0');
    const entry = document.createElement("div");
    entry.textContent = `${timestamp} - ${message}`;
    EL.console.appendChild(entry);
    EL.console.scrollTop = EL.console.scrollHeight;
}

// TODO offsetUsec doesn't make sense i think and can be removed

function calculateStartTimeUsec(referenceStartUsec, bpm, nowUsec, offsetUsec = 0) {
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

function calculateStartTimeUsec2(referenceStartUsec, bpm, nowUsec, offsetUsec = 0, beatsPerMeasure = 4) {
    const beatPeriodUsec = (60 / bpm) * 1_000_000;
    const measurePeriodUsec = beatsPerMeasure * beatPeriodUsec;

    const adjustedStart = referenceStartUsec + offsetUsec;
    const elapsed = nowUsec - adjustedStart;

    if (elapsed < 0) {
        // The start is still in the future
        return adjustedStart;
    }

    // How many full measures have elapsed since adjustedStart
    const measuresElapsed = Math.floor(elapsed / measurePeriodUsec);

    // The start time of the NEXT measure (so "one" hits correctly)
    return adjustedStart + (measuresElapsed + 1) * measurePeriodUsec;
}


function setUserId(value) {
    _user.user_id = value;
    EL.header.userIdLabel.textContent = value;
}

function updateUser(response) {
    setUserRole(response.is_ref);
    setUserOffset(response.offset_usec);
    _user.server.user_id = response.server.user_id;
    _user.server.timestamp_usec = response.server.timestamp_usec;
}

function isSuccessfulCall(response) { return response.success === true; }
function isUserNew(response) { return response.found !== true; }
function isServerSet(response) { return !!response.server.user_id; }
function isServerSetLocally() { return !!_user.server.user_id; }

function hideAllPage() {
    document.querySelectorAll(".hidable").forEach(b => hide(b));
}


const _navTimeOriginUsec = Math.round(performance.timeOrigin * 1000);
function clientNowUsec() {
    return _navTimeOriginUsec + Math.round(performance.now() * 1000);
}
requestAnimationFrame(rafTick);
function rafTick() {
    const now_usecs = clientNowUsec();
    document.getElementById('unadj').textContent = formatUsec(now_usecs);
    if (_user.offset_usec !== null && _user.offset_usec > 0) {
        const adj_usecs = now_usecs - _user.offset_usec;
        document.getElementById('adj').textContent = formatUsec(adj_usecs);
    } else {
        document.getElementById('adj').textContent = '--:--:--.------';
    }
    requestAnimationFrame(rafTick);
}
function formatUsec(usec) {
    const ms = Math.floor(usec / 1000);
    const date = new Date(ms);
    const hh = String(date.getHours()).padStart(2, '0');
    const mm = String(date.getMinutes()).padStart(2, '0');
    const ss = String(date.getSeconds()).padStart(2, '0');
    const msec = String(date.getMilliseconds()).padStart(3, '0');
    const remUsec = String(usec % 1000000).padStart(6, '0');
    return `${hh}:${mm}:${ss}.${remUsec}`;
}