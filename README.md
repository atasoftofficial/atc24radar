# ATC24 Radar — by ATASOFT

A free, non-commercial community radar client for **Roblox Pilot Training
Flight Simulator (PTFS) / ATC24**. Not affiliated with Roblox, PTFS/ATC24,
or any airline whose livery/callsign data appears in the app.

Target deployment: `https://atc24radar.atasoft.xyz`

This is a redesign of the open-source **[atc24radar](https://github.com/t-arpin/atc24radar)**
project by **t-arpin** (GPL-3.0), which is the functional base of this build.
See [`ATTRIBUTION.md`](./ATTRIBUTION.md) for full credits and license detail,
and [`FEATURE_MATRIX.md`](./FEATURE_MATRIX.md) for exactly what came from
where. **24RC** by Autumnhd was also reviewed during research but its code is
not part of this build.

## What this build changes vs. upstream

- **Critical data-source bugfix**: the backend was polling
  `https://24data.ptfs.app/acft-data/event`. Per ATC24's official 24data API
  documentation, that endpoint returns traffic for a **separate, occasional
  event server**, not the main ATC24 server most pilots/controllers use.
  This is almost certainly why the radar showed little or no traffic under
  normal conditions. Fixed to poll `https://24data.ptfs.app/acft-data` (the
  main server), with the matching WebSocket flight-plan filter switched from
  `EVENT_FLIGHT_PLAN` to `FLIGHT_PLAN` to match.
- Fixed a related latent bug in the ATIS cache: it compared a value to
  itself *after* already overwriting it (so a branch that referenced an
  undefined `atisFullData` variable could never actually run). Simplified to
  what's actually used downstream - the current ATIS letter per airport.
- Confirmed correct against the official docs: `GET https://24data.ptfs.app/atis`
  (no per-airport path upstream - it returns all airports at once and is
  cached locally), and the existing 3-second aircraft-poll /
  6-second ATIS-poll intervals, which match ATC24's documented recommended
  rates exactly.
- Full ATASOFT visual redesign (dark, purple/light-blue theme) — every
  existing element ID/class the JS relies on was preserved, only appearance
  changed.
- Fixed: the frontend was hardcoded to the upstream author's own production
  server (`api.24radar.xyz`) instead of whatever backend is actually
  deployed alongside it. Now same-origin by default, configurable via
  `window.ATASOFT_API_HOST` in `index.html`.
- Fixed: `/approaches/:icao` on the backend pointed at a folder
  (`src/assets/maps/...`) that never existed, so it 404'd unconditionally.
  It now correctly resolves to `public/assets/maps/...`.
- Fixed: an unauthorized-origin request crashed with `ENOENT` because it
  tried to send a `403.html` file that isn't in the repository. Replaced
  with an inline response.
- Fixed: CORS/origin allowlist was hardcoded to the upstream author's own
  GitHub Pages domain and personal IP. Now configurable via `ALLOWED_ORIGINS`
  (see `.env.example`).
- Added: a real `package.json` (the upstream repo has none - dependencies
  had to be reverse-engineered from its `require()` calls).
- Added: a live connection/data-freshness indicator in the top bar (`●
  LIVE` / `RECONNECTING` / `STALE`), driven by the actual polling loop -
  not a fake always-on indicator.
- Added: flight trails now persist for the **entire flight** (takeoff to
  landing) instead of being capped at the last 15 points, and are
  color-coded by altitude (amber near the ground → ATASOFT purple at
  cruise). The trail clears the moment the aircraft lands.
- Added: an altitude-history sparkline in the aircraft inspection panel,
  built from the same per-flight trail data.
- Added: a clear "selected aircraft" icon highlight, and a pinned-aircraft
  icon ring (in addition to the existing ★ label indicator).
- Aircraft icon default color changed from a hardcoded red dot to a
  near-white radar-target color for readability against the new theme.
- Added: the Departures/Arrivals boards now also show **filed** flights, from
  AWDEV's per-airport lists (`/dep/{ICAO}`, `/arr/{ICAO}`, proxied by
  `airport-flights.php`) - `src/airportFlights.js`. They used to be built only
  from aircraft already in the live feed, so a flight had no strip until it
  was airborne. A filed row is drawn dimmer/italic, and disappears as soon as
  the live feed carries that same callsign (the live row wins), so nothing is
  listed twice.
