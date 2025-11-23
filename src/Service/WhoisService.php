<?php
namespace App\Service;

class WhoisService
{
    private string $baseUrl;
    private ?string $apiKey;
    private CacheService $cache;

    public function __construct(string $baseUrl, ?string $apiKey = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->cache = new CacheService();
    }

    public function getWhois(string $domain): array
    {
        $cacheKey = 'whois_' . $domain;
        return $this->cache->get($cacheKey, function () use ($domain) {
            return $this->queryWhois($domain);
        }, 3600);
    }

    public function isAvailable(string $domain): bool
    {
        $data = $this->getWhois($domain);
        return ($data['available'] ?? false) === true;
    }

    public function cacheWhois(string $domain): void
    {
        $this->cache->set('whois_' . $domain, $this->queryWhois($domain), 3600);
    }

    private function queryWhois(string $domain): array
    {
        if (preg_match('/\.ir$/i', $domain)) {
            return $this->queryIrnic($domain);
        }

        $url = $this->baseUrl . '/whois?domain=' . urlencode($domain);
        $ch = curl_init($url);
        $headers = ['Accept: application/json'];
        if ($this->apiKey) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err !== '' || $code >= 400) {
            return ['success' => false, 'available' => false, 'raw' => $body, 'error' => $err ?: ('HTTP ' . $code)];
        }

        $json = json_decode((string)$body, true);
        if (is_array($json)) {
            return $json;
        }

        return ['success' => true, 'available' => false, 'raw' => $body];
    }

    private function queryIrnic(string $domain): array
    {
        $fp = @fsockopen('whois.nic.ir', 43, $errno, $errstr, 8);
        if (!$fp) {
            return ['success' => false, 'available' => false, 'error' => $errstr];
        }
        fwrite($fp, $domain . "\r\n");
        $response = '';
        while (!feof($fp)) {
            $response .= fgets($fp, 128);
        }
        fclose($fp);

        $available = stripos($response, 'no entries found') !== false;
        return [
            'success' => true,
            'available' => $available,
            'raw' => $response,
        ];
    }
}
