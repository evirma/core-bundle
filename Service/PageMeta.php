<?php

namespace Evirma\Bundle\CoreBundle\Service;

use Evirma\Bundle\AutotextBundle\Autotext;
use Evirma\Bundle\CoreBundle\Entity\User;
use Evirma\Bundle\CoreBundle\Filter\FilterStatic;
use Evirma\Bundle\CoreBundle\Filter\Rule\MetaDescription;
use Evirma\Bundle\CoreBundle\Filter\Rule\MetaKeywords;
use Evirma\Bundle\CoreBundle\Filter\Rule\MetaRobots;
use Evirma\Bundle\CoreBundle\Filter\Rule\MetaTrim;
use Evirma\Bundle\CoreBundle\Filter\Rule\Name;
use Evirma\Bundle\CoreBundle\Util\StringUtil;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Templating\Helper\HelperInterface;
use Symfony\Component\Translation\LoggingTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageMeta implements HelperInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var LoggingTranslator
     */
    private $translator;

    protected $charset = 'UTF-8';
    private $node = null;
    private $metaTitle = '';
    private $metaDescription = '';
    private $metaKeywords = '';
    private $metaRobots = 'INDEX, FOLLOW';
    private $iconClass = '';
    private $h1 = '';
    private $breadcrumbs;
    private $canonical = '';
    private $inPoint = '';

    private $storage = [];
    private $styles = [];
    private $javascripts = [];
    private $preload = [];

    private $links = [];
    private $headerMetas = [];

    private $data = [];
    private $autotextSeed;
    protected ?AuthorizationCheckerInterface $authorizationChecker;
    private ?TokenStorageInterface $tokenStorage;
    private Packages $packages;
    /**
     * @var PageMetaOpenGraph
     */
    private $og;

    public function __construct(RouterInterface $router, TranslatorInterface $translator, Packages $packages, ?AuthorizationCheckerInterface $authorizationChecker = null, TokenStorageInterface $tokenStorage = null)
    {
        $this->router = $router;
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
        $this->packages = $packages;
        $this->tokenStorage = $tokenStorage;
    }

    private function isGranted($role)
    {
        if ($this->tokenStorage && (null !== ($token = $this->tokenStorage->getToken()))) {
            return true == $this->authorizationChecker->isGranted($role);
        }

        return false;
    }

    public function isRoot()
    {
        return $this->isGranted(User::ROLE_ROOT);
    }

    public function isAdmin()
    {
        return $this->isGranted(User::ROLE_ADMIN);
    }

    public function isRubricEditor()
    {
        return $this->isGranted(User::ROLE_ADMIN);
    }

    public function get($key, $default = null)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return $default;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function getPhrase1Text($item)
    {
        if (!is_array($item)) return '';
        if (isset($item['phrase1'])) return $item['phrase1'];
        if (isset($item['name'])) return $item['name'];
        return '';
    }

    public function getPhrase2Text($item)
    {
        if (!is_array($item)) return '';
        if (isset($item['phrase2'])) return $item['phrase2'];
        if (isset($item['name'])) return $item['name'];
        return '';
    }

    public function getPhrase10Text($item)
    {
        if (!is_array($item)) return '';
        if (isset($item['phrase10'])) return $item['phrase10'];
        if (isset($item['name'])) return $item['name'];
        return '';
    }

    public function getH1Text($item)
    {
        if (!is_array($item)) return '';
        if (isset($item['h1'])) return $item['h1'];
        return $this->getPhrase2Text($item);
    }

    public function getBreadcrumbsText($item)
    {
        if (!is_array($item)) return '';
        if (isset($item['meta_breadcrumbs'])) return $item['meta_breadcrumbs'];
        return $this->getPhrase2Text($item);
    }

    /**
     * @return string
     */
    public function getIconClass(): string
    {
        return $this->iconClass;
    }

    /**
     * @param string $iconClass
     * @return PageMeta
     */
    public function setIconClass(string $iconClass): PageMeta
    {
        $this->iconClass = $iconClass;
        return $this;
    }

    /**
     * @param string $h1
     * @param array  $parameters
     * @param string $domain
     * @return PageMeta
     */
    public function setIconClassTrans($h1, $parameters = [], $domain = 'messages')
    {
        $this->iconClass = $this->translator->trans($h1, $parameters, $domain);
        return $this;
    }


    public function getIconTag($withNbsp = true)
    {
        if (!$class = $this->getIconClass()) return '';
        return "<i class=\"{$class}\"></i>" . ($withNbsp ? '&nbsp;' : '');
    }

    /**
     * @param        $node
     * @param string $domain
     * @param array  $parameters
     * @return PageMeta
     */
    public function init($node, $domain = 'messages', $parameters = [])
    {
        $this->setNode($node);
        $catalogue = $this->translator->getCatalogue();

        if ($catalogue->has($node.'.meta_title', $domain)) {
            $this->setMetaTitleTrans($node.'.meta_title', $parameters, $domain);
        }

        if ($catalogue->has($node.'.meta_description', $domain)) {
            $this->setMetaDescriptionTrans($node.'.meta_description', $parameters, $domain);
        }

        if ($catalogue->has($node.'.meta_keywords', $domain)) {
            $this->setMetaKeywordsTrans($node.'.meta_keywords', $parameters, $domain);
        }

        if ($catalogue->has($node.'.meta_robots', $domain)) {
            $this->setMetaRobotsTrans($node.'.meta_robots', $parameters, $domain);
        }

        if ($catalogue->has($node.'.h1', $domain)) {
            $this->setH1Trans($node.'.h1', $parameters, $domain);
        }

        if ($catalogue->has($node.'.icon_class', $domain)) {
            $this->setIconClassTrans($node.'.icon_class', $parameters, $domain);
        }

        return $this;
    }

    /**
     * @param array $pageArray
     * @return PageMeta
     */
    public function setPageArray($pageArray)
    {
        $metaTitle = $pageArray['meta_title'] ?? null;
        if ($metaTitle) {
            $this->setMetaTitle($metaTitle);
        }

        if (!$this->getMetaTitle()) {
            $metaTitleGenerated = $pageArray['meta_title_generated'] ?? null;
            if ($metaTitleGenerated) {
                $this->setMetaTitle($metaTitleGenerated);
            }
        }

        $metaDescription = $pageArray['meta_description'] ?? null;
        if ($metaDescription) {
            $this->setMetaDescription($metaDescription);
        }

        if (!$this->getMetaDescription()) {
            $metaDescriptionGenerated = $pageArray['meta_description_generated'] ?? null;
            if ($metaDescriptionGenerated) {
                $this->setMetaDescription($metaDescriptionGenerated);
            }
        }

        $metaKeywords = $pageArray['meta_keywords'] ?? null;
        if ($metaKeywords) {
            $this->setMetaKeywords($metaKeywords);
        }

        if (!$this->getMetaKeywords()) {
            $metaKeywordsGenerated = $pageArray['meta_keywords_generated'] ?? null;
            if ($metaKeywordsGenerated) {
                $this->setMetaKeywords($metaKeywordsGenerated);
            }
        }

        $metaRobots = $pageArray['meta_robots'] ?? null;
        if ($metaRobots) {
            $this->setMetaRobots($metaRobots);
        }

        $h1 = $pageArray['h1'] ?? null;
        if ($h1) {
            $this->setH1($h1);
        }

        if (!$this->getH1()) {
            $h1Generated = $pageArray['h1_generated'] ?? null;
            if ($h1Generated) {
                $this->setH1($h1Generated);
            }
        }

        return $this;
    }

    /**
     * @param null $default
     * @return string
     */
    public function getMetaTitle($default = null)
    {
        $metaTitle = $this->metaTitle ? $this->metaTitle : $default;
        if ($this->autotextSeed) {
            $metaTitle = Autotext::autotext(' ' . $metaTitle, $this->autotextSeed);
        }

        return FilterStatic::filterValue($metaTitle, MetaTrim::class);
    }

    /**
     * @param string $metaTitle
     * @deprecated
     * @return PageMeta
     */
    public function setTitle($metaTitle)
    {
        return $this->setMetaTitle($metaTitle);
    }

    /**
     * @param string $metaTitle
     * @return PageMeta
     */
    public function setMetaTitle($metaTitle)
    {
        $this->metaTitle = $metaTitle;
        return $this;
    }

    /**
     * @param string $metaTitle
     * @param array  $parameters
     * @param string $domain
     * @return PageMeta
     */
    public function setMetaTitleTrans($metaTitle, $parameters = [], $domain = 'messages')
    {
        $this->metaTitle = $this->translator->trans($metaTitle, $parameters, $domain);
        return $this;
    }

    /**
     * @param null $default
     * @return string
     */
    public function getH1($default = null)
    {
        $h1 = $this->h1 ? $this->h1 : $default;
        if ($this->autotextSeed) {
            $h1 = Autotext::autotext(' ' . $h1, $this->autotextSeed);
        }

        return FilterStatic::filterValue($h1, Name::class);
    }

    /**
     * @param string $h1
     * @return PageMeta
     */
    public function setH1($h1)
    {
        $this->h1 = FilterStatic::filterValue($h1, Name::class);
        return $this;
    }

    /**
     * @param string $h1
     * @return PageMeta
     */
    public function setH1Raw($h1)
    {
        $this->h1 = $h1;
        return $this;
    }

    /**
     * @deprecated
     * @param null $default
     * @return string
     */
    public function getH1Raw($default = null)
    {
        $h1 = $this->h1 ?: $default;
        if ($this->autotextSeed) {
            $h1 = Autotext::autotext(' ' . $h1, $this->autotextSeed);
        }
        return $h1;
    }

    /**
     * @param string $h1
     * @param array  $parameters
     * @param string $domain
     * @return PageMeta
     */
    public function setH1Trans($h1, $parameters = [], $domain = 'messages')
    {
        $this->h1 = $this->translator->trans($h1, $parameters, $domain);
        return $this;
    }

    /**
     * @param null $default
     * @param null $truncate
     * @return string
     */
    public function getMetaDescription($default = null, $truncate = null)
    {
        $metaDescription = $this->metaDescription ? $this->metaDescription : $default;
        if ($this->autotextSeed) {
            $metaDescription = Autotext::autotext(' '.$metaDescription, $this->autotextSeed);
        }
        $metaDescription = FilterStatic::filterValue($metaDescription, MetaDescription::class);
        if ($truncate) {
            $metaDescription = StringUtil::truncate($metaDescription, (int)$truncate, true, '');
        }
        return $metaDescription;
    }

    /**
     * @param       string
     * @return PageMeta
     */
    public function setMetaDescription($metaDescription = '')
    {
        $this->metaDescription = $metaDescription;
        return $this;
    }

    /**
     * @param string $metaDescription
     * @param array  $parameters
     * @param string $domain
     * @return PageMeta
     */
    public function setMetaDescriptionTrans($metaDescription = '', $parameters = [], $domain = 'messages')
    {
        $metaDescription = $this->translator->trans($metaDescription, $parameters, $domain);
        return $this->setMetaDescription($metaDescription);
    }

    /**
     * @param null $default
     * @return string
     */
    public function getMetaKeywords($default = null)
    {
        $metaKeywords = $this->metaKeywords ? $this->metaKeywords : $default;
        if ($this->autotextSeed) {
            $metaKeywords = FilterStatic::filterValue($metaKeywords, MetaKeywords::class);
            $metaKeywords = Autotext::autotext(' '.$metaKeywords, $this->autotextSeed);
        }

        return FilterStatic::filterValue($metaKeywords, MetaKeywords::class);
    }

    /**
     * @param string|array $metaKeywords
     * @return PageMeta
     */
    public function setMetaKeywords($metaKeywords)
    {
        $this->metaKeywords = $metaKeywords;
        return $this;
    }

    /**
     * @param string $metaKeywords
     * @param array  $parameters
     * @param string $domain
     * @return PageMeta
     */
    public function setMetaKeywordsTrans($metaKeywords, $parameters = [], $domain = 'messages')
    {
        $metaKeywords = $this->translator->trans($metaKeywords, $parameters, $domain);

        return $this->setMetaKeywords($metaKeywords);
    }

    /**
     * @param $metaKeywords
     * @return $this
     */
    public function appendMetaKeywords($metaKeywords)
    {
        $metaKeywordsRegistry = [];

        $_metaKeywords = array_map('trim', explode(',', $this->getMetaKeywords()));
        foreach ($_metaKeywords as $mk) {
            $metaKeywordsRegistry[StringUtil::lower($mk)] = $mk;
        }

        if (!is_array($metaKeywords)) {
            $metaKeywords = explode(',', $metaKeywords);
        }

        $metaKeywords = array_map('trim', $metaKeywords);
        foreach ($metaKeywords as $mk) {
            $metaKeywordsRegistry[StringUtil::lower($mk)] = $mk;
        }

        return $this->setMetaKeywords($metaKeywordsRegistry);
    }

    /**
     * @param null $default
     * @return string
     */
    public function getMetaRobots($default = null)
    {
        $metaRobots = $this->metaRobots ? $this->metaRobots : $default;

        return FilterStatic::filterValue($metaRobots, MetaRobots::class);
    }

    /**
     * @param string $metaRobots
     * @return PageMeta
     */
    public function setMetaRobots($metaRobots)
    {
        $this->metaRobots = $metaRobots;
        return $this;
    }

    /**
     * @param       string $metaRobots
     * @param array        $parameters
     * @param string       $domain
     * @return PageMeta
     */
    public function setMetaRobotsTrans($metaRobots = '', $parameters = [], $domain = 'messages')
    {
        return $this->setMetaRobots($this->translator->trans($metaRobots, $parameters, $domain));
    }

    /**
     * Sets the default charset.
     * @param string $charset The charset
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    /**
     * Gets the default charset.
     * @param null $default
     * @return string The default charset
     */
    public function getCharset($default = null)
    {
        return $this->charset ? $this->charset : $default;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'page_meta';
    }

    /**
     * @param $text
     * @param $url
     * @return PageMeta
     */
    public function addBreadcrumbItem($text, $url)
    {
        $this->breadcrumbs[] = [
            'url' => $url,
            'text' => $text,
        ];

        return $this;
    }

    /**
     * @param       $text
     * @param       $route
     * @param array $parameters
     * @param int   $referenceType
     * @return PageMeta
     */
    public function addBreadcrumbRouteItem(
        $text,
        $route,
        array $parameters = array(),
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    )
    {
        $this->breadcrumbs[] = [
            'url' => $this->router->generate($route, $parameters, $referenceType),
            'text' => FilterStatic::filterValue($text, Name::class),
        ];

        return $this;
    }

    /**
     * @param        $text
     * @param        $route
     * @param array  $parameters
     * @param int    $referenceType
     * @param array  $translationParameters
     * @param string $domain
     * @return PageMeta
     */
    public function addBreadcrumbRouteItemTrans(
        $text,
        $route,
        array $parameters = array(),
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
        array $translationParameters = array(),
        $domain = 'messages'
    )
    {
        $this->breadcrumbs[] = [
            'url' => $this->router->generate($route, $parameters, $referenceType),
            'text' => $this->translator->trans($text, $translationParameters, $domain),
        ];

        return $this;
    }

    /**
     * @param       $text
     * @param       $title
     * @param       $route
     * @param array $parameters
     * @param int   $referenceType
     * @return PageMeta
     */
    public function addBreadcrumbRouteItemTitled(
        $text,
        $title,
        $route,
        array $parameters = array(),
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    )
    {
        $this->breadcrumbs[] = [
            'url' => $this->router->generate($route, $parameters, $referenceType),
            'text' => FilterStatic::filterValue($text, Name::class),
            'title' => FilterStatic::filterValue($title, Name::class),
        ];
        return $this;
    }

    /**
     * @param        $text
     * @param        $title
     * @param        $route
     * @param array  $parameters
     * @param int    $referenceType
     * @param array  $translationParameters
     * @param string $domain
     * @return PageMeta
     */
    public function addBreadcrumbRouteItemTitledTrans(
        $text,
        $title,
        $route,
        array $parameters = array(),
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
        array $translationParameters = array(),
        $domain = 'messages'
    )
    {
        $this->breadcrumbs[] = [
            'url' => $this->router->generate($route, $translationParameters, $referenceType),
            'text' => $this->translator->trans($text, $parameters, $domain),
            'title' => $this->translator->trans($title, $parameters, $domain),
        ];
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBreadcrumbs()
    {
        return $this->breadcrumbs;
    }

    /**
     * @return mixed
     */
    public function getAutotextSeed()
    {
        return $this->autotextSeed;
    }

    /**
     * @param mixed $autotextSeed
     * @return PageMeta
     */
    public function setAutotextSeed($autotextSeed)
    {
        if ($autotextSeed instanceof Request) {
            $autotextSeed = $autotextSeed->getSchemeAndHttpHost().$autotextSeed->getRequestUri();
        }
        $this->autotextSeed = $autotextSeed;
        $this->getOg()->setAutotextSeed($autotextSeed);
        return $this;
    }

    /**
     * @return string
     */
    public function getCanonical()
    {
        return $this->canonical;
    }

    /**
     * @param string $canonical
     * @return PageMeta
     */
    public function setCanonical($canonical)
    {
        $this->canonical = $canonical;

        return $this;
    }

    public function getCanonicalTag()
    {
        if (!$link = $this->getCanonical()) return null;
        return "<link rel=\"canonical\" href=\"{$link}\" />";
    }

    /**
     * @return null
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @param null $node
     * @return PageMeta
     */
    public function setNode($node)
    {
        $this->node = $node;
        return $this;
    }

    /**
     * @return string
     */
    public function getInPoint(): string
    {
        return $this->inPoint;
    }

    /**
     * @param string $inPoint
     * @return PageMeta
     */
    public function setInPoint(string $inPoint): PageMeta
    {
        $this->inPoint = $inPoint;
        return $this;
    }

    public function addPreload($link, $packageName = null, $as = 'style')
    {
        $url = $this->getUrl($link, $packageName);

        $linkHash = $link.'_'.$as;
        if (!isset($this->preload[$linkHash])) {
            $this->preload[$linkHash] = [
                'link' => $url,
                'as' => $as
            ];
        }
    }

    public function addStyle($link, $packageName = null, $group = 'default',  $media = 'all')
    {
        $url = $this->getUrl($link, $packageName);
        $linkHash = $url . '_' . $media;

        if (!$group) $group = 'default';

        if (!isset($this->styles[$group])) {
            $this->styles[$group] = [];
        }

        if (!in_array($linkHash, $this->styles[$group])) {
            $this->styles[$group][$linkHash] = [
                'link' => $url,
                'media' => $media
            ];
        }

        return $this;
    }

    public function removeStyle($link, $packageName = null, $group = 'default',  $media = 'all')
    {
        if (!isset($this->styles[$group])) return $this;

        $url = $this->getUrl($link, $packageName);
        $linkHash = $url . '_' . $media;

        if (in_array($linkHash, $this->styles)) {
            unset($this->styles[$linkHash]);
        }

        return $this;
    }

    public function getStyleLinks($group = 'default')
    {
        if (!isset($this->styles[$group])) return null;

        $result = '';
        foreach ($this->styles[$group] as $style)
        {
            $result .= "<link rel=\"stylesheet\" href=\"{$style['link']}\" media=\"{$style['media']}\" />\n";
        }

        return $result;
    }

    public function getPreloadLinks()
    {
        $result = '';
        foreach ($this->preload as $preload)
        {
            $result .= "<link rel=\"preload\" href=\"{$preload['link']}\" as=\"{$preload['as']}\" />\n";
        }

        return $result;
    }

    public function addJavascript($link, $packageName = null, $group = 'default', $type = 'text/javascript')
    {
        $url = $this->getUrl($link, $packageName);
        $linkHash = $url.'_'.$type;

        if (!$group) $group = 'default';

        if (!isset($this->javascripts[$group])) {
            $this->javascripts[$group] = [];
        }

        if (!in_array($linkHash, $this->javascripts[$group])) {
            $this->javascripts[$group][$linkHash] = [
                'link' => $url,
                'as' => $type
            ];
        }

        return $this;
    }

    public function removeJavascript($link, $packageName = null, $group = 'default', $type = 'text/javascript')
    {
        if (!isset($this->javascripts[$group])) return $this;

        $url = $this->getUrl($link, $packageName);
        $linkHash = $url.'_'.$type;

        if (in_array($linkHash, $this->javascripts)) {
            unset($this->javascripts[$linkHash]);
        }

        return $this;
    }

    public function getJavascriptLinks($group = 'default')
    {
        if (!$group) $group = 'default';
        if (!isset($this->javascripts[$group])) return null;

        $result = '';
        foreach ($this->javascripts[$group] as $style)
        {
            $typeParam = '';
            if (isset($style['type']) && ($style['type'] != 'text/javascript')) {
                $typeParam = " type=\"{$style['type']}\"";
            }
            $result .= "<script{$typeParam} src=\"{$style['link']}\"></script>\n";
        }

        return $result;
    }

    public function addToStorage($text, $group = 'default')
    {
        $textHash = md5($text);
        $this->storage[$group][$textHash] = $text;
        return $this;
    }

    public function getFromStorage($group = 'default')
    {
        if (!isset($this->storage[$group])) return [];
        return $this->storage[$group];
    }

    /**
     * Returns the public url/path of an asset.
     *
     * If the package used to generate the path is an instance of
     * UrlPackage, you will always get a URL and not a path.
     *
     * @param string $path        A public path
     * @param string $packageName The name of the asset package to use
     *
     * @return string The public path of the asset
     */
    public function getUrl($path, $packageName = null)
    {
        return $this->packages->getUrl($path, $packageName);
    }

    public function javascriptShow($group = 'default')
    {
        $group = 'javascript_' . str_replace('javascript_', '', $group);
        if (!$items = $this->getFromStorage($group)) {
            return '';
        }

        $result = "<script>\n";
        foreach ($this->getFromStorage($group) as $item) {
            $item = preg_replace('#^\s*<script[^>]*>#usi', '', $item);
            $item = preg_replace('#</script[^>]*>\s*$#usi', '', $item);
            $result .= trim($item) . "\n";

        }
        $result .= "</script>";

        return $result;
    }

    public function addHeaderLink($rel, $href = null, array $params = [])
    {
        if ($href) $params['href'] = $href;
        $link = [
            'rel' => $rel,
            'params' => $params
        ];
        array_multisort($link);
        $hash = md5(json_encode($link));

        $this->links[$hash] = $link;
        return $this;
    }

    public function getHeaderLinksAsHtml()
    {
        $result = '';
        foreach ($this->links as $link) {
            $result .= "<link rel=\"{$link['rel']}\"";
            foreach ($link['params'] as $k => $v) {
                $result .= " {$k}=\"{$v}\"";
            }
            $result .= " />\n";
        }

        return trim($result);
    }

    public function addHeaderMeta(array $params = [])
    {
        if (empty($params)) return $this;
        array_multisort($params);
        $hash = md5(json_encode($params));

        $this->headerMetas[$hash] = $params;
        return $this;
    }

    public function getHeaderMetasAsHtml()
    {
        $result = '';
        foreach ($this->headerMetas as $meta) {
            $result .= "<meta";
            foreach ($meta as $k => $v) {
                $result .= " {$k}=\"{$v}\"";
            }
            $result .= " />\n";
        }

        return trim($result);
    }

    public function getOg()
    {
        if (!$this->og) {
            $this->og = new PageMetaOpenGraph();

            if ($this->autotextSeed) {
                $this->og->setAutotextSeed($this->getAutotextSeed());
            }
        }

        return $this->og;
    }

    public function setNoindexNofollow(bool $flag)
    {
        if ($flag) {
            $this->setMetaRobots('NOINDEX, NOFOLLOW');
        }

        return $this;
    }
}
