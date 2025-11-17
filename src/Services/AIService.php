<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Project;

class AIService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/openai.php';
        $this->apiKey = $config['api_key'];
        $this->model = $config['model'];
        $this->baseUrl = rtrim($config['base_url'], '/');
    }

    public function generateLeadSummary(array $lead): array
    {
        $prompt = "Summarize the company {$lead['company_name']} operating in {$lead['industry']} located in {$lead['country']} with website {$lead['website']}.";
        $response = $this->callAI('Summarize lead context', $prompt);
        return ['summary' => $response];
    }

    public function generateSalesScripts(array $lead, array $recommendedApps): array
    {
        $appText = json_encode($recommendedApps);
        $prompt = "Create a call script, email template, and talking points for selling custom web apps to {$lead['company_name']} ({$lead['industry']}). Use this context: {$appText}.";
        $response = $this->callAI('Sales script generator', $prompt);
        return $this->parseScriptResponse($response);
    }

    public function generateAppRecommendations(array $lead, array $ruleBased): array
    {
        $prompt = "Based on the following rule based suggestions: " . json_encode($ruleBased) . " provide concise descriptions, features, and benefits for {$lead['company_name']} in {$lead['industry']}.
Return JSON with app_name, description, key_features (array), benefits (array).";
        $response = $this->callAI('App recommendation generator', $prompt);
        return $this->safeJson($response, $ruleBased);
    }

    public function generateProposal(array $lead, array $recommendedApps, array $pricing): array
    {
        $prompt = "Write a persuasive proposal for {$lead['company_name']} referencing these apps: " . json_encode($recommendedApps) . '. Include context, solution overview, pricing narrative, and ROI.';
        $response = $this->callAI('Proposal writer', $prompt);
        return ['content' => $response];
    }

    public function generateProjectSpec(?array $project, array $lead, array $apps): array
    {
        $prompt = "Draft a project spec for {$project['project_name']}. Lead info: " . json_encode($lead) . '. Recommended apps: ' . json_encode($apps) . '. Provide overview, user roles, features, non-functional requirements, tech notes, milestones.';
        $response = $this->callAI('Project spec generator', $prompt);
        return [
            'spec_summary' => $response,
            'tech_notes' => $response,
        ];
    }

    private function callAI(string $purpose, string $prompt): string
    {
        if (empty($this->apiKey)) {
            return "[AI placeholder for {$purpose}] " . $prompt;
        }
        $payload = json_encode([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert B2B SaaS consultant and sales copywriter.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);
        $ch = curl_init($this->baseUrl . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => $payload,
        ]);
        $result = curl_exec($ch);
        if ($result === false) {
            return 'AI request failed: ' . curl_error($ch);
        }
        $decoded = json_decode($result, true);
        return $decoded['choices'][0]['message']['content'] ?? 'AI response unavailable';
    }

    private function parseScriptResponse(string $response): array
    {
        return [
            'call_script' => $response,
            'email_template' => $response,
            'talking_points' => explode("\n", $response),
        ];
    }

    private function safeJson(string $response, array $fallback): array
    {
        $decoded = json_decode($response, true);
        if (!$decoded) {
            return $fallback;
        }
        return $decoded;
    }
}
