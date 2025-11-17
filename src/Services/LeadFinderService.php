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
            $leadData = [
                'company_name' => $data['name'] ?? 'Unknown Company',
                'website' => $data['website'] ?? '',
                'industry' => $data['industry'] ?? 'General',
                'country' => $data['country'] ?? '',
                'city' => $data['city'] ?? '',
                'state_province' => $data['state_province'] ?? '',
                'company_size' => $data['company_size'] ?? '1-10',
                'contact_name' => $data['contact_name'] ?? '',
                'contact_email' => $data['contact_email'] ?? '',
                'lead_score' => $this->scoreLead($data),
            ];
            $this->leadModel->create($leadData);
            $count++;
        }
        fclose($handle);
        return $count;
    }

    public function discoverLeads(array $filters = []): array
    {
        // TODO integrate external APIs such as LinkedIn Sales Navigator or Google Places
        // Use $filters to build API queries. Keep secrets in environment variables.
        return [];
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
}
