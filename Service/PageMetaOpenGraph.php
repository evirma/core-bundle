<?php

namespace Evirma\Bundle\CoreBundle\Service;

class PageMetaOpenGraph
{
    /**
     * @var string|null
     */
    private $type = 'website';

    /**
     * @var string|null
     */
    private $title = '';

    /**
     * @var string|null
     */
    private $description = '';

    /**
     * @var string|null
     */
    private $locale = 'ru_RU';

    /**
     * @var string|null
     */
    private $siteName = '';

    /**
     * @var string|null
     */
    private $url;

    /**
     * @var array|null
     */
    private $images = [];

    public function __toString()
    {
        if (!$this->isAllowToString()) {
            return '';
        }

        $result = '';
        if ($type = $this->getType()) {
            $result .= "<meta property=\"og:type\" content=\"{$type}\" />\n";
        }

        if ($url = $this->getUrl()) {
            $result .= "<meta property=\"og:url\" content=\"{$url}\" />\n";
        }

        if (!empty($this->images)) {
            foreach ($this->images as $image) {
                if (!isset($image['url'])) {
                    continue;
                }

                $result .= "<meta property=\"og:image\" content=\"{$image['url']}\" />\n";

                if (preg_match('#^https://#', $image['url'])) {
                    $result .= "<meta property=\"og:image:secure_url\" content=\"{$image['url']}\" />\n";
                }

                if (isset($image['type'])) {
                    $result .= "<meta property=\"og:image:type\" content=\"{$image['type']}\" />\n";
                }

                if (isset($image['width'])) {
                    $result .= "<meta property=\"og:image:width\" content=\"{$image['width']}\" />\n";
                }

                if (isset($image['height'])) {
                    $result .= "<meta property=\"og:image:height\" content=\"{$image['height']}\" />\n";
                }
            }
        }

        if ($title = $this->getTitle()) {
            $result .= "<meta property=\"og:title\" content=\"{$title}\" />\n";
        }

        if ($description = $this->getDescription()) {
            $result .= "<meta property=\"og:description\" content=\"{$description}\" />\n";
        }

        if ($locale = $this->getLocale()) {
            $result .= "<meta property=\"og:locale\" content=\"{$locale}\" />\n";
        }

        if ($siteName = $this->getSiteName()) {
            $result .= "<meta property=\"og:site_name\" content=\"{$siteName}\" />\n";
        }

        return $result;
    }

    protected function isAllowToString()
    {
        if ($this->title && $this->url) {
            return true;
        }

        return false;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     * @return PageMetaOpenGraph
     */
    public function setType(?string $type): PageMetaOpenGraph
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     * @return PageMetaOpenGraph
     */
    public function setTitle(?string $title): PageMetaOpenGraph
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return PageMetaOpenGraph
     */
    public function setDescription(?string $description): PageMetaOpenGraph
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string|null $locale
     * @return PageMetaOpenGraph
     */
    public function setLocale(?string $locale): PageMetaOpenGraph
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSiteName(): ?string
    {
        return $this->siteName;
    }

    /**
     * @param string|null $siteName
     * @return PageMetaOpenGraph
     */
    public function setSiteName(?string $siteName): PageMetaOpenGraph
    {
        $this->siteName = $siteName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     * @return PageMetaOpenGraph
     */
    public function setUrl(?string $url): PageMetaOpenGraph
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @param      $url
     * @param null $alt
     * @param null $type
     * @param null $width
     * @param null $height
     * @return $this
     */
    public function addImage($url, $alt = null, $type = null, $width = null, $height = null)
    {
        $hash = sha1($url);
        $this->images[$hash] = [
            'url' => $url,
            'alt' => $alt,
            'type' => $type,
            'width' => $width,
            'height' => $height,
        ];

        return $this;
    }

    /**
     * @return array|null
     */
    public function getImages()
    {
        return $this->images;
    }

}