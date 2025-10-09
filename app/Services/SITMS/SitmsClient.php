<?php
namespace App\Services\SITMS;

interface SitmsClient {
    /** @return array{data: array<int,array>, meta?: array} */
    public function fetchEmployeesPage(int $page=1, int $size=1000): array;

    /** @return array{directorates: array, units: array, positions: array, levels: array, locations: array}|array */
    public function fetchMasters(): array;
}
