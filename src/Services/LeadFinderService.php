<?php

namespace App\Services;

use App\Models\Lead;

class LeadFinderService
{
    private Lead $leadModel;
    private ?string $lastError = null;

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

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function discoverLeads(array $filters = []): array
    {
        $this->lastError = null;
        $apiKey = getenv('GOOGLE_PLACES_API_KEY') ?: '';
        if ($apiKey === '') {
            $this->lastError = 'Google Places API key missing. Add GOOGLE_PLACES_API_KEY to your environment.';
            return [];
        }

        $query = $this->buildQueryFromFilters($filters);
        if ($query === '') {
            // No filters provided; skip remote discovery so the default list still loads quickly.
            return [];
        }

        $params = [
            'query' => $query,
            'key' => $apiKey,
        ];
        if (!empty($filters['country']) && strlen($filters['country']) === 2) {
            $params['region'] = strtolower($filters['country']);
        }

        $searchResponse = $this->fetchJson('https://maps.googleapis.com/maps/api/place/textsearch/json?' . http_build_query($params));
        if ($searchResponse === null) {
            $this->lastError = 'Unable to contact Google Places API.';
            return [];
        }

        $status = $searchResponse['status'] ?? 'UNKNOWN_ERROR';
        if ($status !== 'OK') {
            if ($status === 'ZERO_RESULTS') {
                return [];
            }
            $this->lastError = $searchResponse['error_message'] ?? ('Google Places error: ' . $status);
            return [];
        }

        $leads = [];
        $results = array_slice($searchResponse['results'] ?? [], 0, 8);
        foreach ($results as $result) {
            if (empty($result['place_id'])) {
                continue;
            }
            $place = $this->fetchPlaceDetails($result['place_id'], $apiKey);
            $address = $this->extractAddressComponents($place, $result['formatted_address'] ?? '');
            $industry = $this->inferIndustry($filters, $result, $place);
            $companySize = $filters['company_size'] ?? '';
            $leadData = [
                'id' => null,
                'source' => 'google_places',
                'company_name' => $result['name'] ?? 'Unknown Company',
                'website' => $place['website'] ?? '',
                'industry' => $industry,
                'country' => $address['country'] ?? ($filters['country'] ?? ''),
                'city' => $address['city'] ?? ($filters['city'] ?? ''),
                'state_province' => $address['state'] ?? '',
                'company_size' => $companySize !== '' ? $companySize : '1-10',
                'contact_name' => '',
                'contact_email' => $place['email'] ?? '',
                'contact_phone' => $place['formatted_phone_number'] ?? ($place['international_phone_number'] ?? ''),
                'status' => 'new',
                'formatted_address' => $result['formatted_address'] ?? '',
                'place_id' => $result['place_id'],
                'rating' => $result['rating'] ?? null,
                'user_ratings_total' => $result['user_ratings_total'] ?? null,
            ];
            $leadData['lead_score'] = $this->scoreLead([
                'industry' => $leadData['industry'],
                'website' => $leadData['website'],
                'contact_email' => $leadData['contact_email'],
            ]);
            $leads[] = $leadData;
        }

        return $leads;
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

    private function buildQueryFromFilters(array $filters): string
    {
        $segments = [];
        if (!empty($filters['industry'])) {
            $segments[] = trim($filters['industry']) . ' companies';
        }
        $location = trim(($filters['city'] ?? '') . ' ' . ($filters['country'] ?? ''));
        if ($location !== '') {
            $segments[] = $location;
        }
        return trim(implode(' in ', array_filter($segments)));
    }

    private function fetchJson(string $url): ?array
    {
        $response = null;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
            ]);
            $response = curl_exec($ch);
            if ($response === false) {
                curl_close($ch);
                return null;
            }
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($status >= 400) {
                return null;
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                ],
            ]);
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                return null;
            }
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function fetchPlaceDetails(string $placeId, string $apiKey): array
    {
        $params = [
            'place_id' => $placeId,
            'fields' => 'name,website,formatted_address,formatted_phone_number,international_phone_number,address_components,types',
            'key' => $apiKey,
        ];
        $details = $this->fetchJson('https://maps.googleapis.com/maps/api/place/details/json?' . http_build_query($params));
        if (!is_array($details) || ($details['status'] ?? '') !== 'OK') {
            return [];
        }
        return $details['result'] ?? [];
    }

    private function extractAddressComponents(array $place, string $fallback = ''): array
    {
        $parsed = [
            'city' => '',
            'state' => '',
            'country' => '',
        ];
        foreach ($place['address_components'] ?? [] as $component) {
            $types = $component['types'] ?? [];
            if (in_array('locality', $types, true) || in_array('postal_town', $types, true)) {
                $parsed['city'] = $component['long_name'];
            }
            if (in_array('administrative_area_level_1', $types, true)) {
                $parsed['state'] = $component['long_name'];
            }
            if (in_array('country', $types, true)) {
                $parsed['country'] = $component['long_name'];
            }
        }
        if ($fallback !== '') {
            $parts = array_map('trim', explode(',', $fallback));
            if ($parsed['country'] === '' && !empty($parts)) {
                $parsed['country'] = array_pop($parts);
            }
            if ($parsed['state'] === '' && !empty($parts)) {
                $parsed['state'] = array_pop($parts);
            }
            if ($parsed['city'] === '' && !empty($parts)) {
                $parsed['city'] = array_pop($parts);
            }
        }
        return $parsed;
    }

    private function inferIndustry(array $filters, array $result, array $place = []): string
    {
        if (!empty($filters['industry'])) {
            return $filters['industry'];
        }
        $types = $place['types'] ?? $result['types'] ?? [];
        $ignored = ['point_of_interest', 'establishment', 'store'];
        foreach ($types as $type) {
            if (in_array($type, $ignored, true)) {
                continue;
            }
            return ucwords(str_replace('_', ' ', $type));
        }
        return 'General';
    }
}