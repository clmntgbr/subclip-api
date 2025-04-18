<?php

namespace App\Model;

use App\Entity\Configuration;
use App\Protobuf\ConfigurationSubtitleFont;
use App\Protobuf\ConfigurationSubtitleOutlineThickness;
use App\Protobuf\ConfigurationSubtitleShadow;
use App\Protobuf\VideoFormatStyle;

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
        
        if ($this->subtitleFont !== null) {
            $configuration->setSubtitleFont($this->subtitleFont);
        }
        
        if ($this->subtitleSize !== null) {
            $configuration->setSubtitleSize($this->subtitleSize);
        }
        
        if ($this->subtitleColor !== null) {
            $configuration->setSubtitleColor($this->subtitleColor);
        }
        
        if ($this->subtitleBold !== null) {
            $configuration->setSubtitleBold($this->subtitleBold);
        }
        
        if ($this->subtitleItalic !== null) {
            $configuration->setSubtitleItalic($this->subtitleItalic);
        }
        
        if ($this->subtitleUnderline !== null) {
            $configuration->setSubtitleUnderline($this->subtitleUnderline);
        }
        
        if ($this->subtitleOutlineColor !== null) {
            $configuration->setSubtitleOutlineColor($this->subtitleOutlineColor);
        }
        
        if ($this->subtitleOutlineThickness !== null) {
            $configuration->setSubtitleOutlineThickness($this->subtitleOutlineThickness);
        }
        
        if ($this->subtitleShadow !== null) {
            $configuration->setSubtitleShadow($this->subtitleShadow);
        }
        
        if ($this->subtitleShadowColor !== null) {
            $configuration->setSubtitleShadowColor($this->subtitleShadowColor);
        }
        
        if ($this->format !== null) {
            $configuration->setFormat($this->format);
        }
        
        if ($this->split !== null) {
            $configuration->setSplit($this->split);
        }
        
        if ($this->marginV !== null) {
            $configuration->setMarginV($this->marginV);
        }
        
        if ($this->privacyOptions !== null) {
            $configuration->setPrivacyOptions($this->privacyOptions);
        }
        
        return $configuration;
    }
}