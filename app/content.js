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

document.getElementById("volumeSelect").addEventListener('click', () => {
    let value = parseFloat(document.getElementById("volumeSelect").value);
    _metronomeServer.setVolume(value);
    _metronomeClient.setVolume(value);
    _metronome.setVolume(value);
});

EL.header.pitchSelect.addEventListener("change", () => {
    let value = parseInt(EL.header.pitchSelect.value);
    _metronomeServer.setTickPitch(value);
    _metronomeClient.setTickPitch(value);
    _metronome.setTickPitch(value);
});

EL.header.typeSelect.addEventListener("change", () => {
    let value = EL.header.typeSelect.value;
    _metronomeServer.setTickType(value);
    _metronomeClient.setTickType(value);
    _metronome.setTickType(value);
});

function hide(el) { el.classList.toggle('hidden', true); }
function show(el) { el.classList.toggle('hidden', false); }
function toggle(el, value) { el.classList.toggle('hidden', value); }