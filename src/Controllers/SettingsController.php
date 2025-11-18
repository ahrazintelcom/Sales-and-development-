<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\CompanySizeMultiplier;
use App\Models\PricingRule;
use App\Models\Setting;

class SettingsController extends Controller
{
    private Setting $settingModel;
    private PricingRule $pricingRuleModel;
    private CompanySizeMultiplier $multiplierModel;

    public function __construct()
    {
        $this->settingModel = new Setting();
        $this->pricingRuleModel = new PricingRule();
        $this->multiplierModel = new CompanySizeMultiplier();
    }

    public function index(): void
    {
        Auth::requireRole(['admin']);
        $settings = $this->settingModel->all();
        $pricingRules = $this->pricingRuleModel->all();
        $multipliers = $this->multiplierModel->all();
        $this->view('settings/index', compact('settings', 'pricingRules', 'multipliers'));
    }

    public function save(): void
    {
        Auth::requireRole(['admin']);
        foreach (['openai_api_key', 'openai_model', 'github_token', 'github_org'] as $key) {
            if (isset($_POST[$key])) {
                $this->settingModel->set($key, trim($_POST[$key]));
            }
        }
        $this->redirect('/?route=settings');
    }

    public function savePricing(): void
    {
        Auth::requireRole(['admin']);
        $appType = $_POST['app_type'];
        $basePrice = (float)$_POST['base_price'];
        $this->pricingRuleModel->set($appType, $basePrice);
        $this->redirect('/?route=settings');
    }

    public function saveMultiplier(): void
    {
        Auth::requireRole(['admin']);
        $category = $_POST['size_category'];
        $multiplier = (float)$_POST['multiplier'];
        $this->multiplierModel->set($category, $multiplier);
        $this->redirect('/?route=settings');
    }
}
