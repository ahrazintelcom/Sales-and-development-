<?php

namespace App\Services;

class PricingService
{
    private array $basePrices = [
        'restaurant' => 8000,
        'ordering' => 8000,
        'delivery' => 8500,
        'retail' => 9000,
        'commerce' => 9500,
        'marketplace' => 15000,
        'property' => 12000,
        'health' => 11000,
        'education' => 9000,
        'portal' => 9000,
        'workflow' => 7000,
        'default' => 5000,
    ];

    private array $sizeMultipliers = [
        '1-10' => 1.0,
        'solo' => 1.0,
        '11-50' => 1.2,
        '51-200' => 1.5,
        '200+' => 1.8,
        'enterprise' => 1.8,
    ];

    public function estimateRange(?string $appType, ?string $companySize): array
    {
        $base = $this->resolveBasePrice($appType);
        $multiplier = $this->resolveMultiplier($companySize);
        $min = $base * $multiplier;
        $max = $min * 1.4;
        return [
            'min' => round($min, 0),
            'max' => round($max, 0),
        ];
    }

    private function resolveBasePrice(?string $appType): float
    {
        if (!$appType) {
            return (float)$this->basePrices['default'];
        }
        $type = strtolower($appType);
        foreach ($this->basePrices as $keyword => $price) {
            if ($keyword === 'default') {
                continue;
            }
            if (str_contains($type, $keyword)) {
                return (float)$price;
            }
        }
        return (float)$this->basePrices['default'];
    }

    private function resolveMultiplier(?string $companySize): float
    {
        if (!$companySize) {
            return 1.0;
        }
        $key = strtolower(trim($companySize));
        foreach ($this->sizeMultipliers as $size => $multiplier) {
            if ($key === strtolower($size)) {
                return (float)$multiplier;
            }
        }
        if (preg_match('/(\d+)\s*-\s*(\d+)/', $companySize, $matches)) {
            $upper = (int)$matches[2];
            if ($upper <= 10) {
                return 1.0;
            }
            if ($upper <= 50) {
                return 1.2;
            }
            if ($upper <= 200) {
                return 1.5;
            }
        }
        if (preg_match('/(\d+)/', $companySize, $matches) && (int)$matches[1] >= 200) {
            return 1.8;
        }
        return 1.0;
    }
}
