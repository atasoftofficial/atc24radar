<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>ATC24 Radar — ATASOFT</title>
    <meta name="description" content="ATC24 Radar by ATASOFT - a free, non-commercial community radar client for Roblox PTFS / ATC24." />
    <meta name="theme-color" content="#060814" />

    <link rel="icon" type="image/svg+xml" href="public/assets/favicon.svg" />
    <link rel="alternate icon" type="image/png" href="public/assets/icon.png" />
    <link rel="apple-touch-icon" href="public/assets/apple-touch-icon.png" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- ATASOFT: the intro gate. Inline and before the stylesheets so it runs
         before first paint: a returning visitor gets .intro-skip on <html> and
         the overlay is display:none from the very first frame, with no flash of
         the intro and no layout shift. If storage is unavailable (private mode,
         blocked cookies) the intro simply shows again - see src/introSequence.js. -->
    <script>
        (function () {
            try {
                var seen = window.localStorage.getItem('atasoft_intro_seen') === '1';
                var replay = /(?:^|[?&])intro=1(?:&|$)/.test(window.location.search);
                if (seen && !replay) document.documentElement.classList.add('intro-skip');
            } catch (err) { /* storage unavailable: show the intro */ }
        })();
    </script>
    <?php require __DIR__ . '/inc/fonts.php'; ?>
    <link rel="stylesheet" href="public/style.css?v=<?= @filemtime(__DIR__ . '/public/style.css') ?: time() ?>">
    <!-- ATASOFT: additive sheet for the ATC Sentence Helper (and the Flight
         Planner page). Every rule in it is new or scoped, so nothing the radar
         already draws is affected by loading it. -->
    <link rel="stylesheet" href="public/style-planner.css?v=<?= @filemtime(__DIR__ . '/public/style-planner.css') ?: time() ?>">

    <script>
        // Backend API/WebSocket host for the ATC24 Radar data server.
        // Leave as null to use the same host/origin that served this page
        // (recommended: run the Node backend behind the same domain via a
        // reverse proxy). Override only for a split-domain deployment, e.g.:
        //   window.ATASOFT_API_HOST = "api.atc24radar.atasoft.xyz";
        window.ATASOFT_API_HOST = null;
    </script>
    <script type="module" src="src/main.js?v=<?= @filemtime(__DIR__ . '/src/main.js') ?: time() ?>"></script>
    <!-- Separate module on purpose: the intro owns its own state and must not
         be able to hold up (or be held up by) the radar's bootstrap. -->
    <script type="module" src="src/introSequence.js?v=<?= @filemtime(__DIR__ . '/src/introSequence.js') ?: time() ?>"></script>
    <script src="src/changelog.js" defer></script>
</head>

