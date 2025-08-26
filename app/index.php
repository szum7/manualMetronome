<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metronome App</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="dropstyles.css">
</head>

<body>
    <!-- Header -->
    <header id="header" class="top-bar hidden hidable">
        <div class="field">User ID: <span id="userIdLabel">?</span></div>
        <div class="field">Room ID: <span id="roomIdLabel">?</span></div>
        <button id="changeRoomIdBtn" class="small-btn">Change</button>
        <div class="field">Role: <span id="roleLabel">?</span></div>
        <div class="field">Offset: <span id="offsetLabel">?</span></div>
        <div class="field">
            <label>Pitch: </label>
            <select id="pitchSelect">
                <option value="500">Low</option>
                <option value="1200">Medium</option>
                <option value="1800">High</option>
            </select>
        </div>
        <div class="field">
            <label>Type: </label>
            <select id="typeSelect">
                <option value="sine">Sine</option>
                <option value="square">Square</option>
                <option value="triangle">Triangle</option>
                <option value="sawtooth">Sawtooth</option>
            </select>
        </div>
        <div class="field">
            <label>Volume: </label>
            <select id="volumeSelect">
                <option value="0.3">3</option>
                <option value="0.4">4</option>
                <option value="0.5">5</option>
                <option value="0.6">6</option>
                <option value="0.7">7</option>
                <option value="0.8">8</option>
                <option value="0.9">9</option>
                <option value="1.0">10</option>
            </select>
        </div>
    </header>




    <!-- Page: Enter Room -->
    <div id="initPage" class="full-page-center hidable">
        <h2>Enter Room ID</h2>
        <input id="initialRoomId" class="inp2 bg-bg1 mb10" type="text" value="123">
        <button id="initPageBtn" class="btn2 bg-btn1">Continue</button>
    </div>

    <!-- Page: Choose server-client type -->
    <div id="chooseTypePage" class="full-page-center hidable hidden">
        <div class="toggle-wrapper mb20">
            <span>Server</span>
            <label class="switch">
                <input type="checkbox" id="scToggle">
                <span class="slider"></span>
            </label>
            <span>Client</span>
        </div>
        <button id="chooseTypePageBtn" class="btn2 bg-btn1">Continue</button>
    </div>

    <!-- Page: Known user -->
    <div id="knownUserPage" class="full-page-center hidable hidden">
        <p>User found as <b><span id="ufpType">?</span></b>.</p>
        <div>
            <button id="kupResetBtn" class="btn2 btn-ghost mr10">Reset</button>
            <button id="kupContinueBtn" class="btn2 bg-btn1">Continue</button>
        </div>
    </div>

    <!-- Page: No server yet -->
    <div id="waitForServerPage" class="full-page-center hidable hidden">
        <p>No server set yet. Wait or check again.</p>
        <button id="wfspCheckBtn" class="btn2 bg-btn1">Check</button>
    </div>



    <!-- Main App (hidden until proceed) -->
    <div id="mainApp" class="hidable hidden">

        <!-- Tabs -->
        <nav class="tabs">
            <button id="tabSetup" class="tab-button active" data-tab="setup">Setup</button>
            <button id="tabMetronome" class="tab-button" data-tab="metronome">Metronome</button>
        </nav>

            <!-- Setup Tab -->
            <section id="setupTab" class="tab-content active">
                <div class="content">

                    <div class="clock-column">
                        <div class="clock-line">
                            <div class="clock-label">Unadjusted:</div>
                            <div class="clock-display" id="unadj">--:--:--.------</div>
                        </div>
                        <div class="clock-line">
                            <div class="clock-label">Adjusted:</div>
                            <div class="clock-display" id="adj">--:--:--.------</div>
                        </div>
                    </div>

                    <!-- Server -->
                    <div id="contentServer" class="hidable hidden">
                        <button id="setReferenceBtn" class="btn2 w100 bg-btn2">Set reference point</button>
                        <div class="circle-wrap">
                            <div id="serverCircle" class="blinker outer">
                                <div id="serverBlinker" class="blinker inner"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Client -->
                    <div id="contentClient" class="hidable hidden">
                        <div class="content">
                            <div class="circle-wrap mb10">
                                <div id="clientCircle" class="blinker outer">
                                    <div id="clientBlinker" class="blinker inner">
                                    </div>
                                </div>
                            </div>
                            <div class="pill-buttons mb20">
                                <button data-delta="1000">+1</button>
                                <button data-delta="10000">+10</button>
                                <button data-delta="100000">+100</button>
                                <button data-delta="0">Reset</button>
                                <button data-delta="-1000">-1</button>
                                <button data-delta="-10000">-10</button>
                                <button data-delta="-100000">-100</button>
                            </div>
                            <div class="center mb20">Offset: <span id="bottomOffsetLabel">0</span> s</div>
                            <button id="saveOffsetBtn" class="btn2 bg-btn2 w100">Save Offset</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Metronome Tab -->
            <section id="metronomeTab" class="tab-content">
                <div class="content">

                    <div class="flex-start mb10">
                        <button id="setMetronomeBtn" class="btn2 bg-btn1 w100">Set</button>
                        <div class="tempo-input-wrap">
                            <input type="number" id="bpm" value="120" />
                            <span class="tempo-label">bpm</span>
                        </div>
                    </div>

                    <button id="startMetronome" class="btn2 w100 bg-btn2 mb10">Join</button>
                    <button id="stopMetronome" class="btn2 w100 btn-ghost mb10">Stop</button>

                    <div class="center mb10">
                        <div id="metronome-circles">
                            <div class="circle"></div>
                            <div class="circle"></div>
                            <div class="circle"></div>
                            <div class="circle"></div>
                        </div>
                        <div class="mt-bpm"><span id="roomBpm">?</span> bpm</div>
                    </div>

                </div>
            </section>
    </div>
    
    <footer class="console" id="consoleLog"></footer>
    <footer class="btns">
        <button id="refreshPage" class="small-btn">Refresh page</button>
        <div class="">version 1.0</div>
        <button id="clearDb" class="small-btn red">Clear DB</button>
    </footer>

    <script src="metronome.js"></script>
    <script src="metronome4.js"></script>
    <script src="globals.js"></script>
    <script src="content.js"></script>
    <script src="script.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/eruda"></script>
    <script>
        eruda.init();
    </script>
</body>

</html>