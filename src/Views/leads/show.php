<?php
$aiData = $aiData ?? [];
$needsRefresh = $needsRefresh ?? true;
$project = $project ?? null;
$formatLocation = function (array $lead): string {
    $city = trim($lead['city'] ?? '');
    $state = trim($lead['state_province'] ?? '');
    $country = trim($lead['country'] ?? '');
    $parts = array_filter([$city, $state], fn ($value) => $value !== '');
    $location = implode(', ', $parts);
    if ($country !== '') {
        $location = $location !== '' ? $location . ' (' . $country . ')' : $country;
    }
    return $location;
};
?>
<div class="row g-4" data-lead-detail data-lead-id="<?= $lead['id'] ?>">
    <div class="col-12">
        <a href="<?= BASE_URL ?>/?route=leads" class="btn btn-link"><i class="bi bi-arrow-left"></i> Back to leads</a>
    </div>
    <div class="col-lg-4">
        <div class="card card-shadow mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="card-title mb-1">Company Overview</h5>
                        <p class="text-muted mb-3">High-level lead profile</p>
                    </div>
                    <span class="badge bg-primary">Lead Score <?= (int)$lead['lead_score'] ?></span>
                </div>
                <p class="mb-1"><strong><?= htmlspecialchars($lead['company_name']) ?></strong></p>
                <?php if (!empty($lead['website'])): ?>
                    <p class="mb-1"><a href="<?= htmlspecialchars($lead['website']) ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i> <?= htmlspecialchars($lead['website']) ?></a></p>
                <?php endif; ?>
                <p class="text-muted mb-1">Industry: <?= htmlspecialchars($lead['industry'] ?? '—') ?></p>
                <p class="text-muted mb-1">Size: <?= htmlspecialchars($lead['company_size'] ?? '—') ?></p>
                <p class="text-muted mb-1">Location: <?= $formatLocation($lead) !== '' ? htmlspecialchars($formatLocation($lead)) : '—' ?></p>
                <?php if (!empty($lead['contact_name']) || !empty($lead['contact_email']) || !empty($lead['contact_phone'])): ?>
                    <hr>
                    <p class="fw-semibold mb-1">Primary Contact</p>
                    <?php if (!empty($lead['contact_name'])): ?><p class="mb-0"><?= htmlspecialchars($lead['contact_name']) ?></p><?php endif; ?>
                    <?php if (!empty($lead['contact_email'])): ?><p class="mb-0"><a href="mailto:<?= htmlspecialchars($lead['contact_email']) ?>"><?= htmlspecialchars($lead['contact_email']) ?></a></p><?php endif; ?>
                    <?php if (!empty($lead['contact_phone'])): ?><p class="mb-0 text-muted"><?= htmlspecialchars($lead['contact_phone']) ?></p><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="card card-shadow mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h5 class="card-title mb-1">AI Insight</h5>
                        <small class="text-muted">Pain points & triggers</small>
                    </div>
                    <span class="badge <?= $needsRefresh ? 'bg-warning text-dark' : 'bg-success' ?>" id="aiStatusBadge">
                        <?= $needsRefresh ? 'Needs refresh' : 'Fresh insight' ?>
                    </span>
                </div>
                <p id="aiInsightSummary" class="mb-3">
                    <?= !empty($aiData['insight_summary']) ? nl2br(htmlspecialchars($aiData['insight_summary'])) : '<span class="text-muted">Click Generate / Refresh AI to craft a personalized narrative.</span>' ?>
                </p>
                <?php if (!empty($aiData['app_benefits'])): ?>
                    <p class="fw-semibold small text-uppercase text-muted mb-2">Likely leverage points</p>
                    <ul class="ps-3" id="aiInsightPoints">
                        <?php foreach (array_slice($aiData['app_benefits'], 0, 3) as $benefit): ?>
                            <li><?= htmlspecialchars($benefit) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <ul class="ps-3 d-none" id="aiInsightPoints"></ul>
                <?php endif; ?>
                <small class="text-muted" id="aiLastGenerated">
                    <?php if (!empty($aiData['last_generated_at'])): ?>
                        Last refreshed <?= date('M j, Y g:i A', strtotime($aiData['last_generated_at'])) ?>
                    <?php else: ?>
                        Not generated yet
                    <?php endif; ?>
                </small>
            </div>
        </div>
        <?php if ($project): ?>
            <div class="card card-shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">Dev Pipeline</h5>
                            <small class="text-muted">Project seeded when lead locked in.</small>
                        </div>
                        <span class="badge bg-info text-dark"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $project['status'] ?? 'backlog'))) ?></span>
                    </div>
                    <p class="mt-3 mb-2"><strong><?= htmlspecialchars($project['project_name']) ?></strong></p>
                    <a href="<?= BASE_URL ?>/?route=projects/view&id=<?= $project['id'] ?>" class="btn btn-outline-primary btn-sm">Open in Dev Pipeline</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-8">
        <div class="card card-shadow mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4 class="mb-1">AI Playbook</h4>
                        <small class="text-muted">Instant collateral for this lead</small>
                    </div>
                    <div class="text-end">
                        <button class="btn btn-outline-primary btn-sm mb-2" id="generateAiBtn" data-generate-ai data-lead-id="<?= $lead['id'] ?>">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                            <span>Generate / Refresh AI</span>
                        </button>
                        <div><small class="text-muted">Est. price range updates automatically.</small></div>
                    </div>
                </div>
                <div id="aiErrorAlert" class="alert alert-danger d-none mt-3"></div>
                <ul class="nav nav-tabs mt-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Overview</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="call-tab" data-bs-toggle="tab" data-bs-target="#call-script" type="button" role="tab">Call Script</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">Email Pitch</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="proposal-tab" data-bs-toggle="tab" data-bs-target="#proposal" type="button" role="tab">Proposal</button>
                    </li>
                </ul>
                <div class="tab-content pt-4">
                    <div class="tab-pane fade show active" id="overview" role="tabpanel">
                        <?php if (!empty($aiData['app_type'])): ?>
                            <div class="d-flex align-items-start justify-content-between mb-3">
                                <div>
                                    <span class="badge bg-primary" id="aiAppType"><?= htmlspecialchars($aiData['app_type']) ?></span>
                                    <p class="mt-3 mb-0" id="aiAppConcept"><?= nl2br(htmlspecialchars($aiData['app_concept'] ?? '')) ?></p>
                                </div>
                                <div class="text-end">
                                    <p class="mb-0 text-muted small">Estimated Investment</p>
                                    <p class="fs-5 mb-0" id="aiPriceRange">
                                        <?php if (!empty($aiData['price_min']) && !empty($aiData['price_max'])): ?>
                                            $<?= number_format((float)$aiData['price_min']) ?> - $<?= number_format((float)$aiData['price_max']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Key Features</h6>
                                    <ul class="ps-3" id="aiFeaturesList">
                                        <?php foreach ($aiData['app_features'] as $feature): ?>
                                            <li><?= htmlspecialchars($feature) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Business Benefits</h6>
                                    <ul class="ps-3" id="aiBenefitsList">
                                        <?php foreach ($aiData['app_benefits'] as $benefit): ?>
                                            <li><?= htmlspecialchars($benefit) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                            <small class="text-muted">Estimates generated via AI—validate scope before quoting.</small>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">No AI package yet. Click “Generate / Refresh AI” to craft the tailored offer.</div>
                        <?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="call-script" role="tabpanel">
                        <pre class="bg-light p-3 rounded" id="aiCallScript"><?= !empty($aiData['call_script']) ? htmlspecialchars($aiData['call_script']) : 'AI call script will appear here.' ?></pre>
                        <h6>Key Talking Points</h6>
                        <ul id="aiTalkingPoints">
                            <?php foreach ($aiData['talking_points'] ?? [] as $point): ?>
                                <li><?= htmlspecialchars($point) ?></li>
                            <?php endforeach; ?>
                            <?php if (empty($aiData['talking_points'])): ?>
                                <li class="text-muted">Refresh AI to surface the sharpest angles.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="tab-pane fade" id="email" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <p class="mb-1 text-muted">Suggested Subject</p>
                                <p class="mb-0" id="aiEmailSubject"><?= htmlspecialchars($aiData['email_subject'] ?? '—') ?></p>
                            </div>
                            <button class="btn btn-outline-secondary btn-sm" data-copy-target="#aiEmailBody"><i class="bi bi-clipboard"></i> Copy Email</button>
                        </div>
                        <pre class="bg-light p-3 rounded" id="aiEmailBody"><?= !empty($aiData['email_body']) ? htmlspecialchars($aiData['email_body']) : 'Email body will populate after running AI.' ?></pre>
                    </div>
                    <div class="tab-pane fade" id="proposal" role="tabpanel">
                        <div class="d-flex justify-content-end gap-2 mb-3">
                            <button class="btn btn-outline-secondary btn-sm" data-copy-target="#aiProposalText"><i class="bi bi-clipboard"></i> Copy Proposal</button>
                            <button class="btn btn-outline-primary btn-sm" data-download-proposal data-lead-id="<?= $lead['id'] ?>"><i class="bi bi-download"></i> Download .txt</button>
                        </div>
                        <div class="proposal-content" id="aiProposalText">
                            <?= !empty($aiData['full_proposal']) ? nl2br(htmlspecialchars($aiData['full_proposal'])) : '<div class="alert alert-info">Generate AI collateral to see the full proposal.</div>' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
