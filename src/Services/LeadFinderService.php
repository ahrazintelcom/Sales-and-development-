<?php

namespace App\Services;

use App\Models\Lead;

class LeadFinderService
{
    private Lead $leadModel;

    public function __construct()
    {
        $this->leadModel = new Lead();
    }

    public function importCsv(string $path): int
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return 0;
        }
        $header = fgetcsv($handle);
        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            $leadData = $this->normalizeLeadData([
                'company_name' => $data['name'] ?? $data['company_name'] ?? 'Unknown Company',
                'website' => $data['website'] ?? '',
                'industry' => $data['industry'] ?? 'General',
                'country' => $data['country'] ?? '',
                'city' => $data['city'] ?? '',
                'state_province' => $data['state_province'] ?? '',
                'company_size' => $data['company_size'] ?? '1-10',
                'contact_name' => $data['contact_name'] ?? '',
                'contact_email' => $data['contact_email'] ?? '',
                'description' => $data['description'] ?? null,
            ]);
            $leadData['lead_score'] = $this->scoreLead($leadData);
            if ($this->leadModel->existsByCompanyOrWebsite($leadData['company_name'], $leadData['website'])) {
                continue;
            }
            $this->leadModel->create($leadData);
            $count++;
        }
        fclose($handle);
        return $count;
    }

    public function discoverLeads(array $filters = []): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $sample = $this->seedDataset();
        $results = array_filter($sample, function ($lead) use ($normalizedFilters) {
            if ($normalizedFilters['country'] && strcasecmp($lead['country'], $normalizedFilters['country']) !== 0) {
                return false;
            }
            if ($normalizedFilters['industry'] && strcasecmp($lead['industry'], $normalizedFilters['industry']) !== 0) {
                return false;
            }
            if ($normalizedFilters['company_size'] && strcasecmp($lead['company_size'], $normalizedFilters['company_size']) !== 0) {
                return false;
            }
            if ($normalizedFilters['city'] && stripos($lead['city'] . ' ' . $lead['state_province'], $normalizedFilters['city']) === false) {
                return false;
            }
            return true;
        });
        if (empty($results)) {
            $results = array_slice($sample, 0, 3);
        }
        return array_map(fn ($lead) => $this->normalizeLeadData($lead, $normalizedFilters), $results);
    }

    public function persistDiscoveredLeads(array $leads): array
    {
        $created = 0;
        $skipped = [];
        foreach ($leads as $lead) {
            $lead['lead_score'] = $this->scoreLead($lead);
            if ($this->leadModel->existsByCompanyOrWebsite($lead['company_name'], $lead['website'])) {
                $skipped[] = $lead['company_name'];
                continue;
            }
            $this->leadModel->create($lead);
            $created++;
        }
        return [
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    private function scoreLead(array $data): int
    {
        $score = 50;
        if (!empty($data['industry'])) {
            $score += 10;
        }
        if (!empty($data['website'])) {
            $score += 15;
        }
        if (!empty($data['contact_email'])) {
            $score += 10;
        }
        return min(100, $score);
    }

    private function normalizeLeadData(array $lead, array $filters = []): array
    {
        $payload = [
            'company_name' => $lead['company_name'] ?? 'Unknown Company',
            'website' => $lead['website'] ?? '',
            'industry' => $lead['industry'] ?? ($filters['industry'] ?? 'General'),
            'description' => $lead['description'] ?? null,
            'country' => $lead['country'] ?? ($filters['country'] ?? ''),
            'state_province' => $lead['state_province'] ?? '',
            'city' => $lead['city'] ?? ($filters['city'] ?? ''),
            'company_size' => $lead['company_size'] ?? ($filters['company_size'] ?? ''),
            'contact_name' => $lead['contact_name'] ?? 'Unknown Contact',
            'contact_email' => $lead['contact_email'] ?? '',
        ];
        foreach ($payload as $key => $value) {
            if (is_string($value)) {
                $payload[$key] = trim($value);
            }
        }
        return $payload;
    }

    private function normalizeFilters(array $filters): array
    {
        return [
            'country' => trim($filters['country'] ?? ''),
            'industry' => trim($filters['industry'] ?? ''),
            'company_size' => trim($filters['company_size'] ?? ''),
            'city' => trim($filters['city'] ?? ''),
        ];
    }

    private function seedDataset(): array
    {
        return [
            [
                'company_name' => 'Blue Ridge Retail Co.',
                'website' => 'https://blueridge-retail.example.com',
                'industry' => 'Retail',
                'description' => 'Regional outdoor retailer expanding omnichannel operations.',
                'country' => 'USA',
                'state_province' => 'CO',
                'city' => 'Denver',
                'company_size' => '51-200',
                'contact_name' => 'Maya Lopez',
                'contact_email' => 'maya.lopez@blueridge.example.com',
            ],
            [
                'company_name' => 'Northwind Bistros',
                'website' => 'https://northwind-bistros.example.com',
                'industry' => 'Restaurant',
                'description' => 'Modern casual dining group searching for loyalty platform.',
                'country' => 'Canada',
                'state_province' => 'BC',
                'city' => 'Vancouver',
                'company_size' => '11-50',
                'contact_name' => 'Elliot Wells',
                'contact_email' => 'elliot@northwindbistros.example.com',
            ],
            [
                'company_name' => 'Evergreen Family Clinics',
                'website' => 'https://evergreenfamily.example.com',
                'industry' => 'Healthcare',
                'description' => 'Multi-location care network digitizing patient intake.',
                'country' => 'USA',
                'state_province' => 'WA',
                'city' => 'Seattle',
                'company_size' => '200+',
                'contact_name' => 'Dr. Priya Raman',
                'contact_email' => 'priya.raman@evergreenfamily.example.com',
            ],
            [
                'company_name' => 'Beacon Title Partners',
                'website' => 'https://beacon-title.example.com',
                'industry' => 'Real Estate',
                'description' => 'Independent title firm looking for workflow automation.',
                'country' => 'USA',
                'state_province' => 'FL',
                'city' => 'Tampa',
                'company_size' => '51-200',
                'contact_name' => 'Nina Patel',
                'contact_email' => 'nina@beacontitle.example.com',
            ],
            [
                'company_name' => 'Hudson Professional Services',
                'website' => 'https://hudsonpro.example.com',
                'industry' => 'Professional Services',
                'description' => 'Accounting consultancy modernizing client portal experience.',
                'country' => 'Canada',
                'state_province' => 'ON',
                'city' => 'Toronto',
                'company_size' => '11-50',
                'contact_name' => 'Leah Brooks',
                'contact_email' => 'leah.brooks@hudsonpro.example.com',
            ],
        ];
    }
}