<body>
    <!-- ATASOFT: first-visit intro sequence.

         Three flat screens over one static radar scope - sweep, rings, blips -
         shown BEFORE the map is looked at:
           welcome  ->  Discord invite  ->  thank you  ->  the radar itself.

         Rules this obeys:
           * it is not a modal trap: Skip (bottom right) or Escape leaves at
             any point, and every screen has its own visible action;
           * it is shown once per browser. The tiny script in <head> reads
             localStorage['atasoft_intro_seen'] BEFORE first paint and adds
             .intro-skip to <html>, so a returning visitor never sees a frame
             of it. Append ?intro=1 to the URL to replay it;
           * nothing here touches the radar: main.js boots behind the overlay
             exactly as before, and dismissing only hides a fixed layer;
           * it uses the same flat palette as the rest of the console - no
             gradient, no glow. The sweep line and blips ARE the animation.

         Markup only: the behaviour (step order, focus, the stored flag) lives
         in src/introSequence.js. Without that module the overlay stays on the
         welcome screen with a working Continue-less Skip - which is why the
         skip button is a plain <button>, not something JS has to build. -->
    <div id="intro-sequence" class="intro" role="dialog" aria-modal="true" aria-labelledby="intro-welcome-title">
        <div class="intro-scope" aria-hidden="true">
            <svg viewBox="0 0 360 360" focusable="false">
                <!-- The scope powers up as one piece: the tick ring and the
                     rings are drawn in, then the sweep starts and the blips
                     begin to beat. Every delay is a CSS animation-delay, so the
                     whole sequence is one paint. -->
                <circle class="intro-tick-ring" cx="180" cy="180" r="176" />
                <circle class="intro-ring" cx="180" cy="180" r="170" />
                <circle class="intro-ring" cx="180" cy="180" r="158" />
                <circle class="intro-ring" cx="180" cy="180" r="118" />
                <circle class="intro-ring" cx="180" cy="180" r="78" />
                <circle class="intro-ring intro-ring-major" cx="180" cy="180" r="38" />
                <line class="intro-axis" x1="8" y1="180" x2="352" y2="180" />
                <line class="intro-axis" x1="180" y1="8" x2="180" y2="352" />
                <g class="intro-sweep">
                    <line class="intro-sweep-trail" x1="180" y1="180" x2="96" y2="30" />
                    <line class="intro-sweep-trail" x1="180" y1="180" x2="126" y2="18" />
                    <line class="intro-sweep-line" x1="180" y1="180" x2="180" y2="14" />
                </g>
                <!-- Four targets: a lit blip and the ripple it leaves behind,
                     each pair on its own delay so the scope is not blinking in
                     unison. -->
                <circle class="intro-blip-ping" cx="246" cy="96" r="3.5" style="animation-delay: 0s;" />
                <circle class="intro-blip" cx="246" cy="96" r="3.5" style="animation-delay: 0s;" />
                <circle class="intro-blip-ping" cx="112" cy="124" r="2.6" style="animation-delay: 0.9s;" />
                <circle class="intro-blip" cx="112" cy="124" r="2.6" style="animation-delay: 0.9s;" />
                <circle class="intro-blip-ping" cx="222" cy="232" r="3" style="animation-delay: 1.6s;" />
                <circle class="intro-blip" cx="222" cy="232" r="3" style="animation-delay: 1.6s;" />
                <circle class="intro-blip-ping" cx="146" cy="256" r="2.4" style="animation-delay: 2.4s;" />
                <circle class="intro-blip" cx="146" cy="256" r="2.4" style="animation-delay: 2.4s;" />
                <circle class="intro-reticle" cx="180" cy="180" r="2.6" />
                <!-- Two readouts, in the data face, bottom-left of the scope. -->
                <text class="intro-scope-readout" x="14" y="322">SWEEP 7.0 S</text>
                <text class="intro-scope-readout" x="14" y="338">RNG 1-20 NM</text>
            </svg>
        </div>

        <div class="intro-body">
            <p class="intro-kicker">PTFS / ATC24 &middot; COMMUNITY RADAR</p>

            <section class="intro-step is-active" data-intro-step="welcome">
                <!-- The title is split into words so the line can rise in
                     word by word (--i is the stagger index). It stays one
                     heading for assistive tech: the words are inline spans with
                     real spaces between them. -->
                <h1 id="intro-welcome-title" class="intro-title">
                    <span class="intro-word" style="--i: 0;">Welcome</span>
                    <span class="intro-word" style="--i: 1;">to</span>
                    <span class="intro-word" style="--i: 2;">ATC24</span>
                    <span class="intro-word" style="--i: 3;">Radar</span>
                </h1>
                <span class="intro-rule" aria-hidden="true"></span>
                <p class="intro-by">By ATASOFT</p>
                <p class="intro-lead">
                    A free browser radar for PTFS / ATC24 controllers.
                    No account, no download, no paid tier.
                </p>
                <button type="button" class="intro-action" data-intro-next>Continue</button>
            </section>

            <section class="intro-step" data-intro-step="discord">
                <h2 id="intro-discord-title" class="intro-title">Join our Discord server</h2>
                <span class="intro-rule" aria-hidden="true"></span>
                <!-- A flat "invite" card: the server address in the data face,
                     a live dot on the same step-blink as the scope's blips, and
                     the two actions. It slides in behind the heading. -->
                <div class="intro-invite">
                    <p class="intro-invite-head">
                        <span class="intro-invite-dot" aria-hidden="true"></span>
                        <span class="intro-invite-url">discord.gg/38MMyjpT9Q</span>
                    </p>
                    <p class="intro-lead">
                        Chart updates, bug reports and the other controllers using this build.
                        It is where the project actually happens.
                    </p>
                    <a class="intro-action" href="https://discord.gg/38MMyjpT9Q" target="_blank"
                        rel="noopener noreferrer" data-intro-discord>Join the Discord</a>
                    <button type="button" class="intro-action intro-action-quiet" data-intro-next>Maybe later</button>
                </div>
            </section>

            <section class="intro-step" data-intro-step="thanks">
                <h2 id="intro-thanks-title" class="intro-title">Thank you</h2>
                <span class="intro-rule" aria-hidden="true"></span>
                <p class="intro-lead">The scope is online. Clear skies.</p>
                <button type="button" class="intro-action" data-intro-next>Enter the scope</button>
            </section>
        </div>

        <!-- Which of the three screens is up. Decorative, and the only thing
             that survives a reduced-motion setting: the bar is not animated,
             it is just filled. -->
        <div class="intro-progress" aria-hidden="true">
            <span class="intro-progress-seg is-on"></span>
            <span class="intro-progress-seg"></span>
            <span class="intro-progress-seg"></span>
        </div>

        <button type="button" class="intro-skip-link" data-intro-skip>Skip intro</button>
    </div>

    <div id="top-bar">
        <div class="time-display">
            <button id="time-button" class="menu-button">LOCAL</button>
            <span id="time">Loading ...</span>
        </div>
        <button id="ground-aircraft" class="menu-button">GROUND AIRCRAFT</button>
        <label for="airport-dropdown" class="dropdown-label">STATION SELECT</label>
        <div id="station-icon" class="menu-button tool-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-sliders2"
                viewBox="0 0 16 16">
                <path fill-rule="evenodd"
                    d="M10.5 1a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4H1.5a.5.5 0 0 1 0-1H10V1.5a.5.5 0 0 1 .5-.5M12 3.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5m-6.5 2A.5.5 0 0 1 6 6v1.5h8.5a.5.5 0 0 1 0 1H6V10a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5M1 8a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2A.5.5 0 0 1 1 8m9.5 2a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V13H1.5a.5.5 0 0 1 0-1H10v-1.5a.5.5 0 0 1 .5-.5m1.5 2.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5" />
            </svg>
        </div>
        <select id="airport-dropdown" name="airport">
            <option value="IBLT">IBLT</option>
            <option value="IBTH">★IBTH</option>
            <option value="IDCS">IDCS</option>
            <option value="IGAR">IGAR</option>
            <option value="IGRV">★IGRV</option>
            <option value="IHEN">IHEN</option>
            <option value="IIAB">IIAB</option>
            <option value="IJAF">IJAF</option>
            <option value="ILAR">★ILAR</option>
            <option value="ILKL">ILKL</option>
            <option value="IMLR">IMLR</option>
            <option value="IPAP">IPAP</option>
            <option value="IPPH">★IPPH</option>
            <option value="IRFD" selected>★IRFD</option>
            <option value="ISAU">★ISAU</option>
            <option value="ISCM">ISCM</option>
            <option value="ISKP">ISKP</option>
            <option value="ITKO">★ITKO</option>
            <option value="ITRC">ITRC</option>
            <option value="IZOL">★IZOL</option>
        </select>
        <button id="ground-button" class="menu-button">GROUND</button>
        <button id="approach-button" class="menu-button">APP Sel</button>
        <button id="all-airports-button" class="menu-button" title="View ground/approach charts for all airports">ALL APT</button>
        <button id="atc-button" class="menu-button" title="View online ATC positions">ONLINE ATCS</button>
        <button id="strips-button" class="menu-button" title="Flight strip board">STRIPS</button>
        <!-- ATASOFT: the Flight Planner lives on its own page (a real file, no
             router) and shares this app's airport records, Fixes table, live
             feed and ATIS endpoint. Linked rather than rewritten in. -->
        <a id="flight-planner-button" class="menu-button" href="flight-planner.php"
            title="Flight planner - build a route from the real Fixes table and generate the ATC24 Discord flight-plan command">FPL</a>
        <button id="centre-button" class="menu-button" title="Centre Mode - airspace &amp; routes" style="display:none;">CENTRE</button>
        <button id="hdg-button" class="menu-button" title="Vector to point: select an aircraft, click HDG, then click the map to get the required heading">HDG</button>
        <div style="font-size: 2em; font-weight: 100;">|</div>
        <div id="callsign-search-box" style="display:flex; align-items:center; gap:4px;">
            <input type="text" id="callsign-search" placeholder="Search callsign..." autocomplete="off"
                style="background: var(--panel-deep); border: 1px solid var(--border); border-radius: var(--radius-sm);
                color: var(--text); padding: 5px 8px; font-family: var(--font); font-size: 0.85em; width: 130px;">
            <button id="callsign-search-btn" class="menu-button" type="button">FIND</button>
        </div>
        <div style="font-size: 2em; font-weight: 100;">|</div>
        <div id="tools">
            <div id="notepad-icon" class="menu-button tool-icon">
                <svg id="" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                    class="bi bi-journal" viewBox="0 0 16 16">
                    <path
                        d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2" />
                    <path
                        d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1z" />
                </svg>
            </div>
            <div id="departures-icon" class="menu-button tool-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 640 512">
                    <path fill="currentColor"
                        d="M624 448H16c-8.84 0-16 7.16-16 16v32c0 8.84 7.16 16 16 16h608c8.84 0 16-7.16 16-16v-32c0-8.84-7.16-16-16-16M80.55 341.27c6.28 6.84 15.1 10.72 24.33 10.71l130.54-.18a65.62 65.62 0 0 0 29.64-7.12l290.96-147.65c26.74-13.57 50.71-32.94 67.02-58.31c18.31-28.48 20.3-49.09 13.07-63.65c-7.21-14.57-24.74-25.27-58.25-27.45c-29.85-1.94-59.54 5.92-86.28 19.48l-98.51 49.99l-218.7-82.06a17.799 17.799 0 0 0-18-1.11L90.62 67.29c-10.67 5.41-13.25 19.65-5.17 28.53l156.22 98.1l-103.21 52.38l-72.35-36.47a17.804 17.804 0 0 0-16.07.02L9.91 230.22c-10.44 5.3-13.19 19.12-5.57 28.08z" />
                </svg>
            </div>
            <div id="arrivals-icon" class="menu-button tool-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 640 512">
                    <path fill="currentColor"
                        d="M624 448H16c-8.84 0-16 7.16-16 16v32c0 8.84 7.16 16 16 16h608c8.84 0 16-7.16 16-16v-32c0-8.84-7.16-16-16-16M44.81 205.66l88.74 80a62.607 62.607 0 0 0 25.47 13.93l287.6 78.35c26.48 7.21 54.56 8.72 81 1.36c29.67-8.27 43.44-21.21 47.25-35.71c3.83-14.5-1.73-32.71-23.37-54.96c-19.28-19.82-44.35-32.79-70.83-40l-97.51-26.56L282.8 30.22c-1.51-5.81-5.95-10.35-11.66-11.91L206.05.58c-10.56-2.88-20.9 5.32-20.71 16.44l47.92 164.21l-102.2-27.84l-27.59-67.88c-1.93-4.89-6.01-8.57-11.02-9.93L52.72 64.75c-10.34-2.82-20.53 5-20.72 15.88l.23 101.78c.19 8.91 6.03 17.34 12.58 23.25" />
                </svg>
            </div>
            <div id="atis-icon" class="menu-button tool-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                    class="bi bi-body-text" viewBox="0 0 16 16">
                    <path fill-rule="evenodd"
                        d="M0 .5A.5.5 0 0 1 .5 0h4a.5.5 0 0 1 0 1h-4A.5.5 0 0 1 0 .5m0 2A.5.5 0 0 1 .5 2h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m9 0a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m-9 2A.5.5 0 0 1 .5 4h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5m5 0a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m7 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5m-12 2A.5.5 0 0 1 .5 6h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5m8 0a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m-8 2A.5.5 0 0 1 .5 8h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m7 0a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m-7 2a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 0 1h-8a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5" />
                </svg>
            </div>
            <div id="vector-icon" class="menu-button tool-icon">
                <svg width="16" height="16" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img"
                    class="iconify iconify--gis" preserveAspectRatio="xMidYMid meet" fill="#000000">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                    <g id="SVGRepo_iconCarrier">
                        <path
                            d="M33.418 11c-3.497 0-6.672 1.454-8.961 3.781c-2.31 2.281-3.752 5.44-3.752 8.914c0 3.374 1.363 6.446 3.557 8.71L14.016 53.003c-.14-.016-.28-.031-.42-.043c-.332-.046-.662-.094-1.006-.094C5.673 52.867 0 58.54 0 65.457s5.673 12.6 12.59 12.6c6.916 0 12.59-5.683 12.59-12.6c0-3.425-1.397-6.539-3.64-8.816l10.194-20.498c.5.061 1.005.101 1.52.101c1.913 0 3.721-.449 5.344-1.228l.029-.012l37.28 36.684a12.405 12.405 0 0 0-1.087 5.037c0 6.916 5.674 12.59 12.59 12.59c5.79 0 10.694-3.98 12.143-9.333c.062-.217.12-.436.172-.658l.029-.148c.136-.629.238-1.27.275-1.928a3.497 3.497 0 0 0 0-1.394c-.325-5.722-4.513-10.473-9.988-11.598l-.197-2.652l-5.422.408l.178 2.357a12.505 12.505 0 0 0-3.028 1.162L44.627 29.174a12.379 12.379 0 0 0 1.299-4.817l6.328.254l.21-5.43l-7.396-.294C43.191 14.282 38.671 11 33.418 11zm50.8 0c-6.916 0-12.59 5.673-12.59 12.59c0 3.453 1.428 6.574 3.71 8.842c1.875 1.889 4.223 2.998 6.78 3.504c.115.02.228.047.345.064l.326 4.336l5.422-.408l-.328-4.362c5.138-1.588 8.933-6.341 8.933-11.976c0-6.917-5.68-12.59-12.597-12.59zm-.02 7.023c3.106 0 5.548 2.445 5.548 5.551c0 3.107-2.442 5.549-5.549 5.549c-3.106 0-5.55-2.442-5.55-5.549c0-3.106 2.444-5.55 5.55-5.55zm-50.944.122c3.107 0 5.55 2.444 5.55 5.55c0 3.107-2.443 5.55-5.55 5.55c-3.107 0-5.549-2.443-5.549-5.55c0-3.106 2.442-5.55 5.549-5.55zm24.64 1.248l-.21 5.43l10.857.433l.213-5.43zM88.62 45.348l-5.422.408l.817 10.844l5.422-.409zM12.57 59.91c3.106 0 5.548 2.444 5.548 5.55c0 3.108-2.442 5.55-5.549 5.55c-3.106 0-5.55-2.442-5.55-5.55c0-3.106 2.444-5.55 5.55-5.55zM87.55 71c3.106 0 5.549 2.444 5.549 5.55c0 3.107-2.443 5.55-5.55 5.55c-3.106 0-5.55-2.443-5.55-5.55c0-3.106 2.444-5.55 5.55-5.55z"
                            fill="#ffffff"></path>
                    </g>
                </svg>
            </div>
        </div>
        <div id="top-info-cont">
            <div id="station-info"></div>
            <div id="wind-container">W: No Data</div>
        </div>
        <div id="atasoft-status" class="atasoft-status" title="ATC24 data connection status">
            <span class="atasoft-status__dot" data-state="connecting"></span>
            <span class="atasoft-status__label">CONNECTING</span>
        </div>
        <div style="font-size: 2em; font-weight: 100;">|</div>
        <a id="name" href="about.html">ATC24 Radar <span style="opacity:.55; font-weight:500;">by ATASOFT</span></a>
    </div>
    <div id="side-display" style="width: 0vw;">
        <div id="side-panel-content" style="display: none;">
            <div id="side-window-1" class="side-window">
                <button id="groundview-button" class="sidebar-button">Ground view</button>
                <button id="charts-button" class="sidebar-button">Charts</button>
                <button id="side-window-1-button" class="side-window-button menu-button">▲</button>
            </div>
            <div id="side-window-2" class="side-window" style="display: none">
                <button id="groundview-button" class="sidebar-button">Ground view</button>
                <button id="charts-button" class="sidebar-button">Charts</button>
                <button id="side-window-2-button" class="side-window-button menu-button">▲</button>
            </div>
        </div>
        <button id="full-wide-button" class="menu-button">▶</button>
    </div>
    <button id="display-button" class="menu-button">▶</button>
    <button id="resizer" style="width: 0px;"></button>
    <div id="svg-container" style="width: 100vw;">
    </div>
    <!--Overlays-->
    <div id="info-overlay" class="overlay">
        <svg id="close-icon" class="close-svg" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="#fd5754" />
            <line x1="13" y1="13" x2="27" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
            <line x1="27" y1="13" x2="13" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
        </svg>
        <div id="callsign-container">
            <div id="callsign-bar"></div>
            <div id="aircraft-tag"></div>
        </div>
        <div id="player-name"></div>
        <div id="route-container"></div>
        <div id="flight-plan">
            <div id="details-spacer"></div>
            <div id="fp-info"></div>
        </div>
        <div id="scratchpad-container">
            <label for="scratchpad-input">SCRATCHPAD</label>
            <input id="scratchpad-input" type="text" maxlength="14" placeholder="e.g. S250, ILS25L" autocomplete="off">
        </div>
        <div id="detailed-info-container">
            <div id="details-spacer"></div>
            <div id="detailed-info"></div>
        </div>
        <div id="flight-ops-container"></div>
        <div id="fpl-extra-container"></div>
    </div>
    <div id="settings-overlay" class="overlay">
        <!-- ATASOFT: the close button stays a DIRECT child of the overlay. The
             shared overlaySetup() handler closes `closeButton.parentElement`, so
             nesting it inside the header would close nothing (it removes .show
             from the header div instead of from the panel). -->
        <svg class="close-svg" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="#fd5754" />
            <line x1="13" y1="13" x2="27" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
            <line x1="27" y1="13" x2="13" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
        </svg>
        <div class="settings-head">
            <span class="settings-title">SETTINGS</span>
        </div>

        <!-- ATASOFT: one heading per group of related settings, with real space
             between the groups. It used to be one undifferentiated column: the
             Centre Mode box, the name field, five sizes and four unrelated
             toggles all ran together as a single list. -->
        <section class="settings-section">
            <h3 class="settings-section-title">Operations</h3>
            <div class="settings-row settings-row-stack">
                <div class="settings-row-head">
                    <label for="centre-mode-toggle">Centre Mode</label>
                    <input id="centre-mode-toggle" type="checkbox">
                </div>
                <p class="settings-note">
                    En-route mode: pick an airspace, see airborne traffic in/near it, and generate
                    temporary routes with required heading. Airspace is an approximate radius around
                    the airport (this project has no real sector boundary data) - opens the CENTRE button.
                </p>
                <div class="settings-row-head settings-row-sub">
                    <label for="centre-debug-toggle">Debug (console)</label>
                    <input id="centre-debug-toggle" type="checkbox">
                </div>
            </div>
            <div class="settings-row settings-row-stack">
                <div class="settings-row-head">
                    <label for="fpl-callsign-on-radar-toggle">FPL Callsign on Radar</label>
                    <input id="fpl-callsign-on-radar-toggle" type="checkbox" checked>
                </div>
                <p class="settings-note">
                    On: the radar tag shows the pilot's filed callsign (e.g. Delta-39W).
                    Off: shows the ICAO code instead (e.g. DAL3454). The ICAO code is
                    always shown in the aircraft panel either way.
                </p>
            </div>
            <!-- ATASOFT: flight-plan amendment rights. Amending a flight (FL, RWY,
                 SID, STAND, RMK, cleared) is a controller action, so the radar asks
                 the online ATC list whether this name holds a position for the
                 flight's airport before it lets anyone type. The squawk is not an
                 amendment - it is always editable. See src/flightBoard.js. -->
            <div class="settings-row settings-row-stack">
                <div class="settings-row-head">
                    <label for="atasoft-atc-name">My ATC24 Name</label>
                    <span id="atasoft-claim-status" class="atasoft-claim-status">not set</span>
                </div>
                <input id="atasoft-atc-name" class="settings-text" type="text" maxlength="24" autocomplete="off"
                    spellcheck="false" placeholder="e.g. SaabirAlam">
                <p class="settings-note">
                    Used to check your controlling position against the online ATC list (it is also shown as
                    "set by" on the shared board). Without a position for a flight's airport, its FL / RWY /
                    SID / STAND / RMK / cleared cells are read-only - the squawk can always be changed.
                </p>
            </div>
        </section>

        <section class="settings-section">
            <h3 class="settings-section-title">Sizes</h3>
            <div class="settings-row">
                <label for="font-size-input">Label Font Size (Radar)</label>
                <input id="font-size-input" class="settings-input" type="number" value="100">
            </div>
            <div class="settings-row">
                <label for="font-size-input-ground">Label Font Size (Ground)</label>
                <input id="font-size-input-ground" class="settings-input" type="number" value="120">
            </div>
            <div class="settings-row"
                title="Aircraft on the ground only get their data block when the scope is showing less than this many nautical miles across (0 = always).">
                <label for="ground-nm-input">Ground Labels Within (NM)</label>
                <input id="ground-nm-input" class="settings-input" type="number" min="0" value="5">
            </div>
            <div class="settings-row">
                <!-- ATASOFT: 80 -> 100. The fix names on the scope were the
                     smallest lettering on the map; this is their default size
                     now (an existing install keeps whatever it already saved).
                     drawFixes() also gives each name a dark halo for contrast
                     over land/coastline. -->
                <label for="fix-font-size">Fixes Font Size</label>
                <input id="fix-font-size" class="settings-input" type="number" value="100">
            </div>
            <div class="settings-row">
                <label for="fix-size">Fixes Size</label>
                <input id="fix-size" class="settings-input" type="number" value="120">
            </div>
        </section>

        <section class="settings-section">
            <h3 class="settings-section-title">Graphics</h3>
            <div class="settings-row">
                <label for="bounds-check">Airspace Bounds</label>
                <input id="bounds-check" type="checkbox" checked>
            </div>
            <div class="settings-row">
                <label for="min-tool">Minimalistic Toolbar</label>
                <input id="min-tool" type="checkbox">
            </div>
            <div class="settings-row">
                <label for="fix-button">Draw Fixes</label>
                <input id="fix-button" type="checkbox">
            </div>
        </section>

        <section class="settings-section">
            <h3 class="settings-section-title">Radar Aids</h3>
            <div class="settings-row">
                <label for="speed-vector-toggle">Predictive Speed Vector</label>
                <input id="speed-vector-toggle" type="checkbox" checked>
            </div>
            <div class="settings-row">
                <label for="speed-vector-minutes">Vector Length</label>
                <select id="speed-vector-minutes">
                    <option value="1">1 min</option>
                    <option value="2" selected>2 min</option>
                    <option value="3">3 min</option>
                </select>
            </div>
            <div class="settings-row">
                <label for="wind-arrow-toggle">Wind Arrow (selected airport)</label>
                <input id="wind-arrow-toggle" type="checkbox" checked>
            </div>
        </section>

        <section class="settings-section">
            <h3 class="settings-section-title">Alerts &amp; Audio</h3>
            <div class="settings-row">
                <label for="voice-alerts-toggle">Voice Alerts (Emergency &amp; Traffic)</label>
                <input id="voice-alerts-toggle" type="checkbox" checked>
            </div>
        </section>

        <!-- ATASOFT: ATC Sentence Helper. This is a NEW setting and it is OFF
             by default, so every existing install behaves exactly as before
             until it is switched on. On: the selected aircraft's panel gains a
             role-filtered set of ready-made phrases, pre-filled from that
             aircraft's real callsign/type/position, its filed plan, the
             published ATIS and this build's airport records. Nothing is ever
             sent anywhere - a phrase is copied to the clipboard and the panel
             says so. See src/atcPhrases.js and src/sentenceHelper.js. -->
        <section class="settings-section">
            <h3 class="settings-section-title">ATC Helper</h3>
            <div class="settings-row">
                <label for="sentence-helper-toggle">Sentence Helper (aircraft panel)</label>
                <input id="sentence-helper-toggle" type="checkbox">
            </div>
            <p class="settings-note">
                Ready-made phrases for the selected aircraft, filtered by the position the online ATC
                list reports for you (Ground / Tower / Center / Approach / Delivery) and pre-filled from
                the live feed and the filed flight plan. Values this build does not have stay visibly
                missing instead of being guessed. Copying a phrase only copies it - nothing is sent.
            </p>
            <div class="settings-row settings-row-link">
                <a class="about-link" href="flight-planner.php">Open the Flight Planner &rarr;</a>
            </div>
        </section>
    </div>
    <div id="notepad-overlay" class="overlay">
        <svg class="close-svg" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="#fd5754" />
            <line x1="13" y1="13" x2="27" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
            <line x1="27" y1="13" x2="13" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
        </svg>
        Notepad
        <hr>
        </hr>
        <textarea type="text" id="notepad-input" placeholder="Type here..."></textarea>
    </div>
    <div id="departures-overlay" class="overlay">
        <svg class="close-svg" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="#fd5754" />
            <line x1="13" y1="13" x2="27" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
            <line x1="27" y1="13" x2="13" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
        </svg>
        Departures
        <table id="departures-table" border="1" style="border-collapse: collapse; width: 100%; text-align: left;"
            autocomplete="off">
            <!-- Column widths live in public/style.css, not inline here: at
                 phone width the stylesheet replaces them with the pixel
                 width each column needs (see the 760 px block at the end). -->
            <colgroup>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
            </colgroup>
            <thead>
                <tr>
                    <th>CALLSIGN</th>
                    <th>TYPE</th>
                    <th>ARR</th>
                    <th>FR</th>
                    <th>RWY</th>
                    <th>SID</th>
                    <th>FL</th>
                    <th>SQK</th>
                    <th>C</th>
                    <th>RMK</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <div id="arrivals-overlay" class="overlay">
        <svg class="close-svg" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="#fd5754" />
            <line x1="13" y1="13" x2="27" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
            <line x1="27" y1="13" x2="13" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
        </svg>
        Arrivals
        <table id="arrivals-table" border="1" style="border-collapse: collapse; width: 100%; text-align: left;">
            <!-- Column widths: see the note on the departures colgroup above. -->
            <colgroup>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
            </colgroup>
            <thead>
                <tr>
                    <th>CALLSIGN</th>
                    <th>TYPE</th>
                    <th>FROM</th>
                    <th>RWY</th>
                    <th>FL</th>
                    <th>SQK</th>
                    <th>STAND</th>
                    <th>RMK</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <div id="atc-overlay" class="overlay">
        <svg class="close-svg" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="#fd5754" />
            <line x1="13" y1="13" x2="27" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
            <line x1="27" y1="13" x2="13" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
        </svg>
        <div class="atc-overlay-header" style="display:flex; align-items:center; justify-content:space-between; padding-right:20px;">
            <span style="font-weight:600; font-size:1.05em; color:var(--accent-2);">📡 Online ATC Positions</span>
            <span id="atc-online-count" style="font-size:0.8em; color:var(--text-dim);">(0 Active)</span>
        </div>
        <hr style="margin-block:6px; border:none; border-top:1px solid var(--border);" />
        <div id="atc-list-container" class="atc-list-container">
            <div style="opacity:0.6; font-size:0.85em; padding:8px;">Loading...</div>
        </div>
    </div>
    <div id="settings-button" class="settings-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" class="bi bi-gear-fill"
            viewBox="0 0 16 16">
            <path
                d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z" />
        </svg>
    </div>
    <div id="atis-overlay" class="overlay">
        <svg class="close-svg" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="#fd5754" />
            <line x1="13" y1="13" x2="27" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
            <line x1="27" y1="13" x2="13" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
        </svg>
        <div class="left">
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <div style="font-size: 1.2em; font-weight: bold; margin-bottom: 1px;">ATIS Generator</div>
                <label>QNH: <input type="number" id="qnh" value="1013"></label>
            </div>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label>Arrival Runway(s): <select class="atis-dropdown" multiple="multiple"
                        id="arrRwy"></select></label>
                <label>Departure Runway(s): <select class="atis-dropdown" multiple="multiple"
                        id="depRwy"></select></label>
            </div>
            <div style="display: flex; flex-direction: column; justify-content: center;">
                <button id="atis-gen-button">Copy ATIS Command</button>
            </div>
            <div style="display: flex; flex-direction: column; gap:2px">
                <h4>Charts</h4>
                <select id="charts" class="atis-dropdown">
                    <option value="N/A">N/A</option>
                    <option value="Offical">Offical</option>
                </select>
                <label id="copied-text"></label>
            </div>
        </div>
        <div class="right">
            <div id="output"></div>
            <div id="atis-published-wrap">
                <div id="atis-published-header">
                    <span class="atis-pub-title">PUBLISHED ATIS</span>
                    <span id="atis-published-status">--</span>
                    <button id="atis-published-refresh" type="button" title="Refresh the published ATIS list now">&#x21bb;</button>
                </div>
                <div style="font-size:.55em; opacity:.6; margin-bottom:4px;">
                    Live reports from the network, read-only. USE loads the published runways and QNH into the generator above.
                </div>
                <div id="atis-published-list">
                    <div class="atis-pub-empty">Loading published ATIS...</div>
                </div>
            </div>
        </div>
    </div>
    <div id="approach-overlay" class="overlay">
        <svg class="close-svg" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="#fd5754" />
            <line x1="13" y1="13" x2="27" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
            <line x1="27" y1="13" x2="13" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
        </svg>
        Approach Selector
        <select id="airport-dropdown-app" name="airport">
            <option value="IBLT">IBLT</option>
            <option value="IBTH">★IBTH</option>
            <option value="IDCS"> IDCS</option>
            <option value="IGAR">IGAR</option>
            <option value="IGRV">IGRV</option>
            <option value="IHEN">IHEN</option>
            <option value="IIAB">IIAB</option>
            <option value="IJAF">IJAF</option>
            <option value="ILAR">★ILAR</option>
            <option value="ILKL">ILKL</option>
            <option value="IMLR">IMLR</option>
            <option value="IPAP">IPAP</option>
            <option value="IPPH">★IPPH</option>
            <option value="IRFD" selected>★IRFD</option>
            <option value="ISAU">ISAU</option>
            <option value="ISCM">ISCM</option>
            <option value="ISKP">ISKP</option>
            <option value="ITKO">★ITKO</option>
            <option value="ITRC">ITRC</option>
            <option value="IZOL">★IZOL</option>
        </select>
        <div id="approach-toggles">
        </div>
        <hr>
        </hr>
        <div style="font-size: .8em; margin-block: 5px;">Extended Centerline &amp; ILS Cone</div>
        <div id="centerline-toggles" style="font-size: .7em;">
        </div>
        <div style="font-size: .6em; margin-top: 6px; display: flex; align-items: center; gap: 4px;">
            <input id="runway-aid-calibration" type="checkbox">
            <label for="runway-aid-calibration">Calibration mode (click a threshold on the map)</label>
        </div>
    </div>
    <div id="strips-overlay" class="overlay">
        <svg class="close-svg" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="#fd5754" />
            <line x1="13" y1="13" x2="27" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
            <line x1="27" y1="13" x2="13" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
        </svg>
        <div style="font-weight:700; font-size:1.1em; margin-bottom: 4px;">Flight Strip Board</div>
        <div style="font-size: .65em; opacity: .7; margin-bottom: 8px;">
            Departures/arrivals for your worked stations. Drag a strip to reorder; click a strip to select the aircraft.
        </div>
        <div class="strips-section-title">DEPARTURES</div>
        <div id="strips-departures-body" class="strips-body"></div>
        <div class="strips-section-title" style="margin-top: 10px;">ARRIVALS</div>
        <div id="strips-arrivals-body" class="strips-body"></div>
    </div>
    <div id="centre-overlay" class="overlay">
        <svg class="close-svg" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="#fd5754" />
            <line x1="13" y1="13" x2="27" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
            <line x1="27" y1="13" x2="13" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
        </svg>
        <div style="font-weight:700; font-size:1.1em; color:var(--accent); margin-bottom: 6px;">Centre Mode</div>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; font-size:.8em; margin-bottom:6px;">
            <label for="centre-airspace-select">Airspace:</label>
            <select id="centre-airspace-select"></select>
            <label for="centre-radius-input">Radius (NM):</label>
            <input id="centre-radius-input" type="number" min="10" max="150" step="5" style="width:56px;">
            <label for="centre-auto-route-toggle" style="opacity:.85;" title="When off, selecting an aircraft does not auto-build a route - you build it yourself.">Auto-route</label>
            <input id="centre-auto-route-toggle" type="checkbox">
            <button id="centre-regenerate-route" class="menu-button" style="font-size:.85em;">Regenerate Selected Route</button>
            <button id="centre-emergency-route" class="menu-button" style="font-size:.85em; border-color:#ff5f6d; color:#ff8f98;" title="Divert the selected aircraft to the nearest suitable airport">Emergency Divert</button>
        </div>
        <div style="font-size:.6em; opacity:.7; margin-bottom:8px;">
            Radius is an approximation (no real sector boundary data exists) - dashed ring on the map.
            The lighter outer ring is the surrounding "transit" buffer.
        </div>
        <div style="font-weight:700; font-size:.8em; margin-bottom:4px;">AIRCRAFTS IN AIRSPACE</div>
        <div id="centre-airspace-list" class="strips-body"></div>
    </div>
    <div id="vector-overlay" class="overlay">
        <svg class="close-svg" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="#fd5754" />
            <line x1="13" y1="13" x2="27" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
            <line x1="27" y1="13" x2="13" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
        </svg>
        Vectoring Tool
        <div id="vectoring-toggles">
            <div style="display: flex; width: 100%; gap: 10px; margin-bottom: 5px; align-items: center;">
                <button id="vector-start" class="vector-button">Start</button>
                <button id="vector-clear" class="vector-button">Clear</button>
            </div>
            <div style="display: flex; align-items: center;">
                <label for="vector-color" style="font-size: .8em; margin-right: auto">Color</label><input type="color"
                    id="vector-color" value="#008000" />
            </div>
            <div style="display: flex; align-items: center; margin-top: 5px;">
                <label for="vector-distance" style="font-size: .8em; margin-right: auto">Show distance</label><input
                    type="checkbox" id="vector-distance" checked />
            </div>
            <div style="display: flex; align-items: center; margin-top: 5px;">
                <label for="vector-direction-only" style="font-size: .8em; margin-right: auto">Show direction
                    only</label><input type="checkbox" id="vector-direction-only" />
            </div>
            <hr style="height: 1px; border: none; background-color: rgb(116, 116, 116);">
            </hr>
            <div style="display: flex; width: 100%; gap: 10px; margin-block: 5px; align-items: center;">
                <button id="vector-export" class="vector-button" style="font-size: .6em; height: 20px;">Export</button>
                <button id="vector-import" class="vector-button" style="font-size: .6em; height: 20px;">Import</button>
            </div>
        </div>
    </div>
    <div id="station-overlay" class="overlay">
        <svg class="close-svg" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="#fd5754" />
            <line x1="13" y1="13" x2="27" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
            <line x1="27" y1="13" x2="13" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
        </svg>
        Station Settings
        <div style="font-size: .8em;">Topdown</div>
        <div id="station-toggles"></div>
    </div>
    <div id="changes-overlay" class="overlay show">
        <svg class="close-svg" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="#fd5754" />
            <line x1="13" y1="13" x2="27" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
            <line x1="27" y1="13" x2="13" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
        </svg>
        <div class="changes-header" style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
            <div style="font-weight:600;">⚙️ ATC24 Radar by ATASOFT ⚙️</div>
            <label style="font-size:.85em; display:flex; align-items:center; gap:6px; white-space:nowrap;">
            </label>
        </div>
        <hr>
        </hr>
        <div style="font-size: .7em;">
            <h3>✨ What's new</h3>
            <p>• Brand new ATASOFT dark UI (purple / light-blue theme)</p>
            <p>• Redesigned top bar, panels and connection indicator</p>
            <p>• Live data connection status (top bar, right side)</p>
            <p>• Deployment made configurable — no more hardcoded server address</p>
            <h3>ℹ️ About this build</h3>
            <p>• This is a community client, not affiliated with Roblox, PTFS or ATC24.</p>
            <p style="color: var(--text-dim); font-size: .85em;"><a style="color: var(--text-dim);"
                    href="about.html">Full credits, license &amp; attribution</a>
            </p>
        </div>
    </div>
    <div id="dowtime-overlay" class="overlay">
        <svg class="close-svg" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="#fd5754" />
            <line x1="13" y1="13" x2="27" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
            <line x1="27" y1="13" x2="13" y2="27" stroke="#80060d" stroke-width="3" stroke-linecap="round" />
        </svg>
        ❗ Notice ❗
        <hr>
        </hr>
        <div style="font-size: .7em;">
            <h3>Maintenance</h3>
            <p>• This panel is reserved for maintenance / downtime notices.</p>
            <p>• Nothing scheduled right now.</p>
        </div>
    </div>
    <a id="credits" href="about.html">Credits &amp; License · ATASOFT</a>

    <!-- ATASOFT: "All Airports" overview - a self-contained screen, separate
         from the single-airport dropdown/ATIS/runway/departures-arrivals
         logic, so this never touches any of that. Shows every airport's
         ground diagram muted/at-a-glance, with its approach charts one tap
         away. -->
    <div id="all-airports-overlay" class="all-airports-overlay">
        <div class="all-airports-header">
            <span>All Airports — Ground &amp; Approach Charts</span>
            <span id="all-airports-close" class="all-airports-x">✕</span>
        </div>
        <div id="all-airports-grid" class="all-airports-grid"></div>
    </div>
    <div id="all-airports-lightbox" class="all-airports-lightbox">
        <span id="all-airports-lightbox-close" class="all-airports-x">✕</span>
        <div id="all-airports-lightbox-title" class="all-airports-lightbox-title"></div>
        <img id="all-airports-lightbox-img" src="" alt="">
    </div>

</body>

</html>