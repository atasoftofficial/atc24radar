# Attribution

ATC24 Radar by ATASOFT is a derivative work under the **GNU GPL-3.0**.
It does not claim any upstream code as original ATASOFT work.

## Functional base

**[t-arpin/atc24radar](https://github.com/t-arpin/atc24radar)** — GPL-3.0.
This is the functional foundation of this build: the Node.js backend (ATC24
data polling, flight-plan matching, flight-status heuristic, ATIS cache,
approach-chart listing), the SVG radar renderer, ground view, split-screen
system, per-aircraft-type plane icons with heading rotation, draggable
labels with pin support, the measuring/vectoring tool, airport selector,
departures/arrivals tables, and airspace/chart overlays are all reused (with
the fixes and additions described in `README.md` / `FEATURE_MATRIX.md`) from
this project. Also credited in the original project: **Tristan3D3**.

## Reviewed but not incorporated

**[HotDog640/24RC](https://github.com/HotDog640/24RC)** — GPL-3.0, by
**Autumnhd**. This project was cloned and inspected during research (it uses
a different stack - TypeScript, React, PixiJS - and implements a smaller
feature set: a STARS-style DCB button bar and single radar view, without
split-screen, ground view, aircraft pinning, or a measuring tool at the time
it was reviewed). None of its code is included in this build. It is credited
here because it was genuinely used as research input, per GPL-3.0 good
practice around derivative/comparative work, and so nothing here implies its
code was silently absorbed.

## ATASOFT's contribution

- Full visual redesign and rebrand (dark UI, purple/light-blue accent
  palette, typography, iconography, favicon).
- Configuration/deployment fixes: same-origin API defaults instead of a
  hardcoded third-party server, configurable CORS allowlist, configurable
  port, a working `package.json`, `.env.example`, and this documentation
  set.
- Bug fixes: the `/approaches/:icao` path resolution, the missing-file
  crash on unauthorized requests.
- New functionality: live connection/data-freshness indicator, full-flight
  (not truncated) altitude-colored trails that clear on landing,
  altitude-history sparkline in the aircraft inspection panel,
  selected/pinned aircraft icon states.

No proprietary or closed-source ATASOFT code is mixed into this build - the
entire project remains licensed under GPL-3.0. See [`LICENSE`](./LICENSE).

## Aircraft artwork (the radar's aircraft symbols)

**[RexKramer1/AircraftShapesSVG](https://github.com/RexKramer1/AircraftShapesSVG)** — GPL-3.0.
The radar's aircraft symbols are that project's top-view aircraft drawings: one
single-piece SVG per ICAO type designator, drawn nose-up, at 1 unit = 1 metre.
The files ship with this build in `public/assets/aircraft-shapes/` and are baked
into `src/data/AircraftShapes.js` by `tools/build-aircraft-shapes.mjs`, which
also records which drawing each aircraft type in this feed uses (no drawing is
invented here; where the set has no drawing of an exact type, the file's note
says which real aircraft of the same class stands in). Types the set does not
cover at all fall back to a plain airliner of the type's category.

This artwork is distributed under the same GPL-3.0 terms as the rest of this
build; see [`LICENSE`](./LICENSE).

## Third-party runtime dependencies (backend, all MIT-licensed)

| Package | Purpose |
|---|---|
| express | HTTP server / routing |
| cors | CORS handling |
| express-rate-limit | Basic rate limiting on GET requests |
| node-fetch (v2) | Server-side HTTP requests to ATC24's API |
| ws | WebSocket server (own clients) + client (ATC24 flight-plan feed) |
| dotenv | Loads `.env` for local configuration |

## Data source

Aircraft and flight-plan data originates from ATC24's own public endpoints
(`24data.ptfs.app`). This project does not claim ownership of that data and
is not affiliated with ATC24, its developers, Roblox Corporation, or any
airline referenced in callsign/livery data.
