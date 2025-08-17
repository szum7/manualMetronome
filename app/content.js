document.querySelectorAll(".tab-button").forEach(btn => {
    btn.addEventListener("click", () => {
        document.querySelectorAll(".tab-button").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        const tab = btn.dataset.tab;
        document.querySelectorAll(".tab-content").forEach(tc => tc.classList.remove("active"));
        document.getElementById(tab + "Tab").classList.add("active");
    });
});

EL.chooseTypePage.scToggle.addEventListener('change', () => {
    _metronomeServer.mute();
    _metronomeClient.mute();
});

EL.header.changeRoomIdBtn.addEventListener("click", () => {
    hide(EL.mainApp.page);
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

function hide(el) { el.classList.toggle('hidden', true); }
function show(el) { el.classList.toggle('hidden', false); }
function toggle(el, value) { el.classList.toggle('hidden', value); }