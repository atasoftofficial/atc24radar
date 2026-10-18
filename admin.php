<?php
// ATASOFT ATC24 Radar - Admin panel.
//
// Lets an admin paste a logo URL in for each airline, without touching any
// 24data API code. This page ONLY reads/writes public/assets/airline-logos.json
// (a plain "ICAO" => "https://..." map) - it never calls 24data, never
// touches cache/, and the radar frontend only ever reads that JSON file, so
// there is nothing here that can affect the live feed, ATIS, or rate limits.
//
// The password is checked as a SHA-256 hash, not stored in plain text.
// To change it: run  php -r "echo hash('sha256', 'new-password-here');"
// and paste the result into ADMIN_PASSWORD_HASH below.

session_start();

const ADMIN_PASSWORD_HASH = '14048336a8bbc3d52c4195c83689e94cbbc07eaa1c421fab0c3667fcc9f87879';
const LOGOS_FILE = __DIR__ . '/public/assets/airline-logos.json';

// carrier-key => [display name, ICAO] - kept in sync with
// src/data/AirlineMap.js + src/data/CallsignMap.js (GA "private owner"
// passthrough callsigns are intentionally left out; they aren't airlines).
$AIRLINES = [
    'Shamrock'        => ['Aer Lingus', 'EIN'],
    'Air Canadian'    => ['Air Canada', 'ACA'],
    'Americano'       => ['American Airlines', 'AAL'],
    'Speedbird'       => ['British Airways', 'BAW'],
    'Belta'           => ['Delta Air Lines', 'DAL'],
    'Hard'            => ['easyJet', 'EZY'],
    'JetBloo'         => ['JetBlue Airways', 'JBU'],
    'Oantas'          => ['Qantas', 'QFA'],
    'Oatari'          => ['Qatar Airways', 'QTR'],
    'Scandialien'     => ['Scandinavian Airlines', 'SAS'],
    'Sprit Wings'     => ['Spirit Airlines', 'NKS'],
    'Turkey'          => ['Turkish Airlines', 'THY'],
    'BizzAir'         => ['Ryanair Sun', 'RYS'],
    'AirFrans'        => ['Air France', 'AFR'],
    'Doncor'          => ['Condor', 'CFG'],
    'PolKot'          => ['LOT Polish Airlines', 'LOT'],
    'Lifthansa'       => ['Lufthansa', 'DLH'],
    'Emarates'        => ['Emirates', 'UAE'],
    'KoreenAir'       => ['Korean Air', 'KAL'],
    'Channex'         => ['Jet2', 'EXS'],
    'Byanair'         => ['Ryanair', 'RYR'],
    'Northeast'       => ['Southwest Airlines', 'SWA'],
    'TomJet'          => ['TUI Airways', 'TOM'],
    'KLN'             => ['KLM Royal Dutch Airlines', 'KLM'],
    'AirBalistic'     => ['airBaltic', 'BTI'],
    'Singadoor'       => ['Singapore Airlines', 'SIA'],
    'Cafey'           => ['Cathay Pacific', 'CPA'],
    'Thay'            => ['Thai Airways', 'THA'],
    'OldZealand'      => ['Air New Zealand', 'ANZ'],
    'BetiAirlines'    => ['New York Air', 'NYT'],
    'Bliss'           => ['Swiss International Air Lines', 'SWR'],
    'Reunited'        => ['United Airlines', 'UAL'],
    'Vulcan'          => ['Waddington Air Depot', 'WAD'],
    'Supertransport'  => ['British Global Aviation', 'BGA'],
    'Jersey'          => ['flybe', 'BEE'],
    'Tedex'           => ['FedEx Express', 'FDX'],
    'Sus'             => ['UPS Airlines', 'UPS'],
    'Worldstar'       => ['DHL Aviation', 'DHV'],
    'GolfPapaGolf'    => ['Golf Papa Golf', 'GPG'],
    'Bepsi'           => ['Air France', 'AFR'],
    'Antonov Bureau'  => ['Antonov Design Bureau', 'ADB'],
    'Clipper'         => ['Pan Am', 'PAA'],
    'IDERIA'          => ['Iberia', 'IBE'],
    'Broadsword'      => ['Broadsword Airways', 'BRO'],
    'STAFFBOI'        => ['Pacific Coastal Airlines', 'PTC'],
    'CEDU AIR'        => ['Cebu Pacific', 'CEB'],
    'AEROFLAT'        => ['Aeroflot', 'AFL'],
    'AZOL'            => ['Azul Brazilian Airlines', 'AZU'],
];

