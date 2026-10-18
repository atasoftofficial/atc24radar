<?php
// ATASOFT ATC24 Radar - Docs.
//
// The technical companion to guide.php: architecture, every endpoint and its
// cache, the exact data shapes the client consumes, the maths the navigation
// tools use, the flight-plan command contract, the aircraft silhouettes, the
// verification tools and this build's known limitations.
//
// Like guide.php this page is static - it reads no feed and adds no endpoint.
// Where a number appears here it is the constant the code really uses; where
// something is not implemented it is listed as not implemented rather than
// described as if it were.
?>
<!doctype html>
<html lang="en" class="dc-html">

<head>
    <meta charset="UTF-8" />
    <title>Docs - ATC24 Radar - ATASOFT</title>
    <meta name="description"
        content="Technical reference for ATC24 Radar by ATASOFT: architecture, PHP endpoints and caches, data shapes, navigation maths, the flight-plan command contract, aircraft silhouettes and verification tools." />
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
            <a href="guide.php">GUIDE</a>
            <a href="docs.php" aria-current="page">DOCS</a>
        </nav>
        <span class="dc-topbar-spacer"></span>
        <span class="dc-topbar-tag">TECHNICAL REFERENCE</span>
    </div>

    <main class="dc-main">
        <header class="dc-hero">
            <p class="dc-kicker">Docs &middot; for developers and self-hosters</p>
            <h1>ATC24 Radar - technical reference</h1>
            <p class="dc-lede">
                How this build is put together: the PHP endpoints and their caches, the shapes of the
                data the client consumes, the geometry the navigation tools are built on, the exact
                flight-plan command contract, the aircraft silhouettes, and the verification tools that
                hold all of it in place. Written against the shipped source, not a plan.
            </p>
            <div class="dc-meta">
                <span class="dc-chip"><strong>PHP</strong> backend, no build step</span>
                <span class="dc-chip">vanilla ES modules front end</span>
                <span class="dc-chip">GPL-3.0</span>
                <span class="dc-chip">user manual: <a href="guide.php">guide.php</a></span>
            </div>
        </header>

        <div class="dc-layout">
            <nav class="dc-toc" aria-label="Contents">
                <div class="dc-toc-title">Contents</div>
                <ol>
                    <li><a href="#architecture">Architecture</a></li>
                    <li><a href="#files">File map</a></li>
                    <li><a href="#endpoints">Endpoints and caches</a></li>
                    <li><a href="#upstreams">Upstream data sources</a></li>
                    <li><a href="#shapes">Data shapes</a></li>
                    <li><a href="#maths">Navigation and geometry</a></li>
                    <li><a href="#planner-core">Flight-plan core</a></li>
                    <li><a href="#board">Shared flight board</a></li>
                    <li><a href="#centre">Centre mode</a></li>
                    <li><a href="#rendering">Rendering contracts</a></li>
                    <li><a href="#glyphs">Aircraft silhouettes</a></li>
                    <li><a href="#checks">Verification tools</a></li>
                    <li><a href="#deploy">Deployment notes</a></li>
                    <li><a href="#limits">Known limitations</a></li>
                    <li><a href="#glossary">Glossary</a></li>
                </ol>
                <div class="dc-toc-note">
                    Using the app rather than reading its code? Start with
                    <a href="guide.php">the guide</a>.
                </div>
            </nav>

            <article class="dc-doc">
                <!-- ============================ ARCHITECTURE ============================ -->
                <h2 id="architecture"><span class="dc-h2-num">01</span>Architecture</h2>
                <p>
                    Two halves, no build step and no process to keep alive:
                </p>
                <ul>
                    <li>
                        <strong>Front end</strong> - plain PHP pages that each load their own ES module from
                        <code>src/</code> directly (<code>index.php</code> &#8594; <code>src/main.js</code>,
                        <code>flight-planner.php</code> &#8594; <code>src/flightPlanner.js</code>,
                        <code>follow-flight.php</code> &#8594; <code>src/followFlight.js</code>,
                        <code>atis.php</code> &#8594; <code>src/atisBoard.js</code>). There is no bundler, no
                        framework and no transpilation: what is on disk is what the browser runs. Content
                        hashes are appended as query strings (<code>?v=filemtime</code>) so a deploy cannot
                        serve a stale module.
                    </li>
                    <li>
                        <strong>Back end</strong> - a handful of small PHP endpoints that fetch the upstream
                        data server-side, cache it in flat files under <code>cache/</code>, and re-serve it
                        same-origin. Server-side fetching is not an optimisation: 24data's own API docs say
                        browsers cannot call it directly (no permissive CORS header), so the PHP layer is
                        what makes the data reachable at all. The flat-file caches are also the rate limiter
                        - many browser tabs polling one endpoint still cause one upstream request per cache
                        window.
                    </li>
                </ul>
                <p>One cycle, end to end:</p>
                <pre><code>browser  --GET data.php------------------&gt;  PHP  --GET /acft-data (if cache cold)--&gt;  24data
   ^                                          |                                        |
   |                                          +-- cache/acft-data.json (1 s) &lt;--------+
   |                                          +-- cache/fpls.json      (6 s) &lt;-- ws.awdevsoftware.org/fpls
   |                                          +-- cache/emergency.json (6 s) &lt;-- .../sse/acft-data
   |                                          +-- cache/trails.json    (own store)
   +----------- JSON aircraft map &lt;-----------+
</code></pre>
                <p>
                    The client polls <code>data.php</code> every <strong>500 ms</strong> while responses are
                    clean. On any failed response it drops to <strong>1000 ms</strong>, and after three
                    consecutive failures to <strong>2000 ms</strong>; after 30 clean cycles at the slow rate
                    it tries the fast rate again. Because the PHP cache is one second, at 500 ms roughly
                    every other response is the same snapshot - that is deliberate: it keeps the picture
                    from ever being more than half a second behind the cache without asking upstream any
                    more often.
                </p>
                <div class="dc-callout">
                    <span class="dc-callout-icon">NOTE</span>
                    <div>
                        <p>
                            No WebSocket anywhere in this build. Upstream flight plans are streamed over a
                            WebSocket, which needs a long-lived process - not realistic from per-request PHP
                            on shared hosting. This build instead merges AWDEV's REST flight-plan list
                            (<code>/fpls</code>) into the aircraft data server-side. The older Node implementation
                            that did use the WebSocket is kept (unused) in <code>legacy-node-backend/</code>.
                        </p>
                    </div>
                </div>

                <!-- ============================ FILES ============================ -->
                <h2 id="files"><span class="dc-h2-num">02</span>File map</h2>
                <div class="dc-table-wrap">
                    <table>
                        <thead>
                            <tr><th>Path</th><th>Role</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><code>index.php</code></td><td>The radar. Top bar, overlays, scope containers; loads <code>src/main.js</code>.</td></tr>
                            <tr><td><code>flight-planner.php</code></td><td>The planner page; loads <code>src/flightPlanner.js</code> + <code>src/plannerScope.js</code>.</td></tr>
                            <tr><td><code>follow-flight.php</code></td><td>The follow page; loads <code>src/followFlight.js</code> + <code>src/plannerScope.js</code>.</td></tr>
                            <tr><td><code>atis.php</code></td><td>Dual purpose: <code>?icao=</code> returns the ATIS-letter JSON the radar's generator uses; opened plainly it is the ATIS board page.</td></tr>
                            <tr><td><code>guide.php</code></td><td>The user guide (this built-in documentation).</td></tr>
                            <tr><td><code>docs.php</code></td><td>This page.</td></tr>
                            <tr><td><code>about.html</code></td><td>Credits, license, attribution.</td></tr>
                            <tr><td><code>data.php</code></td><td>Aircraft feed endpoint (the radar's own poll target).</td></tr>
                            <tr><td><code>atis-list.php</code></td><td>Published ATIS list endpoint.</td></tr>
                            <tr><td><code>airport-flights.php</code></td><td>Filed departures/arrivals per airport.</td></tr>
                            <tr><td><code>controllers.php</code></td><td>Online controller positions.</td></tr>
                            <tr><td><code>approaches.php</code></td><td>Chart list for an airport (filesystem listing).</td></tr>
                            <tr><td><code>flightboard.php</code></td><td>Shared controller board (read + write).</td></tr>
                            <tr><td><code>logos.php</code></td><td>Serves the airline-logo index (<code>public/assets/airline-logos.json</code>).</td></tr>
                            <tr><td><code>admin.php</code></td><td>Local admin/editor for the logo index.</td></tr>
                            <tr><td><code>inc/upstream.php</code></td><td>Shared upstream helper: cURL fetch, flat-file cache with <code>flock</code>, stale-on-failure fallback.</td></tr>
                            <tr><td><code>src/main.js</code></td><td>The radar: fetch loop, scope rendering, labels, boards, overlays, tools.</td></tr>
                            <tr><td><code>src/flightPlanCore.js</code></td><td>Shared flight-plan maths: validation, command builder, legs, bearing, sequencing, session, advisories. Used by the planner <em>and</em> the follow page, so the two can never disagree.</td></tr>
                            <tr><td><code>src/flightPlanner.js</code></td><td>Planner UI and its own scope wiring.</td></tr>
                            <tr><td><code>src/plannerScope.js</code></td><td>The map used by the planner and the follow page: coast, airspace, fixes, charts, runways, live traffic, zoom/pan.</td></tr>
                            <tr><td><code>src/followFlight.js</code></td><td>Follow page: HUD boxes, advisories, route panels.</td></tr>
                            <tr><td><code>src/atisBoard.js</code></td><td>ATIS board: search, 1 s polling, card rendering.</td></tr>
                            <tr><td><code>src/atisPanel.js</code></td><td>The radar's published-ATIS panel and <code>parsePublishedAtis()</code> - the parser the ATIS board reuses.</td></tr>
                            <tr><td><code>src/flightBoard.js</code></td><td>Client for the shared board: optimistic writes, retry, local fallback, amendment gate.</td></tr>
                            <tr><td><code>src/centre.js</code></td><td>Centre mode: airspace traffic, routes, runway lock, conflicts, go-arounds, divert.</td></tr>
                            <tr><td><code>src/emergency.js</code></td><td>Emergency and traffic alert engine, map markers, voice queue.</td></tr>
                            <tr><td><code>src/atcPhrases.js</code>, <code>src/sentenceHelper.js</code></td><td>The ATC phrase table and the role-aware helper that fills it in.</td></tr>
                            <tr><td><code>src/approachLine.js</code></td><td>One approach-line geometry shared by the ILS cone, the centreline tool, Centre mode and the routes.</td></tr>
                            <tr><td><code>src/data/*.js</code></td><td>Records and lookups: airports, fixes, runway thresholds, chart placement/offsets, icon map, aircraft drawings, zoom/scale tables.</td></tr>
                            <tr><td><code>public/style*.css</code></td><td><code>style.css</code> (radar + shared palette), <code>style-planner.css</code>, <code>style-follow.css</code>, <code>style-atis.css</code>, <code>style-docs.css</code> (guide + docs).</td></tr>
                            <tr><td><code>tools/verify-*.mjs</code></td><td>The verification suite (section 12).</td></tr>
                            <tr><td><code>cache/</code></td><td>Runtime flat-file caches. Must be writable by PHP.</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- ============================ ENDPOINTS ============================ -->
                <h2 id="endpoints"><span class="dc-h2-num">03</span>Endpoints and caches</h2>
                <p>
                    Every endpoint is a plain file served directly - there is no router and no rewrite rule,
                    by design (a root <code>.htaccess</code> that redirected to <code>index.php</code> would
                    break the endpoints). All of them answer same-origin.
                </p>
                <div class="dc-table-wrap">
                    <table>
                        <thead>
                            <tr><th>Endpoint</th><th>Method / params</th><th>Returns</th><th>Cache</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>data.php</code></td>
                                <td>GET</td>
                                <td>The aircraft map, enriched: matched flight plan, on-ground/taxiing flags, status, emergency flag, trail.</td>
                                <td>1 s (<code>CACHE_MAX_AGE</code>)</td>
                            </tr>
                            <tr>
                                <td><code>atis.php</code></td>
                                <td>GET <code>?icao=IRFD</code> or <code>?format=json</code></td>
                                <td><code>{"airport":"IRFD","letter":"A"}</code>, or 400/404/502 with an error object.</td>
                                <td>6 s</td>
                            </tr>
                            <tr>
                                <td><code>atis.php</code></td>
                                <td>GET (no params)</td>
                                <td>The ATIS board HTML page. The JSON half is untouched by it.</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><code>atis-list.php</code></td>
                                <td>GET <code>[?max_age=1..60]</code></td>
                                <td>Upstream's published-ATIS array as-is: <code>airport</code>, <code>letter</code>, <code>content</code>, <code>lines</code>, <code>editor</code>.</td>
                                <td>10 s default, clamped 1-60 s on request</td>
                            </tr>
                            <tr>
                                <td><code>airport-flights.php</code></td>
                                <td>GET <code>?airport=IRFD&amp;type=dep|arr</code></td>
                                <td>That airport's filed flight list.</td>
                                <td>6 s per airport/type</td>
                            </tr>
                            <tr>
                                <td><code>controllers.php</code></td>
                                <td>GET</td>
                                <td>Online controller positions.</td>
                                <td>6 s</td>
                            </tr>
                            <tr>
                                <td><code>approaches.php</code></td>
                                <td>GET <code>?icao=IRFD</code></td>
                                <td>Array of chart filenames under <code>public/assets/maps/IRFD/</code> (ground diagram excluded). No upstream call.</td>
                                <td>none (filesystem listing)</td>
                            </tr>
                            <tr>
                                <td><code>flightboard.php</code></td>
                                <td>GET / POST JSON</td>
                                <td><code>{"shared":true,"updatedAt":&lt;ms&gt;,"flights":{"&lt;KEY&gt;":{...}}}</code>; POST answers with the stored record or a refusal.</td>
                                <td>none (live board; 12 h record retention)</td>
                            </tr>
                            <tr>
                                <td><code>logos.php</code></td>
                                <td>GET</td>
                                <td>The airline-logo index JSON, served through PHP so hosts that block direct <code>.json</code> access still work.</td>
                                <td>no-store</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="dc-callout dc-callout--warn">
                    <span class="dc-callout-icon">CACHE</span>
                    <div>
                        <p>
                            A shorter <code>max_age</code> is a request, not a promise: the value is clamped to
                            1-60 seconds and <strong>one</strong> cache file serves every caller, so a page that
                            asks for one second cannot make the server hit upstream more than once per second.
                            That is what lets the ATIS board refresh once a second without becoming a
                            load problem.
                        </p>
                        <p>
                            On an upstream failure, every one of these endpoints prefers a still-readable
                            stale cache over an error - except that a genuinely cold cache returns 502 with a
                            JSON error object, which the clients treat as "no data", never as "zero traffic".
                        </p>
                    </div>
                </div>

                <!-- ============================ UPSTREAMS ============================ -->
                <h2 id="upstreams"><span class="dc-h2-num">04</span>Upstream data sources</h2>
                <div class="dc-table-wrap">
                    <table>
                        <thead>
                            <tr><th>Upstream</th><th>Used for</th><th>Note</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>24data.ptfs.app/acft-data</code></td>
                                <td>Aircraft positions and state.</td>
                                <td>The <strong>main</strong> server endpoint. The separate occasional-event endpoint (<code>/acft-data/event</code>) is deliberately not used - pointing at it was the original build's critical bug and the reason traffic looked empty.</td>
                            </tr>
                            <tr>
                                <td><code>24data.ptfs.app/atis</code></td>
                                <td>The ATIS letter per airport, for the generator.</td>
                                <td>Returns every airport at once; cached locally, one letter picked out per request.</td>
                            </tr>
                            <tr>
                                <td><code>ws.awdevsoftware.org/atis</code></td>
                                <td>The full published ATIS reports (text, runways, editor).</td>
                                <td>Community REST service. Same shape the ATIS panel, the ATIS board and Centre mode read.</td>
                            </tr>
                            <tr>
                                <td><code>ws.awdevsoftware.org/fpls</code></td>
                                <td>Filed flight plans, matched to aircraft by callsign.</td>
                                <td>Merged server-side in <code>data.php</code>; unmatched aircraft keep a saved plan until they land.</td>
                            </tr>
                            <tr>
                                <td><code>ws.awdevsoftware.org/sse/acft-data</code></td>
                                <td>Emergency reports.</td>
                                <td>Fetched with an 800 ms abort so it can never block <code>data.php</code>.</td>
                            </tr>
                            <tr>
                                <td><code>ws.awdevsoftware.org/dep|arr/{ICAO}</code></td>
                                <td>Filed departure/arrival lists per airport.</td>
                                <td>Behind <code>airport-flights.php</code>; gives the boards their filed rows.</td>
                            </tr>
                            <tr>
                                <td><code>ws.awdevsoftware.org/controllers</code></td>
                                <td>Online controller positions.</td>
                                <td>Behind <code>controllers.php</code>; also the source the shared-board amendment rule checks against.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ============================ SHAPES ============================ -->
                <h2 id="shapes"><span class="dc-h2-num">05</span>Data shapes</h2>

                <h3><code>data.php</code> - one aircraft</h3>
                <p>
                    A JSON object keyed by the feed's own callsign key (for example
                    <code>Delta-3454</code>). Every field below is what <code>data.php</code> actually emits:
                </p>
                <pre><code>{
  "heading": 85,
  "altitude": 31000,
  "playerName": "SomePilot",
  "aircraftType": "Airbus A320",
  "position": { "x": ..., "y": ... },
  "speed": 450,
  "wind": { ... },
  "groundSpeed": 450,
  "isOnGround": false,       // computed: altitude &lt; 100 AND groundspeed &lt; 100
  "isTaxiing": false,        // the feed's own ground flag (absent for helicopters)
  "isEmergencyOccuring": false,
  "flightPlan": { ... } | null,
  "flightStatus": "In Flight",   // "No FLP" | "Parked" | "Taxiing" | "In Flight"
  "trail": [ [x, y, altitude], ... ]
}</code></pre>
                <p>
                    Notes that matter when reading this: <code>isOnGround</code> is <em>computed</em> from
                    altitude and ground speed (thresholds 100 / 100) rather than taken from the feed, while
                    <code>isTaxiing</code> carries the feed's own flag; and <code>flightStatus</code> is a
                    four-value state derived from the matched plan plus those flags - the finer
                    climbing/cruising/descending wording belongs to the client's display logic, not to this
                    endpoint.
                </p>

                <h3>A matched flight plan</h3>
                <p>The plan object as the feed (and the planner's command) states it:</p>
                <pre><code>{
  "realcallsign": "Delta-3454",
  "callsign": "Delta-39W",
  "aircraft": "Airbus A320",
  "flightrules": "IFR",
  "departing": "IRFD",
  "arriving": "ISKP",
  "route": "IRFD KNIFE ABC ISKP",
  "flightlevel": "FL310",
  "filedAt": 1730000000
}</code></pre>
                <p>
                    Matching is exact, and never approximate: the feed's callsign key, then the normalised
                    key (lower case, runs of space/underscore/dash collapsed to one dash), then the flight
                    number's digits. A plan that cannot be matched is left unmatched - the panel then says
                    so instead of showing another flight's plan.
                </p>

                <h3>Flight session (<code>localStorage: atasoft_fp_session</code>)</h3>
                <pre><code>{
  "plan": { "ingameCallsign": ..., "callsign": ..., "robloxName": ..., "aircraft": ...,
            "flightRules": "IFR", "flightLevel": "060",
            "depIcao": "IRFD", "depRunway": "25", "arrIcao": "ISKP", "arrRunway": "07",
            "fixes": ["KNIFE", "ABC"], "airportNameMode": "full" },
  "cursor": 1,            // route leg the guidance is on
  "startedAt": ..., "endedAt": null
}</code></pre>
                <p>
                    The same key is written by the planner and read by the follow page - one session, one
                    source of truth. <code>cursor</code> is only ever advanced forward, and only by the
                    planner's live cycle or the follow page's own sequencing.
                </p>

                <h3>Shared board record (<code>flightboard.php</code>)</h3>
                <pre><code>{
  "shared": true,
  "updatedAt": 1730000000000,
  "flights": {
    "QANTAS-3312": {
      "fl": "FL340", "squawk": "1421", "rwy": "25", "sid": "...",
      "stand": "...", "rmk": "...", "cleared": true,
      "dep": "IRFD", "arr": "ISKP", "type": "A320",
      "by": "SomeController", "at": 1730000000000
    }
  }
}</code></pre>
                <p>
                    Records are keyed by the normalised callsign. Only these field names are ever stored
                    (<code>fl</code>, <code>squawk</code>, <code>rwy</code>, <code>sid</code>,
                    <code>stand</code>, <code>rmk</code>, <code>cleared</code>, <code>dep</code>,
                    <code>arr</code>, <code>type</code>); each value is length-limited (40 characters) and
                    records older than 12 hours are pruned, with a hard cap of 800 records. Anything else in
                    a POST body is ignored rather than stored.
                </p>

                <!-- ============================ MATHS ============================ -->
                <h2 id="maths"><span class="dc-h2-num">06</span>Navigation and geometry</h2>

                <h3>The bearing convention</h3>
                <p>
                    Map y grows <strong>downward</strong>, so north is <code>-y</code>. Every bearing in this
                    build - the radar's heading tool, the measuring line, Centre mode and the planner/follow
                    guidance - is computed as:
                </p>
                <pre><code>bearingDeg(from, to) = (atan2(to.x - from.x, -(to.y - from.y)) * 180 / PI + 360) % 360</code></pre>
                <div class="dc-callout dc-callout--danger">
                    <span class="dc-callout-icon">BUG</span>
                    <div>
                        <p>
                            <code>flightPlanCore.bearingDeg()</code> once used <code>atan2(dx, dy)</code>.
                            That mirrored every required heading about the east-west axis: on a live
                            IRFD &#8594; KNIFE leg the display said 173&deg; where the waypoint lay at 007&deg;,
                            so a pilot who flew the instruction turned away from the waypoint and back - the
                            "it zig-zags instead of pointing straight at the waypoint" report. The convention
                            above is now pinned by <code>tools/verify-flight-planner.mjs</code>, which checks
                            it against the radar's own source.
                        </p>
                    </div>
                </div>

                <h3>Route legs and sequencing</h3>
                <p>
                    A route is a list of legs built from records this build really has:
                    departure threshold &#8594; each selected fix &#8594; arrival threshold. A leg whose
                    coordinates are missing is kept in the list but can never be measured against - it is
                    reported as unavailable rather than skipped silently. Sequencing then answers one
                    question: <em>which leg is the aircraft on?</em>
                </p>
                <ul>
                    <li>The leg is the route segment the aircraft is <strong>closest</strong> to
                        (<code>segmentDistanceNm</code>), searched forward only, with a tie margin so the
                        shared waypoint at a turn stays with the leg being flown.</li>
                    <li>The next waypoint is where that leg ends - which is why a cut corner, a route that
                        doubles back, or a leg shorter than a waypoint radius all sequence correctly.</li>
                    <li>The cursor can never move backwards, and it can advance at most
                        <code>MAX_CURSOR_ADVANCE_PER_CYCLE</code> legs per call, so a corrupt position sample
                        cannot skip the route.</li>
                    <li>If the aircraft is more than <code>PASSED_LEG_GATE_NM</code> (20 NM) off every leg,
                        nothing is sequenced: the previous target is kept rather than a new one invented.</li>
                    <li>Standing within <code>WAYPOINT_RADIUS_NM</code> (3 NM) of the current waypoint ends
                        that leg. The departure threshold is a gate, not a waypoint - it stops being the next
                        waypoint on a real airborne flag or once the aircraft is demonstrably out along the
                        first leg.</li>
                </ul>

                <h3>Distance and scale</h3>
                <p>
                    Distances are planar nautical miles in map units, via the project's own
                    <code>NM_TO_UNITS</code> scale - the same scale the range rings are generated from. The
                    rings are drawn at 1, 2.5, 5, 10 and 20 NM rather than from the shipped
                    <code>rings.svg</code>, because that file labels a 248-unit circle "10nm", which is
                    7.5 NM at the scale the rest of the app measures with.
                </p>

                <h3>Turn guidance</h3>
                <p>
                    The turn direction and size come from the signed difference between the current heading
                    and the required bearing: <code>LEFT</code> for a counter-clockwise turn,
                    <code>RIGHT</code> for a clockwise one, and a magnitude under a few degrees collapses to
                    "on course". The HDG tool, the planner's live instruction and the follow page's headline
                    all read the same function, so the three can never disagree.
                </p>

                <!-- ============================ PLANNER CORE ============================ -->
                <h2 id="planner-core"><span class="dc-h2-num">07</span>Flight-plan core</h2>
                <p>
                    <code>src/flightPlanCore.js</code> is the single implementation of everything the planner
                    and the follow page share. Its contract, in order:
                </p>
                <ol class="dc-steps">
                    <li><strong>Validation</strong> against real records: every required field, the airport
                        exists in this build, the runway belongs to that airport (<code>validateRunway</code>),
                        the level parses, and every waypoint is a real Fixes-table identifier. Errors block
                        filing; warnings (a same-airport circuit, for example) do not.</li>
                    <li><strong>Flight levels</strong> (<code>parseFlightLevel</code>): <code>060</code>,
                        <code>FL060</code> and <code>60</code> normalise to <code>060</code>; the range this
                        network files (001-450) is accepted, <code>000</code>, four digits and non-numeric text
                        are refused; block levels (<code>010-020</code>) and the wordings the feed really
                        carries (<code>below 030</code>, <code>at or below 020</code>) are carried through in
                        their own form - a wording is never quietly turned into a number, and <code>-</code>
                        ("no level filed") is never read as a level.</li>
                    <li><strong>The command</strong> (<code>buildCommand</code>) - fixed parameter order:
                        <code>ingamecallsign</code>, <code>callsign</code>, <code>aircraft</code>,
                        <code>flightrules</code>, <code>departing</code>, <code>arriving</code>,
                        <code>flightlevel</code>, <code>ingamename</code>, <code>route</code>. Spaces inside a
                        value are emitted as-is, matching the documented example
                        (<code>departing:Greater Rockford</code>).</li>
                    <li><strong>The route string</strong>: departure runway, selected waypoints in order,
                        arrival runway - for example <code>25 KNIFE ABC 07</code>. This build adds no
                        navigation tokens of its own (no invented "DIRECT"), and a token is only a real
                        record if it is in the Fixes table.</li>
                    <li><strong>Legs and sequencing</strong> (<code>buildLegs</code>,
                        <code>advanceCursor</code>) as described in section 06.</li>
                    <li><strong>Live navigation</strong> (<code>navigationSnapshot</code>): required heading,
                        turn, distance, the current leg, progress, and the level wording -
                        <code>maintain 060</code>, <code>climb to 060 (4200 ft to go)</code>,
                        <code>descend to 040 (2500 ft to go)</code>.</li>
                    <li><strong>Advisories</strong> (<code>trafficAdvisory</code>,
                        <code>clearanceRequest</code>): traffic within 2 NM and vertically close is an
                        <em>alert</em>; within 5 NM in front (&#177;45&deg;) and within 2000 ft is a
                        <em>caution</em>; the level request appears only on the last leg and only while there
                        is a real difference to fly.</li>
                    <li><strong>Session state</strong> (<code>sessionState</code>): PRE-FLIGHT, WAITING FOR
                        AIRCRAFT, DEPARTURE, EN ROUTE, ARRIVAL, COMPLETED, PAUSED, INTERRUPTED or STATUS
                        UNAVAILABLE - derived from matched position, ground flags and route progress. It
                        never assumes the aircraft was found.</li>
                </ol>
                <p>
                    The module exports constants with the same names used above, so the numbers in this
                    section can be checked against the source directly. The planner additionally enforces
                    Discord's <strong>2000-character</strong> limit on the finished command.
                </p>

                <!-- ============================ BOARD ============================ -->
                <h2 id="board"><span class="dc-h2-num">08</span>Shared flight board</h2>
                <p>
                    The board exists because a departure controller and an arrival controller work the same
                    flight from different browsers, and the things they type - cleared level, squawk, runway,
                    SID, stand, remarks, cleared tick - are in no upstream feed. It is one JSON file with a
                    lock, following the same pattern as the other caches, so it needs no database.
                </p>
                <h3>The amendment rule</h3>
                <p>
                    Amending a flight (FL, RWY, SID, STAND, RMK, the cleared tick) requires a controlling
                    position at one of that flight's airports, or a CTR position that covers the field, on
                    the online-controller feed. The client's name is set once in Settings as <em>MY ATC24
                    NAME</em>.
                </p>
                <ul>
                    <li>Enforced in <strong>both</strong> places: the client makes those cells read-only and
                        says why, and <code>flightboard.php</code> re-checks the write, so a hand-made POST
                        cannot skip the rule.</li>
                    <li>The client treats a refusal as a decision - it is not retried forever.</li>
                    <li>The squawk is deliberately exempt: it is read off the flight and passed on, not an
                        amendment.</li>
                    <li>No name, no position, or a failed check all mean "not confirmed", which is the
                        read-only side. Nothing is assumed in the controller's favour.</li>
                    <li>The gate proves <em>possession</em> of a position, not identity: the name in a write is
                        taken at face value, exactly as the upstream feed does. This endpoint has no login,
                        and it stores operator annotation for a callsign and nothing else.</li>
                </ul>

                <!-- ============================ CENTRE ============================ -->
                <h2 id="centre"><span class="dc-h2-num">09</span>Centre mode</h2>
                <p>
                    Centre mode (<code>src/centre.js</code>) is the en-route layer: an airspace is selected
                    as an approximate radius around an airport (10-150 NM), airborne traffic in or near it is
                    listed and marked, and selecting an aircraft generates a temporary route with required
                    heading.
                </p>
                <ul>
                    <li><strong>Airspace is approximate, and labelled as such.</strong> This project has no
                        sector boundary data, so it draws a radius rather than inventing a boundary.</li>
                    <li><strong>Routes are built from the same approach line</strong> (<code>src/approachLine.js</code>)
                        the ILS cone and the extended-centreline tool use, so a route, the chart and the cone
                        cannot disagree about where final is. The route's arrival runway is taken from the
                        <em>published ATIS</em>, which is why an ATIS change is a real reason for a route to
                        change.</li>
                    <li><strong>The runway is locked per flight.</strong> Every cycle still scores every
                        published runway - the alternatives are kept on the route as
                        <code>route.alternates</code> and are never rendered - but the flight keeps its
                        runway unless that runway stops being offered at all, or another beats the held one by
                        a wide margin for at least 20 seconds. Regenerating is the one action that drops the
                        lock. Measured in <code>tools/verify-runway-lock.mjs</code>: at a position where the
                        unlocked scoring flips 6 times in 10 noisy probes, the locked route rebuilt 0 times
                        over 25 jittery cycles (the unlocked path rebuilt 7).</li>
                    <li><strong>Flags, not automation.</strong> Sequence conflicts (two routes converging on
                        one runway too close together) and go-arounds (the runway occupied by another aircraft
                        on that runway) are reported as flags; nothing is rerouted silently.</li>
                    <li><strong>Emergency Divert</strong> routes the selected aircraft to the nearest suitable
                        airport using the same leg maths as everything else.</li>
                </ul>

                <!-- ============================ RENDERING ============================ -->
                <h2 id="rendering"><span class="dc-h2-num">10</span>Rendering contracts</h2>
                <p>
                    These are the invariants the UI is held to; each one is enforced by a check in the
                    verification suite or pinned by explicit constants.
                </p>
                <div class="dc-table-wrap">
                    <table>
                        <thead>
                            <tr><th>Contract</th><th>Why it is a rule</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>A data block is a fixed number of <strong>screen pixels</strong>, not map units.</td>
                                <td>A map-unit figure rendered through the panel's own SVG scale read at under 7 px on a narrow window and 20+ px on a wide one, and it changed with every zoom step. Text is 12 px by default, held inside a 9-16 px band; the field in Settings shows the value &times;10.</td>
                            </tr>
                            <tr>
                                <td>A block's offset from its symbol is a fixed screen offset, and per class.</td>
                                <td>A world-unit offset walked the label away from its aircraft as the map zoomed in ("the tag scatters when I zoom in"). The floor comes from the silhouette's own half wingspan, so a 747's tag cannot land on its wing tip.</td>
                            </tr>
                            <tr>
                                <td>Every block is re-derived on every scale change, and never from an unmeasurable scale.</td>
                                <td>A collapsed side panel gives a zero-wide SVG; the old fallback of <code>1</code> produced a block about 20&times; too big. The scale helper now returns NaN in that state and the writers bail out, and a <code>ResizeObserver</code> re-derives blocks when the app resizes its own map (no window event fires for that).</td>
                            </tr>
                            <tr>
                                <td>Block lines are left-aligned by the renderer's own pen advance.</td>
                                <td>Aligning by the previous line's measured <em>ink</em> width (plus a flat 6 px) shifted each line further left than the one above it - the "the tag falls apart" report.</td>
                            </tr>
                            <tr>
                                <td>Which side of its symbol a block goes on is decided per aircraft, and stored on the label.</td>
                                <td>Sharing one <code>textAlign</code> across the scope meant one block flipping over flipped every later one.</td>
                            </tr>
                            <tr>
                                <td>Symbol rims are screen-space hairlines.</td>
                                <td><code>vector-effect: non-scaling-stroke</code> at ~1 px: 0.6 map units was sub-pixel on a wide view and a thick band when zoomed in.</td>
                            </tr>
                            <tr>
                                <td>On-ground blocks appear from a shallower zoom than 10&times;.</td>
                                <td>The old threshold was deeper than anyone zooms to work an airport, so taxiing aircraft had no callsign at all.</td>
                            </tr>
                            <tr>
                                <td>The map palette lives in one CSS block of variables.</td>
                                <td>Coast, boundaries and rings were all flat <code>#333333</code>/<code>#212121</code> on a <code>#060814</code> background - land, sea and boundaries within a couple of percent of each other. The assets' own colours are redirected to <code>--map-*</code> as the layers load, so the artwork is untouched and the charts keep their own styling.</td>
                            </tr>
                            <tr>
                                <td>Board columns are pixel widths with a min-width, and each panel is capped with a sideways scroll.</td>
                                <td>Percentage columns shrank every cell with the window - a <code>FL340</code> cell 15 px wide at 360 px, and the last character cut at 1280 px. <code>tools/verify-board-columns.mjs</code> fails the build if a percentage column comes back.</td>
                            </tr>
                            <tr>
                                <td>The top bar wraps instead of overflowing.</td>
                                <td>It is a row of <code>nowrap</code> buttons; below ~1500 px the old bar painted items outside itself and squeezed buttons under their own labels.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ============================ GLYPHS ============================ -->
                <h2 id="glyphs"><span class="dc-h2-num">11</span>Aircraft silhouettes</h2>
                <p>
                    Every aircraft is drawn as the real top view of that aircraft - not a symbol invented to
                    mean "an aeroplane". Each drawing is a single-piece SVG of one type, shipped in
                    <code>public/assets/aircraft-shapes/</code> and baked into
                    <code>src/data/AircraftShapes.js</code> by <code>tools/build-aircraft-shapes.mjs</code>, so
                    there is no fetch to fail at display time. 63 drawings cover the 101 aircraft types the
                    feed can report, across 19 icon categories.
                </p>
                <p>
                    The type the feed reports picks the drawing: the model name is looked up in
                    <code>MODEL_SHAPE</code> first (a 737-800 is drawn as a 737-800), then its category from
                    <code>src/data/AircraftIconMap.js</code> is looked up in <code>CATEGORY_SHAPE</code>, and a
                    type that is in neither draws <code>B738</code>. Both tables are generated from the
                    artwork's own file names, so a drawing that ships is a drawing that can be used.
                </p>
                <p>Geometry convention: the drawings keep their own coordinates - 1 unit = 1 metre, nose up,
                    tail along <code>+y</code>. The build tool measures each drawing's outline (curves included,
                    not just their control points), finds its real centre, and emits one transform that
                    centres the aircraft on its reported position, points the nose along <code>-y</code> and
                    scales its half wingspan to <code>span</code> map units. That span comes from the aircraft's
                    <em>published wingspan in metres</em>, mapped onto the 7.2-12.2 map-unit range the scope
                    can show, and then corrected to the drawing's own measured extent - the artwork, not the
                    table, is what the label has to clear. A Cessna is 11 m across and an A380 80 m, and the drawn sizes keep that order a
                    Cessna can still be read at 14 px and an A380 still fits under its own data block. Sizes
                    are therefore comparable but compressed, and the data block offset is derived from the
                    same span, so a heavy's callsign can never land on its wing tip.</p>

                <h3>The drawing set</h3>
                <div class="dc-table-wrap">
                    <table>
                        <thead>
                            <tr><th>Drawing</th><th>What it is</th><th>Drawn for</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><code>C172</code></td><td>High straight wing, fixed nose propeller, cabin and tailplane.</td><td>the light-single class - Cessna, Piper, Cirrus</td></tr>
                            <tr><td><code>B350</code></td><td>Two propeller nacelles on a straight wing, T-tail.</td><td>light and medium twins (King Air, Twin Otter)</td></tr>
                            <tr><td><code>DH8D</code></td><td>Long high wings, two big propellers, long fuselage.</td><td>regional turboprops (Q400, ATR)</td></tr>
                            <tr><td><code>B738</code></td><td>Narrow fuselage, swept wing, two underwing nacelles, winglets.</td><td>the default and the narrow twin-jet class (737, 757, E-Jets)</td></tr>
                            <tr><td><code>CRJ7</code> / <code>LJ35</code></td><td>Rear-fuselage engines, T-tail.</td><td>regional and business jets (CRJ, Learjet, MD-80/90)</td></tr>
                            <tr><td><code>B77W</code> / <code>A333</code></td><td>Wide fuselage, very large swept wing, two big fans.</td><td>wide-body twins (767, 777, 787, A330)</td></tr>
                            <tr><td><code>B744</code> / <code>A388</code></td><td>Four nacelles, jumbo fuselage; the A380's wing runs almost the full span.</td><td>747 and A380</td></tr>
                            <tr><td><code>A225</code></td><td>Six engines, twin tail, and the second-widest symbol in the set after the A380.</td><td>An-225 and the heavy-freight class</td></tr>
                            <tr><td><code>C17</code> / <code>C130</code></td><td>High wing, four (or two) nacelles, fat transport fuselage, T-tail.</td><td>military transports</td></tr>
                            <tr><td><code>E3CF</code></td><td>Transport airframe carrying a rotodome above the fuselage.</td><td>The E-3/E-8 surveillance aircraft</td></tr>
                            <tr><td><code>F16</code> / <code>A10</code> / <code>EUFI</code></td><td>Rounded nose, narrow fuselage, sharply swept or delta wing, tip rails.</td><td>fighters and attack aircraft</td></tr>
                            <tr><td><code>H60</code></td><td>Cabin, tail boom, tail rotor, main rotor drawn as blades - and the blades turn.</td><td>the single-rotor helicopter class</td></tr>
                            <tr><td><code>H47</code></td><td>Two main rotors fore and aft, counter-rotating.</td><td>the tandem-rotor Chinook layout</td></tr>
                        </tbody>
                    </table>
                </div>
                <p>
                    A type that has no drawing of its own falls back through its icon category to one of
                    these, and a category the build has never seen falls back to <code>B738</code> - the
                    symbol is never empty and never a bare dot.
                </p>

                <h3>The turning rotor</h3>
                <p>
                    The rotary-wing drawings already carry their blades seen from above, so the symbol is
                    recognisable even when the animation is off. On top of that artwork the radar draws, per
                    hub, a translucent disc (so the rotor area reads between blade passes) and a second blade
                    pair that is animated with an SVG <code>&lt;animateTransform type="rotate"&gt;</code> about
                    the hub - declared in the markup, running from the moment the symbol appears, needing no
                    JavaScript tick. The hub position and blade radius of each drawing are measured by the
                    build tool (they are the point where that drawing's blades actually cross, and the
                    published rotor diameter), never hand-placed, and counter-rotating rotors on the tandem
                    layout turn in opposite directions.
                </p>
                <div class="dc-callout">
                    <span class="dc-callout-icon">NOTE</span>
                    <div>
                        <p>
                            The rotor is deliberately drawn as a disc plus two blades rather than many blades:
                            the symbol is only about 12-16 px on the scope, and anything finer reads as a blur
                            at that size. The discs are also why rotary traffic keeps its own accent colour.
                        </p>
                    </div>
                </div>

                <h3>How the drawings are verified</h3>
                <p>
                    <code>tools/verify-aircraft-glyphs.mjs</code> rebuilds the module in check mode (so a
                    drawing that changed on disk but not in the module cannot ship), then rasterises the
                    module's own path data under the same non-zero fill rule the browser uses (curves
                    flattened, subpath windings accumulated per sample) and probes it: the fuselage must be
                    solid on the centreline, each wing must be present at the declared span and empty beyond
                    it, the nose and the tail must lie where the transform says they do, and no drawing may
                    be smaller than the size at which it can be recognised. It also walks all 101 feed types
                    and fails if any of them resolves to no drawing, and asserts that <code>main.js</code> and
                    <code>plannerScope.js</code> still draw the shapes the way the artwork needs (separate
                    paths, counter-scaled group, turning rotor). <code>--ascii</code> prints every drawing to
                    the terminal.
                </p>

                <!-- ============================ CHECKS ============================ -->
                <h2 id="checks"><span class="dc-h2-num">12</span>Verification tools</h2>
                <p>
                    The tools run against the <em>shipped</em> source, print a report and exit non-zero on
                    failure. They are the reason this documentation can state numbers with confidence - each
                    one loads the real modules and, where it can, reads the real data files.
                </p>
                <pre><code>node tools/verify-flight-planner.mjs     # command format, real records only, bearing convention, sequencing
node tools/verify-aircraft-glyphs.mjs    # every feed type draws its own, correctly placed aircraft drawing
node tools/verify-label-geometry.mjs     # data block: px sizing, aligned lines, per-aircraft side, zoom
node tools/verify-centre-joins.mjs       # route geometry never flies away from the field
node tools/verify-traffic-plan.mjs       # runway allocation, sequences, go-around, departures
node tools/verify-runway-lock.mjs        # one flight keeps one runway
node tools/verify-amendment-rule.mjs     # who may amend a flight plan, and what is sent
node tools/verify-board-columns.mjs      # DEP/ARR columns keep a readable width
node tools/verify-approach-lines.mjs     # cone, centreline and route share one approach line
node tools/verify-sentence-helper.mjs    # phrase roles and placeholders
node tools/apply-runway-offsets.mjs      # (maintenance) re-derive runway geometry
node tools/build-chart-from-published.mjs # (maintenance) rebuild a chart layer</code></pre>
                <p>
                    <code>verify-aircraft-glyphs.mjs --ascii</code> prints every silhouette into the terminal,
                    which is the fastest way to review a change to the artwork without a browser.
                </p>

                <!-- ============================ DEPLOY ============================ -->
                <h2 id="deploy"><span class="dc-h2-num">13</span>Deployment notes</h2>
                <ul>
                    <li><strong>No install and no process.</strong> PHP on shared hosting is the target: upload
                        the files, the pages and endpoints work. With PHP locally,
                        <code>php -S localhost:8000</code> in the project root is enough (see
                        <a href="DEPLOYMENT.md">DEPLOYMENT.md</a>).</li>
                    <li><strong>No build step.</strong> The browser loads <code>src/*.js</code> directly, so a
                        file edit is a deploy. Cache-busting uses file modification times.</li>
                    <li><strong><code>cache/</code> must be writable by PHP</strong> - it holds the upstream
                        caches, the trails, the saved flight plans and the shared board.</li>
                    <li><strong>No rewrite rules.</strong> Every page and endpoint is a real file; a
                        catch-all rewrite to <code>index.php</code> in this or a parent directory would break
                        them.</li>
                    <li><strong>Secrets: none.</strong> Every upstream is public and read-only, so the project
                        ships no keys. The shared board is intentionally unauthenticated and stores only
                        operator annotation (see section 08 for the one rule it does enforce).</li>
                    <li><strong>Hardening already in the tree</strong>: dotfiles denied, direct <code>.json</code>
                        access denied except the logo index (which is served through <code>logos.php</code>),
                        directory listing off, a Content-Security-Policy header, and no <code>Origin</code>-based
                        authorisation logic on the read endpoints - the PHP layer is same-origin by construction
                        and rate-limited by its own caches.</li>
                </ul>

                <!-- ============================ LIMITS ============================ -->
                <h2 id="limits"><span class="dc-h2-num">14</span>Known limitations</h2>
                <p>
                    Stated plainly, because a radar that hides its gaps is worse than one that names them:
                </p>
                <div class="dc-table-wrap">
                    <table>
                        <thead>
                            <tr><th>Area</th><th>Limitation</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Upstream flight plans</td>
                                <td>Arrive over AWDEV's REST list, not the network's WebSocket stream. A plan that
                                    service does not carry at that moment is absent until it does - the aircraft is
                                    still drawn, without a plan.</td>
                            </tr>
                            <tr>
                                <td>Flight status wording</td>
                                <td>Climbing/cruising/descending is inferred from altitude and trend in the client;
                                    the endpoint itself reports four states (<code>No FLP</code>, <code>Parked</code>,
                                    <code>Taxiing</code>, <code>In Flight</code>).</td>
                            </tr>
                            <tr>
                                <td>Controller attribution</td>
                                <td>The network publishes which positions are claimed, not which aircraft belongs
                                    to which controller, so per-aircraft controller info is not shown or invented.</td>
                            </tr>
                            <tr>
                                <td>Airspace</td>
                                <td>No sector boundary data exists here. Centre mode's airspace is an approximate
                                    radius around a field, and says so on screen.</td>
                            </tr>
                            <tr>
                                <td>Predicted track</td>
                                <td>A straight predictive speed vector is drawn from current heading and speed;
                                    there is no turn-aware trajectory modelling.</td>
                            </tr>
                            <tr>
                                <td>Waypoint turn radius</td>
                                <td>3 NM, one value for the whole route on this network's often short legs. It can
                                    switch the target to the next waypoint up to 3 NM early by design, and its
                                    calibration for very short legs is an open item, not a hidden constant.</td>
                            </tr>
                            <tr>
                                <td>Traffic alerts</td>
                                <td>TCAS-style tiers over measured values with confirmation and hysteresis, but
                                    they are advisories. They are not, and do not claim to be, a separation
                                    standard.</td>
                            </tr>
                            <tr>
                                <td>Ground data</td>
                                <td>Ground diagrams, stands and taxiways come from this build's own chart records;
                                    where a field has no published chart, the ground view has nothing to draw.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ============================ GLOSSARY ============================ -->
                <h2 id="glossary"><span class="dc-h2-num">15</span>Glossary</h2>
                <dl>
                    <dt>ATIS</dt>
                    <dd>Automatic Terminal Information Service - the recorded field report. Here: both the text
                        published on the network and the generator that writes one.</dd>
                    <dt>Fix</dt>
                    <dd>A named navigation point from this build's Fixes table. The planner and the radar draw
                        the same records.</dd>
                    <dt>Leg</dt>
                    <dd>One segment of a route, between two consecutive waypoints. Sequencing asks which leg the
                        aircraft is on.</dd>
                    <dt>Data block / tag</dt>
                    <dd>The three-line label beside an aircraft symbol: callsign, altitude and speed, heading
                        and type.</dd>
                    <dt>Strip</dt>
                    <dd>A flight's row on the strip board - the digital equivalent of a paper flight strip.</dd>
                    <dt>Session</dt>
                    <dd>The flight plan the planner started and the follow page tracks, stored in the browser's
                        <code>localStorage</code>.</dd>
                    <dt>Amendment</dt>
                    <dd>A controller change to a flight's operative data (level, runway, SID, stand, remarks,
                        cleared). Requires a position; the squawk is exempt.</dd>
                    <dt>Runway lock</dt>
                    <dd>Centre mode's rule that a flight keeps the arrival runway it was given, instead of
                        re-deriving it every few seconds.</dd>
                </dl>

                <hr>

                <h3>See also</h3>
                <div class="dc-cards">
                    <a class="dc-card" href="guide.php">
                        <span class="dc-card-title">User guide</span>
                        <span class="dc-card-text">Every screen and control, explained in use.</span>
                    </a>
                    <a class="dc-card" href="README.md">
                        <span class="dc-card-title">README.md</span>
                        <span class="dc-card-text">Build-over-build change log and the reasoning behind each fix.</span>
                    </a>
                    <a class="dc-card" href="FEATURE_MATRIX.md">
                        <span class="dc-card-title">FEATURE_MATRIX.md</span>
                        <span class="dc-card-text">Every feature against its source: reused, refactored, fixed or new.</span>
                    </a>
                    <a class="dc-card" href="DEPLOYMENT.md">
                        <span class="dc-card-title">DEPLOYMENT.md</span>
                        <span class="dc-card-text">Hosting setup, caches and permissions.</span>
                    </a>
                    <a class="dc-card" href="ATTRIBUTION.md">
                        <span class="dc-card-title">ATTRIBUTION.md</span>
                        <span class="dc-card-text">Upstream projects, licences and what came from where.</span>
                    </a>
                    <a class="dc-card" href="about.html">
                        <span class="dc-card-title">Credits &amp; license</span>
                        <span class="dc-card-text">The same attribution, on the site itself.</span>
                    </a>
                </div>

                <footer class="dc-footer">
                    <span>ATC24 Radar by ATASOFT &middot; community project, GPL-3.0</span>
                    <span class="dc-footer-spacer"></span>
                    <a href="index.php">Radar</a>
                    <a href="flight-planner.php">Planner</a>
                    <a href="follow-flight.php">Follow flight</a>
                    <a href="atis.php">ATIS</a>
                    <a href="guide.php">Guide</a>
                    <a href="about.html">Credits</a>
                </footer>
            </article>
        </div>
    </main>
</body>

</html>
