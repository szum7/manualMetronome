document.addEventListener("DOMContentLoaded", () => {
    const loadResult = true; // Simulated DB result

    const consoleLog = document.getElementById("consoleLog");

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

    // Wizard button events
    document.getElementById("resetBtn").addEventListener("click", () => showStep("step-server-client"));
    document.getElementById("asServerBtn").addEventListener("click", () => showStep("step-server"));
    document.getElementById("asClientBtn").addEventListener("click", () => showStep("step-client"));

    // Initial step
    if (loadResult) {
        showStep("step-initial");
    } else {
        showStep("step-server-client");
    }
});
