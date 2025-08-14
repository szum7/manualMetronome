document.addEventListener("DOMContentLoaded", () => {

    const API_GET_CLIENT = "get_client.php";

    const loadResult = true; // Simulated DB result

    const consoleLog = document.getElementById("consoleLog");
    const syncIdEntry = document.getElementById("syncIdEntry");
    const mainApp = document.getElementById("mainApp");

    let _sync_id = null;
    let _client_id = null;
    let _offsetUsec = null;

    // RUN
    generateClientId();

    function showStep(stepId) {
        document.querySelectorAll(".wizard-step").forEach(step => step.classList.remove("active"));
        document.getElementById(stepId).classList.add("active");
        log(`Showing step: ${stepId}`);
    }

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

    // Proceed from Sync ID entry
    document.getElementById("proceedBtn").addEventListener("click", () => {
        const syncId = document.getElementById("initialSyncId").value.trim();
        setSyncId(syncId);

        syncIdEntry.classList.add("hidden");
        mainApp.classList.remove("hidden");

        try {
            const res = postJson(API_GET_CLIENT, {
                sync_id: _sync_id,
                client_id: _clientId
            });
            if (res.success === true) {

                if (res.found === true) {
                    setOffsetUsec(res.offset_usec);
                    // TODO set blinking
                    //showStep("step-client");
                } else {

                }
            } else {
                log(`Error: ${JSON.stringify(res)}`);
            }
        } catch (e) {
            log('Network error: ' + e.message);
        }

        log(`Sync ID set to ${syncId}`);
    });

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

    // Wizard navigation
    // document.getElementById("resetBtn").addEventListener("click", () => showStep("step-server-client"));
    // document.getElementById("asServerBtn").addEventListener("click", () => showStep("step-server"));
    // document.getElementById("asClientBtn").addEventListener("click", () => showStep("step-client"));
});

function postJson(url, payload) {
    console.log(JSON.stringify(payload));
    const r = fetch(url, {
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

const toggle = document.getElementById('toggle-switch');
const contentA = document.getElementById('content-a');
const contentB = document.getElementById('content-b');

toggle.addEventListener('change', () => {
  contentA.classList.toggle('hidden', toggle.checked);
  contentB.classList.toggle('hidden', !toggle.checked);
});