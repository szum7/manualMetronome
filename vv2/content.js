document.querySelectorAll(".tab-button").forEach(btn => {
    btn.addEventListener("click", () => {
        document.querySelectorAll(".tab-button").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        const tab = btn.dataset.tab;
        document.querySelectorAll(".tab-content").forEach(tc => tc.classList.remove("active"));
        document.getElementById(tab + "Tab").classList.add("active");
    });
});

EL_TOGGLE_SC.addEventListener('change', () => {
    EL_CONTENT_SERVER.classList.toggle('hidden', EL_TOGGLE_SC.checked);
    EL_CONTENT_CLIENT.classList.toggle('hidden', !EL_TOGGLE_SC.checked);
    _isServerMuted = true;
    _isClientMuted = true;
});

EL_CHANGE_SYNC_ID_BTN.addEventListener("click", () => {
    EL_MAIN_APP.classList.add("hidden");
    EL_SCREEN_SYNC_ID.classList.remove("hidden");
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