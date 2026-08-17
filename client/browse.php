<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('client');

$filters = [
    'skill_id' => $_GET['skill_id'] ?? '',
    'nationality_country_id' => $_GET['nationality_country_id'] ?? '',
    'min_experience' => $_GET['min_experience'] ?? '',
    'availability_status' => $_GET['availability_status'] ?? '',
];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;

$result = Housemaid::browse($filters, $page, $perPage);
$totalPages = (int) ceil($result['total'] / $perPage);

$skills = MasterData::skills();
$countries = MasterData::countries();

$pageTitle = 'Browse Housemaids';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1>Browse housemaids</h1>
            <p><?= $result['total'] ?> approved profile<?= $result['total'] === 1 ? '' : 's' ?></p>
        </div>
    </div>

    <form method="get" class="card" style="margin-bottom:22px;">
        <div class="field-row">
            <div class="field" style="margin-bottom:0;">
                <label for="skill_id">Skill</label>
                <select id="skill_id" name="skill_id">
                    <option value="">Any</option>
                    <?php foreach ($skills as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= (string) $filters['skill_id'] === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="margin-bottom:0;">
                <label for="nationality_country_id">Nationality</label>
                <select id="nationality_country_id" name="nationality_country_id">
                    <option value="">Any</option>
                    <?php foreach ($countries as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (string) $filters['nationality_country_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="margin-bottom:0;">
                <label for="min_experience">Min. experience (years)</label>
                <input type="number" id="min_experience" name="min_experience" min="0" max="60" value="<?= e((string) $filters['min_experience']) ?>">
            </div>
            <div class="field" style="margin-bottom:0;">
                <label for="availability_status">Availability</label>
                <select id="availability_status" name="availability_status">
                    <option value="">Any</option>
                    <option value="available" <?= $filters['availability_status'] === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="placed" <?= $filters['availability_status'] === 'placed' ? 'selected' : '' ?>>Placed</option>
                </select>
            </div>
        </div>
        <div class="btn-row" style="margin-top:14px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a class="btn btn-ghost" href="<?= e(rtrim(APP_URL, '/')) ?>/client/browse.php">Clear</a>
        </div>
    </form>

    <div class="quick-links">
        <?php if (!$result['rows']): ?>
            <p class="muted">No housemaids match those filters yet.</p>
        <?php endif; ?>
        <?php foreach ($result['rows'] as $h): ?>
        <a class="quick-link" href="<?= e(rtrim(APP_URL, '/')) ?>/client/candidate.php?id=<?= (int) $h['id'] ?>">
            <div class="ql-title"><?= e($h['full_name']) ?></div>
            <div class="ql-desc">
                <?= e($h['nationality_name'] ?? '—') ?> · <?= e((string) ($h['years_experience'] ?? 0)) ?> yrs
                <?php if ($h['avg_rating']): ?> · ★ <?= e(number_format((float) $h['avg_rating'], 1)) ?><?php endif; ?>
            </div>
            <div style="margin-top:8px;">
                <span class="pill <?= e($h['availability_status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $h['availability_status']))) ?></span>
                <span class="muted" style="font-size:12px;"> · <?= e($h['agency_name']) ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="btn-row" style="margin-top:22px;justify-content:center;">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>"
               href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
