<?php

namespace App\Services\SITMS;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\RequestException;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

class HttpSitmsClient implements SitmsClient
{
    protected string $baseUrl;
    protected string $employeesPath;
    protected string $apiKey;
    protected string $cookie;
    protected bool $verifySsl;
    protected int $timeout;
    protected int $retries;
    protected string $pagination;
    protected int $defaultPerPage;
    protected ?string $csrfToken = null;
    protected LoggerInterface $log;
    protected array $lastHeaders = [];
    protected string $authMode;
    protected ?string $stickyAttempt = null;
    protected ?int $stickyPerPage = null;

    public function __construct(LoggerInterface $log)
    {
        $cfg = config('sitms', []);
        $this->baseUrl = (string) ($cfg['base_url'] ?? env('SITMS_BASE_URL', ''));
        $this->employeesPath = (string) ($cfg['paths']['employees_list'] ?? env('SITMS_EMPLOYEE_ENDPOINT', '/employees_list'));
        $this->apiKey = (string) ($cfg['api_key'] ?? env('SITMS_API_KEY', env('SITMS_APIKEY', '')));
        $this->cookie = (string) ($cfg['cookie'] ?? env('SITMS_COOKIE', ''));
        $this->verifySsl = (bool) ($cfg['verify_ssl'] ?? env('SITMS_VERIFY_SSL', true));
        $this->timeout = (int) ($cfg['timeout'] ?? env('SITMS_TIMEOUT', 60));
        $this->retries = (int) ($cfg['retries'] ?? env('SITMS_RETRIES', 5));
        $this->pagination = (string) ($cfg['pagination'] ?? env('SITMS_PAGINATION', 'page'));
        $this->defaultPerPage = (int) ($cfg['per_page'] ?? env('SITMS_PER_PAGE', 1000));
        $this->authMode = (string) ($cfg['auth_mode'] ?? env('SITMS_AUTH_MODE', 'auto'));
        $this->log = $log;
    }

    public function getLastHeaders(): array
    {
        return $this->lastHeaders;
    }

    public function fetchEmployeesPage(int $page = 1, int $size = 1000): array
    {
        $urlBase = $this->resolveUrl($this->baseUrl, $this->employeesPath);
        $this->preflight($urlBase);

        $ladder = [];
        foreach ([$size, $this->defaultPerPage, 2000, 1500, 1000, 500] as $n) {
            $n = max(1, (int) $n);
            if (!in_array($n, $ladder, true)) $ladder[] = $n;
        }

        $total = null;
        $last = null;
        $rows = [];
        $used = null;
        $usedPerPage = max(1, $size);

        if ($this->stickyAttempt && $this->stickyPerPage) {
            [$json, $attempt] = $this->singleAttempt($urlBase, $page, $this->stickyPerPage, $this->stickyAttempt);
            if ($json !== null) {
                $used = $attempt;
                $usedPerPage = $this->stickyPerPage;
                $total = $this->extractTotal($json);
                $rows = $this->extractEmployeesListAggressive($json);
            }
        }

        if ($used === null) {
            foreach ($ladder as $perPage) {
                [$json, $attempt] = $this->tryAllRequestPatterns($urlBase, $page, $perPage);
                if ($json === null) continue;

                $used = $attempt;
                $usedPerPage = $perPage;
                $total = $this->extractTotal($json);
                $rows = $this->extractEmployeesListAggressive($json);

                $this->stickyAttempt = $attempt;
                $this->stickyPerPage = $perPage;
                break;
            }
        }

        if (is_numeric($total) && $usedPerPage > 0) {
            $last = (int) ceil($total / $usedPerPage);
        }

        return [
            'rows' => $rows ?: [],
            'page' => $page,
            'per_page' => $usedPerPage,
            'total' => is_numeric($total) ? (int) $total : null,
            'last' => $last,
            'attempt' => $used,
        ];
    }

    public function fetchMasters(): array
    {
        return ['directorates' => [], 'units' => [], 'positions' => [], 'levels' => [], 'locations' => []];
    }

