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

        $query  = trim((string) $request->getVar('q'));
        $idParam = trim((string) $request->getVar('id'));

        $baseUrl = Environment::getEnv('SILVERVAULT_BASE_URL');
        if (empty($baseUrl)) {
            return $this->jsonError('SILVERVAULT_BASE_URL not configured', 500);
        }

        $publicUrl   = rtrim($baseUrl, '/');
        $resolvedUrl = rtrim(SilvervaultFile::resolveSilvervaultUrl($baseUrl), '/');

        // ID lookup: exact single result
        if ($idParam !== '') {
            return $this->fetchById($idParam, $resolvedUrl, $publicUrl);
        }

        if (strlen($query) < 2) {
            return $this->jsonResponse(['items' => []]);
        }

        $searchUrl = $resolvedUrl . '/api/v1/MediaItem/search?' . http_build_query(['title' => $query]);

        try {
            $client   = $this->buildClient();
            $response = $client->get($searchUrl);

            if ($response->getStatusCode() !== 200) {
                return $this->jsonResponse(['items' => []]);
            }

            $decoded = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return $this->jsonResponse(['items' => []]);
            }

            // Silvervault may return a plain array or a wrapped object.
            $rawItems = $decoded;
            if (isset($decoded['Items']) && is_array($decoded['Items'])) {
                $rawItems = $decoded['Items'];
            } elseif (isset($decoded['items']) && is_array($decoded['items'])) {
                $rawItems = $decoded['items'];
            }

            $items = array_map(
                fn(array $item) => $this->normalizeItem($item, $resolvedUrl, $publicUrl),
                array_values($rawItems)
            );
            $items = array_values(array_filter($items, fn($i) => $i['silvervaultId'] !== ''));

            return $this->jsonResponse(['items' => $items]);
        } catch (RequestException $e) {
            return $this->jsonResponse(['items' => []]);
        }
    }

    private function fetchById(string $id, string $resolvedUrl, string $publicUrl): HTTPResponse
    {
        try {
            $client   = $this->buildClient();
            $response = $client->get($resolvedUrl . '/api/v1/MediaItem/' . rawurlencode($id));

            if ($response->getStatusCode() !== 200) {
                return $this->jsonResponse(['items' => []]);
            }

            $decoded = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return $this->jsonResponse(['items' => []]);
            }

            $item = $this->normalizeItem($decoded, $resolvedUrl, $publicUrl);
            $items = $item['silvervaultId'] !== '' ? [$item] : [];

            return $this->jsonResponse(['items' => $items]);
        } catch (RequestException $e) {
            return $this->jsonResponse(['items' => []]);
        }
    }

    private function buildClient(): Client
    {
        $token   = Environment::getEnv('SILVERVAULT_TOKEN');
        $headers = ['Accept' => 'application/json'];
        if (!empty($token)) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return new Client([
            'verify'          => false,
            'timeout'         => 15,
            'allow_redirects' => true,
            'headers'         => $headers,
        ]);
    }

    private function normalizeItem(array $item, string $resolvedUrl, string $publicUrl): array
    {
        $thumbnail = $item['Thumbnail'] ?? $item['thumbnail'] ?? '';
        if ($resolvedUrl !== $publicUrl && $thumbnail) {
            $thumbnail = str_replace($resolvedUrl, $publicUrl, $thumbnail);
        }
        return [
            'silvervaultId' => (string) ($item['ID'] ?? $item['Id'] ?? $item['id'] ?? ''),
            'title'         => $item['Title'] ?? $item['title'] ?? '',
            'description'   => $item['Description'] ?? $item['description'] ?? '',
            'rightsinfo'    => $item['Rightsinfo'] ?? $item['rightsinfo'] ?? '',
            'thumbnail'     => $thumbnail,
        ];
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
