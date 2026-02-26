<?php

namespace Atwx\SilvervaultField\Controllers;

use Atwx\SilvervaultField\Models\SilvervaultFile;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;
use SilverStripe\Security\Security;

class SilvervaultSearchController extends Controller
{
    private static $url_handlers = [
        'GET search' => 'search',
    ];

    private static $allowed_actions = [
        'search',
    ];

    /**
     * Search the Silvervault media database.
     *
     * Expects query parameter: ?q=<search term>
     * Returns JSON: { "items": [ { silvervaultId, title, description, rightsinfo, thumbnail } ] }
     *
     * The Silvervault search endpoint is assumed to be GET /api/v1/MediaItem?q=<query>.
     * Adjust the URL construction below if the actual endpoint differs.
     */
    public function search(HTTPRequest $request): HTTPResponse
    {
        if (!Security::getCurrentUser()) {
            return $this->jsonError('Unauthorized', 401);
        }

        $query = trim((string) $request->getVar('q'));

        if (strlen($query) < 2) {
            return $this->jsonResponse(['items' => []]);
        }

        $baseUrl = Environment::getEnv('SILVERVAULT_BASE_URL');
        if (empty($baseUrl)) {
            return $this->jsonError('SILVERVAULT_BASE_URL not configured', 500);
        }

        $resolvedUrl = SilvervaultFile::resolveSilvervaultUrl($baseUrl);
        // Adjust the path below if your Silvervault instance uses a different search endpoint.
        $searchUrl = rtrim($resolvedUrl, '/') . '/api/v1/MediaItem/search?' . http_build_query(['title' => $query]);

        try {
            $token = Environment::getEnv('SILVERVAULT_TOKEN');

            $options = [
                'verify' => false,
                'timeout' => 15,
                'allow_redirects' => true,
            ];

            if (!empty($token)) {
                $options['headers'] = [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ];
            } else {
                $options['headers'] = ['Accept' => 'application/json'];
            }

            $client = new Client($options);
            $response = $client->get($searchUrl);

            if ($response->getStatusCode() !== 200) {
                return $this->jsonResponse(['items' => []]);
            }

            $body = $response->getBody()->getContents();
            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return $this->jsonResponse(['items' => []]);
            }

            // Silvervault may return a plain array or a wrapped object.
            // Unwrap if necessary.
            $rawItems = $decoded;
            if (isset($decoded['Items']) && is_array($decoded['Items'])) {
                $rawItems = $decoded['Items'];
            } elseif (isset($decoded['items']) && is_array($decoded['items'])) {
                $rawItems = $decoded['items'];
            }

            $items = array_map(function (array $item): array {
                return [
                    'silvervaultId' => $item['ID'] ?? $item['Id'] ?? $item['id'] ?? '',
                    'title'         => $item['Title'] ?? $item['title'] ?? '',
                    'description'   => $item['Description'] ?? $item['description'] ?? '',
                    'rightsinfo'    => $item['Rightsinfo'] ?? $item['rightsinfo'] ?? '',
                    'thumbnail'     => $item['Thumbnail'] ?? $item['thumbnail'] ?? '',
                ];
            }, array_values($rawItems));

            // Remove entries without an ID
            $items = array_values(array_filter($items, fn($i) => $i['silvervaultId'] !== ''));

            return $this->jsonResponse(['items' => $items]);
        } catch (RequestException $e) {
            return $this->jsonResponse(['items' => []]);
        }
    }

    private function jsonResponse(array $data, int $status = 200): HTTPResponse
    {
        return HTTPResponse::create()
            ->setStatusCode($status)
            ->addHeader('Content-Type', 'application/json')
            ->setBody((string) json_encode($data));
    }

    private function jsonError(string $message, int $status = 400): HTTPResponse
    {
        return $this->jsonResponse(['error' => $message], $status);
    }
}