    protected function singleAttempt(string $urlBase, int $page, int $perPage, string $tag): array
    {
        [$method, $urlFn, $payload, $hdrFn] = $this->attemptByTag($urlBase, $page, $perPage, $tag);
        $url = $urlFn();
        $headers = $hdrFn($url);
        $json = $this->doJsonRequest($method, $url, $payload, $headers, true, $tag);
        return [$json, $tag];
    }

    protected function attemptByTag(string $urlBase, int $page, int $perPage, string $tag): array
    {
        $data = $this->buildPayloads($urlBase, $page, $perPage);
        return $data[$tag] ?? ['GET', fn() => $urlBase, [], fn($u) => $this->headersNoXrw($u)];
    }

    protected function tryAllRequestPatterns(string $urlBase, int $page, int $perPage): array
    {
        $p = $this->buildPayloads($urlBase, $page, $perPage);

        if ($this->pagination === 'page') $order = ['PAGE_GET', 'PAGE_POST_JSON', 'DT_GET', 'DT_POST_FORM', 'OFF_POST_JSON', 'POST_JSON_apikey_only', 'GET_query_apikey', 'GET_bearer_no_xrw', 'GET_x_api_key'];
        elseif ($this->pagination === 'offset') $order = ['OFF_POST_JSON', 'PAGE_POST_JSON', 'PAGE_GET', 'DT_GET', 'DT_POST_FORM', 'POST_JSON_apikey_only', 'GET_query_apikey', 'GET_bearer_no_xrw', 'GET_x_api_key'];
        else $order = ['DT_GET', 'DT_POST_FORM', 'PAGE_GET', 'PAGE_POST_JSON', 'OFF_POST_JSON', 'POST_JSON_apikey_only', 'GET_query_apikey', 'GET_bearer_no_xrw', 'GET_x_api_key'];

        foreach ($order as $tag) {
            [$method, $urlFn, $payload, $hdrFn] = $p[$tag];
            $url = $urlFn();
            $headers = $hdrFn($url);
            $json = $this->doJsonRequest($method, $url, $payload, $headers, true, $tag);
            if ($json !== null) return [$json, $tag];
            $this->preflight($url, true);
        }
        return [null, null];
    }

    protected function buildPayloads(string $urlBase, int $page, int $perPage): array
    {
        $unfilter = [
            'status' => 'all',
            'include_all' => 1,
            'is_active' => '',
            'employee_status' => '',
            'filter' => 'all',
            'show' => 'all',
        ];

        $offset = max(0, ($page - 1) * $perPage);

        $headersBrowser = fn(string $url) => $this->headersBrowser($url);
        $headersNoXrw = fn(string $url, array $extra = []) => $this->headersNoXrw($url, $extra);
        $urlQueryApiKey = $this->appendQuery($urlBase, ['apikey' => $this->apiKey]);

        $pageBody = ['apikey' => $this->apiKey, 'page' => $page, 'per_page' => $perPage] + $unfilter;
        $pageQuery = ['page' => $page, 'per_page' => $perPage] + $unfilter;

        $dtQuery = [
            'start' => ($page - 1) * $perPage,
            'length' => $perPage,
            'draw' => $page,
            'search[value]' => '',
            'search[regex]' => 'false',
        ] + $unfilter;

        $offBody = ['apikey' => $this->apiKey, 'offset' => $offset, 'limit' => $perPage] + $unfilter;

        return [
            'OFF_POST_JSON' => ['POST_JSON', fn() => $urlBase, $offBody, fn($u) => $headersNoXrw($u, ['Content-Type' => 'application/json'])],
            'PAGE_POST_JSON' => ['POST_JSON', fn() => $urlBase, $pageBody, fn($u) => $headersNoXrw($u, ['Content-Type' => 'application/json'])],
            'PAGE_GET' => ['GET', fn() => $urlBase, $pageQuery, fn($u) => $headersNoXrw($u)],
            'DT_GET' => ['GET', fn() => $urlBase, $dtQuery, fn($u) => $headersBrowser($u)],
            'DT_POST_FORM' => ['POST_FORM', fn() => $urlBase, $dtQuery, fn($u) => ($headersBrowser($u) + ['Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'])],
            'POST_JSON_apikey_only' => ['POST_JSON', fn() => $urlBase, ['apikey' => $this->apiKey] + ['include_all' => 1, 'status' => 'all', 'show' => 'all'], fn($u) => $headersNoXrw($u, ['Content-Type' => 'application/json'])],
            'GET_query_apikey' => ['GET', fn() => $urlQueryApiKey, ['include_all' => 1, 'status' => 'all', 'show' => 'all'], fn($u) => $headersNoXrw($u)],
            'GET_bearer_no_xrw' => ['GET', fn() => $urlBase, ['include_all' => 1, 'status' => 'all', 'show' => 'all'], fn($u) => $headersNoXrw($u, ['Authorization' => 'Bearer ' . $this->apiKey])],
            'GET_x_api_key' => ['GET', fn() => $urlBase, ['include_all' => 1, 'status' => 'all', 'show' => 'all'], fn($u) => $headersNoXrw($u, ['X-API-KEY' => $this->apiKey])],
        ];
    }

