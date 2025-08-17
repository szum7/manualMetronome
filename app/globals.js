// Api urls
const API_GET_USER = "get_user.php";
const API_SET_AS_SERVER = "save_as_server.php";
const API_SAVE_CLIENT_OFFSET = "save_user_offset.php";
const API_DELETE_USER = "delete_user.php";

// Elements
const EL = {
    "initPage": {
        "page": document.getElementById("initPage"),
        "roomIdInput": document.getElementById("initialRoomId"),
        "continueBtn": document.getElementById("initPageBtn"),
    },
    "chooseTypePage": {
        "page": document.getElementById("chooseTypePage"),
        "scToggle": document.getElementById("scToggle"),
        "continueBtn": document.getElementById("chooseTypePageBtn"),
    },
    "knownUserPage": {
        "page": document.getElementById("knownUserPage"),
        "resetBtn": document.getElementById("kupResetBtn"),
        "continueBtn": document.getElementById("kupContinueBtn"),
        "ufpTypeLabel": document.getElementById("ufpType"),
    },
    "waitForServerPage": {
        "page": document.getElementById("waitForServerPage"),
        "checkBtn": document.getElementById("wfspCheckBtn")
    },
    "mainApp": {
        "page": document.getElementById("mainApp"),
        "tabSetup": document.getElementById("tabSetup"),
        "tabMetronome": document.getElementById("tabMetronome"),
    },
    "header": {
        "userIdLabel": document.getElementById("userIdLabel"),
        "roomIdLabel": document.getElementById("roomIdLabel"),
        "changeRoomIdBtn": document.getElementById("changeRoomIdBtn"),
        "roleLabel": document.getElementById("roleLabel"),
        "offsetLabel": document.getElementById("offsetLabel"),
        "pitchSelect": document.getElementById("pitchSelect"),
        "typeSelect": document.getElementById("typeSelect"),
        "refreshBtn": document.getElementById("refreshPage"),
    },
    "setupTab": {
        "server": {
            "content": document.getElementById("contentServer"),
            "setReferenceBtn": document.getElementById("setReferenceBtn"),
            "circle": document.getElementById("serverCircle"),
            "blinker": document.getElementById("serverBlinker"),
        },
        "client": {
            "content": document.getElementById("contentClient"),
            // "contentNoRef": document.getElementById("contentClientAlert"),
            // "contentInner": document.getElementById("contentClientInner"),
            "circle": document.getElementById("clientCircle"),
            "blinker": document.getElementById("clientBlinker"),
            "saveOffsetBtn": document.getElementById("saveOffsetBtn"),
            "tryAgainBtn": document.getElementById("tryAgainBtn"),
            "offsetLabel": document.getElementById("bottomOffsetLabel"),
            "offsetAdjustPills": document.querySelectorAll('.pill-buttons button'),
        }
    },
    "console": document.getElementById("consoleLog"),
};

// Globals
let _metronomeServer = new Metronome(EL.setupTab.server.blinker, 30, EL.header.pitchSelect.value, EL.header.typeSelect.value);
let _metronomeClient = new Metronome(EL.setupTab.client.blinker, 30, EL.header.pitchSelect.value, EL.header.typeSelect.value);
let _user = {
    "room_id": null,
    "user_id": null,
    "offset_usec": null,
    "timestamp_usec": null,
    "is_ref": null,
    "server": {
        "user_id": null,
        "timestamp_usec": null
    }
};