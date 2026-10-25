<?php
// RedeemALL Stage 2: login + offers read from the MySQL database on the DB VM.
// Needs /var/www/config.php (database settings) and the IMAGES/ folder next to this file.

session_start();

$config = require '/var/www/config.php';
$IMAGE_DIR = 'IMAGES';   // folder name is case-sensitive on Linux

try {
    $db = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $ex) {
    error_log('RedeemALL DB connection failed: ' . $ex->getMessage());
    http_response_code(500);
    exit('Cannot connect to the database. Check /var/www/config.php and that the DB VM is running.');
}

function e($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function go($to = 'index.php') {
    header('Location: ' . $to);
    exit;
}

// A random token stops other websites from submitting forms on your behalf.
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$validPost = $_SERVER['REQUEST_METHOD'] === 'POST'
    && hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$error = '';

// ---------- Sign in / sign out ----------
if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    go();
}

if ($action === 'login' && $validPost) {
    $stmt = $db->prepare('SELECT id, display_name, password_hash FROM users WHERE username = ?');
    $stmt->execute([trim($_POST['username'] ?? '')]);
    $user = $stmt->fetch();
    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['display_name'];
        go();
    }
    $error = 'Wrong username or password.';
}

$loggedIn = isset($_SESSION['user_id']);

// ---------- Offer actions (signed-in users only) ----------
if ($loggedIn && $validPost) {
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'toggle') {
        $db->prepare('UPDATE offers SET is_used = NOT is_used WHERE id = ?')->execute([$id]);
        go();
    }
    if ($action === 'delete') {
        $db->prepare('DELETE FROM offers WHERE id = ?')->execute([$id]);
        go();
    }
    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $cardId = (int) ($_POST['card_id'] ?? 0);
        if ($title === '' || $cardId === 0) {
            $error = 'Please choose a card and enter a title.';
        } else {
            $db->prepare('INSERT INTO offers (card_id, title, merchant, value_text, expires_on, image)
                          VALUES (?, ?, ?, ?, ?, ?)')
               ->execute([
                   $cardId,
                   $title,
                   trim($_POST['merchant'] ?? '') ?: null,
                   trim($_POST['value_text'] ?? '') ?: null,
                   ($_POST['expires_on'] ?? '') ?: null,
                   basename($_POST['image'] ?? '') ?: null,
               ]);
            go();
        }
    }
}

// ---------- Data for the page ----------
$offers = [];
$cards = [];
$active = $soon = $used = 0;
if ($loggedIn) {
    $offers = $db->query(
        'SELECT o.*, c.nickname, c.last_four,
                DATEDIFF(o.expires_on, CURDATE()) AS days_left
         FROM offers o JOIN cards c ON c.id = o.card_id
         ORDER BY o.is_used, o.expires_on IS NULL, o.expires_on'
    )->fetchAll();
    $cards = $db->query('SELECT id, nickname, last_four FROM cards ORDER BY nickname')->fetchAll();

    foreach ($offers as $o) {
        if ($o['is_used']) {
            $used++;
        } elseif ($o['days_left'] === null || $o['days_left'] >= 0) {
            $active++;
            if ($o['days_left'] !== null && $o['days_left'] <= 7) {
                $soon++;
            }
        }
    }
}

function status($o) {
    if ($o['is_used'])               return ['Used', 'used'];
    if ($o['days_left'] === null)    return ['No expiry', 'ok'];
    if ($o['days_left'] < 0)         return ['Expired', 'expired'];
    if ($o['days_left'] <= 7)        return [$o['days_left'] . ' days left', 'soon'];
    return ['Active', 'ok'];
}

function imageFor($file) {
    global $IMAGE_DIR;
    $path = $IMAGE_DIR . '/' . basename((string) $file);
    return ($file && is_file(__DIR__ . '/' . $path)) ? $path : $IMAGE_DIR . '/travel-card.png';
}

$images = ['hotel-cashback.png' => 'Hotel', 'flight-miles.png' => 'Travel', 'dining.png' => 'Dining',
           'shopping.png' => 'Shopping', 'fuel.png' => 'Fuel', 'travel-card.png' => 'Other'];
