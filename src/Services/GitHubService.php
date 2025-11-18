<?php

namespace App\Services;

use App\Models\Setting;

class GitHubService
{
    private Setting $settingModel;

    public function __construct()
    {
        $this->settingModel = new Setting();
    }

    public function createRepoFromTemplate(array $project, array $lead): array
    {
        $token = $this->settingModel->get('github_token');
        $org = $this->settingModel->get('github_org');
        $template = $this->settingModel->get('github_template_' . ($project['app_type'] ?? 'default'));
        // TODO: Use GitHub REST API to create repository from template and return repo URL
        // Keep PAT secure and configurable via settings.
        return [
            'repo_url' => $template ? "https://github.com/{$org}/{$project['project_name']}" : null,
            'status' => 'stubbed',
        ];
    }
}
