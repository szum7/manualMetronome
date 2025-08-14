document.addEventListener("DOMContentLoaded", () => {
    const loadResult = true; // Simulated DB result

    const consoleLog = document.getElementById("consoleLog");
    const syncIdEntry = document.getElementById("syncIdEntry");
    const mainApp = document.getElementById("mainApp");

    function log(message) {
        const now = new Date();
        const timestamp = now.toTimeString().split(" ")[0] + "." + now.getMilliseconds().toString().padStart(5, '0');
        const entry = document.createElement("div");
        entry.textContent = `${timestamp} - ${message}`;
        consoleLog.appendChild(entry);
        consoleLog.scrollTop = consoleLog.scrollHeight;
    }

    function showStep(stepId) {
        document.querySelectorAll(".wizard-step").forEach(step => step.classList.remove("active"));
        document.getElementById(stepId).classList.add("active");
        log(`Showing step: ${stepId}`);
    }

    // Proceed from Sync ID entry
    document.getElementById("proceedBtn").addEventListener("click", () => {
        const syncId = document.getElementById("initialSyncId").value.trim();
        document.getElementById("syncIdLabel").textContent = syncId;
        syncIdEntry.classList.add("hidden");
        mainApp.classList.remove("hidden");

        if (loadResult) {
            showStep("step-initial");
        } else {
            showStep("step-server-client");
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
    document.getElementById("resetBtn").addEventListener("click", () => showStep("step-server-client"));
    document.getElementById("asServerBtn").addEventListener("click", () => showStep("step-server"));
    document.getElementById("asClientBtn").addEventListener("click", () => showStep("step-client"));
});