- Added: a **shared flight board** for controller-entered data -
  `flightboard.php` (one JSON file + file lock, `cache/flightboard.json`) with
  `src/flightBoard.js` on the client. The DEP and ARR boards each gained an
  editable **FL** and **SQK** column, plus the cleared tick, RWY, SID, STAND
  and RMK cells, all keyed by the flight's callsign. So a squawk the departure
  controller issues, or a level they re-clear, is read straight back by the
  arrival controller (and vice versa) - the two positions no longer have to
  see different values for the same flight. Edits are optimistic (typing never
  waits for the network), pushed on change (never per keystroke), retried if a
  write fails, and fall back to per-browser storage - labelled `LOCAL ONLY` in
  the aircraft panel - if the endpoint is unreachable. The endpoint is not
  authenticated: it is a public community radar, and it only ever stores
  operator annotation for a callsign (never the feed, never another user's
  data), with length-limited fields and 12-hour retention.
- Added: a **FROM** column on the arrivals board, written automatically from
  each flight's filed plan when the system knows where it departed from (its
  origin) - at a busy field the arrival controller should not have to type
  information the plan already carries. Empty (with the reason in the tooltip)
  when the flight has no plan yet; never guessed. Fields the system can read -
  type, destination (departures), origin (arrivals) and the filed level - are
  likewise filled in automatically.
- Added: a **level check** on both boards. The FL cell turns green when the
  aircraft's live altitude is on the level written in that cell (within +/-200
  ft, i.e. FL050 = 5000 ft covers 4800-5200), and its tooltip states the live
  altitude, the level in feet and whether that level is the controller's
  cleared level or the pilot's filed one. A flight that is clearly not on its
  level is dimmed rather than marked. Filed-only rows are never marked - there
  is no altitude to compare.
- Added: the **ATIS board** (`atis.php`, `src/atisBoard.js`,
  `public/style-atis.css`). The same URL that has always served the generator's
  ATIS letter (`atis.php?icao=IRFD`, unchanged) is now also a page: every
  published report at once, searchable by 4-letter airport code while typing,
  refreshed once a second without a page reload (or paused, which then says how
  old the list on screen is). The report text is upstream's own, verbatim, with
  the dep/arr runway list, wind, QNH and transition level shown beside it as
  labelled chips - parsed by `parsePublishedAtis()` from `src/atisPanel.js`, the
  same parser the radar's ATIS window uses, so the board and the generator can
  never disagree about what a report says. The list comes from
  `atis-list.php?max_age=1` (the existing endpoint, its cache shortened from
  10 s to the one second the page promises; the value is clamped to 1-60 s and
  one cache file still serves every caller), so no new upstream, endpoint or
  second copy of the data is added. Linked from the planner's and the follow
  page's own nav.
- Fixed: **the required heading on the follow page, and the waypoint it points
  at**. `flightPlanCore.bearingDeg()` computed `atan2(dx, dy)` while every other
  bearing in this build - the radar's own heading tool, the measuring line,
  Centre mode - uses `atan2(dx, -dy)`: y grows *downward* on the map, so north
  is -y, and reading it the other way mirrored every heading about the
  east-west axis. On a live IRFD -> KNIFE leg the HUD said **173°** where the
  waypoint lay at **007°**, so following it flew the flight away from its
  waypoint and back - the reported "it zig-zags instead of pointing straight at
  the waypoint". The same fault mislabelled traffic as "ahead" when it was
  behind. Waypoint progression was rebuilt as well: it now asks which route
  **leg** the aircraft is on (the segment it is closest to) and points at the
  waypoint where that leg ends, which is the only test that survives a cut
  corner, a route that doubles back on itself (this network files local VFR
  circuits), a leg shorter than the waypoint radius, or a cursor left behind by
  a reload - the old distance/perpendicular tests either skipped a waypoint the
  aircraft had not reached or stayed on one it had already flown.
  `tools/verify-flight-planner.mjs` pins the bearing convention against
  `main.js`/`centre.js` and covers each of those sequencing cases.
- Added: a read-only **published ATIS panel** (`src/atisPanel.js`, fed by the
  existing `atis-list.php` -> `ws.awdevsoftware.org/atis`). It lists every
  published report verbatim - airport, information letter, publishing
  controller and the full text - with the fields currently being worked pinned
  to the top, and is refreshed on the same polite cadence Centre Mode already
  used. `USE` loads that report's dep/arr runways and QNH into the existing ATIS
  generator, so the published report and the one being written cannot drift
  apart.

