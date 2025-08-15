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
    toggle(EL_CONTENT_SERVER, EL_TOGGLE_SC.checked);
    toggle(EL_CONTENT_CLIENT, !EL_TOGGLE_SC.checked);
    _isServerMuted = true;
    _isClientMuted = true;
});

EL_CHANGE_SYNC_ID_BTN.addEventListener("click", () => {
    hide(EL_MAIN_APP);
    show(EL_SCREEN_SYNC_ID);
});

function setToServerOrClient(name) {
    if (name === "server") {
        EL_TOGGLE_SC.checked = false;
        hide(EL_CONTENT_CLIENT);
        show(EL_CONTENT_SERVER);
    } else if (name === "client") {
        EL_TOGGLE_SC.checked = true;
        hide(EL_CONTENT_SERVER);
        show(EL_CONTENT_CLIENT);
    }
}

function hide(id) { id.classList.toggle('hidden', true); }
function show(id) { id.classList.toggle('hidden', false); }
function toggle(id, value) { id.classList.toggle('hidden', value); }