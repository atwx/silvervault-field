<?php

namespace Atwx\SilvervaultField\Models;

use Atwx\SilvervaultField\Services\UrlSigningService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataObject;

class SilvervaultFile extends DataObject
{
    private static $table_name = 'SilvervaultFile';

    private static $db = [
        'SilvervaultID' => 'Varchar(255)',
        'Data' => 'HTMLText',
        'Title' => 'Varchar(255)',
        'Description' => 'Text',
        'Rightsinfo' => 'Text',
        'ThumbnailURL' => 'Varchar(512)',
        'Caption' => 'Text',
        'AltText' => 'Varchar(255)',
    ];

    private static $field_labels = [
        'Title' => 'Titel',
        'SilvervaultID' => 'Silvervault ID',
        'Description' => 'Beschreibung',
        'Rightsinfo' => 'Rechteinformationen',
        'ThumbnailURL' => 'Vorschaubild URL',
        'Caption' => 'Bildunterschrift',
        'AltText' => 'Alt-Text',
    ];

    private static $casting = [
        'FitMax' => 'HTMLText',
        'ScaleWidth' => 'HTMLText',
        'ScaleHeight' => 'HTMLText',
        'Fill' => 'HTMLText',
        'FillMax' => 'HTMLText',
        'Pad' => 'HTMLText',
        'CropWidth' => 'HTMLText',
        'CropHeight' => 'HTMLText',
    ];

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Check if the SilvervaultID has changed
        if ($this->isChanged('SilvervaultID') && !empty($this->SilvervaultID)) {
            $jsonData = $this->fetchSilvervaultData($this->SilvervaultID);
            $this->Data = $jsonData;

            // Parse JSON and fill fields
            if ($jsonData) {
                $data = json_decode($jsonData, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    $this->Title = $data['Title'] ?? '';
                    $this->Description = $data['Description'] ?? '';
                    $this->Rightsinfo = $data['Rightsinfo'] ?? '';
                    $this->ThumbnailURL = $data['Thumbnail'] ?? '';
                }
            }
        }
    }

    protected function fetchSilvervaultData($silvervaultID)
    {
        $baseUrl = Environment::getEnv('SILVERVAULT_BASE_URL');
        if (empty($baseUrl)) {
            return null;
        }

        $url = static::resolveSilvervaultUrl($baseUrl);
        $url = rtrim($url, '/') . '/api/v1/MediaItem/' . $silvervaultID;

        try {
            $token = Environment::getEnv('SILVERVAULT_TOKEN');

            $options = [
                'verify' => false,
                'timeout' => 30,
                'allow_redirects' => true,
            ];

            if (!empty($token)) {
                $options['headers'] = [
                    'Authorization' => 'Bearer ' . $token,
                ];
            }

            $client = new Client($options);
            $response = $client->get($url);

            if ($response->getStatusCode() === 200) {
                return $response->getBody()->getContents();
            }
        } catch (RequestException $e) {
            return null;
        }

        return null;
    }

    /**
     * Resolve the Silvervault base URL, correcting for DDEV internal routing.
     */
    public static function resolveSilvervaultUrl(string $baseUrl): string
    {
        if (strpos($baseUrl, '.ddev.site') !== false) {
            preg_match('/https?:\/\/([^.]+)\.ddev\.site/', $baseUrl, $matches);
            if (isset($matches[1])) {
                return 'http://' . $matches[1] . '-web';
            }
        }

        return $baseUrl;
    }

    /**
     * Generate a signed URL for scaled image access.
     */
    protected function generateSignedUrl(string $method, ?int $width = null, ?int $height = null): ?string
    {
        $baseUrl = Environment::getEnv('SILVERVAULT_BASE_URL');
        $secret = Environment::getEnv('SILVERVAULT_SECRET');

        if (empty($baseUrl) || empty($secret) || empty($this->SilvervaultID)) {
            return null;
        }

        $path = '/api/v1/ScaledImage/' . $this->SilvervaultID;

        $params = ['Method' => $method];
        if ($width !== null) {
            $params['Width'] = $width;
        }
        if ($height !== null) {
            $params['Height'] = $height;
        }

        $signingService = UrlSigningService::create();
        $signedPath = $signingService->signUrl($path, $params, $secret);

        return rtrim($baseUrl, '/') . $signedPath;
    }

    /**
     * Get the effective alt text: local override first, then vault title.
     */
    public function getEffectiveAltText(): string
    {
        return $this->AltText ?: $this->Title ?: '';
    }

    public function FitMax(int $width, int $height): ?SilvervaultScaledImage
    {
        $url = $this->generateSignedUrl('FitMax', $width, $height);
        if (!$url) {
            return null;
        }

        return new SilvervaultScaledImage($url, $width, $height, $this->getEffectiveAltText());
    }

    public function ScaleWidth(int $width): ?SilvervaultScaledImage
    {
        $url = $this->generateSignedUrl('ScaleWidth', $width);
        if (!$url) {
            return null;
        }

        return new SilvervaultScaledImage($url, $width, null, $this->getEffectiveAltText());
    }

    public function ScaleHeight(int $height): ?SilvervaultScaledImage
    {
        $url = $this->generateSignedUrl('ScaleHeight', null, $height);
        if (!$url) {
            return null;
        }

        return new SilvervaultScaledImage($url, null, $height, $this->getEffectiveAltText());
    }

    public function Fill(int $width, int $height): ?SilvervaultScaledImage
    {
        $url = $this->generateSignedUrl('Fill', $width, $height);
        if (!$url) {
            return null;
        }

        return new SilvervaultScaledImage($url, $width, $height, $this->getEffectiveAltText());
    }

    public function FillMax(int $width, int $height): ?SilvervaultScaledImage
    {
        $url = $this->generateSignedUrl('FillMax', $width, $height);
        if (!$url) {
            return null;
        }

        return new SilvervaultScaledImage($url, $width, $height, $this->getEffectiveAltText());
    }

    public function Pad(int $width, int $height): ?SilvervaultScaledImage
    {
        $url = $this->generateSignedUrl('Pad', $width, $height);
        if (!$url) {
            return null;
        }

        return new SilvervaultScaledImage($url, $width, $height, $this->getEffectiveAltText());
    }

    public function CropWidth(int $width): ?SilvervaultScaledImage
    {
        $url = $this->generateSignedUrl('CropWidth', $width);
        if (!$url) {
            return null;
        }

        return new SilvervaultScaledImage($url, $width, null, $this->getEffectiveAltText());
    }

    public function CropHeight(int $height): ?SilvervaultScaledImage
    {
        $url = $this->generateSignedUrl('CropHeight', null, $height);
        if (!$url) {
            return null;
        }

        return new SilvervaultScaledImage($url, null, $height, $this->getEffectiveAltText());
    }
}
