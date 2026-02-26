<?php

namespace Atwx\SilvervaultField\Fields;

use Atwx\SilvervaultField\Models\SilvervaultFile;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataObjectInterface;

class SilvervaultFileField extends FormField
{
    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_TEXT;

    protected $schemaComponent = 'SilvervaultFileField';

    /**
     * Accept a SilvervaultFile object, a numeric SilvervaultFile record ID,
     * a JSON string (from form submission), or empty/null.
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

        $silvervaultFile->SilvervaultID = (string) ($data['silvervaultId'] ?? '');
        $silvervaultFile->Caption = $data['caption'] ?? '';
        $silvervaultFile->AltText = $data['altText'] ?? '';
        $silvervaultFile->write();

        $record->{$fieldName . 'ID'} = $silvervaultFile->ID;
    }

    public function getSchemaDataDefaults()
    {
        $data = parent::getSchemaDataDefaults();
        $data['data']['searchEndpoint'] = Director::absoluteURL('api/silvervault/search');
        return $data;
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

    private function fileToJson(SilvervaultFile $file): string
    {
        return (string) json_encode([
            'silvervaultId' => $file->SilvervaultID,
            'title'         => $file->Title,
            'description'   => $file->Description,
            'rightsinfo'    => $file->Rightsinfo,
            'thumbnail'     => $file->ThumbnailURL,
            'caption'       => $file->Caption,
            'altText'       => $file->AltText,
        ]);
    }
}
