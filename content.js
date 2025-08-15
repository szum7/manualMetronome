document.querySelectorAll(".tab-button").forEach(btn => {
    btn.addEventListener("click", () => {
        document.querySelectorAll(".tab-button").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        const tab = btn.dataset.tab;
        document.querySelectorAll(".tab-content").forEach(tc => tc.classList.remove("active"));
        document.getElementById(tab + "Tab").classList.add("active");
    });
});

EL.setupTab.scToggle.toggle.addEventListener('change', () => {
    toggle(EL.setupTab.scToggle.contentServer, EL.setupTab.scToggle.toggle.checked);
    toggle(EL.setupTab.scToggle.contentClient, !EL.setupTab.scToggle.toggle.checked);
    _metronomeServer.mute();
    _metronomeClient.mute();
});

EL.header.changeSyncIdBtn.addEventListener("click", () => {
    hide(EL.mainApp);
    show(EL.initPage.page);
});

EL.setupTab.server.circle.addEventListener('click', () => {
    _metronomeServer.toggleMute();
});

EL.setupTab.client.circle.addEventListener('click', () => {
    _metronomeClient.toggleMute();
});

EL.header.pitchSelect.addEventListener("change", () => {
    _metronomeServer.setTickPitch(EL.header.pitchSelect.value);
    _metronomeClient.setTickPitch(EL.header.pitchSelect.value);
});

EL.header.typeSelect.addEventListener("change", () => {
    _metronomeServer.setTickType(EL.header.typeSelect.value);
    _metronomeClient.setTickType(EL.header.typeSelect.value);
});

function showSettingServerContent() {
    EL.setupTab.scToggle.toggle.checked = false;
    hide(EL.setupTab.scToggle.contentClient);
    show(EL.setupTab.scToggle.contentServer);
}

function showSettingClientContent() {
    EL.setupTab.scToggle.toggle.checked = true;
    hide(EL.setupTab.scToggle.contentServer);
    show(EL.setupTab.scToggle.contentClient);
}

function hide(id) { id.classList.toggle('hidden', true); }
function show(id) { id.classList.toggle('hidden', false); }
function toggle(id, value) { id.classList.toggle('hidden', value); }