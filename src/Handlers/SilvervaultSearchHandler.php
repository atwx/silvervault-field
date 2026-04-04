<?php

namespace Atwx\SilvervaultField\Handlers;

use Atwx\CmsPopup\Handler\CmsPopupSearchHandler;
use Atwx\SilvervaultField\Models\SilvervaultFile;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;

class SilvervaultSearchHandler extends CmsPopupSearchHandler
{
    public function getSearchFormFields(): FieldList
    {
        return FieldList::create([
            TextField::create('q', _t(self::class . '.SEARCH_LABEL', 'Suche'))
                ->setAttribute('placeholder', _t(self::class . '.SEARCH_PLACEHOLDER', 'Suchbegriff oder ID eingeben...'))
                ->setAttribute('autofocus', 'autofocus'),
        ]);
    }

    public function search(string $query, HTTPRequest $request): string
    {
        $baseUrl = Environment::getEnv('SILVERVAULT_BASE_URL');
        if (empty($baseUrl)) {
            return '<div class="alert alert-danger">SILVERVAULT_BASE_URL not configured</div>';
        }

        $publicUrl = rtrim($baseUrl, '/');
        $resolvedUrl = rtrim(SilvervaultFile::resolveSilvervaultUrl($baseUrl), '/');

        if (ctype_digit($query)) {
            $items = $this->fetchItemsById($query, $resolvedUrl, $publicUrl);
        } else {
            $items = $this->fetchItemsByQuery($query, $resolvedUrl, $publicUrl);
        }

        if ($items === []) {
            return '<div style="color: #6c757d; text-align: center; padding: 20px;">'
                . _t(self::class . '.NO_RESULTS', 'Keine Ergebnisse gefunden.')
                . '</div>';
        }

        return $this->renderResultsHtml($items, $publicUrl);
    }

    private function fetchItemsByQuery(string $query, string $resolvedUrl, string $publicUrl): array
    {
        $searchUrl = $resolvedUrl . '/api/v1/MediaItem/search?' . http_build_query(['title' => $query]);

        try {
            $client = $this->buildClient();
            $response = $client->get($searchUrl);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $decoded = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return [];
            }

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

            return array_values(array_filter($items, fn($i) => $i['silvervaultId'] !== ''));
        } catch (RequestException $e) {
            return [];
        }
    }

    private function fetchItemsById(string $id, string $resolvedUrl, string $publicUrl): array
    {
        try {
            $client = $this->buildClient();
            $response = $client->get($resolvedUrl . '/api/v1/MediaItem/' . rawurlencode($id));

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $decoded = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return [];
            }

            $item = $this->normalizeItem($decoded, $resolvedUrl, $publicUrl);
            return $item['silvervaultId'] !== '' ? [$item] : [];
        } catch (RequestException $e) {
            return [];
        }
    }

    private function renderResultsHtml(array $items, string $publicUrl): string
    {
        $html = '<div class="silvervault-search-results">';

        foreach ($items as $item) {
            $selectData = htmlspecialchars((string) json_encode($item), ENT_QUOTES, 'UTF-8');
            $title = htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8');
            $description = htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8');
            $rightsinfo = htmlspecialchars($item['rightsinfo'], ENT_QUOTES, 'UTF-8');
            $thumbnail = htmlspecialchars($item['thumbnail'], ENT_QUOTES, 'UTF-8');
            $svId = htmlspecialchars($item['silvervaultId'], ENT_QUOTES, 'UTF-8');

            $html .= '<div data-cms-select="' . $selectData . '" '
                . 'style="display: flex; gap: 12px; align-items: center; padding: 10px 8px; border-bottom: 1px solid #eee; cursor: pointer;" '
                . 'onmouseover="this.style.backgroundColor=\'#f8f9fa\'" '
                . 'onmouseout="this.style.backgroundColor=\'transparent\'">';

            if ($thumbnail) {
                $html .= '<img src="' . $thumbnail . '" alt="' . $title . '" '
                    . 'style="width: 56px; height: 56px; object-fit: cover; border-radius: 4px; flex-shrink: 0;" />';
            }

            $html .= '<div style="flex: 1; min-width: 0;">';
            $html .= '<strong>' . $title . '</strong>';
            if ($description) {
                $html .= '<div style="color: #6c757d; font-size: 0.85em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' . $description . '</div>';
            }
            if ($rightsinfo) {
                $html .= '<div style="color: #6c757d; font-size: 0.8em; font-style: italic;">' . $rightsinfo . '</div>';
            }
            $html .= '<span style="color: #adb5bd; font-size: 0.75em;">ID: ' . $svId . '</span>';
            $html .= '</div>';

            $html .= '<button type="button" class="btn btn-sm btn-primary" style="flex-shrink: 0;">'
                . _t(self::class . '.SELECT', 'Auswählen')
                . '</button>';

            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    private function buildClient(): Client
    {
        $token = Environment::getEnv('SILVERVAULT_TOKEN');
        $headers = ['Accept' => 'application/json'];
        if (!empty($token)) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return new Client([
            'verify' => false,
            'timeout' => 15,
            'allow_redirects' => true,
            'headers' => $headers,
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
            'title' => $item['Title'] ?? $item['title'] ?? '',
            'description' => $item['Description'] ?? $item['description'] ?? '',
            'rightsinfo' => $item['Rightsinfo'] ?? $item['rightsinfo'] ?? '',
            'thumbnail' => $thumbnail,
        ];
    }
}
