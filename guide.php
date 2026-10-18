<?php
// ATASOFT ATC24 Radar - Guide.
//
// The user-facing manual: what every page, panel and button does, in the order
// someone actually meets them. It is a static document - it reads no feed, adds
// no endpoint, and changes nothing in the app - but every claim on it is one
// this build really makes: the controls named here are the ones in index.php,
// the badges and thresholds are the constants in src/, and the pages it links
// to are the pages that exist.
//
// The technical companion is docs.php (code, endpoints, data shapes, checks).
?>
<!doctype html>
<html lang="en" class="dc-html">

<head>
    <meta charset="UTF-8" />
    <title>Guide - ATC24 Radar - ATASOFT</title>
    <meta name="description"
        content="How to use ATC24 Radar by ATASOFT: the radar scope, aircraft tags, DEP/ARR boards, ATIS, the flight planner, follow flight, Centre mode, settings and troubleshooting." />
    <meta name="theme-color" content="#060814" />

    <link rel="icon" type="image/svg+xml" href="public/assets/favicon.svg" />
    <link rel="alternate icon" type="image/png" href="public/assets/icon.png" />
    <link rel="apple-touch-icon" href="public/assets/apple-touch-icon.png" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php require __DIR__ . '/inc/fonts.php'; ?>
    <link rel="stylesheet" href="public/style.css?v=<?= @filemtime(__DIR__ . '/public/style.css') ?: time() ?>">
    <link rel="stylesheet" href="public/style-docs.css?v=<?= @filemtime(__DIR__ . '/public/style-docs.css') ?: time() ?>">
</head>

