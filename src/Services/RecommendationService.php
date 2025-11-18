<?php

namespace App\Services;

use App\Models\CompanySizeMultiplier;
use App\Models\LeadRecommendedApp;
use App\Models\PricingRule;

class RecommendationService
{
    private LeadRecommendedApp $appModel;
    private PricingRule $pricingRuleModel;
    private CompanySizeMultiplier $multiplierModel;
    private AIService $aiService;

    private array $rules = [
        'Restaurant' => 'restaurant_portal',
        'Retail' => 'retail_portal',
        'Healthcare' => 'telehealth_portal',
        'Real Estate' => 'real_estate_portal',
        'Education' => 'education_portal',
        'Professional Services' => 'professional_services_portal',
        'Default' => 'smb_workflow',
    ];

    public function __construct()
    {
        $this->appModel = new LeadRecommendedApp();
        $this->pricingRuleModel = new PricingRule();
        $this->multiplierModel = new CompanySizeMultiplier();
        $this->aiService = new AIService();
    }

    public function generateForLead(array $lead, bool $force = false): array
    {
        $ruleBased = $this->ruleBasedSuggestion($lead);
        $aiDetails = $this->aiService->generateAppRecommendations($lead, $ruleBased);
        if (empty($aiDetails)) {
            $aiDetails = $ruleBased;
        }
        if (isset($aiDetails['app_name'])) {
            $aiDetails = [$aiDetails];
        }
        $enriched = [];
        foreach ($aiDetails as $app) {
            $pricing = $this->calculatePrice($lead, $app['app_type'] ?? $ruleBased[0]['app_type']);
            $keyFeatures = is_array($app['key_features'] ?? null) ? $app['key_features'] : (array)($app['key_features'] ?? []);
            $benefits = is_array($app['benefits'] ?? null) ? $app['benefits'] : (array)($app['benefits'] ?? []);
            $record = [
                'lead_id' => $lead['id'],
                'app_type' => $app['app_type'] ?? $ruleBased[0]['app_type'],
                'app_name' => $app['app_name'] ?? $ruleBased[0]['app_name'],
                'description' => $app['description'] ?? '',
                'key_features' => $keyFeatures ?: $ruleBased[0]['key_features'],
                'benefits' => $benefits ?: $ruleBased[0]['benefits'],
                'price_min' => $pricing['min'],
                'price_max' => $pricing['max'],
            ];
            if (!$force) {
                $this->appModel->create($record);
            }
            $enriched[] = $record;
        }
        return $enriched;
    }

    private function ruleBasedSuggestion(array $lead): array
    {
        $industry = $lead['industry'] ?? 'Default';
        $appTypeKey = $this->rules['Default'];
        foreach ($this->rules as $key => $value) {
            if (stripos($industry, $key) !== false) {
                $appTypeKey = $value;
                break;
            }
        }
        $templates = [
            'restaurant_portal' => [
                'app_name' => 'Restaurant Ordering & Delivery Portal',
                'key_features' => ['Menu management', 'Online ordering', 'Delivery routing', 'Table reservations'],
                'benefits' => ['Higher order volume', 'Reduced phone calls', 'Better data tracking'],
            ],
            'retail_portal' => [
                'app_name' => 'Retail eCommerce + Inventory Suite',
                'key_features' => ['Product catalog', 'Inventory sync', 'Promotions', 'Customer analytics'],
                'benefits' => ['Unified sales', 'Lower stockouts', 'Omnichannel experience'],
            ],
            'telehealth_portal' => [
                'app_name' => 'Telehealth & Patient Portal',
                'key_features' => ['Video appointments', 'E-prescriptions', 'Patient intake forms'],
                'benefits' => ['Better patient experience', 'Automated scheduling', 'Secure messaging'],
            ],
            'real_estate_portal' => [
                'app_name' => 'Property Management Portal',
                'key_features' => ['Listings', 'Tenant portal', 'Payments', 'Maintenance tickets'],
                'benefits' => ['Faster leasing', 'Less admin work', 'Improved retention'],
            ],
            'education_portal' => [
                'app_name' => 'Learning Management Portal',
                'key_features' => ['Course builder', 'Assessments', 'Progress tracking'],
                'benefits' => ['Scalable training', 'Engaged students', 'Revenue expansion'],
            ],
            'professional_services_portal' => [
                'app_name' => 'Client Portal & CRM',
                'key_features' => ['Bookings', 'CRM', 'Document sharing'],
                'benefits' => ['Streamlined operations', 'Better client experience'],
            ],
            'smb_workflow' => [
                'app_name' => 'Workflow Automation Suite',
                'key_features' => ['Task management', 'Approvals', 'Reporting'],
                'benefits' => ['Less manual work', 'Visibility', 'Accountability'],
            ],
        ];
        $template = $templates[$appTypeKey];
        return [[
            'app_type' => $appTypeKey,
            'app_name' => $template['app_name'],
            'description' => '',
            'key_features' => $template['key_features'],
            'benefits' => $template['benefits'],
        ]];
    }

    private function calculatePrice(array $lead, string $appType): array
    {
        $base = 8000;
        foreach ($this->pricingRuleModel->all() as $rule) {
            if ($rule['app_type'] === $appType) {
                $base = (float)$rule['base_price'];
                break;
            }
        }
        $multiplier = $this->multiplierModel->getMultiplier($lead['company_size'] ?? '1-10');
        $min = $base * $multiplier;
        $max = $min * 1.4;
        return ['min' => round($min, 0), 'max' => round($max, 0)];
    }
}
