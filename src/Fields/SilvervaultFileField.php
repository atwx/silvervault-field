<?php

namespace Atwx\SilvervaultField\Fields;

use S2Hub\CmsPopup\Control\CmsPopupSearchRouterController;
use Atwx\SilvervaultField\Handlers\SilvervaultSearchHandler;
use Atwx\SilvervaultField\Models\SilvervaultFile;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataObjectInterface;

class SilvervaultFileField extends FormField
{
    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_CUSTOM;

    protected $schemaComponent = 'SilvervaultFileField';

    /**
     * Accept a SilvervaultFile object, a numeric SilvervaultFile record ID,
     * a SilvervaultID string, or empty/null.
     */
    public function setValue($value, $data = null)
    {
        if ($value instanceof SilvervaultFile) {
            return parent::setValue($this->fileToJson($value), $data);
        }

        if (is_numeric($value) && (int) $value > 0) {
            $file = SilvervaultFile::get()->byID((int) $value);
            if ($file) {
                return parent::setValue($this->fileToJson($file), $data);
            }

            return parent::setValue('', $data);
        }

        return parent::setValue($value, $data);
    }

    public function saveInto(DataObjectInterface $record)
    {
        $fieldName = $this->getName();
        $jsonValue = $this->dataValue();

        if (empty($jsonValue)) {
            $record->{$fieldName . 'ID'} = 0;
            return;
        }

        $data = json_decode($jsonValue, true);

        if (!$data || empty($data['silvervaultId'])) {
            $record->{$fieldName . 'ID'} = 0;
            return;
        }

        $existingFileID = $record->{$fieldName . 'ID'};
        $silvervaultFile = null;

        if ($existingFileID) {
            $silvervaultFile = SilvervaultFile::get()->byID((int) $existingFileID);
        }

        if (!$silvervaultFile) {
            $silvervaultFile = SilvervaultFile::create();
        }

        $silvervaultFile->SilvervaultID = (string) $data['silvervaultId'];
        $silvervaultFile->Caption = $data['caption'] ?? '';
        $silvervaultFile->AltText = $data['altText'] ?? '';
        $silvervaultFile->RightsOverride = $data['rightsOverride'] ?? '';

        if (!$silvervaultFile->isInDB() || $silvervaultFile->isChanged()) {
            $silvervaultFile->write();
        }

        $record->{$fieldName . 'ID'} = $silvervaultFile->ID;
    }

    public function Type()
    {
        return 'silvervault-file';
    }

    /**
     * Return the linked SilvervaultFile record, or null.
     */
    public function getVaultFile(): ?SilvervaultFile
    {
        $form = $this->getForm();
        if (!$form) {
            return null;
        }

        $record = $form->getRecord();
        if (!$record) {
            return null;
        }

        $id = $record->{$this->getName() . 'ID'};
        if ($id) {
            return SilvervaultFile::get()->byID((int) $id) ?: null;
        }

        return null;
    }

    public function getSchemaDataDefaults()
    {
        $data = parent::getSchemaDataDefaults();

        $endpoints = $this->getSearchEndpoints();
        $data['data']['searchFormEndpoint'] = $endpoints['searchForm'];
        $data['data']['searchEndpoint'] = $endpoints['searchResults'];
        $data['data']['silvervaultBaseUrl'] = $this->getSilvervaultBaseUrl();

        $vaultFile = $this->getVaultFile();
        if ($vaultFile) {
            $data['data']['vaultFile'] = [
                'silvervaultId' => $vaultFile->SilvervaultID,
                'title' => $vaultFile->Title,
                'description' => $vaultFile->Description,
                'rightsinfo' => $vaultFile->Rightsinfo,
                'thumbnail' => $vaultFile->ThumbnailURL,
                'caption' => $vaultFile->Caption,
                'altText' => $vaultFile->AltText,
                'rightsOverride' => $vaultFile->RightsOverride,
            ];
        }

        return $data;
    }

    public function getSilvervaultBaseUrl(): string
    {
        return rtrim((string) Environment::getEnv('SILVERVAULT_BASE_URL'), '/');
    }

    private function fileToJson(SilvervaultFile $file): string
    {
        return (string) json_encode([
            'silvervaultId' => $file->SilvervaultID,
            'title' => $file->Title,
            'description' => $file->Description,
            'rightsinfo' => $file->Rightsinfo,
            'thumbnail' => $file->ThumbnailURL,
            'caption' => $file->Caption,
            'altText' => $file->AltText,
            'rightsOverride' => $file->RightsOverride,
        ]);
    }

    public function getModalDataJSON(): string
    {
        $endpoints = $this->getSearchEndpoints();
        return (string) json_encode([
            'formEndpoint' => $endpoints['searchForm'],
            'searchEndpoint' => $endpoints['searchResults'],
        ]);
    }

    /**
     * JSON config consumed by the entwine mount in classic (non-FormSchema) forms.
     */
    public function getMountConfigJSON(): string
    {
        $endpoints = $this->getSearchEndpoints();
        $config = [
            'searchFormEndpoint' => $endpoints['searchForm'],
            'searchEndpoint' => $endpoints['searchResults'],
            'silvervaultBaseUrl' => $this->getSilvervaultBaseUrl(),
        ];

        $vaultFile = $this->getVaultFile();
        if ($vaultFile) {
            $config['vaultFile'] = $this->vaultFileToArray($vaultFile);
        }

        return (string) json_encode($config);
    }

    /**
     * JSON-encoded initial value (matching what onChange emits) for the entwine mount.
     */
    public function getMountInitialValue(): string
    {
        $vaultFile = $this->getVaultFile();
        if (!$vaultFile) {
            return '';
        }
        return (string) json_encode($this->vaultFileToArray($vaultFile));
    }

    private function vaultFileToArray(SilvervaultFile $file): array
    {
        return [
            'silvervaultId' => $file->SilvervaultID,
            'title' => $file->Title,
            'description' => $file->Description,
            'rightsinfo' => $file->Rightsinfo,
            'thumbnail' => $file->ThumbnailURL,
            'caption' => $file->Caption,
            'altText' => $file->AltText,
            'rightsOverride' => $file->RightsOverride,
        ];
    }

    /**
     * Returns absolute URLs for the Silvervault search endpoints.
     * Single source of truth used by both getSchemaDataDefaults() and getModalDataJSON().
     *
     * @return array{searchForm: string, searchResults: string}
     */
    private function getSearchEndpoints(): array
    {
        return CmsPopupSearchRouterController::endpointsForHandler(SilvervaultSearchHandler::class);
    }
}
