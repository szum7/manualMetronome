<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metronome App</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- Header -->
    <header class="top-bar">
        <div class="field">Client ID: <span id="clientIdLabel"></span></div>
        <div class="field">Room ID: <span id="syncIdLabel">?</span></div>
        <button id="changeSyncIdBtn" class="small-btn hidden">Change</button>
        <div class="field">Role: <span id="roleLabel">?</span></div>
        <div class="field">Offset: <span id="offsetLabel">?</span></div>
        <div class="select-pill">
            <label>Pitch</label>
            <select id="pitchSelect">
                <option value="500">Low</option>
                <option value="1200">Medium</option>
                <option value="1800">High</option>
            </select>
        </div>
        <div class="select-pill">
            <label>Type</label>
            <select id="typeSelect">
                <option value="sine">Sine</option>
                <option value="square">Square</option>
                <option value="triangle">Triangle</option>
                <option value="sawtooth">Sawtooth</option>
            </select>
        </div>
        <button id="clearDb" class="small-btn">Clear DB</button>
    </header>

    <!-- Page -->
    <div id="syncIdPage" class="full-page-center">
        <h2>Enter Room ID</h2>
        <input id="initialSyncId" type="text" value="123">
        <button id="syncIdPageBtn">Continue</button>
    </div>

    <!-- Page -->
    <div id="typeSelectPage" class="full-page-center hidden">
        <div class="toggle-wrapper">
            <span>Server</span>
            <label class="switch">
                <input type="checkbox" id="scToggle">
                <span class="slider"></span>
            </label>
            <span>Client</span>
        </div>
        <button id="typeSelectPageBtn">Continue</button>
    </div>

    <!-- Page -->
    <div id="userFoundPage" class="full-page-center hidden">
        <p>You're already been to this room as <span id="ufpType"><b></b></span></p>
        <button id="ufpReset">Reset</button>
        <button id="ufpContinue">Continue</button>
    </div>

    <!-- Main App (hidden until proceed) -->
    <div id="mainApp" class="hidden">

        <!-- Tabs -->
        <nav class="tabs">
            <button class="tab-button active" data-tab="setup">Setup</button>
            <button class="tab-button" data-tab="metronome">Metronome</button>
        </nav>

        <!-- Content Area -->
        <main>
            <!-- Setup Tab -->
            <section id="setupTab" class="tab-content active">

                <div class="toggle-wrapper">
                    <span>Server</span>
                    <label class="switch">
                        <input type="checkbox" id="scToggle">
                        <span class="slider"></span>
                    </label>
                    <span>Client</span>
                </div>

                <!-- Server -->
                <div id="contentServer" class="hidden">
                    <div class="">
                        <button id="setReferenceBtn">Set reference point</button>
                        <p>Resets the room!</p>
                        <div id="serverCircle" class="blinker outer">
                            <div id="serverBlinker" class="blinker inner">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Client -->
                <div id="contentClient" class="hidden">
                    <!-- Client:Error -->
                    <div id="contentClientAlert" class="hidden">
                        <p>No server is set yet for the current room.</p>
                        <button id="tryAgainBtn">Check again</button>
                    </div>
                    <!-- Client:Main -->
                    <div id="contentClientInner" class="hidden">
                        <div id="clientCircle" class="blinker outer">
                            <div id="clientBlinker" class="blinker inner">
                            </div>
                        </div>
                        <div class="pill-buttons">
                            <button data-delta="1000">+1</button>
                            <button data-delta="10000">+10</button>
                            <button data-delta="100000">+100</button>
                            <button data-delta="-1000">-1</button>
                            <button data-delta="-10000">-10</button>
                            <button data-delta="-100000">-100</button>
                        </div>
                        <div>Offset: <span id="offset-label">0</span> s</div>
                        <button id="saveOffsetBtn">Save Offset</button>
                    </div>
                </div>

            </section>

            <!-- Metronome Tab -->
            <section id="metronomeTab" class="tab-content">
                <!-- Empty for now -->
            </section>
        </main>

        <!-- Fixed Console -->
        <footer class="console" id="consoleLog"></footer>
    </div>

    <script src="metronome.js"></script>
    <script src="globals.js"></script>
    <script src="content.js"></script>
    <script src="script.js"></script>
</body>

</html>