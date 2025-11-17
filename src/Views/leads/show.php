<div class="row g-4">
    <div class="col-12">
        <a href="/?route=leads" class="btn btn-link"><i class="bi bi-arrow-left"></i> Back to leads</a>
    </div>
    <div class="col-lg-4">
        <div class="card card-shadow mb-4">
            <div class="card-body">
                <h5 class="card-title">Company Overview</h5>
                <p class="text-muted mb-1">Industry: <?= htmlspecialchars($lead['industry']) ?></p>
                <p class="text-muted mb-1">Size: <?= htmlspecialchars($lead['company_size']) ?></p>
                <p class="text-muted mb-1">Location: <?= htmlspecialchars($lead['city']) ?>, <?= htmlspecialchars($lead['state_province']) ?>, <?= htmlspecialchars($lead['country']) ?></p>
                <p class="text-muted mb-1">Website: <a href="<?= htmlspecialchars($lead['website']) ?>" target="_blank"><?= htmlspecialchars($lead['website']) ?></a></p>
                <hr>
                <p id="leadSummary"><?= htmlspecialchars($leadSummary['summary'] ?? $leadSummary) ?></p>
            </div>
        </div>
        <div class="card card-shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="card-title mb-0">Recommended Web Apps</h5>
                        <small class="text-muted">AI-tailored opportunities</small>
                    </div>
                    <button class="btn btn-outline-primary btn-sm" data-regenerate data-lead-id="<?= $lead['id'] ?>">
                        <span class="spinner-border spinner-border-sm d-none"></span>
                        <i class="bi bi-stars"></i> Regenerate
                    </button>
                </div>
                <div id="recommendedApps">
                    <?php foreach ($recommendedApps as $app): ?>
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between">
                                <strong><?= htmlspecialchars($app['app_name']) ?></strong>
                                <span class="badge bg-success">$<?= number_format($app['price_min']) ?> - $<?= number_format($app['price_max']) ?></span>
                            </div>
                            <p class="text-muted small mb-2"><?= htmlspecialchars($app['description']) ?></p>
                            <p class="fw-semibold mb-1">Key Modules</p>
                            <ul>
                                <?php foreach ($app['key_features'] as $feature): ?>
                                    <li><?= htmlspecialchars($feature) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <p class="fw-semibold mb-1">Benefits</p>
                            <ul>
                                <?php foreach ($app['benefits'] as $benefit): ?>
                                    <li><?= htmlspecialchars($benefit) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-shadow mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="card-title mb-0">Call Script</h5>
                        <small class="text-muted">Tailored to this lead</small>
                    </div>
                    <button class="btn btn-outline-primary btn-sm" data-regenerate data-lead-id="<?= $lead['id'] ?>">
                        <span class="spinner-border spinner-border-sm d-none"></span> Refresh Scripts
                    </button>
                </div>
                <pre class="bg-light p-3 rounded" id="callScript"><?= htmlspecialchars($salesScripts['call_script'] ?? '') ?></pre>
            </div>
        </div>
        <div class="card card-shadow mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Email Pitch</h5>
                    <button class="btn btn-outline-secondary btn-sm" data-proposal data-lead-id="<?= $lead['id'] ?>"><i class="bi bi-file-text"></i> Proposal Preview</button>
                </div>
                <pre class="bg-light p-3 rounded" id="emailScript"><?= htmlspecialchars($salesScripts['email_template'] ?? '') ?></pre>
            </div>
        </div>
        <div class="card card-shadow">
            <div class="card-body">
                <h5 class="card-title">Key Talking Points</h5>
                <ul id="talkingPoints">
                    <?php foreach ($salesScripts['talking_points'] ?? [] as $point): ?>
                        <li><?= htmlspecialchars($point) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="proposalModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Proposal Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="proposalBody"></div>
        </div>
    </div>
</div>
