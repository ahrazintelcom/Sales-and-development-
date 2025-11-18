use App\Services\AIService;
use App\Services\LeadFinderService;
use App\Services\PricingService;
    private Project $projectModel;
    private LeadFinderService $leadFinderService;
    private AIService $aiService;
    private PricingService $pricingService;

    public function __construct()
    {
        $this->leadModel = new Lead();
        $this->appModel = new LeadRecommendedApp();
        $this->projectModel = new Project();
        $this->leadFinderService = new LeadFinderService();
        $this->aiService = new AIService();
        $this->pricingService = new PricingService();
    }
    public function index(): void
    {
        Auth::requireRole(['admin', 'sales']);
        $filters = [
            'country' => $_GET['country'] ?? '',
        $this->view('leads/index', compact('leads', 'filters', 'discoveryError', 'searchExecuted', 'aiLeadCount'));
    }

    public function discover(): void
    {
        // Reuse the index pipeline so we don't duplicate Google Places logic.
        $this->index();
    }
    public function show(): void
    {
        Auth::requireRole(['admin', 'sales']);
        $id = (int)($_GET['id'] ?? 0);
        $lead = $this->leadModel->find($id);
        if (!$lead) {
            $this->redirect('/?route=leads');
        }
        $aiData = $this->transformLeadAiData($lead);
        $needsRefresh = $this->needsAiRefresh($aiData['last_generated_at']);
        $project = $this->projectModel->findByLead($lead['id']);
        $this->view('leads/show', compact('lead', 'aiData', 'needsRefresh', 'project'));
    }
    public function markStatus(): void
    {
        Auth::requireRole(['admin', 'sales']);
        $id = (int)($_POST['id'] ?? 0);
        if ($status === 'locked_in') {
            $existingProject = $this->projectModel->findByLead($lead['id']);
            if (!$existingProject) {
                $appLabel = $lead['ai_app_type'] ?: null;
                $appType = $appLabel ?: 'custom';
                $recommendedApp = $this->appModel->getByLead($id)[0] ?? null;
                if (!$appLabel && $recommendedApp) {
                    $appLabel = $recommendedApp['app_name'] ?? null;
                    $appType = $recommendedApp['app_type'] ?? $appType;
                }
                $projectName = $lead['company_name'] . ' – ' . ($appLabel ?: 'Custom Web App');
                $projectId = $this->projectModel->create([
                    'lead_id' => $lead['id'],
                    'project_name' => $projectName,
                    'app_type' => $appType,
                    'status' => 'backlog',
                    'spec_summary' => 'Initial spec pending AI generation.',
                ]);
                $appsForSpec = $this->appModel->getByLead($lead['id']);
                if (empty($appsForSpec) && $lead['ai_app_type']) {
                    $appsForSpec = [[
                        'app_name' => $lead['ai_app_type'],
                        'app_type' => $lead['ai_app_type'],
                        'key_features' => $this->decodeStoredList($lead['ai_app_features'] ?? null),
                        'benefits' => $this->decodeStoredList($lead['ai_app_benefits'] ?? null),
                        'price_min' => $lead['ai_price_min'],
                        'price_max' => $lead['ai_price_max'],
                    ]];
                } else {
                    $appsForSpec = array_map(function ($stored) {
                        $stored['key_features'] = json_decode($stored['key_features'], true) ?: [];
                        $stored['benefits'] = json_decode($stored['benefits'], true) ?: [];
                        return $stored;
                    }, $appsForSpec);
                }
                $spec = $this->aiService->generateProjectSpec($this->projectModel->find($projectId), $lead, $appsForSpec);
                $this->projectModel->updateSpec($projectId, [
                    'spec_summary' => $spec['spec_summary'] ?? '',
                    'tech_notes' => $spec['tech_notes'] ?? '',
                ]);
            }
        }
        $this->redirect('/?route=leads');
    }

    public function generateAi(): void
    {
        Auth::requireRole(['admin', 'sales']);
        $id = (int)($_POST['id'] ?? 0);
        $lead = $this->leadModel->find($id);
        if (!$lead) {
            $this->json(['error' => 'Lead not found.'], 404);
            return;
        }
        $analysis = $this->aiService->analyseLeadAndSuggestApp($lead);
        if (!empty($analysis['error'])) {
            $this->json(['error' => $analysis['error']], 502);
            return;
        }
        $pricing = $this->pricingService->estimateRange($analysis['app_type'] ?? 'Custom Web App', $lead['company_size'] ?? null);
        $scripts = $this->aiService->generateSalesScripts($lead, $analysis, $pricing);
        if (!empty($scripts['error'])) {
            $this->json(['error' => $scripts['error']], 502);
            return;
        }
        $proposal = $this->aiService->generateFullProposal($lead, $analysis, $pricing);
        if (!empty($proposal['error'])) {
            $this->json(['error' => $proposal['error']], 502);
            return;
        }
        $timestamp = date('Y-m-d H:i:s');
        $this->leadModel->updateAiData($lead['id'], [
            'ai_app_type' => $analysis['app_type'] ?? null,
            'ai_insight_summary' => $analysis['insight_summary'] ?? null,
            'ai_app_concept' => $analysis['app_concept'] ?? null,
            'ai_app_features' => json_encode($analysis['app_features'] ?? []),
            'ai_app_benefits' => json_encode($analysis['app_benefits'] ?? []),
            'ai_price_min' => $pricing['min'],
            'ai_price_max' => $pricing['max'],
            'ai_call_script' => $scripts['call_script'],
            'ai_email_script' => json_encode([
                'subject' => $scripts['email_subject'] ?? '',
                'body' => $scripts['email_body'] ?? '',
            ], JSON_UNESCAPED_UNICODE),
            'ai_talking_points' => implode("\n", $scripts['talking_points'] ?? []),
            'ai_full_proposal' => $proposal['full_text'],
            'ai_last_generated_at' => $timestamp,
        ]);

        $freshLead = $this->leadModel->find($lead['id']);
        $aiData = $this->transformLeadAiData($freshLead);
        $this->json([
            'lead_id' => $lead['id'],
            'ai' => $aiData,
            'pricing' => $pricing,
        ]);
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
        $analysis = $this->aiService->analyseLeadAndSuggestApp($lead);
        if (!empty($analysis['error'])) {
            $this->json(['error' => $analysis['error']], 502);
            return;
        }
        $pricing = $this->pricingService->estimateRange($analysis['app_type'] ?? 'Custom Web App', $lead['company_size'] ?? null);
        $scripts = $this->aiService->generateSalesScripts($lead, $analysis, $pricing);
        if (!empty($scripts['error'])) {
            $this->json(['error' => $scripts['error']], 502);
            return;
        }
        $summary = ['summary' => $analysis['insight_summary'] ?? $analysis['app_concept'] ?? ''];
        $this->json([
            'summary' => $summary,
            'scripts' => $scripts,
            'apps' => [$analysis],
        ]);
    }
    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    private function shouldRunDiscovery(array $filters): bool
    {
        }
        return false;
    }

    private function transformLeadAiData(array $lead): array
    {
        $emailSubject = '';
        $emailBody = '';
        $rawEmail = $lead['ai_email_script'] ?? '';
        if ($rawEmail) {
            $decoded = json_decode($rawEmail, true);
            if (is_array($decoded)) {
                $emailSubject = $decoded['subject'] ?? '';
                $emailBody = $decoded['body'] ?? '';
            } else {
                $parts = preg_split("/\r?\n\r?\n/", $rawEmail, 2);
                $emailSubject = trim($parts[0] ?? '');
                $emailBody = trim($parts[1] ?? $rawEmail);
            }
        }
        return [
            'app_type' => $lead['ai_app_type'] ?? '',
            'insight_summary' => $lead['ai_insight_summary'] ?? '',
            'app_concept' => $lead['ai_app_concept'] ?? '',
            'app_features' => $this->decodeStoredList($lead['ai_app_features'] ?? null),
            'app_benefits' => $this->decodeStoredList($lead['ai_app_benefits'] ?? null),
            'price_min' => $lead['ai_price_min'] ?? null,
            'price_max' => $lead['ai_price_max'] ?? null,
            'call_script' => $lead['ai_call_script'] ?? '',
            'email_subject' => $emailSubject,
            'email_body' => $emailBody,
            'talking_points' => $this->decodeStoredList($lead['ai_talking_points'] ?? null),
            'full_proposal' => $lead['ai_full_proposal'] ?? '',
            'last_generated_at' => $lead['ai_last_generated_at'] ?? null,
        ];
    }

    private function decodeStoredList($value): array
    {
        if (!$value) {
            return [];
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            }
        }
        if (is_string($value)) {
            $value = preg_split("/\r?\n/", $value);
        }
        if (!is_array($value)) {
            return [];
        }
        $value = array_map('trim', $value);
        $value = array_filter($value, fn ($item) => $item !== '');
        return array_values($value);
    }

    private function needsAiRefresh(?string $timestamp): bool
    {
        if (!$timestamp) {
            return true;
        }
        $last = strtotime($timestamp);
        if ($last === false) {
            return true;
        }
        return (time() - $last) > 86400;
    }
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