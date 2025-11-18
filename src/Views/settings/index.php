<?php $settingsMap = array_column($settings, 'value', 'key'); ?>
<div class="row g-4">
    <div class="col-12">
        <h3>Settings</h3>
        <p class="text-muted">Manage AI, pricing, and integration preferences.</p>
    </div>
    <div class="col-lg-4">
        <div class="card card-shadow">
            <div class="card-body">
                <h5 class="card-title">OpenAI Configuration</h5>
                <form method="post" action="<?= BASE_URL ?>/?route=settings/save">
                    <div class="mb-3">
                        <label class="form-label">API Key</label>
                        <input type="text" class="form-control" name="openai_api_key" value="<?= htmlspecialchars($settingsMap['openai_api_key'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Model</label>
                        <input type="text" class="form-control" name="openai_model" value="<?= htmlspecialchars($settingsMap['openai_model'] ?? 'gpt-4o-mini') ?>">
                    </div>
                    <button class="btn btn-primary w-100">Save</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-shadow">
            <div class="card-body">
                <h5 class="card-title">GitHub Integration</h5>
                <form method="post" action="<?= BASE_URL ?>/?route=settings/save">
                    <div class="mb-3">
                        <label class="form-label">Personal Access Token</label>
                        <input type="text" class="form-control" name="github_token" value="<?= htmlspecialchars($settingsMap['github_token'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Default Org/User</label>
                        <input type="text" class="form-control" name="github_org" value="<?= htmlspecialchars($settingsMap['github_org'] ?? '') ?>">
                    </div>
                    <button class="btn btn-primary w-100">Save</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-shadow">
            <div class="card-body">
                <h5 class="card-title">Pricing Rules</h5>
                <form class="row g-2" method="post" action="<?= BASE_URL ?>/?route=settings/pricing">
                    <div class="col-6">
                        <input type="text" name="app_type" class="form-control" placeholder="App type key" required>
                    </div>
                    <div class="col-4">
                        <input type="number" name="base_price" class="form-control" placeholder="Base price" required>
                    </div>
                    <div class="col-2">
                        <button class="btn btn-success w-100"><i class="bi bi-plus"></i></button>
                    </div>
                </form>
                <ul class="list-group mt-3">
                    <?php foreach ($pricingRules as $rule): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= htmlspecialchars($rule['app_type']) ?></span>
                            <strong>$<?= number_format($rule['base_price']) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-shadow">
            <div class="card-body">
                <h5 class="card-title">Company Size Multipliers</h5>
                <form class="row g-2" method="post" action="<?= BASE_URL ?>/?route=settings/multiplier">
                    <div class="col-6">
                        <input type="text" name="size_category" class="form-control" placeholder="11-50" required>
                    </div>
                    <div class="col-4">
                        <input type="number" step="0.1" name="multiplier" class="form-control" placeholder="1.2" required>
                    </div>
                    <div class="col-2">
                        <button class="btn btn-success w-100"><i class="bi bi-plus"></i></button>
                    </div>
                </form>
                <ul class="list-group mt-3">
                    <?php foreach ($multipliers as $multiplier): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= htmlspecialchars($multiplier['size_category']) ?></span>
                            <strong>x<?= $multiplier['multiplier'] ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
