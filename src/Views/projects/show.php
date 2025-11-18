<div class="row g-4">
    <div class="col-12">
        <a href="<?= BASE_URL ?>/?route=projects" class="btn btn-link"><i class="bi bi-arrow-left"></i> Back to pipeline</a>
    </div>
    <div class="col-lg-4">
        <div class="card card-shadow mb-4">
            <div class="card-body">
                <h5 class="card-title">Client Summary</h5>
                <p class="mb-1"><strong><?= htmlspecialchars($lead['company_name']) ?></strong></p>
                <p class="text-muted mb-1">Industry: <?= htmlspecialchars($lead['industry']) ?></p>
                <p class="text-muted mb-1">Size: <?= htmlspecialchars($lead['company_size']) ?></p>
                <p class="text-muted mb-1">Location: <?= htmlspecialchars($lead['city']) ?>, <?= htmlspecialchars($lead['country']) ?></p>
                <p class="text-muted mb-1">Website: <a href="<?= htmlspecialchars($lead['website']) ?>" target="_blank"><?= htmlspecialchars($lead['website']) ?></a></p>
            </div>
        </div>
        <div class="card card-shadow">
            <div class="card-body">
                <h5 class="card-title">Actions</h5>
                <form method="post" action="<?= BASE_URL ?>/?route=projects/spec" class="mb-2">
                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                    <button class="btn btn-outline-primary w-100"><i class="bi bi-stars"></i> Regenerate Spec</button>
                </form>
                <form method="post" action="<?= BASE_URL ?>/?route=projects/github">
                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                    <button class="btn btn-outline-dark w-100"><i class="bi bi-github"></i> Create GitHub Repo & Issues</button>
                </form>
                <?php if (!empty($project['github_repo_url'])): ?>
                    <a href="<?= htmlspecialchars($project['github_repo_url']) ?>" target="_blank" class="btn btn-link w-100 mt-3"><i class="bi bi-box-arrow-up-right"></i> Open Repo</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-shadow mb-4">
            <div class="card-body">
                <h5 class="card-title">Project Spec</h5>
                <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow:auto;"><?= htmlspecialchars($project['spec_summary']) ?></pre>
            </div>
        </div>
        <div class="card card-shadow mb-4">
            <div class="card-body">
                <h5 class="card-title">Technical Notes</h5>
                <pre class="bg-light p-3 rounded" style="max-height: 200px; overflow:auto;"><?= htmlspecialchars($project['tech_notes']) ?></pre>
            </div>
        </div>
        <div class="card card-shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Tasks</h5>
                </div>
                <form class="row g-2 mb-4" method="post" action="<?= BASE_URL ?>/?route=projects/task">
                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="title" placeholder="Task title" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="description" placeholder="Description">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="status">
                            <option value="todo">To Do</option>
                            <option value="doing">Doing</option>
                            <option value="done">Done</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="assigned_to_user_id">
                            <option value="">Unassigned</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?> (<?= $user['role'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Task</button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Assigned</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><?= htmlspecialchars($task['title']) ?></td>
                                <td><span class="badge bg-secondary status-badge"><?= htmlspecialchars($task['status']) ?></span></td>
                                <td><?= htmlspecialchars($task['assigned_name'] ?? 'Unassigned') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($tasks)): ?>
                        <p class="text-muted">No tasks yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
