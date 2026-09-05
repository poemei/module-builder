<?php
declare(strict_types=1);

/* [AI:GPT-5.6 | 2026-09-04 UTC] */
/**
 * Shared read-only Chaos MVC developer-certification client.
 *
 * Certification describes developer qualification. It never authorizes Builder
 * use and never replaces release signature verification.
 */
final class builder_certification_client
{
    private const DEFAULT_ENDPOINT = 'https://chaos-mvc.org/developers/verify';
    private const SUCCESS_TTL = 86400;
    private const FAILURE_TTL = 300;
    private const MAX_RESPONSE_BYTES = 65536;
    private string $transportKey = '';

    public function __construct(private string $cacheDirectory)
    {
        $config = json_decode((string) @file_get_contents(__DIR__ . '/../data/certification.json'), true);
        $key = is_array($config) ? strtolower(trim((string) ($config['transport_key'] ?? ''))) : '';
        if (preg_match('/^[a-f0-9]{64}$/', $key)) $this->transportKey = $key;
    }

    public function verify(string $developer, string $domain, string $artifactType, string $algorithm, string $keyId): array
    {
        $developer = trim($developer);
        $domain = strtolower(trim($domain));
        $artifactType = strtolower(trim($artifactType));
        $algorithm = strtolower(trim($algorithm));
        $keyId = strtolower(trim($keyId));
        $base = [
            'state' => 'not_verified',
            'certified' => false,
            'signing' => false,
            'developer' => $developer,
            'domain' => $domain,
            'certification' => $artifactType,
            'key_id' => $keyId,
            'credential_id' => null,
            'public_key' => null,
            'fingerprint' => null,
            'reason' => null,
            'message' => 'Certification: Not Verified. Builder and signing remain available.',
        ];
        if ($developer === '' || !$this->validDomain($domain)
            || !in_array($artifactType, ['module', 'theme'], true)
            || !in_array($algorithm, ['rsa-sha256', 'openpgp'], true)
            || !preg_match('/^[a-z0-9][a-z0-9_-]{2,63}$/', $keyId)) {
            return array_replace($base, ['reason' => 'identity_not_configured']);
        }

        $cache = $this->readCache($developer, $domain, $artifactType, $algorithm, $keyId);
        if ($cache !== null) {
            return array_replace($base, $cache, ['message' => $this->message($cache)]);
        }

        $endpoint = trim((string) (getenv('CHAOS_CERTIFICATION_ENDPOINT') ?: self::DEFAULT_ENDPOINT));
        if (!$this->validEndpoint($endpoint)) {
            return array_replace($base, [
                'state' => 'unavailable',
                'reason' => 'invalid_endpoint',
                'message' => 'Certification: Unavailable (invalid certification endpoint). Builder and signing remain available.',
            ]);
        }

        $query = http_build_query([
            'developer' => $developer,
            'domain' => $domain,
            'type' => $artifactType,
            'key_id' => $keyId,
        ], '', '&', PHP_QUERY_RFC3986);
        $separator = str_contains($endpoint, '?') ? '&' : '?';
        [$status, $raw, $transportError] = $this->request($endpoint . $separator . $query);
        $response = is_string($raw) ? json_decode($raw, true) : null;
        if ($status < 200 || $status >= 300 || !is_array($response)) {
            $failure = $transportError !== '' ? $transportError
                : ($status > 0 ? 'HTTP ' . $status . (is_string($raw) && !is_array($response) ? ', response was not JSON' : '') : 'no HTTP response');
            return array_replace($base, [
                'state' => 'unavailable',
                'reason' => 'service_unavailable',
                'message' => 'Certification: Unavailable (' . $failure . '). Builder and signing remain available.',
            ]);
        }

        $result = $this->normalize($response, $developer, $domain, $artifactType, $algorithm, $keyId);
        $this->writeCache($developer, $domain, $artifactType, $algorithm, $keyId, $result);
        return array_replace($base, $result, ['message' => $this->message($result)]);
    }

