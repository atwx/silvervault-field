<?php

namespace Atwx\SilvervaultField\Services;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Control\Director;

/**
 * URL Signing Service
 *
 * Creates HMAC-based signatures for URLs to prevent unauthorized access
 */
class UrlSigningService
{
    use Injectable;

    /**
     * Sign a URL with HMAC signature
     *
     * @param string $path The URL path (without query parameters)
     * @param array $params Query parameters to sign
     * @param string $secret The secret key for signing
     * @param int $expiresIn Time in seconds until expiration (default: 3600 = 1 hour)
     * @return string Full signed URL
     */
    public function signUrl(string $path, array $params, string $secret, int $expiresIn = 3600): string
    {
        $expires = time() + $expiresIn;

        // Create signature
        $signature = $this->generateSignature($path, $params, $expires, $secret);

        // Add expires and signature to params
        $params['expires'] = $expires;
        $params['signature'] = $signature;

        // Build query string
        $queryString = http_build_query($params);

        return $path . '?' . $queryString;
    }

    /**
     * Generate HMAC signature for a URL
     *
     * @param string $path The URL path
     * @param array $params Query parameters
     * @param int $expires The expiration timestamp
     * @param string $secret The secret key
     * @return string The HMAC signature
     */
    protected function generateSignature(string $path, array $params, int $expires, string $secret): string
    {
        // Sort params for consistent signature
        ksort($params);

        // Create signature payload: path|param1=value1|param2=value2|expires
        $payload = $path . '|' . http_build_query($params) . '|' . $expires;

        // Generate HMAC
        return hash_hmac('sha256', $payload, $secret);
    }
}
