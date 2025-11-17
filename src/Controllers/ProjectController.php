<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Lead;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\AIService;
use App\Services\GitHubService;

class ProjectController extends Controller
{
    private Project $projectModel;
    private ProjectTask $taskModel;
    private Lead $leadModel;
    private User $userModel;
    private AIService $aiService;
    private GitHubService $gitHubService;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->taskModel = new ProjectTask();
        $this->leadModel = new Lead();
        $this->userModel = new User();
        $this->aiService = new AIService();
        $this->gitHubService = new GitHubService();
    }

    public function index(): void
    {
        Auth::requireRole(['admin', 'dev', 'sales']);
        $projects = $this->projectModel->all();
        $this->view('projects/index', compact('projects'));
    }

    public function show(): void
    {
        Auth::requireRole(['admin', 'dev', 'sales']);
        $id = (int)($_GET['id'] ?? 0);
        $project = $this->projectModel->find($id);
        if (!$project) {
            $this->redirect('/?route=projects');
        }
        $lead = $this->leadModel->find($project['lead_id']);
        $tasks = $this->taskModel->getByProject($project['id']);
        $users = $this->getAssignableUsers();
        $this->view('projects/show', compact('project', 'lead', 'tasks', 'users'));
    }

    public function addTask(): void
    {
        Auth::requireRole(['admin', 'dev']);
        $this->taskModel->create([
            'project_id' => (int)$_POST['project_id'],
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? '',
            'status' => $_POST['status'] ?? 'todo',
            'assigned_to_user_id' => $_POST['assigned_to_user_id'] ?? null,
        ]);
        $this->redirect('/?route=projects/view&id=' . (int)$_POST['project_id']);
    }

    public function regenerateSpec(): void
    {
        Auth::requireRole(['admin', 'dev']);
        $projectId = (int)$_POST['project_id'];
        $project = $this->projectModel->find($projectId);
        if (!$project) {
            $this->redirect('/?route=projects');
        }
        $lead = $this->leadModel->find($project['lead_id']);
        $spec = $this->aiService->generateProjectSpec($project, $lead, []);
        $this->projectModel->updateSpec($projectId, [
            'spec_summary' => $spec['spec_summary'] ?? '',
            'tech_notes' => $spec['tech_notes'] ?? '',
        ]);
        $this->redirect('/?route=projects/view&id=' . $projectId);
    }

    public function createGithub(): void
    {
        Auth::requireRole(['admin', 'dev']);
        $projectId = (int)$_POST['project_id'];
        $project = $this->projectModel->find($projectId);
        if (!$project) {
            $this->redirect('/?route=projects');
        }
        $lead = $this->leadModel->find($project['lead_id']);
        $result = $this->gitHubService->createRepoFromTemplate($project, $lead);
        if (!empty($result['repo_url'])) {
            $this->projectModel->updateSpec($projectId, [
                'spec_summary' => $project['spec_summary'],
                'tech_notes' => $project['tech_notes'],
                'github_repo_url' => $result['repo_url'],
            ]);
        }
        $this->redirect('/?route=projects/view&id=' . $projectId);
    }

    private function getAssignableUsers(): array
    {
        return $this->userModel->getAssignableDevs();
    }
}