- Fixed: the per-airport **ATC position buttons** were all drawn in one place a
  few units from the map origin, instead of on their own fields.
  `GroundOffsets.js`'s x/y are world map units - the same numbers `centre.js`
  and `emergency.js` place a field with - and the label code was dividing them
  by 100. They now sit at each airport (a few screen pixels below/right of it,
  so the pavement is never covered), and a button that would land on top of
  another one is pushed down a line, so two fields close together at a wide
  zoom can no longer print over each other.
- Fixed: the **top bar at narrow window sizes**. It is a fixed-height row of
  `nowrap` buttons, so on a window narrower than roughly 1500 px the row was
  wider than the panel: the trailing items were painted outside it, and every
  button was squeezed below the width of its own label, so the labels printed
  over the buttons next to them ("GROUND AIRCRAFT" needed 76 px and was being
  given 48). The bar now wraps onto as many lines as it needs, buttons are
  sized by their text, and the controls get tighter as the window narrows.
  Nothing functional is hidden. Measured at 13 window widths from 480 to
  2200 px: before, every one of them had either items outside the bar or
  labels wider than their boxes; now none of them do.
- Fixed: **the DEP/ARR boards, which squeezed their own text off the screen**.
  Both boards are a fixed set of columns - ten on the departures board - and
  their widths were *percentages* of the panel, so the narrower the window the
  narrower every cell became: in a 360 px window the FL cell was 15 px wide for
  `FL340` and the callsign cell 27 px for `Shuttle 96 Heavy`, and even at
  1280 px the last character of a level was already being cut. Each cell's own
  input clips its text, which is what reads as "the writing runs out of its
  box". Every column now asks for a **pixel** width, the table never goes
  below the sum of them, and each panel is wide enough for its own board
  (750 px / 640 px) and capped to the viewport with a sideways scroll for
  smaller windows - so no column is ever narrower than the text in it, on any
  screen. The two widest texts any board shows (the flight's name and an
  aircraft model, up to 117 px and 82 px in the live feed) size those two
  columns. Measured in a browser at 380, 560, 900, 1280 and 1920 px: 0 clipped
  board cells at every one of them (before the fix: 20+ clipped inputs at
  380 px, 12 at 1280 px), 0 panels off screen, no page-level overflow.
  `tools/verify-board-columns.mjs` fails if a percentage column comes back.
- Added: **stable Centre Mode routes**. A flight's arrival runway is resolved
  once and then LOCKED to that flight. Every cycle still scores every
  published runway - the alternatives are still being watched, and they are
  kept on the route for the tools and for the record - but the flight keeps
  the runway it has unless the runway stops being offered at all (a real
  change: the published ATIS arrival runway changed, or the operator changed
  the panel), or another runway beats the held one by a wide margin for at
  least 20 seconds. The panel shows the chosen runway and why; the discarded
  alternatives are no longer listed for the controller (`not chosen: ...` is
  gone from the text), and the alternatives themselves stay off the screen.
  `Regenerate` is the one action that drops the lock and chooses again.
  Measured in `tools/verify-runway-lock.mjs`: at a position where the same
  scoring flips 6 times in 10 noisy probes, the locked route is rebuilt 0
  times over 25 jittery cycles, where the old lock-free path was rebuilt 7
  times.
