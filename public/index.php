<?php
session_start();
require_once __DIR__ . '/../src/LicenseManager.php';

$manager = new LicenseManager();
$msg = '';

if ($_POST) {
    $act = $_POST['action'] ?? '';
    if ($act === 'generate') {
        $r = $manager->generate($_POST);
        $_SESSION['keys'] = $r['licenses'];
        $msg = "✅ Generated {$r['count']} keys!";
    } elseif ($act === 'revoke') {
        $manager->revoke($_POST['license_key']);
        $msg = "✅ License revoked!";
    } elseif ($act === 'validate') {
        $_SESSION['vresult'] = $manager->validate($_POST['license_key']);
    }
}

$stats = $manager->getStats();
$licenses = $manager->getAll();
$keys = $_SESSION['keys'] ?? null;
$vresult = $_SESSION['vresult'] ?? null;
unset($_SESSION['keys'], $_SESSION['vresult']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Manager</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:Arial,sans-serif;background:#0a0a1a;color:#eee;padding:15px}
        .container{max-width:1200px;margin:0 auto}
        h1{color:#0f8;margin-bottom:20px;font-size:24px}
        .stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:20px}
        .stat{background:#1a1a2e;padding:15px;border-radius:8px;text-align:center}
        .stat .num{font-size:28px;color:#0f8;font-weight:bold}
        .stat .label{color:#888;font-size:12px}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px}
        @media(max-width:700px){.grid{grid-template-columns:1fr}}
        .card{background:#1a1a2e;padding:20px;border-radius:10px}
        .card h2{color:#0f8;font-size:16px;margin-bottom:15px;border-bottom:1px solid #333;padding-bottom:10px}
        label{display:block;margin-bottom:5px;color:#aaa;font-size:13px}
        input,select{width:100%;padding:10px;margin-bottom:12px;border:1px solid #333;border-radius:6px;background:#0a0a1a;color:#fff;font-size:14px}
        input:focus,select:focus{border-color:#0f8;outline:none}
        .btn{width:100%;padding:12px;border:none;border-radius:6px;cursor:pointer;font-weight:bold;font-size:14px}
        .btn-primary{background:#0f8;color:#000}
        .btn-danger{background:#d33;color:#fff}
        .btn:hover{opacity:0.9}
        .msg{background:#0f83;padding:12px;border-radius:6px;margin-bottom:15px;color:#0f8}
        .key-box{background:#0a0a1a;border:1px solid #0f8;padding:10px;border-radius:6px;margin:8px 0;font-family:monospace;font-size:13px;color:#0f8;word-break:break-all}
        .vbox{padding:15px;border-radius:8px;margin-top:15px}
        .vbox.valid{background:#0f82;border:1px solid #0f8}
        .vbox.invalid{background:#d332;border:1px solid #d33}
        table{width:100%;border-collapse:collapse;font-size:12px}
        th,td{padding:10px;text-align:left;border-bottom:1px solid #333}
        th{background:#0a0a1a;color:#0f8}
        .badge{padding:3px 8px;border-radius:10px;font-size:10px}
        .badge.active{background:#0f83;color:#0f8}
        .badge.revoked{background:#d333;color:#d33}
        .copy-btn{background:#333;color:#fff;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;font-size:11px}
        .scroll-x{overflow-x:auto}
    </style>
</head>
<body>
<div class="container">
    <h1>🔐 License Manager</h1>
    
    <?php if($msg): ?><div class="msg"><?=$msg?></div><?php endif; ?>
    
    <div class="stats">
        <div class="stat"><div class="num"><?=$stats['total']?></div><div class="label">Total</div></div>
        <div class="stat"><div class="num"><?=$stats['active']?></div><div class="label">Active</div></div>
        <div class="stat"><div class="num"><?=$stats['used']?></div><div class="label">Used</div></div>
    </div>
    
    <div class="grid">
        <div class="card">
            <h2>🔑 Generate License</h2>
            <form method="POST">
                <input type="hidden" name="action" value="generate">
                <label>Product Name</label>
                <input type="text" name="product_name" value="MyTool" required>
                <label>License Type</label>
                <select name="license_type">
                    <option value="TRIAL">Trial (30 days)</option>
                    <option value="BASIC">Basic (1 year)</option>
                    <option value="PRO" selected>Pro (1 year)</option>
                    <option value="ENTERPRISE">Enterprise (2 years)</option>
                    <option value="LIFETIME">Lifetime</option>
                </select>
                <label>Max Activations</label>
                <input type="number" name="max_activations" value="1" min="1">
                <label>Quantity</label>
                <input type="number" name="quantity" value="1" min="1" max="50">
                <label>Customer Email (optional)</label>
                <input type="email" name="customer_email" placeholder="customer@email.com">
                <button type="submit" class="btn btn-primary">Generate</button>
            </form>
            <?php if($keys): ?>
                <div style="margin-top:15px">
                    <strong style="color:#0f8">Generated Keys:</strong>
                    <?php foreach($keys as $k): ?>
                        <div class="key-box" onclick="navigator.clipboard.writeText('<?=$k['key']?>')"><?=$k['key']?> <small>(tap to copy)</small></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>🔍 Validate License</h2>
            <form method="POST">
                <input type="hidden" name="action" value="validate">
                <label>License Key</label>
                <input type="text" name="license_key" placeholder="XX-XXXX-XXXX-XXXX-XXXX-XXXX" required>
                <button type="submit" class="btn btn-primary">Validate</button>
            </form>
            <?php if($vresult): ?>
                <div class="vbox <?=$vresult['valid']?'valid':'invalid'?>">
                    <?php if($vresult['valid']): ?>
                        <strong>✅ Valid!</strong><br>
                        Type: <?=$vresult['license_type']?><br>
                        Product: <?=$vresult['product']?><br>
                        Expires: <?=$vresult['expires']??'Never'?><br>
                        Activations left: <?=$vresult['left']?>
                    <?php else: ?>
                        <strong>❌ Invalid</strong><br><?=$vresult['message']?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <h2>📋 All Licenses</h2>
        <div class="scroll-x">
            <table>
                <tr>
                    <th>License Key</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Used</th>
                    <th>Expires</th>
                    <th>Action</th>
                </tr>
                <?php foreach($licenses as $l): ?>
                <tr>
                    <td style="font-family:monospace;color:#0f8;font-size:11px"><?=$l['license_key']?></td>
                    <td><?=$l['license_type']?></td>
                    <td><span class="badge <?=$l['status']?>"><?=$l['status']?></span></td>
                    <td><?=$l['current_activations']?>/<?=$l['max_activations']?></td>
                    <td><?=$l['expires_at']?date('d M Y',strtotime($l['expires_at'])):'Never'?></td>
                    <td>
                        <button class="copy-btn" onclick="navigator.clipboard.writeText('<?=$l['license_key']?>')">Copy</button>
                        <?php if($l['status']==='active'): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="revoke">
                            <input type="hidden" name="license_key" value="<?=$l['license_key']?>">
                            <button type="submit" class="copy-btn" style="background:#d33" onclick="return confirm('Revoke?')">Revoke</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>
