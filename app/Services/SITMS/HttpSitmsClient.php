<?php

namespace App\Services\SITMS;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class HttpSitmsClient implements SitmsClient
{
    protected array $lastHeaders = [];

    public function fetchEmployeesPage(int $page=1, int $size=1000): array
    {
        $cfg   = config('sitms');
        $base  = rtrim($cfg['base_url'], '/');
        $host  = parse_url($base, PHP_URL_HOST) ?? '';
        $cookies   = $this->cookieArrayFromEnv($cfg['cookie']);
        $hasApiKey = filled($cfg['apikey']);

        $baseHeaders = [
            'Accept'     => 'application/json',
            'User-Agent' => 'HCIS-PTSI/1.0',
        ];
        if ($hasApiKey) {
            $baseHeaders['X-API-KEY'] = $cfg['apikey'];
        }

        $queryApiKey = $hasApiKey ? ('&apikey='.urlencode($cfg['apikey'])) : '';
        $url = "$base/admin/api/employees_list?page={$page}&size={$size}{$queryApiKey}";

        $req = Http::withHeaders($baseHeaders)->timeout($cfg['timeout']);
        $req = $hasApiKey ? $req : $req->withCookies($cookies, $host);
        $res = $req->get($url);

        $this->lastHeaders = $this->normalizeHeaders($res->headers());
        $json = (array) $res->json();
        $rows = $json['employeeData'] ?? ($json['data'] ?? null);

        if (!is_array($rows)) {
            $url = "$base/admin/api/employees_list";
            $postBody = [
                'start'  => max(0, ($page-1) * $size),
                'length' => $size,
                'search' => ['value' => ''],
            ];
            if ($hasApiKey) {
                $postBody['apikey'] = $cfg['apikey'];
            }

            $req = Http::withHeaders(array_merge($baseHeaders, [
                        'Content-Type'     => 'application/json',
                        'X-Requested-With' => 'XMLHttpRequest',
                    ]))
                    ->timeout($cfg['timeout']);
            $req = $hasApiKey ? $req : $req->withCookies($cookies, $host);

            $res  = $req->post($url, $postBody);
            $this->lastHeaders = $this->normalizeHeaders($res->headers());
            $json = (array) $res->json();
            $rows = $json['employeeData'] ?? ($json['data'] ?? []);
        }

        $rows = is_array($rows) ? $rows : [];
        $count = count($rows);

        $total = (int) (
            $json['employeesCountAll'] ?? $json['recordsFiltered'] ?? $json['recordsTotal'] ?? 0
        );

        $perPage = $count > 0 ? $count : $size;
        $lastPage = $total > 0 ? (int) ceil($total / max(1, $size)) : 0;

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total ?: null,
                'last_page'    => $lastPage ?: null,
            ],
        ];
    }

    public function fetchMasters(): array
    {
        return ['directorates'=>[], 'units'=>[], 'positions'=>[], 'levels'=>[], 'locations'=>[]];
    }

    public function getLastHeaders(): array
    {
        return $this->lastHeaders;
    }

    private function cookieArrayFromEnv(string $cookie): array
    {
        $out = [];
        foreach (array_filter(array_map('trim', preg_split('/;\s*/', $cookie) ?: [])) as $pair) {
            if (!str_contains($pair, '=')) continue;
            [$k,$v] = explode('=', $pair, 2);
            $k = trim($k); $v = trim($v);
            if ($k !== '') $out[$k] = $v;
        }
        return $out;
    }

    private function normalizeHeaders(array $headers): array
    {
        $flat = [];
        foreach ($headers as $k => $vals) {
            if (is_array($vals) && count($vals) > 0) {
                $flat[$k] = $vals[0];
            } else {
                $flat[$k] = is_scalar($vals) ? (string)$vals : '';
            }
        }
        return $flat;
    }
}
