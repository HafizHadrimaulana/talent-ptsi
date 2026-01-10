<?php

namespace App\Services\SITMS;

class NullSitmsClient implements SitmsClient
{
    public function fetchEmployeesPage(int $page = 1, int $size = 1000): array
    {
        return ['rows' => [], 'page' => $page, 'per_page' => $size, 'total' => 0, 'last' => 1, 'attempt' => null];
    }

    public function fetchMasters(): array
    {
        return ['directorates' => [], 'units' => [], 'positions' => [], 'levels' => [], 'locations' => []];
    }

    public function getLastHeaders(): array
    {
        return [];
    }
}