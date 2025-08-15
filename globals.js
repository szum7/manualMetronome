// Api urls
const API_GET_CLIENT = "get_client.php";
const API_SET_AS_SERVER = "save_as_server.php";
const API_SAVE_CLIENT_OFFSET = "save_client_offset.php";

// Elements
const EL_SYNC_ID_INPUT = document.getElementById("initialSyncId");
const EL_SET_SYNC_ID_BTN = document.getElementById("proceedBtn");
const EL_CONSOLE = document.getElementById("consoleLog");
const EL_SCREEN_SYNC_ID = document.getElementById("screenSyncId");
const EL_MAIN_APP = document.getElementById("mainApp");
const EL_CIRCLE_SERVER = document.getElementById("blinkerServerOuter");
const EL_CIRCLE_CLIENT = document.getElementById("blinkerClientOuter");
const EL_TOGGLE_SC = document.getElementById('toggle-sc');
const EL_CONTENT_SERVER = document.getElementById('content-server');
const EL_CONTENT_CLIENT = document.getElementById('content-client');
const EL_SERVER_BLINKER = document.getElementById('blinkerServerInner');
const EL_CLIENT_BLINKER = document.getElementById('blinkerClientInner');
const EL_OFFSET_LABEL = document.getElementById('offset-label');
const EL_CHANGE_SYNC_ID_BTN = document.getElementById("changeSyncIdBtn");
const EL_TRY_AGAIN_BTN = document.getElementById("tryAgainBtn");
const EL_TICK_PITCH = document.getElementById("pitchSelect");
const EL_TICK_TYPE = document.getElementById("typeSelect");

const EL = {
    "initPage": {
        "page": document.getElementById("syncIdPage"),
        "syncIdInput": document.getElementById("initialSyncId"),
        "okBtn": document.getElementById("proceedBtn"),
    },
    "mainApp": document.getElementById("mainApp"),
    "header": {
        "clientIdLabel": document.getElementById("clientIdLabel"),
        "syncIdLabel": document.getElementById("syncIdLabel"),
        "changeSyncIdBtn": document.getElementById("changeSyncIdBtn"),
        "roleLabel": document.getElementById("roleLabel"),
        "offsetLabel": document.getElementById("offsetLabel"),
        "pitchSelect": document.getElementById("pitchSelect"),
        "typeSelect": document.getElementById("typeSelect"),
    },
    "setupTab": {
        "scToggle": {
            "toggle": document.getElementById("scToggle"),
            "contentServer": document.getElementById("contentServer"),
            "contentClient": document.getElementById("contentClient"),
        },
        "server": {
            "setReferenceBtn": document.getElementById("setReferenceBtn"),
            "circle": document.getElementById("serverCircle"),
            "blinker": document.getElementById("serverBlinker"),
        },
        "client": {
            "circle": document.getElementById("clientCircle"),
            "blinker": document.getElementById("clientBlinker"),
            "saveOffsetBtn": document.getElementById("saveOffsetBtn"),
            "tryAgainBtn": document.getElementById("tryAgainBtn"),
        }
    },
    "console": document.getElementById("consoleLog"),
};

// Globals
let _metronomeServer = new Metronome(EL.setupTab.server.blinker, 30, EL.header.pitchSelect.value, EL.header.typeSelect.value);
let _metronomeClient = new Metronome(EL.setupTab.client.blinker, 30, EL.header.pitchSelect.value, EL.header.typeSelect.value);
let _user = {
    "sync_id": null,
    "client_id": null,
    "offset_usec": null,
    "server_timestamp_usec": null,
    "is_ref": null,
    "server": {
        "client_id": null,
        "server_timestamp_usec": null //let _serverTimestampUsec = null;
    }
};
// let _isMuted = {
//     "settings": {
//         "client": true,
//         "server": true
//     }
// };
// let _settings = {
//     "bpm": 30,
//     "isMuted": {
//         "client": true,
//         "server": true
//     }
// };
//let _syncId = null;
//let _clientId = null;

let _customBlinkStart = null;
let _customOffsetUsec = 0;
let _clientLastBeatN = -1;
//let _isClientMuted = true;
//let _isServerMuted = true;
const _bpm = 30;
const _beatPeriodUsec = (60 / _bpm) * 1_000_000;
let _serverLastBeatN = -1;
let _audioCtx;

//let _offsetUsec = null;