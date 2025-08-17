// Api urls
const API_GET_CLIENT = "get_client.php";
const API_SET_AS_SERVER = "save_as_server.php";
const API_SAVE_CLIENT_OFFSET = "save_client_offset.php";

// Elements
const EL = {
    "initPage": {
        "page": document.getElementById("syncIdPage"),
        "syncIdInput": document.getElementById("initialSyncId"),
        "okBtn": document.getElementById("syncIdPageBtn"),
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
            "offsetAdjustPills": document.querySelectorAll('.pill-buttons button'),
            "contentNoRef": document.getElementById("contentClientAlert"),
            "content": document.getElementById("contentClientInner"),
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
    "timestamp_usec": null,
    "is_ref": null,
    "server": {
        "client_id": null,
        "timestamp_usec": null
    }
};