$csrf = e($_SESSION['csrf']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RedeemALL<?= $loggedIn ? ' · Offers' : ' · Sign in' ?></title>
    <link rel="icon" type="image/png" href="<?= $IMAGE_DIR ?>/favicon.png">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; background: #f5f6f8; color: #1d2330; }
        main { max-width: 960px; margin: 0 auto; padding: 24px 16px; }
        h1 { margin-top: 0; }
        a { color: #2457d6; text-decoration: none; }
        .topbar { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #1d2330; flex-wrap: wrap; gap: 8px; }
        .topbar nav a { color: #fff; margin-left: 16px; }
        .brand img { height: 40px; display: block; }
        .login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px; }
        .login { width: 100%; max-width: 360px; background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        .login .logo { background: #1d2330; border-radius: 8px; padding: 10px; text-align: center; margin-bottom: 16px; }
        .login .logo img { height: 40px; }
        label { display: block; margin: 12px 0 4px; font-weight: 600; }
        input, select { width: 100%; padding: 9px; border: 1px solid #c8ccd4; border-radius: 6px; font: inherit; }
        .stats { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .stats div { background: #fff; padding: 12px 16px; border-radius: 10px; }
        .stats strong { font-size: 1.5rem; display: block; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #eceef2; vertical-align: middle; }
        .offer { display: flex; align-items: center; gap: 12px; }
        .offer img { width: 48px; height: 48px; border-radius: 10px; }
        .muted { color: #6b7385; font-size: .9rem; }
        .badge { padding: 2px 8px; border-radius: 999px; font-size: .8rem; white-space: nowrap; }
        .badge.ok { background: #e6f6ee; color: #166b45; }
        .badge.soon { background: #fff4e5; color: #92400e; }
        .badge.used, .badge.expired { background: #eceef2; color: #6b7385; }
        form.inline { display: inline; }
        button { padding: 8px 14px; border: 0; border-radius: 6px; background: #2457d6; color: #fff; cursor: pointer; font: inherit; }
        button.danger { background: #c7362b; }
        button.wide { width: 100%; margin-top: 18px; }
        .error { background: #fbe4e2; color: #8a1f17; padding: 10px; border-radius: 6px; }
        .add { background: #fff; border-radius: 10px; padding: 20px; margin-top: 24px; }
        .add .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0 16px; }
        .add button { margin-top: 16px; }
    </style>
</head>
<body>
<?php if (!$loggedIn): ?>
    <div class="login-wrap">
        <form class="login" method="post">
            <div class="logo"><img src="<?= $IMAGE_DIR ?>/logo.png" alt="RedeemALL"></div>
            <p class="muted">Every card offer in one place. Sign in to see yours.</p>
            <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <label>Username <input name="username" required autofocus></label>
            <label>Password <input name="password" type="password" required></label>
            <button class="wide">Sign in</button>
        </form>
    </div>
<?php else: ?>
    <header class="topbar">
        <a class="brand" href="index.php"><img src="<?= $IMAGE_DIR ?>/logo.png" alt="RedeemALL"></a>
        <nav>
            <a href="index.php">Offers</a>
            <a href="#add">Add offer</a>
            <a href="index.php?action=logout">Sign out</a>
        </nav>
    </header>
    <main>
        <h1>Hi <?= e($_SESSION['name']) ?></h1>
        <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
        <section class="stats">
            <div><strong><?= $active ?></strong> active</div>
            <div><strong><?= $soon ?></strong> expiring in 7 days</div>
            <div><strong><?= $used ?></strong> used</div>
        </section>

        <?php if (!$offers): ?>
            <p class="muted">No offers yet. Add your first one below.</p>
        <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Offer</th><th>Value</th><th>Card</th><th>Expires</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($offers as $o): [$label, $class] = status($o); ?>
                <tr>
                    <td>
                        <div class="offer">
                            <img src="<?= e(imageFor($o['image'])) ?>" alt="">
                            <div><strong><?= e($o['title']) ?></strong><div class="muted"><?= e($o['merchant']) ?></div></div>
                        </div>
                    </td>
                    <td><?= e($o['value_text'] ?? '-') ?></td>
                    <td><?= e($o['nickname']) ?> (...<?= e($o['last_four']) ?>)</td>
                    <td><?= $o['expires_on'] ? date('d M Y', strtotime($o['expires_on'])) : '<span class="muted">No expiry</span>' ?></td>
                    <td><span class="badge <?= $class ?>"><?= e($label) ?></span></td>
                    <td>
                        <form class="inline" method="post">
                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                            <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                            <button name="action" value="toggle"><?= $o['is_used'] ? 'Mark unused' : 'Mark used' ?></button>
                        </form>
                        <form class="inline" method="post" onsubmit="return confirm('Delete this offer?')">
                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                            <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                            <button class="danger" name="action" value="delete">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>

        <form class="add" id="add" method="post">
            <h2>Add an offer</h2>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <div class="grid">
                <label>Card
                    <select name="card_id" required>
                        <option value="">Choose a card</option>
                        <?php foreach ($cards as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= e($c['nickname']) ?> (...<?= e($c['last_four']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Title <input name="title" placeholder="Hotel Cashback" required></label>
                <label>Merchant <input name="merchant" placeholder="Demo Hotels"></label>
                <label>Value <input name="value_text" placeholder="$50 back, 5% off"></label>
                <label>Expires on <input name="expires_on" type="date"></label>
                <label>Type
                    <select name="image">
                        <?php foreach ($images as $file => $name): ?>
                            <option value="<?= e($file) ?>"><?= e($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <button>Save offer</button>
        </form>
    </main>
<?php endif; ?>
</body>
</html>
