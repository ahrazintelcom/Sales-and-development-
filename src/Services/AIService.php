<?php

namespace App\Services;

use App\Models\Setting;

class AIService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;
    private Setting $settings;

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
        return $this->parseScriptResponse($response['data'] ?? []);
    }

    public function generateAppRecommendations(array $lead, array $ruleBased): array
    {
        $prompt = "Based on the following rule based suggestions: " . json_encode($ruleBased) . " provide concise descriptions, features, and benefits for {$lead['company_name']} in {$lead['industry']}. Return JSON with app_name, description, key_features (array), benefits (array).";
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
            'next_steps' => $data['next_steps'] ?? '',
            'proposal_body' => $data['proposal_body'] ?? '',
            'error' => null,
        ];
    }

    public function generateProjectSpec(?array $project, array $lead, array $apps): array
    {
        $prompt = "Draft a project spec for {$project['project_name']}. Lead info: " . json_encode($lead) . '. Recommended apps: ' . json_encode($apps) . ". Provide overview, user roles, features, non-functional requirements, tech notes, milestones, and delivery risks. Return JSON with spec_summary, tech_notes, milestones (array) and risks (array).";
        $response = $this->callAI('Project spec generator', $prompt, true);
        if (!$response['success']) {
            return [
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

    public function checkConnection(): array
    {
        if (empty($this->apiKey)) {
            return [
                'connected' => false,
                'message' => 'AI API key is missing. Please update your settings.',
            ];
        }

        $endpoint = rtrim($this->baseUrl, '/') . '/models';
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return [
                'connected' => false,
                'message' => 'Unable to reach the AI service: ' . $error,
            ];
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status >= 400) {
            return [
                'connected' => false,
                'message' => 'AI service returned HTTP ' . $status . '. Check your credentials or base URL.',
            ];
        }

        return [
            'connected' => true,
            'message' => 'AI API connection successful.',
        ];
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
