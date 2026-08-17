<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$tab = $_GET['tab'] ?? 'skills';
if (!in_array($tab, ['skills', 'languages', 'countries'], true)) {
    $tab = 'skills';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $formTab = $_POST['tab'] ?? 'skills';

    if (isset($_POST['delete_id'])) {
        $id = (int) $_POST['delete_id'];
        match ($formTab) {
            'skills'    => MasterData::deleteSkill($id),
            'languages' => MasterData::deleteLanguage($id),
            'countries' => MasterData::deleteCountry($id),
            default     => null,
        };
        flash('success', 'Removed.');
    } else {
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            if ($formTab === 'skills') {
                MasterData::addSkill($name, trim($_POST['category'] ?? '') ?: null);
            } elseif ($formTab === 'languages') {
                MasterData::addLanguage($name);
            } elseif ($formTab === 'countries') {
                $iso = trim($_POST['iso_code'] ?? '');
                if (strlen($iso) === 2) {
                    MasterData::addCountry($name, $iso);
                } else {
                    flash('error', 'Country ISO code must be exactly 2 letters.');
                    redirect(rtrim(APP_URL, '/') . '/admin/master_data.php?tab=countries');
                }
            }
            flash('success', 'Added.');
        }
    }
    redirect(rtrim(APP_URL, '/') . '/admin/master_data.php?tab=' . $formTab);
}

$skills = MasterData::skills();
$languages = MasterData::languages();
$countries = MasterData::countries();

$pageTitle = 'Master Data';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1>Master Data</h1>
            <p>Lists shared across every agency and profile.</p>
        </div>
    </div>

    <div class="nav-links" style="margin-bottom:18px;background:var(--paper-raised);border:1px solid var(--line);border-radius:8px;padding:6px;display:inline-flex;">
        <a href="?tab=skills" class="<?= $tab === 'skills' ? 'active' : '' ?>">Skills</a>
        <a href="?tab=languages" class="<?= $tab === 'languages' ? 'active' : '' ?>">Languages</a>
        <a href="?tab=countries" class="<?= $tab === 'countries' ? 'active' : '' ?>">Countries</a>
    </div>

    <?php if ($tab === 'skills'): ?>
    <div class="card" style="margin-bottom:18px;">
        <form method="post" class="field-row" style="align-items:end;">
            <?= csrf_field() ?>
            <input type="hidden" name="tab" value="skills">
            <div class="field" style="margin-bottom:0;"><label>Skill name</label><input type="text" name="name" required></div>
            <div class="field" style="margin-bottom:0;"><label>Category</label><input type="text" name="category" placeholder="e.g. cooking"></div>
            <button type="submit" class="btn btn-primary">Add skill</button>
        </form>
    </div>
    <div class="table-wrap"><table><thead><tr><th>Name</th><th>Category</th><th></th></tr></thead><tbody>
        <?php foreach ($skills as $s): ?>
        <tr><td><?= e($s['name']) ?></td><td class="muted"><?= e($s['category'] ?? '—') ?></td>
        <td><form method="post" data-confirm="Remove this skill?"><?= csrf_field() ?><input type="hidden" name="tab" value="skills"><input type="hidden" name="delete_id" value="<?= (int) $s['id'] ?>"><button class="btn btn-sm btn-ghost" type="submit">Remove</button></form></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>

    <?php if ($tab === 'languages'): ?>
    <div class="card" style="margin-bottom:18px;">
        <form method="post" class="field-row" style="align-items:end;">
            <?= csrf_field() ?>
            <input type="hidden" name="tab" value="languages">
            <div class="field" style="margin-bottom:0;"><label>Language name</label><input type="text" name="name" required></div>
            <button type="submit" class="btn btn-primary">Add language</button>
        </form>
    </div>
    <div class="table-wrap"><table><thead><tr><th>Name</th><th></th></tr></thead><tbody>
        <?php foreach ($languages as $l): ?>
        <tr><td><?= e($l['name']) ?></td>
        <td><form method="post" data-confirm="Remove this language?"><?= csrf_field() ?><input type="hidden" name="tab" value="languages"><input type="hidden" name="delete_id" value="<?= (int) $l['id'] ?>"><button class="btn btn-sm btn-ghost" type="submit">Remove</button></form></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>

    <?php if ($tab === 'countries'): ?>
    <div class="card" style="margin-bottom:18px;">
        <form method="post" class="field-row" style="align-items:end;">
            <?= csrf_field() ?>
            <input type="hidden" name="tab" value="countries">
            <div class="field" style="margin-bottom:0;"><label>Country name</label><input type="text" name="name" required></div>
            <div class="field" style="margin-bottom:0;max-width:100px;"><label>ISO code</label><input type="text" name="iso_code" maxlength="2" placeholder="MY" required></div>
            <button type="submit" class="btn btn-primary">Add country</button>
        </form>
    </div>
    <div class="table-wrap"><table><thead><tr><th>Name</th><th>ISO</th><th></th></tr></thead><tbody>
        <?php foreach ($countries as $c): ?>
        <tr><td><?= e($c['name']) ?></td><td class="mono"><?= e($c['iso_code']) ?></td>
        <td><form method="post" data-confirm="Remove this country?"><?= csrf_field() ?><input type="hidden" name="tab" value="countries"><input type="hidden" name="delete_id" value="<?= (int) $c['id'] ?>"><button class="btn btn-sm btn-ghost" type="submit">Remove</button></form></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
