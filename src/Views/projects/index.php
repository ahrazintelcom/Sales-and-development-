<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3>Development Pipeline</h3>
        <p class="text-muted">Track Locked-In clients across delivery stages.</p>
    </div>
</div>
<?php
$groups = [
    'backlog' => [],
    'in_progress' => [],
    'qa' => [],
    'done' => [],
];
foreach ($projects as $project) {
    if (!isset($groups[$project['status']])) {
        $groups[$project['status']] = [];
    }
    $groups[$project['status']][] = $project;
}
$labels = [
    'backlog' => 'Backlog',
    'in_progress' => 'In Progress',
    'qa' => 'QA',
    'done' => 'Done',
];
?>
<div class="row g-4">
    <?php foreach ($groups as $status => $items): ?>
        <div class="col-lg-3">
            <div class="card card-shadow h-100">
                <div class="card-header bg-light fw-semibold text-uppercase"><?= $labels[$status] ?? ucfirst(str_replace('_',' ',$status)) ?></div>
                <div class="card-body">
                    <?php foreach ($items as $project): ?>
                        <div class="border rounded p-3 mb-3">
                            <h6><?= htmlspecialchars($project['project_name']) ?></h6>
                            <p class="text-muted small mb-1">Client: <?= htmlspecialchars($project['company_name']) ?></p>
                            <p class="text-muted small">App Type: <?= htmlspecialchars($project['app_type']) ?></p>
                            <a href="/?route=projects/view&id=<?= $project['id'] ?>" class="btn btn-sm btn-outline-primary w-100">View Project</a>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($items)): ?>
                        <p class="text-muted small">No projects yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
