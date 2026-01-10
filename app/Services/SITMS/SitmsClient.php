<?php

namespace App\Services\SITMS;

interface SitmsClient
{
    public function fetchEmployeesPage(int $page = 1, int $size = 1000): array;
    public function fetchMasters(): array;
    public function getLastHeaders(): array;
}