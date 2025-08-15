// Api urls
const API_GET_CLIENT = "get_client.php";
const API_SET_AS_SERVER = "save_as_server.php";
const API_SAVE_CLIENT_OFFSET = "save_client_offset.php";

// Elements
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

// Globals
let _syncId = null;
let _clientId = null;
//let _offsetUsec = null;
let _serverTimestampUsec = null;
let _customBlinkStart = null;
let _customOffsetUsec = 0;
let _clientLastBeatN = -1;
let _isClientMuted = true;
let _isServerMuted = true;
const _bpm = 30;
const _beatPeriodUsec = (60 / _bpm) * 1_000_000;
let _serverLastBeatN = -1;
let _audioCtx;