- Added: **flight-plan amendments need a controlling position** - the system
  many other radar clients have. Amending a flight (FL, RWY, SID, STAND, RMK,
  the cleared tick) is a controller action on a board shared with every other
  controller working that flight, so it now requires a position at one of the
  flight's own airports - or a CTR position, which covers the field - on the
  same online-controller feed the ATC buttons come from. The controller's
  ATC24 name is set once in Settings; with no name, no position, or a position
  at some other field, those cells are read-only and say exactly why (in the
  cell's tooltip and in the panel). The **SQUAWK stays editable for everyone**:
  it is read off the flight and passed on rather than an amendment. The same
  rule is enforced server-side in `flightboard.php`, so a hand-made POST cannot
  skip it, and the client treats a refusal as a decision (it is not retried
  forever). No name, no position, or a failed check all mean "not confirmed",
  which is the read-only side - nothing is assumed in the controller's favour.

- Changed: **the scope's aircraft symbols are now the real top view of each
  aircraft**. The previous build drew one hand-made planform per category
  (helicopter, light single, light twin, turboprop, rear-jet, heavy, military
  jet and a generic airliner), and at 13-16 px those invented shapes read as a
  fish with wings rather than as an aeroplane. `src/data/AircraftShapes.js` is
  now generated from **63 real single-piece SVG drawings**, one per ICAO type -
  a 737-800 as a 737-800, a Cessna 172 with its high wing and nose propeller, a
  Chinook with two tandem rotors, an A380 with its full-span wing. They live in
  `public/assets/aircraft-shapes/` (shipped with the app, so there is no fetch
  to fail at display time) and `tools/build-aircraft-shapes.mjs` downloads
  them, measures each drawing and bakes the geometry into the module. The
  mapping covers all 101 types the feed can report: model first, then the
  `AircraftIconMap` category, then the default `B738` - so no type is ever left
  without a symbol. Each drawing keeps its own real wingspan in metres, mapped
  onto the 7.2-12.2 map-unit range the scope shows, so a Cessna and a 747 are
  drawn to their real relative size and the data-block offset follows the same
  span. The rotary-wing drawings carry an animated rotor disc and blade pair on
  the measured hub, so a helicopter's rotor visibly turns.
  `tools/verify-aircraft-glyphs.mjs` rasterises every path under the same
  non-zero fill rule the browser uses, rebuilds the module in check mode, and
  walks all 101 feed types; it fails if a type resolves to no drawing, if a
  drawing is not nose-up or not centred, if a wing is missing at its declared
  span, or if the module no longer matches the artwork on disk. `--ascii`
  prints every drawing to the terminal.
- Fixed: **a data block scaled with the map instead of staying with its
  aircraft**. A radar label is sized and placed in map units, but after the
  aircraft appeared it was only re-derived on the next data cycle (and by a
  separate document-level wheel listener), so scrolling in left the text at the
  world size it had at that moment and the label then grew with the map: a
  zoomed-in scope showed a ~30 px callsign next to a normal-sized symbol, and
  zooming back out never shrank it again, because the offset was left in world
  units too and walked the label away from its own aircraft. The radar wheel
  handler now re-renders every visible data block on the same wheel step
  (`updateLabel` re-derives the font size, the offset and the connector from
  `currentZoom`), so the text and its symbol keep a constant on-screen size and
  a constant gap at every zoom level. A label the user dragged by hand keeps the
  position they gave it. The symbol itself is unchanged from upstream: it
  counter-scales on every zoom step and is the same number of pixels at every
  zoom level (the scope is not a scale model). Measured on the running scope:
  30 paced zoom-ins to 23x and 30 back out left the text, the symbol and the gap
  at a constant on-screen size throughout.
- Fixed: **a data block's size and its place are screen pixels, not map units**.
  The size was a map-unit figure, which is not a unit of legibility: the scope SVG
  is scaled to the panel, so the same setting rendered at under 7 px on a narrow
  window and at 20+ px on a wide one, and it changed with every zoom step. A tag
  is now measured in the pixels it is actually read at - 12 px by default, the
  Settings field shows that x10 (so 120), held inside a 9-16 px band. Its offset
  is fixed in pixels too (10 px right of the aircraft and 4 px down, with the
  per-class floor kept so a wide heavy never gets a tag on its wing tip): a
  world-unit offset walked the block away from its own aircraft as the scope was
  zoomed in, which is the "the tag scatters when I zoom in" report - the text
  stayed 12 px while the gap grew until the block was nowhere near the target.
  The ground view's blocks work the same way (12 px x its own size slider).
  Three follow-on defects in that same code are fixed with it, all measured on
  the running scope: (1) the three lines of a block are **left-aligned by the
  renderer's own pen advance** - the old code shifted each line by the previous
  line's measured ink width (plus a flat 6 px), and ink is a few px wider than
  the advance, so every line sat further left than the one above it (the "the tag
  falls apart" report: `QFA2044` at one x, `391ft ...` ~9 px left of it, `85°
  A333` ~18 px left); measured line-lefts are now identical at 2x, 4x, 22x and
  back out. (2) **which side of its symbol a block goes on is decided per
  aircraft**, from where the block would land on screen (right of the symbol by
  default, flipped left when it would run off the right-hand edge), and stored
  on the label; upstream kept it in one `textAlign` variable shared by the whole
  scope, so one block flipping over flipped every later one, and an `end` block
  is drawn straight across its own symbol. (3) **the airline-logo badge hides
  with its block** (it is a separate element and used to stay on the scope on its
  own: a white airline box beside an aircraft with no data block), sits a fixed
  2 px above the callsign, and can be grabbed to drag the tag and double-clicked
  to open the aircraft - it used to be `pointer-events: none`, which reads as
  "clicking the tag does nothing". A logo that 403s is remembered and not
  re-requested (it was refetched on every cycle and every wheel step). And every
  block is re-derived whenever the scope's scale changes, not only on a wheel
  step: **a size is never derived from a scope that has no measurable scale**
  (a collapsed side panel gives a zero-wide SVG - the old fallback of `1`
  produced a "12 map unit" tag, i.e. one ~20x too big, until the next data
  cycle), and a `ResizeObserver` on the map container re-derives every block when
  the app opens or closes its own side panel (no window resize event fires for
  that). `tools/verify-label-geometry.mjs` pins all of it down statically.
- On-ground traffic now gets its data block back at 2x zoom instead of 10x
  (`LABEL_GROUND_HIDE_ZOOM`): upstream hid those blocks until the scope was about
  10x in, which is deeper than anyone zooms to work an airport, so taxiing
  aircraft simply had no callsign on the scope.
- The heading tool's `HDG ...` readout is 12 px on screen (was ~6 px) with a bigger
  target dot. Startup writes the current size into the Settings field and that
  field persists, so a stored figure that equals a size this build shipped as its
  own default (10, 12, 14, 18) - or that falls outside the band, i.e. a leftover
  of the old map-unit scheme - is ignored in favour of the default.
- Added: **a rim that stays sharp, and real detail on the symbols**. The rim is
  a screen-space hairline (`vector-effect: non-scaling-stroke`, 1.05 px) instead
  of 0.6 map units, which was sub-pixel - no edge at all - on a wide view and a
  thick grey band when zoomed in; the data blocks use a 0.15em halo, precise
  glyph outlines and a small letter gap, so the callsign digits stay crisp. The
  detail that identifies a type (winglets, engine nacelles, wheel fairings,
  rotors) is part of each real drawing now rather than added to a generic shape.
  The scope also gets a
  centre reticle (four short arms with a gap in the middle, under the traffic)
  and one grade more contrast on the 10 NM ring (the ring distances are read
  against).
- Fixed: **the radar map read as a flat grey smear**. The coast artwork, the
  airspace boundaries and the range rings were all drawn in #333333 or #212121
  on a #060814 background - land, sea and boundaries within a couple of percent
  of each other and of the page - so the landmass had no shape, the coastline
  had no line (the coast paths are drawn with `stroke:none`) and the boundaries
  were invisible whenever they were switched on. The whole map palette is now
  decided in one block in `public/style.css`: a darker sea (`--map-sea`) with a
  cold glow at the centre of interest and a vignette at the edge, a bluer land
  (`--map-land`) with a lit coastline (`--map-land-edge`) drawn as a
  non-scaling hairline, cool airspace lines (`--map-boundary`), faint range
  rings and a dark halo behind every data block so a label crossing land or a
  coastline keeps its last character. The colours are redirected from the
  assets' own inline values to those variables when the layers are loaded, so
  the artwork itself is untouched and the charts (which are injected into the
  same element) keep their own styling. **Range rings** were added at 1, 2.5,
  5, 10 and 20 NM around the scope centre, computed from the app's own
  NM scale (`NM_TO_UNITS`) rather than from the shipped `rings.svg`: that file
  labels a 248-unit circle "10nm", which is 7.5 NM at the scale the rest of the
  app measures with, and a controller cannot be handed a mislabelled range. The
  ring labels are held at a constant on-screen size the same way the chart
  lettering is.

See `FEATURE_MATRIX.md` and inline `ATASOFT:` comments throughout
`src/app.js` / `src/main.js` for exactly what changed and why.

## Checks

Several verification tools run against the shipped source (not a copy of it)
and print a report, exiting non-zero on failure:

```bash
node tools/verify-centre-joins.mjs     # route geometry never flies away from the field
node tools/verify-traffic-plan.mjs     # runway allocation, sequences, go-around, departures
node tools/verify-runway-lock.mjs      # one flight keeps one runway (see above)
node tools/verify-amendment-rule.mjs   # who may amend a flight plan, and what is sent
node tools/verify-board-columns.mjs    # the DEP/ARR columns keep a readable width (see above)
node tools/verify-approach-lines.mjs   # cone, centreline and route share one approach line
node tools/verify-aircraft-glyphs.mjs  # every feed type draws its own, correctly placed aircraft drawing (see above)
node tools/verify-label-geometry.mjs   # data block: px sizing, aligned lines, per-aircraft side, badge, zoom re-derivation
node tools/verify-flight-planner.mjs   # flight plan: command format, real records only, bearing convention, waypoint sequencing
```

`verify-aircraft-glyphs.mjs --ascii` prints each drawing to the terminal, which
is the quickest way to check a change to the artwork without a browser.
Rebuilding the module (`node tools/build-aircraft-shapes.mjs`) re-downloads the
artwork and re-measures every drawing; the verifier fails until it is run.

## How the data actually works (verified against ATC24's official 24data API docs)

- Aircraft positions: the backend (`src/app.js`) polls ATC24's public
  **main-server** endpoint `https://24data.ptfs.app/acft-data` every 3
  seconds (the documented recommended rate is 20 req/min = every 3s) and
  re-serves it (enriched) from `GET /data`. The frontend polls that `/data`
  endpoint every 3 seconds. (`/acft-data/event` is a *different* endpoint
  for ATC24's occasional event server - deliberately not used here.)
- Flight plans: upstream, ATC24 only streams these over its own WebSocket
  feed (`wss://24data.ptfs.app/wss`, `FLIGHT_PLAN` events) - there's no REST
  route for them. That needs a long-lived server-to-server connection,
  which isn't realistic from per-request PHP on shared hosting, so **this
  PHP build does not fetch flight plans**: every aircraft's `flightPlan` is
  `null` and its status is always `No FLP`. (The earlier Node build did
  implement this - see `legacy-node-backend/`.)
- Flight **status** beyond `No FLP` (`climbing` / `cruising` / `descending`
  / `taxiing` / `landed` / `parked` / `inFlight`) depended on a matched
  flight plan's filed cruise altitude, so it isn't computed in this build
  either, for the same reason.
- ATIS: `GET https://24data.ptfs.app/atis` returns every airport's ATIS at
  once (confirmed against the official docs), polled every 6 seconds (the
  documented recommended rate) and cached by airport; `GET /atis/:icao`
  serves the current letter for one airport from that cache. The published
  *list* (with the report text, the editor and the structured lines) comes from
  `https://ws.awdevsoftware.org/atis` through `atis-list.php`, cached 10 s by
  default and `?max_age=N` (1-60 s) shorter on request - the ATIS board at
  `atis.php` asks for one second. Both upstreams were confirmed live and
  identical in shape while this was built.
- Not yet used, but confirmed genuinely available per the official docs:
  `GET /controllers` (who's controlling which position/airport right now)
  and `GET /is-controller/:discordId`. Controller/sector info is correctly
  omitted from the UI for now rather than fabricated - see
  `FEATURE_MATRIX.md` for this as a documented, verified follow-up.

## Running locally

No install step - the backend is plain PHP now (`data.php`, `atis.php`,
`approaches.php`), not a Node process. With PHP installed locally:

```bash
php -S localhost:8000
```

then open `http://localhost:8000/index.php`. See
[`DEPLOYMENT.md`](./DEPLOYMENT.md) for the shared-hosting production setup
(just upload the files - no reverse proxy, no process manager needed).

**Note on testing:** the three PHP endpoints were written and reviewed for
correctness against 24data's official docs, but this sandbox has no
outbound network access, so none of them could actually be run against
live `24data.ptfs.app` data. Confirm `data.php`, `atis.php?icao=IRFD`, and
`approaches.php?icao=IRFD` once this is uploaded somewhere with normal
internet access.

An older Node/Express backend (used until this switch to PHP) is kept for
reference in `legacy-node-backend/` - see its own `README.md`.

## License

GPL-3.0 - see [`LICENSE`](./LICENSE). This build remains fully GPL-3.0;
no part of it is proprietary ATASOFT code.
