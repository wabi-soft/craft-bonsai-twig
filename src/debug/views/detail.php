<?php
/** @var wabisoft\bonsaitwig\debug\BonsaiTwigPanel $panel */

$resolutions = $panel->data['resolutions'] ?? [];
$droppedCount = $panel->data['droppedCount'] ?? 0;
$beastmodeParam = $panel->data['beastmodeParam'] ?? '';
$hasCategories = $panel->data['hasCategories'] ?? false;
$hasCommerce = $panel->data['hasCommerce'] ?? false;

$pageUrl = $panel->data['pageUrl'] ?? '';
$siteHandle = $panel->data['siteHandle'] ?? null;
$activeModes = $beastmodeParam !== '' ? array_map('trim', explode(',', $beastmodeParam)) : [];

$typeCounts = [];
foreach ($resolutions as $r) {
    $type = $r['type'] ?? 'unknown';
    $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
}
?>
<style>
    .bonsai-panel { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    .bonsai-panel h1 { font-size: 20px; margin: 0 0 16px; }
    .bonsai-panel h2 { font-size: 15px; margin: 20px 0 10px; border-bottom: 1px solid #ddd; padding-bottom: 6px; }
    .bonsai-panel .bonsai-toggles { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
    .bonsai-panel .bonsai-toggles label { display: flex; align-items: center; gap: 4px; padding: 4px 10px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; cursor: pointer; }
    .bonsai-panel .bonsai-toggles label:hover { background: #e8e8e8; }
    .bonsai-panel .bonsai-toggles label.active { background: #e3f2fd; border-color: #90caf9; }
    .bonsai-panel .bonsai-actions { display: flex; gap: 6px; margin-bottom: 16px; }
    .bonsai-panel .bonsai-actions button { padding: 4px 14px; border-radius: 4px; border: 1px solid #ccc; background: #fff; font-size: 13px; cursor: pointer; }
    .bonsai-panel .bonsai-actions button:hover { background: #f0f0f0; }
    .bonsai-panel .bonsai-actions .bonsai-apply { background: #e8f5e9; border-color: #a5d6a7; color: #2e7d32; }
    .bonsai-panel .bonsai-actions .bonsai-disable { background: #fbe9e7; border-color: #ef9a9a; color: #c62828; }
    .bonsai-panel table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .bonsai-panel th { text-align: left; padding: 6px 10px; background: #f5f5f5; border-bottom: 2px solid #ddd; font-weight: 600; }
    .bonsai-panel td { padding: 6px 10px; border-bottom: 1px solid #eee; vertical-align: top; }
    .bonsai-panel tr:hover td { background: #fafafa; }
    .bonsai-panel .bonsai-tag { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 11px; font-weight: 600; }
    .bonsai-panel .bonsai-tag-hit { background: #e8f5e9; color: #2e7d32; }
    .bonsai-panel .bonsai-tag-miss { background: #fbe9e7; color: #c62828; }
    .bonsai-panel .bonsai-tried { display: none; margin-top: 6px; padding: 6px 8px; background: #f9f9f9; border-radius: 4px; font-size: 12px; font-family: monospace; }
    .bonsai-panel .bonsai-tried.open { display: block; }
    .bonsai-panel .bonsai-tried li { list-style: none; padding: 2px 0; }
    .bonsai-panel .bonsai-expand { cursor: pointer; color: #1976d2; text-decoration: underline; font-size: 12px; }
    .bonsai-panel .bonsai-empty { padding: 20px; text-align: center; color: #888; font-style: italic; }
    .bonsai-panel .bonsai-notice { padding: 8px 12px; background: #fff3e0; border: 1px solid #ffe0b2; border-radius: 4px; font-size: 12px; color: #e65100; margin-top: 10px; }
    .bonsai-panel .bonsai-meta { display: flex; gap: 16px; margin-bottom: 16px; font-size: 13px; color: #555; }
    .bonsai-panel .bonsai-meta strong { color: #333; }
</style>

<div class="bonsai-panel">
    <h1>TPL Routing</h1>

    <div class="bonsai-meta">
        <?php if ($siteHandle): ?>
            <span><strong>Site:</strong> <?= htmlspecialchars($siteHandle) ?></span>
        <?php endif; ?>
        <?php if (!empty($typeCounts)): ?>
            <span><strong>Resolved:</strong> <?= implode(', ', array_map(fn($type, $count) => "$count $type", array_keys($typeCounts), $typeCounts)) ?></span>
        <?php endif; ?>
    </div>

    <h2>Beast Mode</h2>
    <div class="bonsai-toggles" id="bonsai-toggles">
        <label class="<?= in_array('all', $activeModes) ? 'active' : '' ?>">
            <input type="checkbox" value="all" <?= in_array('all', $activeModes) ? 'checked' : '' ?>> All
        </label>
        <label class="<?= in_array('entry', $activeModes) ? 'active' : '' ?>">
            <input type="checkbox" value="entry" <?= in_array('entry', $activeModes) ? 'checked' : '' ?>> Entries
        </label>
        <label class="<?= in_array('item', $activeModes) ? 'active' : '' ?>">
            <input type="checkbox" value="item" <?= in_array('item', $activeModes) ? 'checked' : '' ?>> Items
        </label>
        <label class="<?= in_array('matrix', $activeModes) ? 'active' : '' ?>">
            <input type="checkbox" value="matrix" <?= in_array('matrix', $activeModes) ? 'checked' : '' ?>> Matrix
        </label>
        <label class="<?= in_array('asset', $activeModes) ? 'active' : '' ?>">
            <input type="checkbox" value="asset" <?= in_array('asset', $activeModes) ? 'checked' : '' ?>> Assets
        </label>
        <?php if ($hasCategories): ?>
        <label class="<?= in_array('category', $activeModes) ? 'active' : '' ?>">
            <input type="checkbox" value="category" <?= in_array('category', $activeModes) ? 'checked' : '' ?>> Categories
        </label>
        <?php endif; ?>
        <?php if ($hasCommerce): ?>
        <label class="<?= in_array('product', $activeModes) ? 'active' : '' ?>">
            <input type="checkbox" value="product" <?= in_array('product', $activeModes) ? 'checked' : '' ?>> Products
        </label>
        <?php endif; ?>
    </div>
    <div class="bonsai-actions">
        <button class="bonsai-apply" id="bonsai-apply">Apply</button>
        <button class="bonsai-disable" id="bonsai-disable">Disable All</button>
    </div>

    <h2>Template Resolutions (<?= count($resolutions) ?>)</h2>

    <?php if (empty($resolutions)): ?>
        <div class="bonsai-empty">No Bonsai templates rendered on this page.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Strategy</th>
                    <th>Resolved</th>
                    <th>Tried</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resolutions as $i => $r): ?>
                <tr>
                    <td><span class="bonsai-tag <?= $r['resolved'] ? 'bonsai-tag-hit' : 'bonsai-tag-miss' ?>"><?= htmlspecialchars($r['type']) ?></span></td>
                    <td><?= htmlspecialchars($r['strategy']) ?></td>
                    <td><?= $r['resolved'] ? htmlspecialchars($r['resolved']) : '<em style="color:#c62828">none</em>' ?></td>
                    <td>
                        <span class="bonsai-expand" data-row="<?= $i ?>"><?= count($r['tried']) ?> path<?= count($r['tried']) !== 1 ? 's' : '' ?></span>
                        <ul class="bonsai-tried" id="bonsai-tried-<?= $i ?>">
                            <?php foreach ($r['tried'] as $path): ?>
                            <li><?= $path === $r['resolvedPath'] ? '&#10003; ' : '&#10007; ' ?><?= htmlspecialchars($path) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($droppedCount > 0): ?>
        <div class="bonsai-notice"><?= $droppedCount ?> additional resolution<?= $droppedCount !== 1 ? 's' : '' ?> truncated (max <?= \wabisoft\bonsaitwig\debug\ResolutionCollector::MAX_ENTRIES ?>).</div>
    <?php endif; ?>
</div>

<script>
(function() {
    var pageUrl = <?= json_encode($pageUrl, JSON_HEX_TAG | JSON_HEX_AMP) ?>;

    document.querySelectorAll('.bonsai-expand').forEach(function(el) {
        el.addEventListener('click', function() {
            var tried = document.getElementById('bonsai-tried-' + this.dataset.row);
            tried.classList.toggle('open');
        });
    });

    var toggles = document.getElementById('bonsai-toggles');
    var allCb = toggles.querySelector('input[value="all"]');
    var typeCbs = toggles.querySelectorAll('input:not([value="all"])');

    allCb.addEventListener('change', function() {
        if (this.checked) {
            typeCbs.forEach(function(cb) { cb.checked = false; cb.closest('label').classList.remove('active'); });
            this.closest('label').classList.add('active');
        } else {
            this.closest('label').classList.remove('active');
        }
    });

    typeCbs.forEach(function(cb) {
        cb.addEventListener('change', function() {
            if (this.checked) {
                allCb.checked = false;
                allCb.closest('label').classList.remove('active');
                this.closest('label').classList.add('active');
            } else {
                this.closest('label').classList.remove('active');
            }
        });
    });

    document.getElementById('bonsai-apply').addEventListener('click', function() {
        var url = new URL(pageUrl, window.top.location.origin);
        if (allCb.checked) {
            url.searchParams.set('beastmode', 'all');
        } else {
            var types = [];
            typeCbs.forEach(function(cb) { if (cb.checked) types.push(cb.value); });
            if (types.length > 0) {
                url.searchParams.set('beastmode', types.join(','));
            } else {
                url.searchParams.delete('beastmode');
            }
        }
        window.top.location.href = url.href;
    });

    document.getElementById('bonsai-disable').addEventListener('click', function() {
        var url = new URL(pageUrl, window.top.location.origin);
        url.searchParams.delete('beastmode');
        window.top.location.href = url.href;
    });
})();
</script>