function atasoft_load_logos(): array {
    if (!file_exists(LOGOS_FILE)) return [];
    $data = json_decode((string) file_get_contents(LOGOS_FILE), true);
    return is_array($data) ? $data : [];
}

function atasoft_is_logged_in(): bool {
    return !empty($_SESSION['atasoft_admin']);
}

$error = null;
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && !isset($_POST['action'])) {
    if (hash_equals(ADMIN_PASSWORD_HASH, hash('sha256', (string) $_POST['password']))) {
        $_SESSION['atasoft_admin'] = true;
        $_SESSION['atasoft_csrf'] = bin2hex(random_bytes(16));
    } else {
        $error = 'Şifre yanlış.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php');
    exit;
}

if (atasoft_is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    if (!hash_equals($_SESSION['atasoft_csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $error = 'Oturum süresi doldu, lütfen tekrar deneyin.';
    } else {
        $logos = [];
        foreach ((array) ($_POST['logo'] ?? []) as $icao => $url) {
            $icao = strtoupper(trim((string) $icao));
            $url = trim((string) $url);
            if ($url === '') continue;
            if (!preg_match('/^https?:\/\//i', $url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                $error = "Geçersiz link (http/https ile başlamalı): {$icao}";
                continue;
            }
            $logos[$icao] = $url;
        }
        // Freeform extra ICAO row, for an airline not in the list below.
        $extraIcao = strtoupper(trim((string) ($_POST['extra_icao'] ?? '')));
        $extraUrl  = trim((string) ($_POST['extra_logo'] ?? ''));
        if ($extraIcao !== '' && $extraUrl !== '') {
            if (preg_match('/^[A-Z0-9]{2,4}$/', $extraIcao) && preg_match('/^https?:\/\//i', $extraUrl) && filter_var($extraUrl, FILTER_VALIDATE_URL)) {
                $logos[$extraIcao] = $extraUrl;
            } else {
                $error = 'Ekstra satırdaki ICAO ya da link geçersiz.';
            }
        }

        if ($error === null) {
            $dir = dirname(LOGOS_FILE);
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $lock = fopen(LOGOS_FILE . '.lock', 'c');
            if ($lock && flock($lock, LOCK_EX)) {
                file_put_contents(LOGOS_FILE, json_encode($logos, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
                flock($lock, LOCK_UN);
                fclose($lock);
            }
            $saved = true;
        }
    }
}

$currentLogos = atasoft_load_logos();
$csrf = $_SESSION['atasoft_csrf'] ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ATASOFT ATC24 Radar - Admin</title>
<?php require __DIR__ . '/inc/fonts.php'; ?>
<link rel="stylesheet" href="public/style.css">
<style>
    html, body { overflow: auto; display: block; height: auto; }
    body { padding: 24px; font-family: var(--font-ui); color: var(--text); }
    .admin-wrap { max-width: 760px; margin: 0 auto; }
    .admin-card { background: var(--panel); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 20px 24px; margin-bottom: 16px; }
    .admin-card h1 { font-size: 1.1em; margin: 0 0 4px; }
    .admin-card p.sub { color: var(--text-dim); font-size: 0.85em; margin: 0 0 16px; }
    label { display: block; font-size: 0.85em; color: var(--text-dim); margin-bottom: 4px; }
    input[type=password], input[type=text], input[type=url] {
        width: 100%; box-sizing: border-box; background: var(--panel-deep); border: 1px solid var(--border);
        border-radius: var(--radius-sm); color: var(--text); padding: 8px 10px; font-family: var(--font-data); font-size: 0.9em;
    }
    input:focus { outline: none; border-color: var(--accent); }
    button { background: var(--accent); color: #0b0e1e; border: none; border-radius: var(--radius-sm);
        padding: 9px 18px; font-family: var(--font-ui); font-weight: 600; cursor: pointer; font-size: 0.9em; }
    button.secondary { background: var(--panel-hover); color: var(--text); }
    .row { display: flex; gap: 10px; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border); }
    .row:last-of-type { border-bottom: none; }
    .row .name { flex: 1; min-width: 0; }
    .row .name b { display: block; font-size: 0.92em; }
    .row .name span { color: var(--text-dim); font-size: 0.78em; }
    .row .field { flex: 1.3; }
    .row img.preview { width: 26px; height: 26px; object-fit: contain; border-radius: 4px; background: var(--panel-hover); flex-shrink: 0; }
    .msg { padding: 10px 14px; border-radius: var(--radius-sm); margin-bottom: 16px; font-size: 0.88em; }
    .msg.ok { background: rgba(79,230,179,0.12); color: var(--success); border: 1px solid rgba(79,230,179,0.3); }
    .msg.err { background: rgba(255,95,109,0.12); color: var(--danger); border: 1px solid rgba(255,95,109,0.3); }
    .top-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .logo-hdr { font-size: 0.7em; color: var(--text-dim); text-transform: uppercase; letter-spacing: .05em; }
</style>
</head>
<body>
<div class="admin-wrap">

<?php if (!atasoft_is_logged_in()): ?>

    <div class="admin-card">
        <h1>ATASOFT ATC24 Radar — Admin girişi</h1>
        <p class="sub">Bu panel sadece havayolu logosu linklerini yönetir. API / ATIS / rate limit ile hiçbir ilgisi yoktur.</p>
        <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <label for="password">Şifre</label>
            <input type="password" id="password" name="password" autofocus>
            <div style="margin-top:14px;"><button type="submit">Giriş yap</button></div>
        </form>
    </div>

<?php else: ?>

    <div class="top-actions">
        <h1 style="margin:0;">Havayolu Logoları</h1>
        <form method="post"><input type="hidden" name="action" value="logout"><button class="secondary" type="submit">Çıkış yap</button></form>
    </div>

    <?php if ($saved && $error === null): ?><div class="msg ok">Kaydedildi. Radar sayfası en fazla 60 saniye içinde yeni logoları alacak.</div><?php endif; ?>
    <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="admin-card">
        <p class="sub">Her havayolu için bir logo linki (https://...) yapıştır. Boş bırakırsan o havayolu için logo gösterilmez. Site bu linkleri sadece &lt;img&gt; olarak gösterir, dosyayı kendi sunucusuna indirmez.</p>
        <form method="post">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

            <div class="row">
                <div class="logo-hdr" style="width:26px;"></div>
                <div class="logo-hdr name">Havayolu</div>
                <div class="logo-hdr field">Logo URL</div>
            </div>

            <?php foreach ($AIRLINES as $carrier => [$name, $icao]): ?>
                <?php $val = $currentLogos[$icao] ?? ''; ?>
                <div class="row">
                    <img class="preview" src="<?= htmlspecialchars($val ?: 'public/assets/icons/placeholder.svg') ?>" onerror="this.style.visibility='hidden'">
                    <div class="name"><b><?= htmlspecialchars($name) ?></b><span><?= htmlspecialchars($carrier) ?> · <?= htmlspecialchars($icao) ?></span></div>
                    <div class="field"><input type="url" name="logo[<?= htmlspecialchars($icao) ?>]" value="<?= htmlspecialchars($val) ?>" placeholder="https://..."></div>
                </div>
            <?php endforeach; ?>

            <div class="row">
                <div class="logo-hdr" style="width:26px;"></div>
                <div class="name"><b>Listede yok / diğer</b><span>ICAO gir</span>
                    <input type="text" name="extra_icao" placeholder="ör. RYS" maxlength="4" style="margin-top:6px;">
                </div>
                <div class="field"><input type="url" name="extra_logo" placeholder="https://..."></div>
            </div>

            <div style="margin-top:18px;"><button type="submit">Kaydet</button></div>
        </form>
    </div>

<?php endif; ?>

</div>
</body>
</html>
