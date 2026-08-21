<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('client');

// Agency housemaids and freelancers have genuinely different filter
// dimensions (skill/nationality/experience vs. service/location) and
// pagination sources, so this is a tab switch rather than one merged
// grid — simpler and more honest than half-merging two heterogeneous
// result sets.
$type = ($_GET['type'] ?? 'housemaid') === 'freelancer' ? 'freelancer' : 'housemaid';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;

if ($type === 'housemaid') {
    $filters = [
        'skill_id' => $_GET['skill_id'] ?? '',
        'nationality_country_id' => $_GET['nationality_country_id'] ?? '',
        'min_experience' => $_GET['min_experience'] ?? '',
        'availability_status' => $_GET['availability_status'] ?? '',
    ];
    $result = Housemaid::browse($filters, $page, $perPage);
    $skills = MasterData::skills();
    $countries = MasterData::countries();
} else {
    $filters = [
        'service_id' => $_GET['service_id'] ?? '',
        'location_id' => $_GET['location_id'] ?? '',
        'nationality_country_id' => $_GET['nationality_country_id'] ?? '',
    ];
    $result = Freelancer::browse($filters, $page, $perPage);
    $services = MasterData::services();
    $locations = MasterData::locations();
    $countries = MasterData::countries();
}

$totalPages = (int) ceil($result['total'] / $perPage);

$pageTitle = 'Browse Housemaids';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1>Browse housemaids</h1>
            <p><?= $result['total'] ?> <?= $type === 'freelancer' ? 'freelancer' : 'agency' ?> profile<?= $result['total'] === 1 ? '' : 's' ?></p>
        </div>
    </div>

    <div class="nav-links" style="margin-bottom:18px;background:var(--paper-raised);border:1px solid var(--line);border-radius:8px;padding:6px;display:inline-flex;">
        <a href="?type=housemaid" class="<?= $type === 'housemaid' ? 'active' : '' ?>">Agency housemaids</a>
        <a href="?type=freelancer" class="<?= $type === 'freelancer' ? 'active' : '' ?>">Independent freelancers</a>
    </div>

    <?php if ($type === 'housemaid'): ?>
    <form method="get" class="card" style="margin-bottom:22px;">
        <input type="hidden" name="type" value="housemaid">
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
            <a class="btn btn-ghost" href="<?= e(rtrim(APP_URL, '/')) ?>/client/browse.php?type=housemaid">Clear</a>
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

    <?php else: ?>
    <form method="get" class="card" style="margin-bottom:22px;">
        <input type="hidden" name="type" value="freelancer">
        <div class="field-row">
            <div class="field" style="margin-bottom:0;">
                <label for="service_id">Service</label>
                <select id="service_id" name="service_id">
                    <option value="">Any</option>
                    <?php foreach ($services as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= (string) $filters['service_id'] === (string) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="margin-bottom:0;">
                <label for="location_id">Location</label>
                <select id="location_id" name="location_id">
                    <option value="">Any</option>
                    <?php $curState = null; foreach ($locations as $l): ?>
                        <?php if ($l['state'] !== $curState): ?>
                            <?php if ($curState !== null): ?></optgroup><?php endif; ?>
                            <optgroup label="<?= e($l['state']) ?>">
                            <?php $curState = $l['state']; ?>
                        <?php endif; ?>
                        <option value="<?= (int) $l['id'] ?>" <?= (string) $filters['location_id'] === (string) $l['id'] ? 'selected' : '' ?>><?= e($l['name']) ?></option>
                    <?php endforeach; ?>
                    <?php if ($curState !== null): ?></optgroup><?php endif; ?>
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
        </div>
        <div class="btn-row" style="margin-top:14px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a class="btn btn-ghost" href="<?= e(rtrim(APP_URL, '/')) ?>/client/browse.php?type=freelancer">Clear</a>
        </div>
    </form>

    <div class="candidate-grid">
        <?php if (!$result['rows']): ?>
            <p class="muted">No freelancers match those filters yet.</p>
        <?php endif; ?>
        <?php foreach ($result['rows'] as $f): ?>
        <?php $age = calculate_age($f['date_of_birth']); $svcs = Freelancer::getServices((int) $f['id']); ?>
        <a class="candidate-card" href="<?= e(rtrim(APP_URL, '/')) ?>/client/freelancer.php?id=<?= (int) $f['id'] ?>">
            <?php if ($f['photo_path']): ?>
                <img class="cc-photo" src="<?= e(rtrim(APP_URL, '/')) ?>/download.php?kind=freelancer_photo&id=<?= (int) $f['id'] ?>" alt="<?= e($f['full_name']) ?>" loading="lazy">
            <?php else: ?>
                <div class="cc-photo-placeholder" aria-hidden="true">🧑‍🍳</div>
            <?php endif; ?>
            <div class="cc-body">
                <div class="cc-name"><?= e($f['full_name']) ?></div>
                <div class="cc-meta">
                    <?= e($f['nationality_name'] ?? '—') ?><?php if ($age !== null): ?> · <?= $age ?> yrs old<?php endif; ?>
                    <?php if ($f['avg_rating']): ?> · ★ <?= e(number_format((float) $f['avg_rating'], 1)) ?><?php endif; ?>
                </div>
                <div class="cc-tags">
                    <span class="pill available">Freelancer</span>
                    <span class="muted" style="font-size:12px;">
                        <?= $svcs ? e($svcs[0]['service_name']) . (count($svcs) > 1 ? ' +' . (count($svcs) - 1) : '') . ' from RM' . e(number_format((float) min(array_column($svcs, 'price')), 0)) : 'No services listed' ?>
                    </span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

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
