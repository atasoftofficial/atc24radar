# Deployment guide

Target: `https://atc24radar.atasoft.xyz`

## Architecture (current: PHP)

- **Frontend**: static files - `index.php`, `about.html`, `about.css`,
  `public/`, `src/main.js`, `src/changelog.js`. No build step; served as-is.
  (`index.php` has no server-side logic in it - it's plain HTML, just given
  a `.php` extension.)
- **Backend**: small PHP scripts at the repo root - `data.php`,
  `atis.php`, `atis-list.php`, `airport-flights.php`, `flightboard.php`,
  `approaches.php`, `controllers.php`, `logos.php`, `admin.php`. Each one is
  called directly by the frontend (`fetch('data.php')`, etc.) - there's no
  routing/rewrite setup needed.
  - `flightboard.php` is the one **write** endpoint: it stores the
    controller-entered ops data (cleared level, squawk, rwy/sid/stand/rmk,
    cleared tick) for the DEP/ARR boards and the aircraft panel in
    `cache/flightboard.json`, keyed by callsign, so two controllers on two
    different browsers see the same values for the same flight. It is not
    authenticated (see README) and it prunes records older than 12 hours.
  - `data.php` fetches `https://24data.ptfs.app/acft-data` server-side,
    caches it in `cache/acft-data.json` for 3 seconds, and re-serves an
    enriched version.
  - `atis.php?icao=IRFD` fetches/caches `https://24data.ptfs.app/atis`
    (all airports at once, per 24data's docs) for 6 seconds and returns
    the one airport's current letter. Opened **without** parameters the
    same file is the **ATIS board page** (`src/atisBoard.js`,
    `public/style-atis.css`): every published report, searchable by
    4-letter airport code, refreshed once a second in the page. It reads
    the list through `atis-list.php`, so no second upstream or endpoint is
    added.
  - `atis-list.php` re-serves `https://ws.awdevsoftware.org/atis` (the
    list Centre Mode and the planner already use), cached for 10 seconds.
    `?max_age=N` (1-60 s) shortens that cache for a caller that wants the
    newest second - the board asks for `max_age=1` - and the cache file is
    still the rate limit for every caller at once.
  - `approaches.php?icao=IRFD` just lists `public/assets/maps/IRFD/*.svg`
    - no upstream call.

This has to go through the server (not straight from the browser) because
24data's own docs say their API doesn't send a permissive `Allow-Origin`
header, so browsers are blocked from calling `24data.ptfs.app` directly.

**Why PHP instead of the earlier Node/Express version:** the target
hosting is shared cPanel hosting. Keeping a persistent Node process (and
proxying a WebSocket `Upgrade`) alive reliably on that kind of hosting is
awkward at best; plain PHP scripts that run per-request are exactly what
shared hosting is built for. The previous Node backend is kept for
reference in `legacy-node-backend/` but is not used by this deployment.

**Trade-off:** flight-plan matching (filed route, filed cruise altitude,
and the computed climbing/cruising/descending/taxiing/landed/parked
status) needed a long-lived connection to 24data's own WebSocket feed,
which isn't realistic from per-request PHP. It's not carried over - every
aircraft's flight plan is now `null` and its status shows `No FLP`, which
is exactly what the frontend already displayed for any aircraft with no
filed plan, so nothing crashes or looks broken, it's just less complete
than the Node build was.

## 1. Upload

Upload the whole repository (minus `legacy-node-backend/`, which isn't
needed) to your hosting - e.g. `public_html/` directly for the domain
root, or a subdomain's document root if `atc24radar.atasoft.xyz` is its
own subdomain in cPanel. Any plain PHP hosting works; no "Setup Node.js
App" step, no npm install, no process manager.

## 2. Permissions

The `cache/` folder needs to be writable by PHP (the user PHP runs as on
shared cPanel hosting is normally the same as your file owner, so the
default permissions from uploading are usually already fine - only chmod
it, e.g. `chmod 755 cache`, if `data.php`/`atis.php` start returning a 502
"Upstream unavailable" and nothing ever gets cached).

This now matters for writing as well as caching: `flightboard.php` stores the
shared FL/squawk/clearance data in `cache/flightboard.json`. If that file
cannot be created, the DEP/ARR boards still work, but the aircraft panel says
`LOCAL ONLY` instead of `SHARED` and edits stay in the browser that made them.

`cache/.htaccess` blocks direct web access to whatever gets cached there;
no extra config needed for that.

## 3. Nothing else to configure

No `.env`, no `ALLOWED_ORIGINS`, no reverse proxy, no WebSocket proxying.
`data.php`, `atis.php`, and `approaches.php` are called with plain relative
URLs from `src/main.js`, so they work as soon as they're on the same
domain as `index.php` - which they are, since everything is uploaded
together.

If you ever split the frontend and backend across two different domains,
set `window.ATASOFT_API_HOST` in `index.php` to the backend's host - see
the comment right above it.

## What was actually tested (and what wasn't)

Tested in the build sandbox: `data.php`/`atis.php`/`approaches.php` for
PHP syntax by inspection (no PHP interpreter was available in this sandbox
to run them), and `src/main.js` passes `node --check` for syntax after the
endpoint-URL changes.

**Not testable from this build environment:** this sandbox has no outbound
network access, so none of the three PHP endpoints could actually be run
against live `24data.ptfs.app` data, and the cache-locking behavior under
real concurrent requests hasn't been exercised. Verify all three
(`data.php`, `atis.php?icao=IRFD`, `approaches.php?icao=IRFD`) directly in
a browser once this is uploaded, before relying on it.
