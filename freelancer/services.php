<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('freelancer');

$freelancerId = current_id();
$allServices = MasterData::services();
$mine = Freelancer::getServices($freelancerId);
$myPrices = [];
foreach ($mine as $m) {
    $myPrices[(int) $m['service_id']] = ['price' => $m['price'], 'price_unit' => $m['price_unit']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $selected = array_map('intval', $_POST['selected'] ?? []);
    $rows = [];
    foreach ($selected as $serviceId) {
        $price = (float) ($_POST['price'][$serviceId] ?? 0);
        $unit = $_POST['price_unit'][$serviceId] ?? 'daily';
        if (!in_array($unit, ['hourly', 'daily', 'per_job'], true)) {
            $unit = 'daily';
        }
        if ($price > 0) {
            $rows[] = ['service_id' => $serviceId, 'price' => $price, 'price_unit' => $unit];
        }
    }
    Freelancer::attachServices($freelancerId, $rows);
    flash('success', 'Services updated.');
    redirect(rtrim(APP_URL, '/') . '/freelancer/services.php');
}

$pageTitle = 'My Services';
require __DIR__ . '/../includes/header.php';
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <h1>My services &amp; pricing</h1>
            <p>Tick what you offer and set your own price. Clients see this on your public profile.</p>
        </div>
    </div>

    <form method="post">
        <?= csrf_field() ?>
        <?php foreach ($allServices as $s): ?>
        <?php $sid = (int) $s['id']; $checked = isset($myPrices[$sid]); ?>
        <div class="card" style="margin-bottom:12px;">
            <div class="checkbox-item" style="margin-bottom:<?= $checked ? '10px' : '0' ?>;">
                <input type="checkbox" id="svc_<?= $sid ?>" name="selected[]" value="<?= $sid ?>"
                       data-toggle-price="<?= $sid ?>" <?= $checked ? 'checked' : '' ?>>
                <label for="svc_<?= $sid ?>" style="font-weight:600;"><?= e($s['name']) ?></label>
            </div>
            <div class="field-row" id="price_row_<?= $sid ?>" style="<?= $checked ? '' : 'display:none;' ?>">
                <div class="field" style="margin-bottom:0;">
                    <label for="price_<?= $sid ?>">Price (RM)</label>
                    <input type="number" id="price_<?= $sid ?>" name="price[<?= $sid ?>]" min="0" step="0.01" value="<?= e((string) ($myPrices[$sid]['price'] ?? '')) ?>">
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label for="unit_<?= $sid ?>">Per</label>
                    <select id="unit_<?= $sid ?>" name="price_unit[<?= $sid ?>]">
                        <option value="hourly" <?= ($myPrices[$sid]['price_unit'] ?? '') === 'hourly' ? 'selected' : '' ?>>Hour</option>
                        <option value="daily" <?= ($myPrices[$sid]['price_unit'] ?? 'daily') === 'daily' ? 'selected' : '' ?>>Day</option>
                        <option value="per_job" <?= ($myPrices[$sid]['price_unit'] ?? '') === 'per_job' ? 'selected' : '' ?>>Job</option>
                    </select>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary">Save services</button>
    </form>
</div>
<script>
document.querySelectorAll('[data-toggle-price]').forEach(function (cb) {
    cb.addEventListener('change', function () {
        var row = document.getElementById('price_row_' + this.dataset.togglePrice);
        row.style.display = this.checked ? '' : 'none';
    });
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
