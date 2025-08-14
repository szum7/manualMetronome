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
            <div class="field">Ref Client ID: <span id="refClientId"></span></div>

            <div class="select-pill">
                <label>Pitch</label>
                <select id="pitchSelect">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
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
                
                <!-- Wizard Step 1: Initial Page -->
                <div class="wizard-step" id="step-initial">
                    <button id="getOffsetBtn">Get Offset</button>
                    <button id="resetBtn">Reset</button>
                </div>

                <!-- Wizard Step 2: Server or Client -->
                <div class="wizard-step" id="step-server-client">
                    <button id="asServerBtn">As Server</button>
                    <button id="asClientBtn">As Client</button>
                </div>

                <!-- Wizard Step 3: Server Page -->
                <div class="wizard-step" id="step-server">
                    <div class="blinking-circle"></div>
                    <button id="setRefPointBtn">Set reference point</button>
                </div>

                <!-- Wizard Step 4: Client Page -->
                <div class="wizard-step" id="step-client">
                    <div class="blinking-circle"></div>
                    <div class="pill-buttons">
                        <button>+1</button>
                        <button>+10</button>
                        <button>+100</button>
                        <button>-1</button>
                        <button>-10</button>
                        <button>-100</button>
                    </div>
                    <button id="clientGetOffsetBtn">Get Offset</button>
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
