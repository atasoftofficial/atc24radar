<?php
// ATASOFT ATC24 Radar - Flight Planner.
//
// Served at /flight-planner (see the single rewrite rule in .htaccess) and
// also directly as flight-planner.php.
//
// This page adds a pilot-side flight planner to the SAME application as the
// radar: same palette and type (public/style.css, unchanged variables), same
// airport records (src/data/AirportInfo.js), same waypoint records
// (src/data/fixes.js - the table the radar draws), same aircraft feed
// (data.php, the radar's own ~1s polling endpoint) and same published ATIS
// endpoint (atis-list.php). It introduces no endpoint of its own and no
// second copy of any of that data.
//
// The radar itself (index.php) is untouched by this file.
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Flight Planner - ATC24 Radar - ATASOFT</title>
    <meta name="description" content="Flight planner for ATC24 Radar by ATASOFT - build a route from the real Fixes table, read the published ATIS, and generate the ATC24 Discord flight-plan command." />
    <meta name="theme-color" content="#060814" />

    <link rel="icon" type="image/svg+xml" href="public/assets/favicon.svg" />
    <link rel="alternate icon" type="image/png" href="public/assets/icon.png" />
    <link rel="apple-touch-icon" href="public/assets/apple-touch-icon.png" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php require __DIR__ . '/inc/fonts.php'; ?>
    <link rel="stylesheet" href="public/style.css?v=<?= @filemtime(__DIR__ . '/public/style.css') ?: time() ?>">
    <link rel="stylesheet" href="public/style-planner.css?v=<?= @filemtime(__DIR__ . '/public/style-planner.css') ?: time() ?>">

    <script>
        // Same-origin backend, exactly like index.php.
        window.ATASOFT_API_HOST = null;
    </script>
    <script type="module" src="src/flightPlanner.js?v=<?= @filemtime(__DIR__ . '/src/flightPlanner.js') ?: time() ?>"></script>
</head>

