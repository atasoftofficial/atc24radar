<?php
// ATASOFT ATC24 Radar - Follow Flight.
//
// The page the planner's START FLIGHT hands over to. It reads the SAME session
// the planner stored (localStorage "atasoft_fp_session"), the SAME plan fields,
// the SAME waypoint/airport/runway records and the SAME data.php feed, and it
// draws the SAME radar picture through src/plannerScope.js. It adds no
// endpoint and no second copy of any data.
//
// What it is for: one glance. The heading to fly, the level to hold, the next
// waypoint, and anything close in front - all recomputed once a second, in
// boxes large enough to read from across the desk instead of a table of rows.
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Follow Flight - ATC24 Radar - ATASOFT</title>
    <meta name="description" content="Follow a filed flight on the radar: required heading, level, next waypoint and nearby traffic, recomputed every second." />
    <meta name="theme-color" content="#060814" />

    <link rel="icon" type="image/svg+xml" href="public/assets/favicon.svg" />
    <link rel="alternate icon" type="image/png" href="public/assets/icon.png" />
    <link rel="apple-touch-icon" href="public/assets/apple-touch-icon.png" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php require __DIR__ . '/inc/fonts.php'; ?>
    <link rel="stylesheet" href="public/style.css?v=<?= @filemtime(__DIR__ . '/public/style.css') ?: time() ?>">
    <link rel="stylesheet" href="public/style-planner.css?v=<?= @filemtime(__DIR__ . '/public/style-planner.css') ?: time() ?>">
    <link rel="stylesheet" href="public/style-follow.css?v=<?= @filemtime(__DIR__ . '/public/style-follow.css') ?: time() ?>">

    <script>
        // Same-origin backend, exactly like index.php and flight-planner.php.
        window.ATASOFT_API_HOST = null;
    </script>
    <script type="module" src="src/followFlight.js?v=<?= @filemtime(__DIR__ . '/src/followFlight.js') ?: time() ?>"></script>
</head>

<body class="fp-body ff-body">
    <div id="top-bar" class="fp-topbar">
        <a id="name" href="index.php">ATC24 Radar <span style="opacity:.55; font-weight:500;">by ATASOFT</span></a>
        <div class="fp-nav">
            <a class="menu-button fp-navlink" href="index.php">&larr; RADAR</a>
            <a class="menu-button fp-navlink" href="flight-planner.php">FLIGHT PLANNER</a>
            <span class="menu-button fp-navlink fp-navlink-active">FOLLOW FLIGHT</span>
            <a class="menu-button fp-navlink" href="atis.php">ATIS</a>
        </div>
        <div class="fp-topbar-spacer"></div>
        <div id="ff-status" class="atasoft-status" title="Aircraft data connection status">
            <span class="atasoft-status__dot" data-state="connecting"></span>
            <span class="atasoft-status__label">CONNECTING</span>
        </div>
    </div>

    <main class="ff-main">
        <div id="ff-empty" class="ff-empty" hidden>
            <div class="ff-empty-title">No flight session</div>
            <div class="ff-empty-text">
                This page follows the flight the planner started. Open the planner,
                complete the plan and press START FLIGHT.
            </div>
            <a class="fp-button fp-button-primary" href="flight-planner.php">OPEN FLIGHT PLANNER</a>
        </div>

        <div id="ff-live" class="ff-live">
            <!-- The glance block. Four boxes, each one value, updated every
                 second from the feed: nothing here is a static plan value. -->
            <section class="ff-hud" aria-live="polite">
                <div class="ff-tile" id="ff-tile-hdg">
                    <span class="ff-tile-label">Required heading (next waypoint)</span>
                    <!-- The heading to fly and the turn that gets there are ONE
                         line: "247° L 58" is the whole clearance. The current
                         heading is named as such on the flight tile instead of
                         sitting under the required one as a second number. -->
                    <span class="ff-tile-headline">
                        <span class="ff-tile-value" id="ff-hdg">---</span>
                        <span class="ff-turn" id="ff-turn"></span>
                    </span>
                    <span class="ff-tile-sub" id="ff-hdg-sub">waiting for the aircraft</span>
                </div>
                <div class="ff-tile" id="ff-tile-alt">
                    <span class="ff-tile-label">Required altitude</span>
                    <span class="ff-tile-value" id="ff-alt">----</span>
                    <span class="ff-tile-sub" id="ff-alt-sub">filed level</span>
                </div>
                <div class="ff-tile ff-tile-wide" id="ff-tile-wp">
                    <span class="ff-tile-label">Next waypoint</span>
                    <span class="ff-tile-value" id="ff-wp">---</span>
                    <span class="ff-tile-sub" id="ff-wp-sub">no live position</span>
                </div>
                <div class="ff-tile" id="ff-tile-state">
                    <span class="ff-tile-label">Flight · current</span>
                    <span class="ff-tile-value ff-tile-value-small" id="ff-callsign">---</span>
                    <span class="ff-tile-sub" id="ff-state">PRE-FLIGHT</span>
                </div>
            </section>

            <div id="ff-advisory" class="ff-advisory" aria-live="polite"></div>

            <section class="ff-stage">
                <div class="ff-map-wrap">
                    <div class="fp-scope-bar ff-map-bar">
                        <span class="fp-scope-title">FOLLOWING <span id="ff-map-title">--</span></span>
                        <span class="fp-scope-actions">
                            <button id="ff-zoom-in" class="fp-mini" type="button" title="Zoom in">+</button>
                            <button id="ff-zoom-out" class="fp-mini" type="button" title="Zoom out">&minus;</button>
                            <button id="ff-fit-route" class="fp-mini" type="button" title="Fit the planned route">FIT ROUTE</button>
                            <button id="ff-follow" class="fp-mini fp-toggle is-on" type="button" title="Keep the aircraft centred">FOLLOW A/C</button>
                            <span id="ff-scope-status" class="fp-scope-status"></span>
                        </span>
                    </div>
                    <svg id="ff-plan-view" class="fp-plan-view" preserveAspectRatio="xMidYMid slice"></svg>
                </div>

                <aside class="ff-side">
                    <div class="fp-card">
                        <div class="fp-card-title">FLIGHT PLAN</div>
                        <div id="ff-plan" class="ff-plan"></div>
                    </div>
                    <div class="fp-card">
                        <div class="fp-card-title">ROUTE</div>
                        <div id="ff-route" class="ff-route"></div>
                    </div>
                    <div class="fp-card">
                        <div class="fp-card-title">NEARBY TRAFFIC</div>
                        <div class="fp-hint">
                            Measured from the same feed positions the radar draws. An
                            advisory, not a separation standard.
                        </div>
                        <div id="ff-traffic" class="ff-traffic"></div>
                    </div>
                </aside>
            </section>
        </div>
    </main>

    <a id="credits" href="about.html">Credits &amp; License · ATASOFT</a>
</body>

</html>
