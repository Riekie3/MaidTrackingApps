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

    <div class="candidate-grid">
        <?php if (!$result['rows']): ?>
            <p class="muted">No housemaids match those filters yet.</p>
        <?php endif; ?>
        <?php foreach ($result['rows'] as $h): ?>
        <?php $age = calculate_age($h['date_of_birth']); ?>
        <a class="candidate-card" href="<?= e(rtrim(APP_URL, '/')) ?>/client/candidate.php?id=<?= (int) $h['id'] ?>">
            <?php if ($h['photo_path']): ?>
                <img class="cc-photo" src="<?= e(rtrim(APP_URL, '/')) ?>/download.php?kind=housemaid_photo&id=<?= (int) $h['id'] ?>" alt="<?= e($h['full_name']) ?>" loading="lazy">
            <?php else: ?>
                <div class="cc-photo-placeholder" aria-hidden="true">🧑‍🍳</div>
            <?php endif; ?>
            <div class="cc-body">
                <div class="cc-name"><?= e($h['full_name']) ?></div>
                <div class="cc-meta">
                    <?= e($h['nationality_name'] ?? '—') ?><?php if ($age !== null): ?> · <?= $age ?> yrs old<?php endif; ?>
                    <?php if ($h['avg_rating']): ?> · ★ <?= e(number_format((float) $h['avg_rating'], 1)) ?><?php endif; ?>
                </div>
                <div class="cc-tags">
                    <span class="pill <?= e($h['availability_status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $h['availability_status']))) ?></span>
                    <span class="muted" style="font-size:12px;"><?= e($h['agency_name']) ?></span>
                </div>
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
