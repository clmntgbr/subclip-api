<?php

namespace App\Model;

use App\Entity\Configuration;

class UploadVideoConfiguration
{
    public function __construct(
        public ?string $subtitleFont = null,
        public ?int $subtitleSize = null,
        public ?string $subtitleColor = null,
        public ?string $subtitleBold = null,
        public ?string $subtitleItalic = null,
        public ?string $subtitleUnderline = null,
        public ?string $subtitleOutlineColor = null,
        public ?string $subtitleOutlineThickness = null,
        public ?string $subtitleShadow = null,
        public ?string $subtitleShadowColor = null,
        public ?string $format = null,
        public ?int $split = null,
        public ?float $marginV = null,
        public ?string $privacyOptions = null,
    ) {
        $configuration = new Configuration();
        $this->subtitleFont = $this->subtitleFont ?? $configuration->getSubtitleFont();
        $this->subtitleSize = $this->subtitleSize ?? $configuration->getSubtitleSize();
        $this->subtitleColor = $this->subtitleColor ?? $configuration->getSubtitleColor();
        $this->subtitleBold = $this->subtitleBold ?? $configuration->getSubtitleBold();
        $this->subtitleItalic = $this->subtitleItalic ?? $configuration->getSubtitleItalic();
        $this->subtitleUnderline = $this->subtitleUnderline ?? $configuration->getSubtitleUnderline();
        $this->subtitleOutlineColor = $this->subtitleOutlineColor ?? $configuration->getSubtitleOutlineColor();
        $this->subtitleOutlineThickness = $this->subtitleOutlineThickness ?? $configuration->getSubtitleOutlineThickness();
        $this->subtitleShadow = $this->subtitleShadow ?? $configuration->getSubtitleShadow();
        $this->subtitleShadowColor = $this->subtitleShadowColor ?? $configuration->getSubtitleShadowColor();
        $this->format = $this->format ?? $configuration->getFormat();
        $this->split = $this->split ?? $configuration->getSplit();
        $this->marginV = $this->marginV ?? $configuration->getMarginV();
        $this->privacyOptions = $this->privacyOptions ?? $configuration->getPrivacyOptions();
    }

    public function toEntity(): Configuration
    {
        $configuration = new Configuration();

        if (null !== $this->subtitleFont) {
            $configuration->setSubtitleFont($this->subtitleFont);
        }

        if (null !== $this->subtitleSize) {
            $configuration->setSubtitleSize($this->subtitleSize);
        }

        if (null !== $this->subtitleColor) {
            $configuration->setSubtitleColor($this->subtitleColor);
        }

        if (null !== $this->subtitleBold) {
            $configuration->setSubtitleBold($this->subtitleBold);
        }

        if (null !== $this->subtitleItalic) {
            $configuration->setSubtitleItalic($this->subtitleItalic);
        }

        if (null !== $this->subtitleUnderline) {
            $configuration->setSubtitleUnderline($this->subtitleUnderline);
        }

        if (null !== $this->subtitleOutlineColor) {
            $configuration->setSubtitleOutlineColor($this->subtitleOutlineColor);
        }

        if (null !== $this->subtitleOutlineThickness) {
            $configuration->setSubtitleOutlineThickness($this->subtitleOutlineThickness);
        }

        if (null !== $this->subtitleShadow) {
            $configuration->setSubtitleShadow($this->subtitleShadow);
        }

        if (null !== $this->subtitleShadowColor) {
            $configuration->setSubtitleShadowColor($this->subtitleShadowColor);
        }

        if (null !== $this->format) {
            $configuration->setFormat($this->format);
        }

        if (null !== $this->split) {
            $configuration->setSplit($this->split);
        }

        if (null !== $this->marginV) {
            $configuration->setMarginV($this->marginV);
        }

        if (null !== $this->privacyOptions) {
            $configuration->setPrivacyOptions($this->privacyOptions);
        }

        return $configuration;
    }
}
