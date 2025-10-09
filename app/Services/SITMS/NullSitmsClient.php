<?php
namespace App\Services\SITMS;

class NullSitmsClient implements SitmsClient {
    public function fetchEmployeesPage(int $page=1, int $size=1000): array { return ['data'=>[], 'meta'=>[]]; }
    public function fetchMasters(): array { return ['directorates'=>[], 'units'=>[], 'positions'=>[], 'levels'=>[], 'locations'=>[]]; }
}
