<?php

namespace Atwx\SilvervaultField\Models;

use SilverStripe\Model\ModelData;

/**
 * Represents a scaled image from Silvervault
 * Mimics SilverStripe\Assets\Image behavior for template usage
 */
class SilvervaultScaledImage extends ModelData
{
    protected $url;
    protected $width;
    protected $height;
    protected $alt;

    public function __construct(string $url, ?int $width = null, ?int $height = null, ?string $alt = '')
    {
        $this->url = $url;
        $this->width = $width;
        $this->height = $height;
        $this->alt = $alt;
        parent::__construct();
    }

    /**
     * Get the URL of the scaled image
     *
     * @return string
     */
    public function getURL(): string
    {
        return $this->url;
    }

    /**
     * Alias for getURL()
     */
    public function URL(): string
    {
        return $this->getURL();
    }

    /**
     * Get the width
     *
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * Get the height
     *
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * Get alt text
     *
     * @return string
     */
    public function getAlt(): string
    {
        return $this->alt;
    }

    /**
     * Set alt text
     *
     * @param string $alt
     * @return $this
     */
    public function setAlt(string $alt): self
    {
        $this->alt = $alt;
        return $this;
    }

    /**
     * Render as HTML img tag
     * This is called when the object is used directly in a template
     *
     * @return string
     */
    public function forTemplate(): string
    {
        $attributes = [
            'src="' . htmlspecialchars($this->url) . '"'
        ];

        if ($this->width) {
            $attributes[] = 'width="' . $this->width . '"';
        }

        if ($this->height) {
            $attributes[] = 'height="' . $this->height . '"';
        }

        if ($this->alt) {
            $attributes[] = 'alt="' . htmlspecialchars($this->alt) . '"';
        } else {
            $attributes[] = 'alt=""';
        }

        return '<img ' . implode(' ', $attributes) . ' />';
    }

    /**
     * Convert to string (renders the img tag)
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->forTemplate();
    }
}