    protected function defaultClient(array $headers): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withOptions([
            'verify' => $this->verifySsl,
            'allow_redirects' => true,
            'http_errors' => false,
        ])
            ->timeout($this->timeout)
            ->retry($this->retries, 700)
            ->withHeaders($headers);
    }

    protected function preflight(string $endpointUrl, bool $force = false): void
    {
        if ($this->csrfToken && !$force) return;

        $root = $this->baseUrl;
        $parts = parse_url($root);
        $path = $parts['path'] ?? '';
        if (str_contains($path, '/api')) $path = rtrim(str_replace('/api', '', $path), '/');
        $preflightUrl = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . ($path ?: '/');

        $client = $this->defaultClient([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'User-Agent' => env('SITMS_USER_AGENT', 'Mozilla/5.0'),
        ]);
        if ($this->cookie !== '') $client = $client->withHeaders(['Cookie' => $this->cookie]);

        try {
            $resp = $client->get($preflightUrl);
        } catch (RequestException $e) {
            $resp = $e->response;
        }

        $this->captureHeaders($resp);
        $body = $resp->body() ?? '';
        $this->csrfToken = $this->extractCsrfFromHtml($body) ?? $this->extractCsrfFromSetCookie($resp);
        if ($this->csrfToken) $this->log->info('[SITMS] CSRF token captured from preflight');
    }

    protected function doJsonRequest(string $method, string $url, array $payload, array $headers, bool $savePreviewOnHtml, string $tag): ?array
    {
        $client = $this->defaultClient($headers);

        $this->log->info('[SITMS] request', [
            'tag' => $tag,
            'method' => $method,
            'resolved_url' => $url,
            'payload' => $this->logPayloadBrief($payload),
        ]);

        try {
            if ($method === 'POST_FORM') $resp = $client->asForm()->post($url, $payload);
            elseif ($method === 'POST_JSON') $resp = $client->post($url, $payload);
            else $resp = empty($payload) ? $client->get($url) : $client->get($url, $payload);
        } catch (RequestException $e) {
            $resp = $e->response;
        }

        $this->captureHeaders($resp);

        if ($resp->status() === 404) {
            if ($savePreviewOnHtml) $this->savePreview($resp->body() ?? '');
            $this->log->error('[SITMS] 404 Not Found', ['tag' => $tag, 'url' => $url]);
            throw new UnexpectedValueException('404 Not Found pada ' . $url);
        }

        $json = $this->tryParseJson($resp);
        if ($json !== null) return $json;

        $html = $resp->body() ?? '';
        if ($savePreviewOnHtml) $this->savePreview($html);
        if ($this->looksLikeLoginPage($html)) $this->log->error('[SITMS] Looks like login/expired session', ['tag' => $tag]);
        return null;
    }

    protected function tryParseJson(Response $resp): ?array
    {
        $body = $resp->body() ?? '';
        $j = $resp->json();
        if (is_array($j)) return $j;

        if (preg_match('~<pre[^>]*>(\{.*\}|\[.*\])</pre>~is', $body, $m)) {
            $try = json_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5), true);
            if (is_array($try)) return $try;
        }
        if (preg_match('~<script[^>]*>\s*(\{.*\}|\[.*\])\s*</script>~is', $body, $m)) {
            $try = json_decode($m[1], true);
            if (is_array($try)) return $try;
        }
        if (preg_match('~(\{(?:[^{}]|(?1))*\})~s', $body, $m)) {
            $try = json_decode($m[1], true);
            if (is_array($try)) return $try;
        }
        return null;
    }

    protected function captureHeaders(Response $resp): void
    {
        $this->lastHeaders = [
            'status' => $resp->status(),
            'content_type' => $resp->header('Content-Type'),
            'set_cookie' => $resp->header('Set-Cookie'),
        ];
    }

    protected function savePreview(string $html): void
    {
        try {
            $path = storage_path('logs/sitms_last_preview.html');
            file_put_contents($path, $html);
            $this->log->info('[SITMS] saved preview', ['path' => $path, 'bytes' => strlen($html)]);
        } catch (\Throwable $e) {
        }
    }

    protected function extractTotal(array $json): ?int
    {
        $total = $json['employeesCountAll'] ?? ($json['data']['employeesCountAll'] ?? null) ??
            $json['recordsTotal'] ?? ($json['data']['recordsTotal'] ?? null) ??
            ($json['data']['total'] ?? $json['total'] ?? null);

        return is_numeric($total) ? (int) $total : null;
    }

    protected function extractEmployeesListAggressive($json): array
    {
        $candidates = [
            $json['data']['items'] ?? null,
            $json['data']['data'] ?? null,
            $json['data']['rows'] ?? null,
            $json['data']['result'] ?? null,
            $json['data']['employees'] ?? null,
            $json['data']['employeeData'] ?? null,
            $json['items'] ?? null,
            $json['rows'] ?? null,
            $json['result'] ?? null,
            $json['employees'] ?? null,
            $json['employeeData'] ?? null,
            $json['data'] ?? null,
        ];

        $best = null;
        $bestScore = -INF;
        foreach ($candidates as $cand) {
            $score = $this->scoreList($cand);
            if ($score > $bestScore) {
                $best = $cand;
                $bestScore = $score;
            }
        }
        if ($bestScore > 0) return $best ?? [];

        $allLists = [];
        $this->collectAllLists($json, $allLists);
        foreach ($allLists as $list) {
            $score = $this->scoreList($list);
            if ($score > $bestScore) {
                $best = $list;
                $bestScore = $score;
            }
        }
        if ($bestScore > 0) return $best ?? [];

        $stringLists = $this->collectStringifiedLists($json);
        foreach ($stringLists as $arr) {
            $score = $this->scoreList($arr);
            if ($score > $bestScore) {
                $best = $arr;
                $bestScore = $score;
            }
        }
        return is_array($best) ? $best : [];
    }

    protected function collectAllLists($node, array &$out): void
    {
        if (!is_array($node)) return;
        if ($this->isListOfAssoc($node)) $out[] = $node;
        foreach ($node as $v) if (is_array($v)) $this->collectAllLists($v, $out);
    }

    protected function collectStringifiedLists($node): array
    {
        $out = [];
        $walker = function ($n) use (&$walker, &$out) {
            if (is_array($n)) {
                foreach ($n as $v) $walker($v);
            } elseif (is_string($n) && strlen($n) > 2 && ($n[0] == '{' || $n[0] == '[')) {
                $arr = json_decode($n, true);
                if ($this->isListOfAssoc($arr)) $out[] = $arr;
                if (is_array($arr)) {
                    $tmp = [];
                    $this->collectAllLists($arr, $tmp);
                    foreach ($tmp as $t) $out[] = $t;
                }
            }
        };
        $walker($node);
        return $out;
    }

    protected function scoreList($v): float
    {
        if (!$this->isListOfAssoc($v)) return -INF;
        $first = $v[0] ?? [];
        $keys = array_map('strtolower', array_keys($first));
        $cand = ['employee_id', 'id_sitms', 'id', 'full_name', 'nik_number', 'date_of_birth', 'email', 'unit', 'unit_name', 'position', 'jabatan', 'directorate', 'direktorat'];
        $hits = 0;
        foreach ($cand as $k) if (in_array($k, $keys, true)) $hits++;
        $len = is_countable($v) ? count($v) : 0;
        return $hits + min($len / 1000, 0.5);
    }

    protected function isListOfAssoc($v): bool
    {
        if (!is_array($v) || $v === []) return false;
        $i = 0;
        foreach ($v as $k => $_) {
            if ($k !== $i++) return false;
        }
        return is_array($v[0] ?? null) && $this->isAssoc($v[0]);
    }

    protected function isAssoc(array $a): bool
    {
        foreach (array_keys($a) as $k) if (!is_int($k)) return true;
        return false;
    }

    protected function looksLikeLoginPage(string $html): bool
    {
        $h = strtolower($html);
        return str_contains($h, '<form') && str_contains($h, 'name="_token"')
            || str_contains($h, 'csrf-token')
            || str_contains($h, 'login')
            || str_contains($h, 'masuk')
            || str_contains($h, 'auth');
    }

    protected function extractCsrfFromHtml(string $html): ?string
    {
        if (preg_match('~<meta\s+name=["\']csrf-token["\']\s+content=["\']([^"\']+)["\']~i', $html, $m)) return $m[1];
        if (preg_match('~name=["\']_token["\']\s+value=["\']([^"\']+)["\']~i', $html, $m)) return $m[1];
        return null;
    }

    protected function extractCsrfFromSetCookie(Response $resp): ?string
    {
        $set = $resp->header('Set-Cookie');
        if (!$set) return null;
        $arr = is_array($set) ? $set : [$set];
        foreach ($arr as $line) {
            if (preg_match('~XSRF-TOKEN=([^;]+)~', $line, $m)) return urldecode($m[1]);
            if (preg_match('~csrf[^=]*=([^;]+)~i', $line, $m)) return urldecode($m[1]);
        }
        return null;
    }

    protected function headersBrowser(string $url): array
    {
        $h = [
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'X-Requested-With' => 'XMLHttpRequest',
            'User-Agent' => env('SITMS_USER_AGENT', 'Mozilla/5.0'),
            'Referer' => $this->refererFor($url),
        ];
        if ($this->cookie !== '') $h['Cookie'] = $this->cookie;
        if ($this->authMode === 'bearer' || ($this->authMode === 'auto' && $this->apiKey !== '')) {
            $h['Authorization'] = 'Bearer ' . $this->apiKey;
        }
        if ($this->csrfToken) $h['X-CSRF-TOKEN'] = $this->csrfToken;
        return $h;
    }

    protected function headersNoXrw(string $url, array $extra = []): array
    {
        $h = [
            'Accept' => 'application/json',
            'User-Agent' => env('SITMS_USER_AGENT', 'Mozilla/5.0'),
            'Referer' => $this->refererFor($url),
        ] + $extra;
        if ($this->cookie !== '') $h['Cookie'] = $this->cookie;
        if ($this->csrfToken) $h['X-CSRF-TOKEN'] = $this->csrfToken;
        return $h;
    }

    protected function resolveUrl(string $base, string $endpoint): string
    {
        $b = rtrim($base, '/');
        $e = '/' . ltrim($endpoint, '/');
        $basePath = parse_url($b, PHP_URL_PATH) ?: '';
        if ($basePath !== '' && str_starts_with($e, $basePath . '/')) {
            $e = substr($e, strlen($basePath));
            if ($e === '') $e = '/';
        }
        return $b . $e;
    }

    protected function appendQuery(string $url, array $q): string
    {
        $sep = str_contains($url, '?') ? '&' : '?';
        return $url . $sep . http_build_query($q);
    }

    protected function refererFor(string $url): string
    {
        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '/';
        $dir = rtrim(substr($path, 0, strrpos($path, '/')), '/');
        if ($dir === '') $dir = '/';
        return $scheme . '://' . $host . $dir . '/';
    }

    protected function logPayloadBrief(array $p): array
    {
        $out = $p;
        if (isset($out['apikey'])) $out['apikey'] = '***';
        return $out;
    }
}