<body class="dc-body">
    <div id="top-bar" class="dc-topbar">
        <a class="dc-brand" href="index.php">ATC24 Radar <span>by ATASOFT</span></a>
        <nav class="dc-nav">
            <a href="index.php">RADAR</a>
            <a href="flight-planner.php">FLIGHT PLANNER</a>
            <a href="follow-flight.php">FOLLOW FLIGHT</a>
            <a href="atis.php">ATIS</a>
            <a href="guide.php" aria-current="page">GUIDE</a>
            <a href="docs.php">DOCS</a>
        </nav>
        <span class="dc-topbar-spacer"></span>
        <span class="dc-topbar-tag">USER GUIDE</span>
    </div>

    <main class="dc-main">
        <header class="dc-hero">
            <p class="dc-kicker">User guide &middot; English</p>
            <h1>How to use ATC24 Radar</h1>
            <p class="dc-lede">
                A walkthrough of every page in this client: the radar scope, the aircraft tags,
                the departure and arrival boards, ATIS, the flight planner, the follow-flight
                view, Centre mode, the radio helper and all of Settings - plus what to do when
                something looks wrong. Nothing here needs an account, an install or a payment.
            </p>
            <div class="dc-meta">
                <span class="dc-chip"><strong>5</strong> pages</span>
                <span class="dc-chip">no sign-in</span>
                <span class="dc-chip">desktop first</span>
                <span class="dc-chip">PTFS / ATC24</span>
                <span class="dc-chip">technical reference: <a href="docs.php">docs.php</a></span>
            </div>
        </header>

        <div class="dc-layout">
            <nav class="dc-toc" aria-label="Contents">
                <div class="dc-toc-title">Contents</div>
                <ol>
                    <li><a href="#quick-start">Quick start (five minutes)</a></li>
                    <li><a href="#radar">The radar screen</a></li>
                    <li><a href="#aircraft">Aircraft, tags and the aircraft panel</a></li>
                    <li><a href="#ground">Ground view, charts and split screen</a></li>
                    <li><a href="#boards">Departure, arrival and strip boards</a></li>
                    <li><a href="#atis">ATIS: generator and board</a></li>
                    <li><a href="#planner">Flight planner</a></li>
                    <li><a href="#follow">Follow flight</a></li>
                    <li><a href="#centre">Centre mode</a></li>
                    <li><a href="#helper">Radio helper (sentence helper)</a></li>
                    <li><a href="#settings">Settings reference</a></li>
                    <li><a href="#controls">Mouse and keyboard</a></li>
                    <li><a href="#trouble">Troubleshooting</a></li>
                    <li><a href="#honesty">What the data can and cannot tell you</a></li>
                </ol>
                <div class="dc-toc-note">
                    Looking for the code, endpoints and data formats? See
                    <a href="docs.php">docs.php</a>.
                </div>
            </nav>

            <article class="dc-doc">
                <!-- ============================ QUICK START ============================ -->
                <h2 id="quick-start"><span class="dc-h2-num">01</span>Quick start (five minutes)</h2>
                <p>
                    Everything in this client is a web page. Open the radar, pick a field and work it;
                    the other pages are one click away in the top bar of every page.
                </p>
                <ol class="dc-steps">
                    <li>
                        <strong>Open the radar</strong> at <a href="index.php">index.php</a>. There is no
                        sign-in. The top bar starts as <code>CONNECTING</code> and turns
                        <code>LIVE</code> when the first data cycle lands.
                    </li>
                    <li>
                        <strong>Pick your field.</strong> Use <strong>STATION SELECT</strong> in the top
                        bar. The <code>★</code> in front of a code marks a field this build has published
                        charts for.
                    </li>
                    <li>
                        <strong>Read the scope.</strong> Each aircraft is drawn as the real top view of its
                        type - a 737 as a 737, a Cessna 172 as a high-wing single with a nose propeller, a
                        Chinook with two turning rotors - with a trail behind it and a data block beside
                        it. Click one to open its panel.
                    </li>
                    <li>
                        <strong>Open the boards.</strong> The two plane icons in the tool group open
                        <em>Departures</em> and <em>Arrivals</em>; <strong>STRIPS</strong> opens the
                        draggable strip board; the text icon opens the <em>ATIS</em> window.
                    </li>
                    <li>
                        <strong>Plan or follow a flight.</strong> <a href="flight-planner.php">FLIGHT
                        PLANNER</a> builds a route and the ATC24 Discord flight-plan command;
                        <a href="follow-flight.php">FOLLOW FLIGHT</a> turns that plan into a live
                        heading/level/next-waypoint display.
                    </li>
                </ol>
                <div class="dc-callout dc-callout--good">
                    <span class="dc-callout-icon">TIP</span>
                    <div>
                        <p>
                            If the scope looks empty, check the status badge in the top bar before
                            anything else: <code>LIVE</code> means the feed is arriving, and only then
                            is "no traffic" a statement about the traffic rather than about the
                            connection.
                        </p>
                    </div>
                </div>

                <!-- ============================ RADAR ============================ -->
                <h2 id="radar"><span class="dc-h2-num">02</span>The radar screen</h2>
                <p>
                    <a href="index.php">index.php</a> is the main display. The scope shows every aircraft
                    the ATC24 feed reports, drawn in the project's own map (coastline, airspace,
                    measured runway thresholds and range rings), and it is refreshed continuously - the
                    feed itself is cached for one second and the page polls it every half second, so
                    the picture is never more than a fraction of a second behind.
                </p>

                <h3>The top bar, left to right</h3>
                <div class="dc-table-wrap">
                    <table>
                        <thead>
                            <tr><th>Control</th><th>What it does</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>LOCAL</code> / <code>UTC</code></td>
                                <td>The clock. Click to switch between your local time and Zulu time; the
                                    button always shows the mode you are in.</td>
                            </tr>
                            <tr>
                                <td><code>GROUND AIRCRAFT</code></td>
                                <td>Shows or hides the aircraft the feed reports as being on the ground.
                                    Handy at a busy field: turn it off and only airborne traffic is drawn.</td>
                            </tr>
                            <tr>
                                <td><code>STATION SELECT</code></td>
                                <td>Your field. It drives the ground view, the ATIS generator's runway
                                    lists, the wind readout and which flights appear on the boards.</td>
                            </tr>
                            <tr>
                                <td>Sliders icon</td>
                                <td><em>Station settings</em>: tick neighbouring fields to overlay their
                                    top-down airport diagrams on the scope as well, so you can see the
                                    pavement you are vectoring toward without changing station.</td>
                            </tr>
                            <tr>
                                <td><code>GROUND</code></td>
                                <td>Shows or hides the selected field's ground diagram on the radar
                                    itself (stand numbers, taxiways, runway markings).</td>
                            </tr>
                            <tr>
                                <td><code>APP Sel</code></td>
                                <td><em>Approach selector</em>: pick an airport and choose which approach
                                    drawings are overlaid - the ILS cone, the extended centreline, the
                                    published runway corridor. What it draws is the same approach line the
                                    route tools use, so a vector and the chart agree.</td>
                            </tr>
                            <tr>
                                <td><code>ALL APT</code></td>
                                <td>A one-screen library of every airport's ground diagram and approach
                                    charts. Click a thumbnail to open it full size.</td>
                            </tr>
                            <tr>
                                <td><code>ONLINE ATCS</code></td>
                                <td>The live controller list: which positions (GND / TWR / CTR / APP / DEL)
                                    are claimed, by whom, and how many are online. Refreshed every few
                                    seconds from the network's own controller feed.</td>
                            </tr>
                            <tr>
                                <td><code>STRIPS</code></td>
                                <td>The strip board (see <a href="#boards">section 05</a>).</td>
                            </tr>
                            <tr>
                                <td><code>FLIGHT PLANNER</code></td>
                                <td>Opens the planner page.</td>
                            </tr>
                            <tr>
                                <td><code>CENTRE</code></td>
                                <td>Only appears after Centre mode is switched on in Settings (see
                                    <a href="#centre">section 09</a>).</td>
                            </tr>
                            <tr>
                                <td><code>HDG</code></td>
                                <td>The vector-to-point tool: select an aircraft, click <code>HDG</code>,
                                    then click a point on the map. A line is drawn from the aircraft to
                                    that point labelled with the heading to fly and the distance, for
                                    example <code>HDG LEFT 180&deg; 4.2NM</code> - left or right tells
                                    you which way to turn, and the readout follows the aircraft as it
                                    moves. Click <code>HDG</code> again to put it away.</td>
                            </tr>
                            <tr>
                                <td>Callsign search</td>
                                <td>Type a callsign, flight number or airline code and press
                                    <kbd>Enter</kbd> (or <code>FIND</code>) to select and centre that
                                    aircraft. It matches the filed callsign, the carrier plus flight
                                    number, the ICAO code, the last four digits, and partial text - so
                                    <code>39W</code> finds <code>Delta-39W</code>.</td>
                            </tr>
                            <tr>
                                <td>Notepad icon</td>
                                <td>A scratch pad that survives page changes (stored in your browser).</td>
                            </tr>
                            <tr>
                                <td>Departures / Arrivals icons</td>
                                <td>The two flight boards (see <a href="#boards">section 05</a>).</td>
                            </tr>
                            <tr>
                                <td>ATIS icon</td>
                                <td>The ATIS generator and the published ATIS panel (see
                                    <a href="#atis">section 06</a>).</td>
                            </tr>
                            <tr>
                                <td>Vector icon</td>
                                <td>The vectoring tool: colour, distance display, direction-only mode, and
                                    <em>Export</em> / <em>Import</em> so a set of vector lines can be saved
                                    and loaded again.</td>
                            </tr>
                            <tr>
                                <td>Station info &amp; <code>W:</code></td>
                                <td>The selected field's elevation/ident summary and the current wind read
                                    from the feed.</td>
                            </tr>
                            <tr>
                                <td>Status badge</td>
                                <td>The connection: <code>CONNECTING</code>, <code>LIVE</code>,
                                    <code>RECONNECTING</code> (last request failed, retrying) or
                                    <code>STALE</code> (no successful update for 15 seconds).</td>
                            </tr>
                            <tr>
                                <td>ATC24 Radar by ATASOFT</td>
                                <td>Credits, license and attribution.</td>
                            </tr>
                            <tr>
                                <td>Gear icon</td>
                                <td>Settings (see <a href="#settings">section 11</a>).</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3>The map itself</h3>
                <ul>
                    <li><strong>Pan</strong> with a right-button drag; <strong>zoom</strong> with the mouse
                        wheel; the <code>+</code>/<code>&minus;</code> equivalents live in the side panel on
                        the ground/chart views.</li>
                    <li><strong>Range rings</strong> at 1, 2.5, 5, 10 and 20 NM are drawn around the scope
                        centre from this build's own nautical-mile scale, with the 10 NM ring given one grade
                        more contrast because that is the ring distances are read against.</li>
                    <li><strong>Reticle</strong>: four short arms with a gap in the middle mark the point the
                        rings (and the published airspace) are measured from.</li>
                    <li><strong>Trails</strong> persist for the whole flight and are coloured by altitude -
                        amber near the ground, purple at cruise - and are cleared when the aircraft lands.</li>
                    <li><strong>Fixes</strong> can be drawn on the scope (<em>Draw Fixes</em> in Settings) for
                        conventional navigation.</li>
                </ul>

                <!-- ============================ AIRCRAFT ============================ -->
                <h2 id="aircraft"><span class="dc-h2-num">03</span>Aircraft, tags and the aircraft panel</h2>

                <h3>The data block</h3>
                <p>Every aircraft carries a tag with three lines:</p>
                <ul>
                    <li><strong>Callsign</strong> - the pilot's filed callsign by default, or the ICAO code if
                        you switch <em>FPL Callsign on Radar</em> off in Settings.</li>
                    <li><strong>Altitude and ground speed</strong>.</li>
                    <li><strong>Heading and aircraft type</strong>, for example <code>085&deg; A320</code>.</li>
                </ul>
                <p>
                    The tag stays the same size on screen at every zoom level and keeps a fixed gap from its
                    own aircraft; the aircraft symbol does the same, so zooming changes what you see on the
                    map, never how legible one aircraft is. The symbol itself is the aircraft's real top
                    view, chosen from the type the feed reports: a 747 is drawn wider and longer than a
                    twin-jet, a Cessna is smaller again, the nacelles, propellers and rotors are where that
                    type really has them, and the relative sizes are the aircraft's real wingspans mapped
                    onto the scope's own range - see
                    <a href="docs.php#glyphs">the symbol reference in docs.php</a>.
                </p>

                <h3>Working with one aircraft</h3>
                <div class="dc-table-wrap">
                    <table>
                        <thead>
                            <tr><th>Action</th><th>Result</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Click the symbol</td><td>Selects the aircraft and opens its panel.</td></tr>
                            <tr><td>Click the tag</td><td>Same - selects and opens the panel.</td></tr>
                            <tr><td>Drag the tag</td><td>Moves that aircraft's data block; the position you gave it is
                                kept, even as the map zooms.</td></tr>
                            <tr><td>Middle-click the tag</td><td>Pins the aircraft: a <code>★</code> marks it, and
                                its block is never hidden at a wide zoom. Middle-click again to unpin.</td></tr>
                            <tr><td>Double-click the tag or the airline badge</td><td>Opens the aircraft panel.</td></tr>
                            <tr><td><kbd>Esc</kbd> / the red circle</td><td>Closes the open panel or overlay.</td></tr>
                        </tbody>
                    </table>
                </div>

                <h3>The aircraft panel</h3>
                <p>Clicking an aircraft opens the inspection panel, which is the full picture of that flight:</p>
                <ul>
                    <li><strong>Identity</strong>: callsign (both the filed callsign and the ICAO code),
                        aircraft type, the Roblox player name, and the airline badge.</li>
                    <li><strong>Route</strong>: origin, destination and the filed route, drawn as the
                        departure &#8594; arrival strip. For an aircraft with no flight plan, the two airport
                        boxes are explicitly marked as unset rather than filled with a guess.</li>
                    <li><strong>Flight plan detail</strong>: rules, filed level, squawk and everything the
                        feed carries.</li>
                    <li><strong>Live detail</strong>: altitude, ground speed, heading, vertical trend, and a
                        small altitude-history sparkline built from that flight's own trail - so a level-off
                        or a descent shows as a shape, not just a number.</li>
                    <li><strong>Scratchpad</strong>: a free-text box for this flight (for example
                        <code>S250, ILS25L</code>).</li>
                    <li><strong>ATC helper</strong>: role-filtered radio phrases, if you switched the
                        sentence helper on (see <a href="#helper">section 10</a>).</li>
                    <li><strong>Centre mode block</strong>: when Centre mode is on, the generated route for
                        that flight - waypoints, required heading, runway and why - appears here too.</li>
                </ul>

                <div class="dc-callout">
                    <span class="dc-callout-icon">PERF</span>
                    <div>
                        <p>
                            The scope is built to keep working with a lot of traffic: each aircraft element is
                            updated in place instead of being recreated on every cycle, and the map layers are
                            loaded once. If a page ever feels heavy, the ground view and the split panels are
                            the biggest cost - close what you are not reading.
                        </p>
                    </div>
                </div>

                <!-- ============================ GROUND ============================ -->
                <h2 id="ground"><span class="dc-h2-num">04</span>Ground view, charts and split screen</h2>
                <p>
                    The radar and a second picture can be shown side by side. The <code>&#9654;</code> button
                    on the edge of the scope splits the screen; the divider between the two halves can be
                    dragged, and the button then reads <code>&#9664;</code> to give the map the full width
                    again.
                </p>
                <div class="dc-cards">
                    <div class="dc-card">
                        <span class="dc-card-title">Ground view</span>
                        <span class="dc-card-text">The selected field's diagram with its own aircraft symbols and
                            tags - the view to work a taxiway or an apron from. On-ground tags appear from a
                            shallower zoom than on the radar, and <em>Ground Labels Within (NM)</em> in Settings
                            controls how wide that window is.</span>
                    </div>
                    <div class="dc-card">
                        <span class="dc-card-title">Charts</span>
                        <span class="dc-card-text">Published approach charts for the selected airport, in the
                            same side window. Pan and zoom them like the map; <code>ALL APT</code> opens the
                            library version.</span>
                    </div>
                    <div class="dc-card">
                        <span class="dc-card-title">Station settings</span>
                        <span class="dc-card-text">Overlay a neighbouring field's top-down diagram on the radar
                            so the pavement is visible where the traffic is, not only where the station
                            dropdown points.</span>
                    </div>
                </div>

                <!-- ============================ BOARDS ============================ -->
                <h2 id="boards"><span class="dc-h2-num">05</span>Departure, arrival and strip boards</h2>
                <p>
                    The boards are built from two sources at once: the live feed, and the network's own filed
                    flight-plan lists. A flight therefore appears <em>before</em> it is airborne - a filed row
                    is drawn dimmer/italic, and it is replaced by the live row as soon as the feed carries
                    that callsign, so nothing is ever listed twice.
                </p>

                <h3>Departures board</h3>
                <p>Columns: <code>CALLSIGN</code>, <code>TYPE</code>, <code>ARR</code>, <code>FR</code> (flight
                    rules), <code>RWY</code>, <code>SID</code>, <code>FL</code>, <code>SQK</code>, <code>C</code>
                    (cleared tick) and <code>RMK</code>. Aircraft type, destination and the filed level are
                    written in automatically where the plan already states them.</p>

                <h3>Arrivals board</h3>
                <p>Columns: <code>CALLSIGN</code>, <code>TYPE</code>, <code>FROM</code>, <code>RWY</code>,
                    <code>FL</code>, <code>SQK</code>, <code>STAND</code> and <code>RMK</code>. <code>FROM</code>
                    is filled from the flight's filed origin, so the arrival controller is not typing
                    information the plan already carries.</p>

                <h3>What happens when you type in a cell</h3>
                <ul>
                    <li><strong>It is shared.</strong> The cells are keyed by callsign and stored server-side,
                        so the departure and arrival controller of the same flight read the same values - a
                        squawk or a re-clearance issued by one position is visible to the other.</li>
                    <li><strong>It is optimistic.</strong> Typing never waits for the network; changes are pushed
                        after you stop typing and retried if a write fails.</li>
                    <li><strong>It falls back.</strong> If the board endpoint cannot be reached, edits stay in
                        your browser and the aircraft panel says <code>LOCAL ONLY</code> instead of pretending
                        they were shared.</li>
                    <li><strong>It is retained for 12 hours</strong>, and only ever stores operator annotation
                        for a callsign - never the feed, never another user's data.</li>
                </ul>

                <div class="dc-callout dc-callout--warn">
                    <span class="dc-callout-icon">RULE</span>
                    <div>
                        <p>
                            <strong>Amending a flight needs a position.</strong> FL, RWY, SID, STAND, RMK and the
                            cleared tick are controller actions, so they are editable only by a name that holds a
                            controlling position for one of that flight's airports (or a CTR position that covers
                            it) on the online-controller list. Set <em>MY ATC24 NAME</em> in Settings once, and
                            the radar checks it for you. Without a position those cells are read-only and say
                            why.
                        </p>
                        <p>
                            <strong>The squawk is not an amendment</strong> - it is read off the flight and passed
                            on, so it stays editable for everyone.
                        </p>
                    </div>
                </div>

                <h3>Level check</h3>
                <p>
                    Where a level is written in the <code>FL</code> cell, it turns green when the aircraft's
                    live altitude is on that level (within &plusmn;200 ft, so FL050 = 5000 ft covers
                    4800-5200). Hover it and the tooltip states the live altitude, the level in feet and
                    whether the level is the one the controller cleared or the pilot filed. A flight that is
                    clearly not on its level is dimmed rather than marked, and a filed-only row is never
                    marked, because there is no altitude to compare.
                </p>

                <h3>Strip board</h3>
                <p>
                    <code>STRIPS</code> is the same traffic as a paper-strip board: one row per flight, each
                    with a callsign, route, live vitals (altitude/speed), a scratch note and a
                    <em>cleared</em> tick. Rows can be dragged to order them, and clicking a row selects that
                    aircraft on the scope. <kbd>Esc</kbd> closes the overlay; the board keeps working while it
                    is open.
                </p>

                <!-- ============================ ATIS ============================ -->
                <h2 id="atis"><span class="dc-h2-num">06</span>ATIS: generator and board</h2>
                <p>
                    ATIS exists in two places, and they deliberately share the same parser so a report can
                    never read one way in the generator and another way on the board.
                </p>

                <h3>The ATIS window (radar)</h3>
                <p>The text icon in the radar's tool group opens the generator:</p>
                <ol class="dc-steps">
                    <li>Pick the arrival and departure runway(s) - the lists follow the selected station.</li>
                    <li>Set or confirm <strong>QNH</strong>.</li>
                    <li>Choose whether the ATIS text mentions charts.</li>
                    <li>Press <strong>Copy ATIS Command</strong>: the generated report is copied to your
                        clipboard and previewed beside it. Nothing is transmitted by the page - you paste it
                        into the game/Discord yourself.</li>
                </ol>
                <p>
                    Below the generator is the <strong>published ATIS panel</strong>: every report published on
                    the network, verbatim - airport, information letter, the controller who wrote it and the
                    full text - with the fields you are working pinned to the top. Each entry has a
                    <strong>USE</strong> button that loads that published report's arrival/departure runways
                    and QNH into the generator, so what you publish cannot drift from what is already out
                    there.
                </p>

                <h3>The ATIS board (whole network)</h3>
                <p>
                    <a href="atis.php">atis.php</a> is the board: every published report at once, in a
                    grid of cards.
                </p>
                <ul>
                    <li><strong>Search</strong> by 4-letter airport code while typing; the counter tells you
                        how many of the reports are showing (<code>CLEAR</code> returns all of them).</li>
                    <li><strong>Refresh once a second, without reloading the page.</strong>
                        <code>AUTO 1s</code> is the live mode; <code>PAUSED</code> stops the polling and the
                        line above the list then tells you how old the list on screen is.
                        <code>REFRESH</code> fetches once immediately.</li>
                    <li>Each card shows the verbatim report in a monospace block, with the parsed fields
                        (dep/arr runway, wind, QNH, transition level) as labelled chips above it - the text is
                        always the report, the chips are only a reading of it.</li>
                </ul>

                <!-- ============================ PLANNER ============================ -->
                <h2 id="planner"><span class="dc-h2-num">07</span>Flight planner</h2>
                <p>
                    <a href="flight-planner.php">flight-planner.php</a> is for the pilot side: build a plan from
                    the same airport and waypoint records the radar draws, check it against the published ATIS,
                    and generate the exact ATC24 Discord command that files it. The page builds text - it does
                    not file anything, and copying the command sends nothing.
                </p>

                <h3>Building a plan</h3>
                <ol class="dc-steps">
                    <li>
                        <strong>Fill the flight plan card.</strong> In-game callsign, the flight-plan callsign
                        (they are different values and both are required), your Roblox name, aircraft type - the
                        field suggests types seen in the live feed - and IFR or VFR.
                    </li>
                    <li>
                        <strong>Choose a level.</strong> Three digits, for example <code>060</code>. The field
                        accepts the wordings this network really files (<code>below 030</code>,
                        <code>010-020</code>) and the word <code>any</code>, and refuses a value that is not a
                        level here - including <code>000</code> and a four-digit number.
                    </li>
                    <li>
                        <strong>Departure and arrival.</strong> Pick the airport and runways from this build's
                        own records. The card shows the published ATIS for each field, so the runway you file
                        can be the runway in force.
                    </li>
                    <li>
                        <strong>Build the route.</strong> Click fixes on the map, or search the Fixes table by
                        name. The number beside a selected fix is its place in the route; click it again (or use
                        the list) to remove it. Only real records are selectable - a similar-looking name is
                        never substituted.
                    </li>
                    <li>
                        <strong>Check and copy.</strong> The command preview shows the exact text. Errors -
                        missing fields, a runway that is not that airport's, a waypoint that is not in the
                        table, or a command over Discord's 2000-character limit - are listed under it and the
                        command is not offered as valid.
                    </li>
                </ol>

                <h4>The generated command</h4>
                <pre><code>/createflightplan ingamecallsign:xxxx callsign:xxxx aircraft:xxxx
  flightrules:IFR departing:&lt;airport&gt; arriving:&lt;airport&gt;
  flightlevel:060 ingamename:&lt;roblox name&gt; route:&lt;route&gt;</code></pre>
                <p>
                    The route is written as the departure runway, the waypoints in order, then the arrival
                    runway. The <em>Airport name form</em> selector chooses whether the airports go in as the
                    full PTFS names (<code>departing:Greater Rockford</code>, the documented form) or as
                    4-letter identifiers (<code>departing:IRFD</code>, the form the network's own filed feed
                    stores).
                </p>

                <h3>Starting a flight session</h3>
                <p>
                    <strong>START FLIGHT</strong> stores the plan in your browser (<code>localStorage
                    atasoft_fp_session</code>) and begins tracking the live feed for your aircraft. The
                    session is what makes the live navigation, the live instruction and the pilot phrases
                    below possible, and it is deliberately honest: it reports that it has <em>found</em> your
                    aircraft rather than assuming it. A <em>Use</em> button on the live-traffic list copies an
                    aircraft's filed plan into the form instead of making you type it.
                </p>
                <p>
                    The session states you will see: <code>PRE-FLIGHT</code> &#8594;
                    <code>WAITING FOR AIRCRAFT</code> &#8594; <code>DEPARTURE</code> &#8594;
                    <code>EN ROUTE</code> &#8594; <code>ARRIVAL</code> &#8594; <code>COMPLETED</code>, plus
                    <code>PAUSED</code> and <code>INTERRUPTED</code>. <strong>PAUSE</strong> stops the live
                    tracking without losing the plan; <strong>END SESSION</strong> clears it.
                    <strong>FOLLOW VIEW</strong> opens the follow page for this session.
                </p>
                <div class="dc-callout">
                    <span class="dc-callout-icon">NOTE</span>
                    <div>
                        <p>
                            The planner's live cards (navigation, instruction, phrases, traffic) recompute from
                            the feed while the session is running. A value the feed cannot measure is shown as
                            missing - never filled with a plausible-looking number.
                        </p>
                    </div>
                </div>

                <!-- ============================ FOLLOW ============================ -->
                <h2 id="follow"><span class="dc-h2-num">08</span>Follow flight</h2>
                <p>
                    <a href="follow-flight.php">follow-flight.php</a> turns a session into one glance: the
                    heading to fly, the level to hold, the next waypoint, and anything close in front. It reads
                    the same session, the same records and the same feed as the planner, and it draws the same
                    radar picture through the same scope module.
                </p>

                <h3>The four boxes</h3>
                <div class="dc-table-wrap">
                    <table>
                        <thead>
                            <tr><th>Box</th><th>What it means</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Required heading<br><small>(next waypoint)</small></td>
                                <td>
                                    The heading that points <strong>straight at the next waypoint</strong> from
                                    where the aircraft is now, with the turn needed to get there:
                                    <code>247&deg; L 58</code> is "fly 247, turning left 58 degrees", and
                                    <code>ON COURSE</code> appears when you are already within a few degrees. The
                                    sub-line names the leg and the distance to it.
                                </td>
                            </tr>
                            <tr>
                                <td>Required altitude</td>
                                <td>The level this flight filed, and the wording to get there: maintain,
                                    climb to, or descend to, with the feet still to go. If no level was filed the
                                    box says so.</td>
                            </tr>
                            <tr>
                                <td>Next waypoint</td>
                                <td>The waypoint the guidance is flying toward, its distance, the leg number and
                                    the progress along the route. The route is the plan's own waypoint list when
                                    it has one, otherwise the waypoints the flight actually filed.</td>
                            </tr>
                            <tr>
                                <td>Flight &middot; current</td>
                                <td>The callsign, the session state and what the aircraft is doing right now
                                    (current heading and speed). If the feed goes quiet, the age of the last
                                    sample is printed here instead of pretending the values are live.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3>How the route is sequenced</h3>
                <p>
                    Which waypoint is "next" is decided by the <strong>route leg</strong> the aircraft is on:
                    the page finds the route segment the aircraft is closest to and points at the waypoint where
                    that segment ends. That is the only rule that survives a corner that was cut, a route that
                    turns back on itself, a leg shorter than a waypoint's turn radius, and a page opened
                    mid-flight. It never jumps backwards, and if the aircraft is off the route by more than
                    20 NM nothing is sequenced at all - a stale target is honest, an invented one is not.
                </p>

                <h3>The advisory line</h3>
                <ul>
                    <li><strong>Traffic</strong> is measured, not guessed: within 2 NM <em>and</em> vertically
                        close is a <code>TRAFFIC ALERT</code>; within 5 NM in front (within &plusmn;45&deg; of
                        the nose) and within 2000 ft is <code>TRAFFIC</code>; anything else is listed but not
                        called out. Each row gives the distance, whether it is ahead, left, right or behind, and
                        the height difference.</li>
                    <li><strong>Level requests:</strong> on the last leg, if the flight still has to climb or
                        descend to its filed level, the line offers the request wording
                        (<code>REQ ATC DESCEND 3000 FT</code>) - a required level is not a clearance.</li>
                    <li>This is an advisory read off the feed, <strong>not a separation standard</strong>.</li>
                </ul>

                <h3>Map and side panels</h3>
                <p>
                    The map opens fitted to the route and follows the aircraft; <code>FIT ROUTE</code> refits
                    it, <code>FOLLOW A/C</code> toggles the centring, and <code>+</code>/<code>&minus;</code>
                    zoom. The side panels list the flight plan, the route legs (with the current one marked)
                    and the nearby traffic. If no session exists, the page says so and offers the planner.
                </p>

                <!-- ============================ CENTRE ============================ -->
                <h2 id="centre"><span class="dc-h2-num">09</span>Centre mode</h2>
                <p>
                    Centre mode is the en-route view: pick an airspace, see the airborne traffic in and near
                    it, and get a temporary route with a required heading for any aircraft you select.
                    It is switched on in Settings (it adds the <code>CENTRE</code> button to the top bar) and
                    it is off by default.
                </p>
                <ol class="dc-steps">
                    <li>Settings &#8594; <strong>Centre Mode</strong> on. The <code>CENTRE</code> button appears.</li>
                    <li>Open it and choose the airspace, and the radius in nautical miles (10-150). Airspace here
                        is an approximate radius around the airport - this project has no real sector boundary
                        data, and it says so rather than drawing a boundary it does not have.</li>
                    <li>With <em>Auto route</em> on, selecting an aircraft generates its route. Each aircraft
                        keeps one stable route: the arrival runway is resolved once and <em>locked</em> to that
                        flight, so the route stops changing every few seconds. The alternatives are still
                        scored and kept for the tools, but they are not shown.</li>
                    <li><strong>Regenerate Selected Route</strong> is the one action that drops the lock and
                        chooses again. <strong>Emergency Divert</strong> routes the selected aircraft to the
                        nearest suitable airport.</li>
                </ol>
                <p>
                    Routes are drawn with the same approach line the charts and the ILS cone use, and each
                    carries its required heading, the runway, and the reason it was chosen. Where the mode can
                    flag a sequence conflict (two routes converging on the same runway too close together) or a
                    go-around (runway occupied by another aircraft on that runway), it reports it as a flag -
                    it does not silently reroute anyone.
                </p>

                <!-- ============================ HELPER ============================ -->
                <h2 id="helper"><span class="dc-h2-num">10</span>Radio helper (sentence helper)</h2>
                <p>
                    Settings &#8594; <strong>Sentence Helper</strong> adds a set of ready-made radio phrases to
                    the selected aircraft's panel. It is off by default, so nothing changes for an existing
                    install until you switch it on.
                </p>
                <ul>
                    <li>Phrases are filtered by your position - Ground, Tower, Center, Approach, Delivery - as
                        reported for your <em>MY ATC24 NAME</em> on the online ATC list. With no name or no
                        position you get the general set.</li>
                    <li>They are pre-filled from real values: the aircraft's callsign, type and position, its
                        filed plan, the published ATIS and this build's airport records.</li>
                    <li>A value this build does not have stays visibly missing instead of being invented, and
                        the categories cover clearance, departure, en route, arrival, readback and
                        coordination.</li>
                    <li><strong>Copying only copies.</strong> Nothing is transmitted by this page.</li>
                </ul>

                <!-- ============================ SETTINGS ============================ -->
                <h2 id="settings"><span class="dc-h2-num">11</span>Settings reference</h2>
                <p>The gear at the corner of the scope opens Settings. Everything here is stored in your own browser.</p>
                <div class="dc-table-wrap">
                    <table>
                        <thead>
                            <tr><th>Setting</th><th>What it does</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Centre Mode</td>
                                <td>Switches on the en-route mode and its <code>CENTRE</code> button
                                    (<a href="#centre">section 09</a>). <em>Debug (console)</em> logs its scoring
                                    for troubleshooting.</td>
                            </tr>
                            <tr>
                                <td>FPL Callsign on Radar</td>
                                <td>On: tags show the pilot's filed callsign (Delta-39W). Off: tags show the ICAO
                                    code (DAL3454). The panel always shows both.</td>
                            </tr>
                            <tr>
                                <td>MY ATC24 NAME</td>
                                <td>Your controller name. It is what the amendment rule checks against the online
                                    ATC list, and it is shown as "set by" on the shared board.</td>
                            </tr>
                            <tr>
                                <td>Label Font Size (Radar)</td>
                                <td>Data-block text size, in screen pixels (the field shows the value &times;10 and it
                                    is held inside a legible band).</td>
                            </tr>
                            <tr>
                                <td>Label Font Size (Ground)</td>
                                <td>The same for the ground view's own symbols.</td>
                            </tr>
                            <tr>
                                <td>Ground Labels Within (NM)</td>
                                <td>How wide the scope has to be before on-ground tags are drawn; 0 = always.
                                    Prevents a wide view being carpeted with taxiing callsigns.</td>
                            </tr>
                            <tr>
                                <td>Fixes Font Size / Fixes Size</td>
                                <td>The size of the fix names and their symbols when <em>Draw Fixes</em> is on.</td>
                            </tr>
                            <tr>
                                <td>Airspace Bounds</td>
                                <td>Show or hide the published airspace boundaries.</td>
                            </tr>
                            <tr>
                                <td>Minimalistic Toolbar</td>
                                <td>Trims the tool group down when you want more room for the map.</td>
                            </tr>
                            <tr>
                                <td>Draw Fixes</td>
                                <td>Draws the project's fix table on the scope.</td>
                            </tr>
                            <tr>
                                <td>Predictive Speed Vector</td>
                                <td>A thin line out of each aircraft's nose showing where it will be in 1, 2 or 3
                                    minutes (the length is selectable), with a dot at each whole minute.</td>
                            </tr>
                            <tr>
                                <td>Wind Arrow (selected airport)</td>
                                <td>Draws the current wind at the selected field on the scope.</td>
                            </tr>
                            <tr>
                                <td>Voice Alerts</td>
                                <td>Spoken alerts for emergencies and traffic conflicts (see
                                    <a href="#honesty">section 13</a> for what triggers them and their limits).</td>
                            </tr>
                            <tr>
                                <td>Sentence Helper</td>
                                <td>The radio phrase set in the aircraft panel
                                    (<a href="#helper">section 10</a>).</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ============================ CONTROLS ============================ -->
                <h2 id="controls"><span class="dc-h2-num">12</span>Mouse and keyboard</h2>
                <div class="dc-table-wrap">
                    <table>
                        <thead>
                            <tr><th>Input</th><th>Where</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Left click</td><td>aircraft symbol or tag</td><td>Select and open its panel</td></tr>
                            <tr><td>Left drag</td><td>tag</td><td>Move that data block</td></tr>
                            <tr><td>Middle click</td><td>tag</td><td>Pin / unpin (<code>★</code>)</td></tr>
                            <tr><td>Double click</td><td>aircraft scope, empty map</td><td>Start a measuring line; drag to set its end. The line shows the distance and heading between the two points.</td></tr>
                            <tr><td>Right drag</td><td>map</td><td>Pan</td></tr>
                            <tr><td>Wheel</td><td>map, charts, ground view</td><td>Zoom (labels and symbols keep their on-screen size)</td></tr>
                            <tr><td><kbd>Esc</kbd></td><td>measuring line / overlays</td><td>Cancel the measurement; close the top overlay</td></tr>
                            <tr><td><kbd>Delete</kbd></td><td>vector or measuring line</td><td>Remove the line under the pointer, or the one being drawn</td></tr>
                            <tr><td><kbd>Enter</kbd></td><td>callsign search</td><td>Run the search</td></tr>
                            <tr><td><kbd>Enter</kbd></td><td>MY ATC24 NAME</td><td>Save the name</td></tr>
                        </tbody>
                    </table>
                </div>
                <p>
                    The measuring tool is the same drawing engine as the vectoring tool, so a measurement can be
                    exported with the other vectors if you want to keep it.
                </p>

                <!-- ============================ TROUBLESHOOTING ============================ -->
                <h2 id="trouble"><span class="dc-h2-num">13</span>Troubleshooting</h2>

                <h3>The status badge is not <code>LIVE</code></h3>
                <ul>
                    <li><code>RECONNECTING</code> means the last request failed. The page keeps trying, and
                        deliberately slows its polling instead of hammering the server: 500 ms drops to 1 s, and
                        then backs off further if it keeps failing. Nothing needs restarting.</li>
                    <li><code>STALE</code> means no successful update for 15 seconds - the numbers on screen are
                        the last ones that arrived, not current. Treat the picture as history until it returns
                        to <code>LIVE</code>.</li>
                    <li>All four pages report the same thing in their own top bar, so if one is stale they all
                        are: it is the network's data source, not the page you happen to be looking at.</li>
                </ul>

                <h3>No aircraft at all</h3>
                <ul>
                    <li>Check the status badge first.</li>
                    <li>Check <code>GROUND AIRCRAFT</code> - with ground traffic hidden, a quiet field can
                        legitimately look empty.</li>
                    <li>Zoom out: the scope may be centred on a field with nothing in it.</li>
                </ul>

                <h3>Aircraft appear but there is no flight plan</h3>
                <p>
                    Flight plans are attached by matching the pilot's in-game name. An aircraft whose pilot has
                    not filed, or whose name cannot be matched exactly, has no plan - its panel says so, and no
                    fields are invented to fill the gap.
                </p>

                <h3>A board cell will not accept typing</h3>
                <p>
                    That is the amendment rule (<a href="#boards">section 05</a>). Check that
                    <em>MY ATC24 NAME</em> in Settings matches the name on the online ATC list and that you hold
                    a position at one of that flight's airports. The squawk works either way.
                </p>

                <h3>Edits say <code>LOCAL ONLY</code></h3>
                <p>
                    The shared board could not be reached, so your edits are stored in this browser only. They
                    are not lost, but other controllers cannot see them - refresh when the connection is back.
                </p>

                <h3>The follow page says there is no session</h3>
                <p>
                    The session lives in the browser that started it. Open the planner, complete the plan and
                    press <strong>START FLIGHT</strong>. If the browser's storage was cleared, the session is
                    gone and must be started again.
                </p>

                <h3>The follow page is waiting for the aircraft</h3>
                <p>
                    It has a plan but has not found your aircraft in the feed yet. This is usually a name
                    mismatch: the Roblox / in-game name in the plan must match the pilot name in the feed. The
                    planner's session detail tells you what it is looking for.
                </p>

                <h3>A heading or distance shows as missing</h3>
                <p>
                    That value could not be measured - no live position, no coordinates for that leg, or no
                    filed level. The page names the missing thing instead of printing a number that is not
                    real.
                </p>

                <h3>Small screens</h3>
                <p>
                    The radar is a desktop tool: the top bar wraps, the boards scroll sideways, and the ATIS
                    board and these documentation pages reflow for narrow windows, but the scope itself is best
                    at a full desktop window.
                </p>

                <!-- ============================ HONESTY ============================ -->
                <h2 id="honesty"><span class="dc-h2-num">14</span>What the data can and cannot tell you</h2>
                <p>
                    This client shows what the ATC24 data source provides and computes the rest openly, so you
                    always know which is which.
                </p>
                <div class="dc-table-wrap">
                    <table>
                        <thead>
                            <tr><th>Shown on screen</th><th>Where it comes from</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Position, altitude, ground speed, heading, type, on-ground flag</td><td>The ATC24 feed, as reported.</td></tr>
                            <tr><td>Route, filed level, rules, callsign, origin/destination</td><td>The flight's filed plan, as filed.</td></tr>
                            <tr><td>Status wording (climbing / cruising / descending / taxiing / landed / parked)</td><td><strong>Computed</strong> by this client from the feed's altitude and speed - it is a heuristic, labelled as text and not as a feed value.</td></tr>
                            <tr><td>Required heading, turn, distance to waypoint, route progress</td><td><strong>Computed</strong> from this build's own waypoint and runway records plus the live position.</td></tr>
                            <tr><td>Traffic advisory, conflict and go-around flags</td><td><strong>Computed</strong> from measured positions - advisories, not separation standards.</td></tr>
                            <tr><td>ATIS text</td><td>Verbatim from the network's published reports.</td></tr>
                            <tr><td>Controller / sector per aircraft</td><td><strong>Not shown.</strong> The network publishes which positions are claimed, not which aircraft belongs to which controller, so this client does not invent that attribution.</td></tr>
                        </tbody>
                    </table>
                </div>
                <p>
                    A field the system cannot read is left empty with the reason attached, never filled with a
                    plausible value. That is the single rule the whole client is built on, and it is why some
                    boxes look empty on a flight with no plan.
                </p>

                <hr>

                <h3>Where to next</h3>
                <div class="dc-cards">
                    <a class="dc-card" href="index.php">
                        <span class="dc-card-title">Open the radar</span>
                        <span class="dc-card-text">The main scope - everything in sections 02 to 05.</span>
                    </a>
                    <a class="dc-card" href="flight-planner.php">
                        <span class="dc-card-title">Flight planner</span>
                        <span class="dc-card-text">Build a route and the Discord flight-plan command.</span>
                    </a>
                    <a class="dc-card" href="follow-flight.php">
                        <span class="dc-card-title">Follow flight</span>
                        <span class="dc-card-text">Live heading, level and next waypoint for one flight.</span>
                    </a>
                    <a class="dc-card" href="atis.php">
                        <span class="dc-card-title">ATIS board</span>
                        <span class="dc-card-text">Every published report, searchable, refreshed once a second.</span>
                    </a>
                    <a class="dc-card" href="docs.php">
                        <span class="dc-card-title">Technical docs</span>
                        <span class="dc-card-text">Architecture, endpoints, data shapes and the verification tools.</span>
                    </a>
                    <a class="dc-card" href="about.html">
                        <span class="dc-card-title">Credits &amp; license</span>
                        <span class="dc-card-text">Who built what, and the GPL-3.0 terms.</span>
                    </a>
                </div>

                <footer class="dc-footer">
                    <span>ATC24 Radar by ATASOFT &middot; community project, GPL-3.0</span>
                    <span class="dc-footer-spacer"></span>
                    <a href="index.php">Radar</a>
                    <a href="flight-planner.php">Planner</a>
                    <a href="follow-flight.php">Follow flight</a>
                    <a href="atis.php">ATIS</a>
                    <a href="docs.php">Docs</a>
                    <a href="about.html">Credits</a>
                </footer>
            </article>
        </div>
    </main>
</body>

</html>
