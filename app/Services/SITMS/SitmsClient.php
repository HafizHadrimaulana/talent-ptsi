<?php

namespace App\Services\SITMS;

interface SitmsClient {
    /** @return array{rows: array<int,array>, page:int, per_page:int, total:int|null, last:int|null, attempt:?string} */
    public function fetchEmployeesPage(int $page=1, int $size=1000): array;
    public function fetchMasters(): array;
    public function getLastHeaders(): array;
}
