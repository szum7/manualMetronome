<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metronome App</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- Initial Sync ID Page -->
    <div id="syncIdEntry" class="full-page-center">
        <h2>Enter Sync ID</h2>
        <input id="initialSyncId" type="text" value="123">
        <button id="proceedBtn">Proceed</button>
    </div>

    <!-- Main App (hidden until proceed) -->
    <div id="mainApp" class="hidden">

        <!-- Top Fixed Bar -->
        <header class="top-bar">
            <div class="field">Client ID: <span id="clientId"></span></div>
            <div class="field">Sync ID: <span id="syncIdLabel"></span></div>
            <button id="changeSyncIdBtn" class="small-btn">Change</button>
            <div class="field">Offset: <span id="offset"></span></div>
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
        </header>

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
                        <input type="checkbox" id="toggle-sc">
                        <span class="slider"></span>
                    </label>
                    <span>Client</span>
                </div>

                <!-- Server -->
                <div id="content-server">
                    <div class="" id="step-server">
                        <div class="blinking-circle"></div>
                        <button id="setRefPointBtn">Set reference point</button>
                        <p>Resets the room!</p>

                        <div id="outer-circle" class="blinker outer">
                            <div id="inner-circle" class="blinker inner">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Client -->
                <div id="content-client" class="hidden">
                    <div class="" id="step-client">                        
                        <div id="outer-circle2" class="blinker outer">
                            <div id="inner-circle2" class="blinker inner">
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
                        <div>Offset: <span id="offset-label">0</span></div>
                        <button id="clientGetOffsetBtn">Save Offset</button>
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

    <script src="script.js"></script>
</body>

</html>