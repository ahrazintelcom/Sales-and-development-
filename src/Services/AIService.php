    public function __construct(?Setting $settings = null)
    {
        $this->settings = $settings ?? new Setting();
        $config = require __DIR__ . '/../../config/openai.php';
        $this->apiKey = (string)$this->settings->get('openai_api_key', $config['api_key']);
        $this->model = (string)$this->settings->get('openai_model', $config['model']);
        $this->baseUrl = rtrim((string)$this->settings->get('openai_base_url', $config['base_url']), '/');
    }

    public function analyseLeadAndSuggestApp(array $lead): array
    {
        $context = $this->buildLeadContext($lead);
        $prompt = 'Lead data: ' . json_encode($context, JSON_UNESCAPED_UNICODE) . '. '
            . 'Infer the most valuable custom web application we can build for them. '
            . 'Return JSON with keys: app_type (short label), insight_summary (2 sentences about who they are and key pain points), '
            . 'app_concept (2-3 sentences describing the proposed solution), app_features (array of 5-10 bullet strings), '
            . 'app_benefits (array of 5-10 bullet strings focused on business outcomes).';
        $response = $this->callAI('Lead analysis + app concept', $prompt, true);
        if (!$response['success']) {
            return ['error' => $response['error'] ?? 'Unable to analyse lead.'];
        }
        $data = $response['data'] ?? [];
        return [
            'app_type' => $data['app_type'] ?? 'Custom Web App',
            'insight_summary' => $data['insight_summary'] ?? '',
            'app_concept' => $data['app_concept'] ?? '',
            'app_features' => $this->normaliseList($data['app_features'] ?? []),
            'app_benefits' => $this->normaliseList($data['app_benefits'] ?? []),
            'error' => null,
        ];
    }
    public function generateSalesScripts(array $lead, array $appData, ?array $pricing = null): array
    {
        $context = [
            'lead' => $this->buildLeadContext($lead),
            'app' => $appData,
            'pricing' => $pricing,
        ];
        $prompt = 'Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) . '. '
            . 'Write high-converting outbound assets. Return JSON with: '
            . 'call_script (step-by-step structure with Opening, Discovery, Pitch, Objections, Closing), '
            . 'email_subject (short compelling line), email_body (3-4 paragraph outreach email), '
            . 'talking_points (array of 5-7 concise bullets focusing on value).';
        $response = $this->callAI('Sales collateral generator', $prompt, true);
        if (!$response['success']) {
            return [
                'call_script' => $this->fallbackCopy('Call script unavailable', $lead),
                'email_subject' => 'Subject pending AI refresh',
                'email_body' => $this->fallbackCopy('Email body unavailable', $lead),
                'email_template' => $this->fallbackCopy('Email body unavailable', $lead),
                'talking_points' => [],
                'error' => $response['error'],
            ];
        }
        $data = $response['data'] ?? [];
        $emailSubject = trim($data['email_subject'] ?? 'Follow up from our studio');
        $emailBody = trim($data['email_body'] ?? '');
        return [
            'call_script' => trim($data['call_script'] ?? ''),
            'email_subject' => $emailSubject,
            'email_body' => $emailBody,
            'email_template' => trim($emailSubject . "\n\n" . $emailBody),
            'talking_points' => $this->normaliseList($data['talking_points'] ?? []),
            'error' => null,
        ];
    }

    public function generateFullProposal(array $lead, array $appData, array $pricing): array
    {
        $context = [
            'lead' => $this->buildLeadContext($lead),
            'app' => $appData,
            'pricing' => $pricing,
        ];
        $prompt = 'Using this context ' . json_encode($context, JSON_UNESCAPED_UNICODE) . ' craft a persuasive proposal. '
            . 'Return JSON with sections introduction, proposed_solution, features_benefits, implementation_plan, investment_roi, next_steps.';
        $response = $this->callAI('Proposal generator', $prompt, true);
        if (!$response['success']) {
            return [
                'sections' => [],
                'full_text' => $response['error'] ?? 'Proposal unavailable.',
                'error' => $response['error'],
            ];
        }
        $data = $response['data'] ?? [];
        $sections = [
            'Introduction & Understanding of Client' => trim($data['introduction'] ?? ''),
            'Proposed Solution' => trim($data['proposed_solution'] ?? ''),
            'Features & Benefits' => trim($data['features_benefits'] ?? ''),
            'Implementation Plan & Timeline' => trim($data['implementation_plan'] ?? ''),
            'Investment & ROI' => trim($data['investment_roi'] ?? ''),
            'Next Steps' => trim($data['next_steps'] ?? ''),
        ];
        $fullText = $this->compileSections($sections);
        return [
            'sections' => $sections,
            'full_text' => $fullText,
            'error' => null,
        ];
    }

    public function __construct(?Setting $settings = null)
    {
        $this->settings = $settings ?? new Setting();
        $config = require __DIR__ . '/../../config/openai.php';
        $this->apiKey = (string)$this->settings->get('openai_api_key', $config['api_key']);
        $this->model = (string)$this->settings->get('openai_model', $config['model']);
        $this->baseUrl = rtrim((string)$this->settings->get('openai_base_url', $config['base_url']), '/');
    }

    public function generateLeadSummary(array $lead): array
    {
        $prompt = "Summarize the company {$lead['company_name']} operating in {$lead['industry']} located in {$lead['country']} with website {$lead['website']} in three sentences.";
        $response = $this->callAI('Summarize lead context', $prompt);
        if (!$response['success']) {
            return ['summary' => $response['error'] ?? 'Summary unavailable'];
        }
        return ['summary' => (string)$response['data']];
    }

    public function generateSalesScripts(array $lead, array $recommendedApps): array
    {
        $appText = json_encode($recommendedApps);
        $prompt = "Create a call script, email template, talking points, and proposal/spec outlines for selling custom web apps to {$lead['company_name']} ({$lead['industry']}). Use this context: {$appText}. Return JSON with call_script (string), email_template (string), talking_points (array of concise bullet strings), proposal_segments (object with executive_summary, pricing_context, implementation_plan), and spec_segments (object with solution_overview, technical_notes, milestones array).";
        $response = $this->callAI('Sales script generator', $prompt, true);
        if (!$response['success']) {
            return [
                'call_script' => $this->fallbackCopy('Call script unavailable', $lead),
                'email_template' => $this->fallbackCopy('Email template unavailable', $lead),
                'talking_points' => [],
                'proposal_segments' => [],
                'spec_segments' => [],
                'error' => $response['error'],
            ];
        }
        return $this->parseScriptResponse($response['data'] ?? []);        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a senior SaaS consultant and sales copywriter. You design web apps and write persuasive B2B proposals.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ];
        if ($expectJson) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = $this->callAI('App recommendation generator', $prompt, true);
        if (!$response['success']) {
            return $ruleBased;
        }
        return is_array($response['data']) ? $response['data'] : $ruleBased;
    }

    public function generateProposal(array $lead, array $recommendedApps, array $pricing): array
    {
        $pricingNarrative = $this->formatPricingContext($recommendedApps, $pricing);
        $prompt = "Write a persuasive proposal for {$lead['company_name']} referencing these apps: " . json_encode($recommendedApps) . ". Lead data: " . json_encode($lead) . ". Pricing context: {$pricingNarrative}. Return JSON with executive_summary, solution_overview, pricing_context, roi_projection, next_steps, and proposal_body.";
        $response = $this->callAI('Proposal writer', $prompt, true);
        if (!$response['success']) {
            return [
                'executive_summary' => 'Proposal unavailable at the moment.',
                'solution_overview' => '',
                'pricing_context' => $pricingNarrative,
                'roi_projection' => '',
                'next_steps' => '',
                'proposal_body' => $response['error'] ?? '',
                'error' => $response['error'],
            ];
        }
        $data = $response['data'] ?? [];
        return [
            'executive_summary' => $data['executive_summary'] ?? '',
            'solution_overview' => $data['solution_overview'] ?? '',
            'pricing_context' => $data['pricing_context'] ?? $pricingNarrative,
            'roi_projection' => $data['roi_projection'] ?? '',
    private function fallbackCopy(string $message, array $lead): string
    {
        return $message . ' for ' . ($lead['company_name'] ?? 'this lead') . '.';
    }

    private function normaliseList($items): array
    {
        if (is_string($items)) {
            $items = explode("\n", $items);
        }
        if (!is_array($items)) {
            return [];
        }
        $items = array_map('trim', $items);
        $items = array_filter($items, fn ($value) => $value !== '');
        return array_values($items);
    }

    private function buildLeadContext(array $lead): array
    {
        return [
            'company_name' => $lead['company_name'] ?? '',
            'website' => $lead['website'] ?? '',
            'industry' => $lead['industry'] ?? '',
            'description' => $lead['description'] ?? '',
            'country' => $lead['country'] ?? '',
            'city' => $lead['city'] ?? '',
            'company_size' => $lead['company_size'] ?? '',
            'lead_score' => $lead['lead_score'] ?? '',
        ];
    }

    private function compileSections(array $sections): string
    {
        $parts = [];
        foreach ($sections as $title => $content) {
            if (trim($content) === '') {
                continue;
            }
            $parts[] = $title . "\n" . $content;
        }
        return trim(implode("\n\n", $parts));
    }

    private function formatPricingContext(array $apps, array $pricing): string
    {
        $ranges = array_map(function ($app) {
                'spec_summary' => 'Spec unavailable.',
                'tech_notes' => $response['error'] ?? '',
                'milestones' => [],
                'risks' => [],
                'error' => $response['error'],
            ];
        }
        $data = $response['data'] ?? [];
        return [
            'spec_summary' => $data['spec_summary'] ?? '',
            'tech_notes' => $data['tech_notes'] ?? '',
            'milestones' => $data['milestones'] ?? [],
            'risks' => $data['risks'] ?? [],
            'error' => null,
        ];
    }

    private function callAI(string $purpose, string $prompt, bool $expectJson = false): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'data' => null,
                'error' => "AI credentials are not configured. {$purpose} cannot be generated.",
            ];
        }
        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert B2B SaaS consultant and sales copywriter. Always follow formatting instructions.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ];
        if ($expectJson) {
            $payload['response_format'] = ['type' => 'json_object'];
        }
        $ch = curl_init($this->baseUrl . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        $result = curl_exec($ch);
        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return [
                'success' => false,
                'data' => null,
                'error' => 'AI request failed: ' . $error,
            ];
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($status >= 400) {
            return [
                'success' => false,
                'data' => null,
                'error' => "AI service returned HTTP {$status} for {$purpose}.",
            ];
        }
        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Unable to decode AI response.',
            ];
        }
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if ($content === null) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'AI response missing content.',
            ];
        }
        if ($expectJson) {
            $structured = json_decode($content, true);
            if (!is_array($structured)) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Failed to parse AI JSON payload: ' . json_last_error_msg(),
                ];
            }
            return ['success' => true, 'data' => $structured, 'error' => null];
        }
        return ['success' => true, 'data' => trim($content), 'error' => null];
    }

    private function parseScriptResponse(array $response): array
    {
        $talkingPoints = $response['talking_points'] ?? [];
        if (is_string($talkingPoints)) {
            $talkingPoints = array_filter(array_map('trim', explode("\n", $talkingPoints)));
        }
        return [
            'call_script' => $response['call_script'] ?? '',
            'email_template' => $response['email_template'] ?? '',
            'talking_points' => is_array($talkingPoints) ? array_values($talkingPoints) : [],
            'proposal_segments' => $response['proposal_segments'] ?? [],
            'spec_segments' => $response['spec_segments'] ?? [],
            'error' => null,
        ];
    }

    private function fallbackCopy(string $message, array $lead): string
    {
        return $message . ' for ' . ($lead['company_name'] ?? 'this lead') . '.';
    }

    private function formatPricingContext(array $apps, array $pricing): string
    {
        $ranges = array_map(function ($app) {
            if (!isset($app['app_name'])) {
                return null;
            }
            $min = isset($app['price_min']) ? number_format((float)$app['price_min'], 0) : 'N/A';
            $max = isset($app['price_max']) ? number_format((float)$app['price_max'], 0) : 'N/A';
            return $app['app_name'] . " ($" . $min . " - $" . $max . ")";
        }, $apps);
        $ranges = array_values(array_filter($ranges));
        if (!empty($pricing['average'])) {
            $avg = $pricing['average'];
            $ranges[] = "Average range: $" . number_format((float)$avg['min'], 0) . " - $" . number_format((float)$avg['max'], 0);
        }
        return implode('; ', $ranges);
    }
}