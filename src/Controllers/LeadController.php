<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Lead;
use App\Models\LeadRecommendedApp;
use App\Models\Project;
use App\Services\AIService;
use App\Services\LeadFinderService;
use App\Services\RecommendationService;

class LeadController extends Controller
{
    private Lead $leadModel;
    private LeadRecommendedApp $appModel;
    private Project $projectModel;
    private LeadFinderService $leadFinderService;
    private RecommendationService $recommendationService;
    private AIService $aiService;

    public function __construct()
    {
        $this->leadModel = new Lead();
        $this->appModel = new LeadRecommendedApp();
        $this->projectModel = new Project();
        $this->leadFinderService = new LeadFinderService();
        $this->recommendationService = new RecommendationService();
        $this->aiService = new AIService();
    }

    public function index(): void
    {
        Auth::requireRole(['admin', 'sales']);
        $filters = [
            'country' => $_GET['country'] ?? '',
            'industry' => $_GET['industry'] ?? '',
            'company_size' => $_GET['company_size'] ?? '',
            'city' => $_GET['city'] ?? '',
        ];
        $searchExecuted = $this->shouldRunDiscovery($filters);
        $discoveredLeads = $searchExecuted ? $this->leadFinderService->discoverLeads($filters) : [];
        $storedLeads = $this->leadModel->all($filters);
        $leads = array_merge($discoveredLeads, $storedLeads);
        $discoveryError = $searchExecuted ? $this->leadFinderService->getLastError() : null;
        $aiLeadCount = count($discoveredLeads);
        $this->view('leads/index', compact('leads', 'filters', 'discoveryError', 'searchExecuted', 'aiLeadCount'));
    }

    public function importCsv(): void
    {
        Auth::requireRole(['admin', 'sales']);
        if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Failed to upload CSV file.';
            $this->redirect('/?route=leads');
        }
        $count = $this->leadFinderService->importCsv($_FILES['csv']['tmp_name']);
        $_SESSION['flash_success'] = "Imported {$count} leads.";
        $this->redirect('/?route=leads');
    }

    public function show(): void
    {
        Auth::requireRole(['admin', 'sales']);
        $id = (int)($_GET['id'] ?? 0);
        $lead = $this->leadModel->find($id);
        if (!$lead) {
            $this->redirect('/?route=leads');
        }
        $recommendedApps = $this->appModel->getByLead($lead['id']);
        if (empty($recommendedApps)) {
            $recommendedApps = $this->recommendationService->generateForLead($lead);
        } else {
            $recommendedApps = array_map(function ($app) {
                $app['key_features'] = json_decode($app['key_features'], true) ?: [];
                $app['benefits'] = json_decode($app['benefits'], true) ?: [];
                return $app;
            }, $recommendedApps);
        }
        $leadSummary = $this->aiService->generateLeadSummary($lead);
        $salesScripts = $this->aiService->generateSalesScripts($lead, $recommendedApps);
        $proposalPreview = null;
        $this->view('leads/show', compact('lead', 'recommendedApps', 'leadSummary', 'salesScripts', 'proposalPreview'));
    }

    public function markStatus(): void
    {
        Auth::requireRole(['admin', 'sales']);
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'new';
        $allowed = ['new','contacted','follow_up','locked_in','lost'];
        if (!in_array($status, $allowed, true)) {
            $status = 'new';
        }
        $lead = $this->leadModel->find($id);
        if (!$lead) {
            $this->redirect('/?route=leads');
        }
        $this->leadModel->updateStatus($id, $status);
        if ($status === 'locked_in') {
            $app = $this->appModel->getByLead($id)[0] ?? null;
            $projectName = $lead['company_name'] . ' – ' . ($app['app_name'] ?? 'Custom Web App');
            $projectId = $this->projectModel->create([
                'lead_id' => $lead['id'],
                'project_name' => $projectName,
                'app_type' => $app['app_type'] ?? 'custom',
                'status' => 'backlog',
                'spec_summary' => 'Initial spec pending AI generation.',
            ]);
            $appsForSpec = array_map(function ($stored) {
                $stored['key_features'] = json_decode($stored['key_features'], true) ?: [];
                $stored['benefits'] = json_decode($stored['benefits'], true) ?: [];
                return $stored;
            }, $this->appModel->getByLead($lead['id']));
            $spec = $this->aiService->generateProjectSpec($this->projectModel->find($projectId), $lead, $appsForSpec);
            $this->projectModel->updateSpec($projectId, [
                'spec_summary' => $spec['spec_summary'] ?? '',
                'tech_notes' => $spec['tech_notes'] ?? '',
            ]);
        }
        $this->redirect('/?route=leads');
    }

    public function regenerateScripts(): void
    {
        Auth::requireRole(['admin', 'sales']);
        $id = (int)($_POST['id'] ?? 0);
        $lead = $this->leadModel->find($id);
        if (!$lead) {
            $this->json(['error' => 'Lead not found'], 404);
            return;
        }
        $apps = $this->recommendationService->generateForLead($lead, true);
        $scripts = $this->aiService->generateSalesScripts($lead, $apps);
        $summary = $this->aiService->generateLeadSummary($lead);
        $this->json([
            'summary' => $summary,
            'scripts' => $scripts,
            'apps' => $apps,
        ]);
    }

    public function generateProposal(): void
    {
        Auth::requireRole(['admin', 'sales']);
        $id = (int)($_POST['id'] ?? 0);
        $lead = $this->leadModel->find($id);
        if (!$lead) {
            $this->json(['error' => 'Lead not found'], 404);
            return;
        }
        $apps = array_map(function ($app) {
            $app['key_features'] = json_decode($app['key_features'], true) ?: [];
            $app['benefits'] = json_decode($app['benefits'], true) ?: [];
            return $app;
        }, $this->appModel->getByLead($lead['id']));
        $pricing = $this->buildPricingSummary($apps);
        $proposal = $this->aiService->generateProposal($lead, $apps, $pricing);
        $status = !empty($proposal['error']) ? 502 : 200;
        $this->json(['proposal' => $proposal], $status);
    }

    private function buildPricingSummary(array $apps): array
    {
        if (empty($apps)) {
            return [];
        }
        $count = 0;
        $minTotal = 0;
        $maxTotal = 0;
        foreach ($apps as $app) {
            if (!isset($app['price_min'], $app['price_max'])) {
                continue;
            }
            $minTotal += (float)$app['price_min'];
            $maxTotal += (float)$app['price_max'];
            $count++;
        }
        if ($count === 0) {
            return [];
        }
        return [
            'average' => [
                'min' => $minTotal / $count,
                'max' => $maxTotal / $count,
            ],
        ];
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    private function shouldRunDiscovery(array $filters): bool
    {
        foreach (['industry', 'city', 'country'] as $key) {
            $value = $filters[$key] ?? '';
            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }
        return false;
    }
}