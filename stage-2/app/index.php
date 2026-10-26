<?php include 'db.php'; ?>

<?php
// "Mark as used" / "Mark unused" button
if (isset($_POST['toggle_id'])) {
    $id = (int) $_POST['toggle_id'];
    $conn->query("UPDATE offers SET is_used = NOT is_used WHERE id = $id");
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RedeemALL - My Card Offers</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f6f8; color: #1d2330; }

        header { background: #1d2330; color: #fff; padding: 18px; font-size: 22px; font-weight: bold; text-align: center; }

        nav { background: #2457d6; text-align: center; padding: 10px; }
        nav a { color: #fff; text-decoration: none; margin: 0 15px; font-weight: bold; }

        .container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; padding: 24px 16px; }

        .card { position: relative; background: #fff; width: 240px; padding: 18px; border-radius: 12px;
                text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .card img { width: 90px; height: 90px; border-radius: 14px; }
        .card h3 { margin: 10px 0 4px; }
        .card p { margin: 4px 0; color: #555; }
        .card .value { font-size: 20px; font-weight: bold; color: #166b45; }
        .card .small { font-size: 13px; color: #6b7385; }

        .status { position: absolute; top: 12px; right: 12px; font-size: 12px; padding: 3px 8px;
                  border-radius: 999px; background: #e6f6ee; color: #166b45; }
        .card.soon .status { background: #fff4e5; color: #92400e; }
        .card.expired .status, .card.used .status { background: #eceef2; color: #6b7385; }
        .card.used, .card.expired { opacity: .6; }

        button { margin-top: 10px; padding: 9px 16px; border: 0; border-radius: 6px; background: #2457d6;
                 color: #fff; cursor: pointer; }
        button:hover { background: #1b45b0; }
    </style>
</head>
<body>

<header> 💳 RedeemALL - Every card offer in one place </header>

<nav>
    <a href="index.php">Offers</a>
    <a href="admin.php">Admin</a>
</nav>

<div class="container">

<?php
$sql = "SELECT offers.*, cards.nickname, cards.last_four
        FROM offers
        JOIN cards ON cards.id = offers.card_id
        ORDER BY offers.is_used, offers.expires_on";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {

        // Work out the status from the expiry date
        $status = "Active";
        $class  = "active";
        if ($row['is_used']) {
            $status = "Used";
            $class  = "used";
        } elseif ($row['expires_on'] != null) {
            $daysLeft = round((strtotime($row['expires_on']) - strtotime(date('Y-m-d'))) / 86400);
            if ($daysLeft < 0) {
                $status = "Expired";
                $class  = "expired";
            } elseif ($daysLeft <= 7) {
                $status = $daysLeft . " days left";
                $class  = "soon";
            }
        }

        echo "<div class='card $class'>";

        $imagePath = $row['image'];

        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            echo "<img src='".htmlspecialchars($imagePath)."' alt='offer'>";
        } else {
            echo "<img src='IMAGES/".htmlspecialchars($imagePath)."' alt='offer'>";
        }

        echo "<span class='status'>".$status."</span>";
        echo "<h3>".htmlspecialchars($row['title'])."</h3>";
        echo "<p>".htmlspecialchars($row['merchant'])."</p>";
        echo "<p class='value'>".htmlspecialchars($row['value_text'])."</p>";
        echo "<p class='small'>".htmlspecialchars($row['nickname'])." (...".htmlspecialchars($row['last_four']).")</p>";

        if ($row['expires_on'] != null) {
            echo "<p class='small'>Expires ".date('d M Y', strtotime($row['expires_on']))."</p>";
        }

        echo "<form method='post'>";
        echo "<input type='hidden' name='toggle_id' value='".$row['id']."'>";
        echo "<button>".($row['is_used'] ? "Mark unused" : "Mark as used")."</button>";
        echo "</form>";

        echo "</div>";
    }
} else {
    echo "<p>No offers yet.</p>";
}
?>

</div>

</body>
</html>