<body class="fp-body">
    <div id="top-bar" class="fp-topbar">
        <a id="name" href="index.php">ATC24 Radar <span style="opacity:.55; font-weight:500;">by ATASOFT</span></a>
        <div class="fp-nav">
            <a class="menu-button fp-navlink" href="index.php">&larr; RADAR</a>
            <span class="menu-button fp-navlink fp-navlink-active">FLIGHT PLANNER</span>
            <a class="menu-button fp-navlink" href="atis.php">ATIS</a>
            <a class="menu-button fp-navlink" href="about.html">ABOUT</a>
        </div>
        <div class="fp-topbar-spacer"></div>
        <div id="fp-status" class="atasoft-status" title="Aircraft data connection status">
            <span class="atasoft-status__dot" data-state="connecting"></span>
            <span class="atasoft-status__label">CONNECTING</span>
        </div>
    </div>

    <main class="fp-main">
        <!-- ============================ SCOPE ============================
             The planner's map. It is the radar's own picture - the same coast
             artwork, airspace, range rings, approach charts, ALL of the
             project's fixes, the measured runway thresholds and the live
             aircraft from the radar's own feed - in its own scope element, so
             it can be zoomed, panned and clicked without touching index.php.
             Clicking a fix adds/removes it from the route. -->
        <section class="fp-scope-section">
            <div class="fp-scope-bar">
                <span class="fp-scope-title">PLAN VIEW - RADAR MAP</span>
                <span class="fp-scope-actions">
                    <button id="fp-zoom-in" class="fp-mini" type="button" title="Zoom in">+</button>
                    <button id="fp-zoom-out" class="fp-mini" type="button" title="Zoom out">&minus;</button>
                    <button id="fp-fit-route" class="fp-mini" type="button" title="Zoom to the planned route">FIT ROUTE</button>
                    <button id="fp-fit-world" class="fp-mini" type="button" title="Zoom out to the whole map">WORLD</button>
                    <button id="fp-follow" class="fp-mini fp-toggle is-on" type="button" title="Keep the scoped aircraft centred">FOLLOW A/C</button>
                    <button id="fp-show-fixes" class="fp-mini fp-toggle is-on" type="button" title="Show every fix from the project's Fixes table">FIXES</button>
                    <button id="fp-show-charts" class="fp-mini fp-toggle is-on" type="button" title="Show the published approach charts of the two selected airports">CHARTS</button>
                    <span id="fp-scope-status" class="fp-scope-status"></span>
                </span>
            </div>
            <svg id="fp-plan-view" class="fp-plan-view" preserveAspectRatio="xMidYMid slice"></svg>
            <div class="fp-scope-hint">
                Drag to pan, wheel (or + / &minus;) to zoom. <strong>Click a fix to add it to the route</strong>;
                click it again to remove it - the number badge next to a selected fix is its place in the route.
                Click an aircraft to load its filed plan into the form. Green line = departure runway, amber = arrival runway.
            </div>
        </section>

        <!-- ============================ PLAN ============================ -->
        <section class="fp-col">
            <div class="fp-card">
                <div class="fp-card-title">FLIGHT PLAN</div>
                <div class="fp-grid">
                    <label class="fp-field">
                        <span>In-game callsign <em>ingamecallsign</em></span>
                        <input id="fp-ingame-callsign" type="text" maxlength="24" autocomplete="off" spellcheck="false" placeholder="e.g. Delta-39W">
                    </label>
                    <label class="fp-field">
                        <span>Callsign <em>callsign</em></span>
                        <input id="fp-callsign" type="text" maxlength="24" autocomplete="off" spellcheck="false" placeholder="e.g. DAL3454">
                    </label>
                    <label class="fp-field">
                        <span>Roblox / in-game name <em>ingamename</em></span>
                        <input id="fp-roblox-name" type="text" maxlength="32" autocomplete="off" spellcheck="false" placeholder="e.g. SaabirAlam">
                    </label>
                    <label class="fp-field">
                        <span>Aircraft <em>aircraft</em></span>
                        <input id="fp-aircraft" type="text" list="fp-aircraft-list" autocomplete="off" spellcheck="false" placeholder="e.g. Airbus A320">
                        <datalist id="fp-aircraft-list"></datalist>
                    </label>
                    <label class="fp-field">
                        <span>Flight rules <em>flightrules</em></span>
                        <select id="fp-rules">
                            <option value="IFR">IFR</option>
                            <option value="VFR">VFR</option>
                        </select>
                    </label>
                    <!-- A validated free field with the everyday levels offered
                         as suggestions, not a closed list: this network's own
                         filed plans include levels that are not multiples of
                         five (e.g. 067), and a select would refuse a value the
                         game itself accepts. -->
                    <label class="fp-field">
                        <span>Flight level <em>flightlevel</em></span>
                        <input id="fp-flight-level" type="text" list="fp-flight-level-list" inputmode="numeric"
                            autocomplete="off" spellcheck="false" maxlength="12" placeholder="e.g. 060 or any">
                        <datalist id="fp-flight-level-list"></datalist>
                    </label>
                </div>
            </div>

            <div class="fp-card">
                <div class="fp-card-title">DEPARTURE</div>
                <div class="fp-grid">
                    <label class="fp-field">
                        <span>Departure airport</span>
                        <select id="fp-dep-airport"></select>
                    </label>
                    <label class="fp-field">
                        <span>Departure runway</span>
                        <select id="fp-dep-runway"></select>
                    </label>
                </div>
                <div id="fp-dep-atis" class="fp-atis"></div>
            </div>

            <div class="fp-card">
                <div class="fp-card-title">ARRIVAL</div>
                <div class="fp-grid">
                    <label class="fp-field">
                        <span>Arrival airport</span>
                        <select id="fp-arr-airport"></select>
                    </label>
                    <label class="fp-field">
                        <span>Arrival runway</span>
                        <select id="fp-arr-runway"></select>
                    </label>
                </div>
                <div id="fp-arr-atis" class="fp-atis"></div>
            </div>
        </section>

        <!-- ============================ ROUTE ============================ -->
        <section class="fp-col">
            <div class="fp-card">
                <div class="fp-card-title">ROUTE - REAL FIXES ONLY</div>
                <div class="fp-hint">
                    Every waypoint here comes from the project's own Fixes table
                    (the records the radar draws). Nothing is invented, and a
                    similar-looking name is never substituted.
                </div>
                <label class="fp-field fp-field-wide">
                    <span>Search fixes</span>
                    <input id="fp-fix-search" type="text" autocomplete="off" spellcheck="false" placeholder="Type a fix name, e.g. KNIFE">
                </label>
                <div id="fp-fix-results" class="fp-fix-results"></div>
                <div class="fp-section-label">Selected route <span id="fp-fix-count" class="fp-count"></span></div>
                <ol id="fp-fix-selected" class="fp-fix-selected"></ol>
                <div id="fp-route-tokens" class="fp-route-tokens"></div>
            </div>

        </section>

        <!-- ============================ COMMAND ============================ -->
        <section class="fp-col">
            <div class="fp-card">
                <div class="fp-card-title">ATC24 DISCORD COMMAND</div>
                <div class="fp-hint">
                    Sent by the pilot in the ATC24 Discord to file the plan. This
                    page only builds the text - copying it does not file anything.
                </div>
                <label class="fp-field fp-field-wide">
                    <span>Airport name form in departing / arriving</span>
                    <select id="fp-airport-mode"></select>
                </label>
                <pre id="fp-command" class="fp-command"></pre>
                <div class="fp-command-actions">
                    <button id="fp-copy-command" class="fp-button fp-button-primary" type="button">COPY FLIGHT PLAN COMMAND</button>
                    <span id="fp-copy-feedback" class="fp-copy-feedback"></span>
                </div>
                <div id="fp-errors" class="fp-errors"></div>
            </div>

            <div class="fp-card">
                <div class="fp-card-title">FLIGHT SESSION</div>
                <div class="fp-hint">
                    A session tracks the live feed, which is how it can tell you it
                    has actually found your aircraft - it never assumes.
                </div>
                <div class="fp-session-actions">
                    <button id="fp-start-flight" class="fp-button fp-button-primary" type="button">START FLIGHT</button>
                    <button id="fp-pause-flight" class="fp-button" type="button">PAUSE</button>
                    <button id="fp-end-flight" class="fp-button fp-button-danger" type="button">END SESSION</button>
                    <a id="fp-open-follow" class="fp-button fp-button-disabled" href="follow-flight.php">FOLLOW VIEW</a>
                </div>
                <div id="fp-session-state" class="fp-session-state"><span class="fp-state-chip">PRE-FLIGHT</span></div>
                <div id="fp-session-detail" class="fp-session-detail"></div>
            </div>

            <div class="fp-card">
                <div class="fp-card-title">LIVE NAVIGATION</div>
                <div id="fp-nav" class="fp-nav-grid"></div>
                <div id="fp-nav-route" class="fp-nav-route"></div>
            </div>

            <div class="fp-card">
                <div class="fp-card-title fp-instruction-title">LIVE INSTRUCTION <span id="fp-instruction-tick" class="fp-live-tick">--:--:--</span></div>
                <div class="fp-hint">
                    Recomputed every second from the same feed: the heading to the
                    next leg and the level this flight filed. A value the feed cannot
                    measure is named as missing instead of being invented.
                </div>
                <div id="fp-instruction" class="fp-instruction"></div>
            </div>

            <div class="fp-card">
                <div class="fp-card-title">PILOT PHRASES</div>
                <div class="fp-hint">
                    What you say on the radio, pre-filled from this plan and your live
                    navigation - clearance request, readback, position report, approach.
                    Values this build does not have stay visibly missing. Copying only
                    copies: nothing is transmitted by this page.
                </div>
                <div id="fp-pilot-phrases" class="fp-phrases"></div>
            </div>

            <div class="fp-card">
                <div class="fp-card-title">LIVE TRAFFIC</div>
                <div class="fp-hint">
                    The radar's own feed (data.php). Use copies a live aircraft's
                    filed plan into the form instead of typing it.
                </div>
                <div id="fp-traffic-meta" class="fp-traffic-meta"></div>
                <div id="fp-traffic" class="fp-traffic"></div>
            </div>
        </section>
    </main>

    <a id="credits" href="about.html">Credits &amp; License · ATASOFT</a>

    <!-- ATASOFT: the START FLIGHT hand-over.

         startSession() used to jump straight to follow-flight.php the moment the
         plan validated, so the page changed with no acknowledgement and any slow
         navigation looked like a dead button. This layer is shown AFTER the
         session has been written to storage and BEFORE the navigation: four
         fixed lines, ~1 s each, then the follow view.

         It is deliberately inert: it owns no session state, and if it never
         appears (script error, or the browser navigates first) the flight is
         still filed and follow-flight.php still opens. The button is disabled
         while it is up, and src/flightPlanner.js clears every timer on pagehide
         so a Back press cannot leave a queued redirect behind.

         Same two type roles as everywhere else: the message is UI face, the
         step counter (01 / 04) is the data face. -->
    <div id="fp-handover" class="fp-handover" hidden role="status" aria-live="polite">
        <div class="fp-handover-body">
            <div class="fp-handover-scope" aria-hidden="true">
                <svg viewBox="0 0 120 120" focusable="false">
                    <circle class="fp-handover-ring" cx="60" cy="60" r="56" />
                    <circle class="fp-handover-ring" cx="60" cy="60" r="40" />
                    <circle class="fp-handover-ring fp-handover-ring-major" cx="60" cy="60" r="24" />
                    <line class="fp-handover-axis" x1="4" y1="60" x2="116" y2="60" />
                    <line class="fp-handover-axis" x1="60" y1="4" x2="60" y2="116" />
                    <g class="fp-handover-sweep">
                        <line class="fp-handover-sweep-line" x1="60" y1="60" x2="60" y2="6" />
                    </g>
                    <circle class="fp-handover-reticle" cx="60" cy="60" r="1.8" />
                </svg>
            </div>
            <div class="fp-handover-copy">
                <p class="fp-handover-kicker">FLIGHT SESSION</p>
                <p id="fp-handover-message" class="fp-handover-message">Loading Your Waypoints...</p>
                <p class="fp-handover-meta">
                    <span id="fp-handover-step" class="fp-handover-step">01 / 04</span>
                    <span id="fp-handover-callsign" class="fp-handover-callsign"></span>
                </p>
            </div>
        </div>
    </div>
</body>

</html>