    public function normalize(array $response, string $developer, string $domain, string $artifactType, string $algorithm, string $keyId): array
    {
        $reason = (string) ($response['reason'] ?? 'invalid_response');
        $certification = is_array($response['certification'] ?? null)
            ? $response['certification'] : [];
        $certificationType = is_string($response['certification'] ?? null)
            ? $response['certification'] : ($certification['type'] ?? null);
        $exact = is_string($response['developer'] ?? null)
            && hash_equals($developer, $response['developer'])
            && is_string($response['domain'] ?? null)
            && hash_equals($domain, strtolower($response['domain']))
            && is_string($certificationType)
            && hash_equals($artifactType, strtolower($certificationType));
        $signing = is_array($response['signing'] ?? null) ? $response['signing'] : [];
        $signingExact = is_string($signing['algorithm'] ?? null)
            && hash_equals($algorithm, strtolower($signing['algorithm']))
            && is_string($signing['key_id'] ?? null)
            && hash_equals($keyId, strtolower($signing['key_id']));
        $expiry = $response['expires_at'] ?? $certification['expires_at'] ?? null;
        $notExpired = $expiry === null || (is_string($expiry) && strtotime($expiry) !== false && strtotime($expiry) > time());
        $active = !array_key_exists('status', $response)
            || (is_string($response['status']) && strtolower(trim($response['status'])) === 'active');
        $verified = ($response['certified'] ?? false) === true && $exact && $signingExact && $notExpired && $active;
        $publicKeySource = $signing['public_key'] ?? $response['signing_public_key'] ?? null;
        $publicKey = $verified && is_string($publicKeySource)
            ? (str_contains($publicKeySource, '-----BEGIN ')
                ? base64_encode(trim($publicKeySource) . "\n")
                : preg_replace('/\s+/', '', $publicKeySource)) : null;
        if ($publicKey !== null && ($publicKey === '' || base64_decode($publicKey, true) === false)) {
            $publicKey = null;
        }
        return [
            'state' => $verified ? 'verified' : 'not_verified',
            'certified' => $verified,
            'signing' => $verified,
            'developer' => $developer,
            'domain' => $domain,
            'certification' => $artifactType,
            'key_id' => $keyId,
            'credential_id' => $verified && is_scalar($response['credential_id'] ?? $certification['credential_id'] ?? null)
                ? (string) ($response['credential_id'] ?? $certification['credential_id']) : null,
            'public_key' => $publicKey,
            'fingerprint' => $verified && is_string($signing['fingerprint'] ?? $response['signing_fingerprint'] ?? $response['openpgp_fingerprint'] ?? null)
                ? trim((string) ($signing['fingerprint'] ?? $response['signing_fingerprint'] ?? $response['openpgp_fingerprint'])) : null,
            'reason' => $verified ? null : (!$notExpired ? 'certification_expired' : (!$active ? 'certification_inactive' : $reason)),
            'verified_at' => gmdate('c'),
            'expires_at' => gmdate('c', time() + ($verified ? self::SUCCESS_TTL : self::FAILURE_TTL)),
        ];
    }

    private function message(array $result): string
    {
        return ($result['state'] ?? '') === 'verified'
            ? 'Certification: Verified. Developer, domain, artifact type, and signing identity match chaos-mvc.org.'
            : 'Certification: Not Verified' . (!empty($result['reason']) ? ' (' . $result['reason'] . ')' : '')
                . '. Builder and signing remain available.';
    }

    private function readCache(string $developer, string $domain, string $type, string $algorithm, string $keyId): ?array
    {
        $path = $this->cachePath($developer, $domain, $type, $algorithm, $keyId);
        if (!is_file($path) || is_link($path)) return null;
        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded) || !is_string($decoded['expires_at'] ?? null)) return null;
        $expiry = strtotime($decoded['expires_at']);
        return $expiry !== false && $expiry > time() ? $decoded : null;
    }

    private function writeCache(string $developer, string $domain, string $type, string $algorithm, string $keyId, array $result): void
    {
        if (is_link($this->cacheDirectory)
            || (!is_dir($this->cacheDirectory) && !mkdir($this->cacheDirectory, 0700, true))) return;
        $path = $this->cachePath($developer, $domain, $type, $algorithm, $keyId);
        $temporary = $path . '.tmp-' . bin2hex(random_bytes(6));
        $json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        if (file_put_contents($temporary, $json, LOCK_EX) === strlen($json)) {
            chmod($temporary, 0600);
            if (!file_exists($path)) rename($temporary, $path);
            else { unlink($path); rename($temporary, $path); }
        }
        if (is_file($temporary)) unlink($temporary);
    }

    private function cachePath(string $developer, string $domain, string $type, string $algorithm, string $keyId): string
    {
        return rtrim($this->cacheDirectory, '/\\') . DIRECTORY_SEPARATOR
            . hash('sha256', implode("\0", ['v2', $developer, $domain, $type, $algorithm, $keyId])) . '.json';
    }

    private function validDomain(string $domain): bool
    {
        return preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $domain) === 1;
    }

    private function validEndpoint(string $url): bool
    {
        $parts = parse_url($url);
        if (filter_var($url, FILTER_VALIDATE_URL) === false || !is_array($parts)
            || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['fragment']) || (isset($parts['port']) && $parts['port'] !== 443)) return false;
        $host = strtolower((string) $parts['host']);
        return $host === 'chaos-mvc.org' || $host === 'www.chaos-mvc.org';
    }

    private function request(string $url): array
    {
        $errors = [];
        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            if ($handle !== false) {
                $headers = ['Accept: application/json'];
                if ($this->transportKey !== '') $headers[] = 'X-API-KEY: ' . $this->transportKey;
                curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_PROTOCOLS => CURLPROTO_HTTPS]);
                $raw = curl_exec($handle); $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
                if ($raw === false) $errors[] = 'cURL: ' . curl_error($handle);
                curl_close($handle);
                if (is_string($raw) && strlen($raw) <= self::MAX_RESPONSE_BYTES) return [$status, $raw, ''];
            }
        }
        if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOL)) return [0, false, implode('; ', array_merge($errors, ['HTTPS streams disabled']))];
        $header = "Accept: application/json\r\nConnection: close\r\n";
        if ($this->transportKey !== '') $header .= 'X-API-KEY: ' . $this->transportKey . "\r\n";
        $raw = @file_get_contents($url, false, stream_context_create(['http' => [
            'method' => 'GET', 'header' => $header,
            'timeout' => 10, 'ignore_errors' => true, 'follow_location' => 0,
        ], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]), 0, self::MAX_RESPONSE_BYTES);
        if ($raw === false) $errors[] = 'HTTPS stream request failed';
        return [$this->httpStatus($http_response_header ?? []), $raw, implode('; ', $errors)];
    }

    private function httpStatus(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/i', (string) $header, $match)) return (int) $match[1];
        }
        return 0;
    }
}
/* [End AI:GPT-5.6] */
