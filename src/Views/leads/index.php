<?php
$discoveryError = $discoveryError ?? null;
$searchExecuted = $searchExecuted ?? false;
$aiLeadCount = $aiLeadCount ?? 0;
?>
<div class="row g-4">
    <div class="col-12">
        <div class="card card-shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="mb-0">Lead Discovery</h4>
                        <small class="text-muted">Filter and surface high intent prospects instantly.</small>
                    </div>
                    <div>
                        <form class="d-inline" method="post" action="<?= BASE_URL ?>/?route=leads/import" enctype="multipart/form-data">
                            <label class="btn btn-outline-secondary btn-sm mb-0">
                                <i class="bi bi-upload"></i> Import Leads CSV
                                <input type="file" name="csv" class="d-none" onchange="this.form.submit()" accept=".csv">
                            </label>
                        </form>
                    </div>
                </div>
                <?php if (!empty($_SESSION['flash_success'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['flash_error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
                <?php endif; ?>
                <form class="row g-3" method="get">
                    <input type="hidden" name="route" value="leads">
                    <div class="col-md-2">
                        <label class="form-label">Country</label>
                        <select class="form-select" name="country">
                            <option value="">All</option>
                            <?php foreach (['USA','Canada'] as $country): ?>
                                <option value="<?= $country ?>" <?= ($filters['country'] ?? '') === $country ? 'selected' : '' ?>><?= $country ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Industry</label>
                        <select class="form-select" name="industry">
                            <option value="">All</option>
                            <?php foreach (['Retail','Restaurant','Healthcare','Real Estate','Education','Professional Services'] as $industry): ?>
                                <option value="<?= $industry ?>" <?= ($filters['industry'] ?? '') === $industry ? 'selected' : '' ?>><?= $industry ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Company Size</label>
                        <select class="form-select" name="company_size">
                            <option value="">All</option>
                            <?php foreach (['1-10','11-50','51-200','200+'] as $size): ?>
                                <option value="<?= $size ?>" <?= ($filters['company_size'] ?? '') === $size ? 'selected' : '' ?>><?= $size ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">City / State</label>
                        <input type="text" class="form-control" name="city" value="<?= htmlspecialchars($filters['city'] ?? '') ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100"><i class="bi bi-search"></i> Search Leads</button>
                    </div>
                </form>
                <?php if ($searchExecuted): ?>
                    <?php if (!empty($discoveryError)): ?>
                        <div class="alert alert-warning mt-3"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($discoveryError) ?></div>
                    <?php elseif ($aiLeadCount > 0): ?>
                        <div class="alert alert-success mt-3"><i class="bi bi-stars"></i> AI discovery surfaced <?= $aiLeadCount ?> new prospect<?= $aiLeadCount === 1 ? '' : 's' ?> via Google Places.</div>
                    <?php else: ?>
                        <div class="alert alert-info mt-3"><i class="bi bi-search"></i> No matching companies found via Google Places. Try broadening the location or industry.</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card card-shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Company</th>
                            <th>Industry</th>
                            <th>Location</th>
                            <th>Contact</th>
                            <th>Lead Score</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($leads as $lead): ?>
                            <?php $isAiLead = ($lead['source'] ?? '') === 'google_places'; ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($lead['company_name']) ?></strong><br>
                                    <?php if (!empty($lead['website'])): ?>
                                        <a href="<?= htmlspecialchars($lead['website']) ?>" target="_blank"><?= htmlspecialchars($lead['website']) ?></a>
                                    <?php else: ?>
                                        <span class="text-muted">No website listed</span>
                                    <?php endif; ?>
                                    <?php if ($isAiLead): ?>
                                        <div><span class="badge bg-info text-dark mt-2"><i class="bi bi-magic"></i> AI Prospect</span></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($lead['industry']) ?></td>
                                <td>
                                    <?php
                                    $city = trim($lead['city'] ?? '');
                                    $state = trim($lead['state_province'] ?? '');
                                    $country = trim($lead['country'] ?? '');
                                    $parts = array_filter([$city, $state], fn ($value) => $value !== '');
                                    $location = implode(', ', $parts);
                                    if ($country !== '') {
                                        $location = $location !== '' ? $location . ' (' . $country . ')' : $country;
                                    }
                                    ?>
                                    <?= $location !== '' ? htmlspecialchars($location) : '<span class="text-muted">—</span>' ?>
                                    <?php if (!empty($lead['formatted_address'])): ?>
                                        <div class="text-muted small"><?= htmlspecialchars($lead['formatted_address']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($lead['contact_name'])): ?>
                                        <?= htmlspecialchars($lead['contact_name']) ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($lead['contact_email'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($lead['contact_email']) ?>"><?= htmlspecialchars($lead['contact_email']) ?></a><br>
                                    <?php endif; ?>
                                    <?php if (!empty($lead['contact_phone'])): ?>
                                        <span class="text-muted"><?= htmlspecialchars($lead['contact_phone']) ?></span>
                                    <?php elseif (empty($lead['contact_email'])): ?>
                                        <span class="text-muted">No contact info</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-primary"><?= (int)$lead['lead_score'] ?></span></td>
                                <td>
                                    <?php
                                    $colors = [
                                        'new' => 'secondary',
                                        'contacted' => 'info',
                                        'follow_up' => 'warning',
                                        'locked_in' => 'success',
                                        'lost' => 'danger'
                                    ];
                                    $statusKey = $lead['status'] ?? 'new';
                                    ?>
                                    <span class="badge bg-<?= $colors[$statusKey] ?? 'secondary' ?> status-badge"><?= str_replace('_',' ', $statusKey) ?></span>
                                </td>
                                <td class="text-end">
                                    <?php if ($isAiLead): ?>
                                        <?php if (!empty($lead['website'])): ?>
                                            <a href="<?= htmlspecialchars($lead['website']) ?>" class="btn btn-sm btn-outline-primary" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Visit Site</a>
                                        <?php endif; ?>
                                        <div class="text-muted small mt-2">Generated from Google Places</div>
                                    <?php elseif (!empty($lead['id'])): ?>
                                        <a href="<?= BASE_URL ?>/?route=leads/view&id=<?= $lead['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a>
                                        <form class="d-inline" method="post" action="<?= BASE_URL ?>/?route=leads/status">
                                            <input type="hidden" name="id" value="<?= $lead['id'] ?>">
                                            <input type="hidden" name="status" value="contacted">
                                            <button class="btn btn-sm btn-outline-secondary">Mark Contacted</button>
                                        </form>
                                        <form class="d-inline" method="post" action="<?= BASE_URL ?>/?route=leads/status">
                                            <input type="hidden" name="id" value="<?= $lead['id'] ?>">
                                            <input type="hidden" name="status" value="follow_up">
                                            <button class="btn btn-sm btn-warning text-white">Mark Follow-up</button>
                                        </form>
                                        <form class="d-inline" method="post" action="<?= BASE_URL ?>/?route=leads/status">
                                            <input type="hidden" name="id" value="<?= $lead['id'] ?>">
                                            <input type="hidden" name="status" value="locked_in">
                                            <button class="btn btn-sm btn-success">Mark Locked In</button>
                                        </form>
                                        <form class="d-inline" method="post" action="<?= BASE_URL ?>/?route=leads/status">
                                            <input type="hidden" name="id" value="<?= $lead['id'] ?>">
                                            <input type="hidden" name="status" value="lost">
                                            <button class="btn btn-sm btn-outline-danger">Mark Lost</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($leads)): ?>
                        <div class="text-center text-muted py-5">No leads yet. Import a CSV or run discovery.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>