<?php
// RedeemALL demo offers page (PHP version of basic.html).
// Images are loaded from the IMAGES/ folder next to this file.

session_start();

$IMAGE_DIR = 'IMAGES';   // folder name is case-sensitive on Linux

// Demo offers. Later these will come from the database.
$demoOffers = [
    ['id' => 1, 'title' => 'Hotel Cashback',  'merchant' => 'Demo Hotels',   'value' => '$50 back',       'card' => 'Travel card (...1234)', 'expires' => '2026-10-15', 'image' => 'hotel-cashback.png', 'used' => false],
    ['id' => 2, 'title' => 'Airline Miles x3', 'merchant' => 'Demo Airways', 'value' => '3x miles',       'card' => 'Travel card (...1234)', 'expires' => '2026-12-31', 'image' => 'flight-miles.png',   'used' => false],
    ['id' => 3, 'title' => 'Dining Rewards',  'merchant' => 'Demo Bistro',   'value' => '15% off',        'card' => 'Gold card (...5678)',   'expires' => '2026-11-20', 'image' => 'dining.png',         'used' => false],
    ['id' => 4, 'title' => 'Online Shopping', 'merchant' => 'Demo Store',    'value' => '10% back',       'card' => 'Gold card (...5678)',   'expires' => '2026-10-12', 'image' => 'shopping.png',       'used' => false],
    ['id' => 5, 'title' => 'Fuel Surcharge',  'merchant' => 'Demo Fuel',     'value' => '1% fee waived',  'card' => 'Travel card (...1234)', 'expires' => '2026-09-30', 'image' => 'fuel.png',           'used' => true],
];

// Keep the list in the session so Delete / Mark used work for the demo.
if (!isset($_SESSION['offers']) || isset($_GET['reset'])) {
    $_SESSION['offers'] = $demoOffers;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
    $id = (int) $_POST['id'];
    foreach ($_SESSION['offers'] as $i => $offer) {
        if ($offer['id'] !== $id) {
            continue;
        }
        if ($_POST['action'] === 'delete') {
            unset($_SESSION['offers'][$i]);
        } elseif ($_POST['action'] === 'used') {
            $_SESSION['offers'][$i]['used'] = true;
        }
    }
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$offers = $_SESSION['offers'];
$today  = new DateTime('today');

$active = 0;
$expiringSoon = 0;
$used = 0;
foreach ($offers as $offer) {
    $daysLeft = (int) $today->diff(new DateTime($offer['expires']))->format('%r%a');
    if ($offer['used']) {
        $used++;
    } elseif ($daysLeft >= 0) {
        $active++;
        if ($daysLeft <= 7) {
            $expiringSoon++;
        }
    }
}

function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Returns the image path, or the default card picture if the file is missing.
function imageFor($file) {
    global $IMAGE_DIR;
    $path = $IMAGE_DIR . '/' . basename($file);
    return is_file(__DIR__ . '/' . $path) ? $path : $IMAGE_DIR . '/travel-card.png';
}

function status($offer, $today) {
    $daysLeft = (int) $today->diff(new DateTime($offer['expires']))->format('%r%a');
    if ($offer['used'])  return ['Used', 'used'];
    if ($daysLeft < 0)   return ['Expired', 'expired'];
    if ($daysLeft <= 7)  return [$daysLeft . ' days left', 'soon'];
    return ['Active', 'ok'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RedeemALL · Offers</title>
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
        .stats { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .stats div { background: #fff; padding: 12px 16px; border-radius: 10px; }
        .stats strong { font-size: 1.5rem; display: block; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #eceef2; vertical-align: middle; }
        .offer { display: flex; align-items: center; gap: 12px; }
        .offer img { width: 48px; height: 48px; border-radius: 10px; }
        .muted { color: #6b7385; font-size: .9rem; }
        .badge { padding: 2px 8px; border-radius: 999px; font-size: .8rem; }
        .badge.ok { background: #e6f6ee; color: #166b45; }
        .badge.soon { background: #fff4e5; color: #92400e; }
        .badge.used, .badge.expired { background: #eceef2; color: #6b7385; }
        form { display: inline; }
        button { padding: 8px 14px; border: 0; border-radius: 6px; background: #2457d6; color: #fff; cursor: pointer; }
        button.danger { background: #c7362b; }
        .empty { text-align: center; padding: 40px; background: #fff; border-radius: 10px; }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="index.php"><img src="<?= $IMAGE_DIR ?>/logo.png" alt="RedeemALL"></a>
        <nav>
            <a href="index.php">Offers</a>
            <a href="#">Add offer</a>
            <a href="#">My cards</a>
            <a href="index.php?reset=1">Reset demo</a>
        </nav>
    </header>
    <main>
        <h1>Hi demo</h1>
        <section class="stats">
            <div><strong><?= $active ?></strong> active</div>
            <div><strong><?= $expiringSoon ?></strong> expiring in 7 days</div>
            <div><strong><?= $used ?></strong> used</div>
        </section>

        <?php if (empty($offers)): ?>
            <div class="empty">
                <img src="<?= $IMAGE_DIR ?>/no-offers.png" alt="No offers" width="200">
                <p><a href="index.php?reset=1">Load the demo offers again</a></p>
            </div>
        <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Offer</th><th>Value</th><th>Card</th><th>Expires</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($offers as $offer): [$label, $class] = status($offer, $today); ?>
                <tr>
                    <td>
                        <div class="offer">
                            <img src="<?= e(imageFor($offer['image'])) ?>" alt="<?= e($offer['title']) ?>">
                            <div><strong><?= e($offer['title']) ?></strong><div class="muted"><?= e($offer['merchant']) ?></div></div>
                        </div>
                    </td>
                    <td><?= e($offer['value']) ?></td>
                    <td><?= e($offer['card']) ?></td>
                    <td><?= date('d M Y', strtotime($offer['expires'])) ?></td>
                    <td><span class="badge <?= $class ?>"><?= e($label) ?></span></td>
                    <td>
                        <?php if (!$offer['used']): ?>
                        <form method="post"><input type="hidden" name="id" value="<?= $offer['id'] ?>"><button name="action" value="used">Mark used</button></form>
                        <?php endif; ?>
                        <form method="post"><input type="hidden" name="id" value="<?= $offer['id'] ?>"><button class="danger" name="action" value="delete">Delete</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
