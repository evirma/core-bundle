<?php

namespace Evirma\Bundle\CoreBundle\Service;

use Evirma\Bundle\CoreBundle\Filter\FilterStatic;
use Evirma\Bundle\CoreBundle\Filter\Rule\RemoveUtm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PageCache
{
    private $storageDir;
    private $env;
    private $tokenStorage;

    public function __construct($storageDir, $env, TokenStorageInterface $tokenStorage)
    {
        $this->storageDir = $storageDir;
        $this->env = $env;
        $this->tokenStorage = $tokenStorage;
    }

    public function saveNginxPageCache(Request $request, $content)
    {
        if (($this->env != 'prod') || $this->isUser()) {
            return true;
        }

        if (!$filename = $this->getNginxFilename($request)) return false;

        if ($this->saveZipped($filename, $content)) {
            $filename = preg_replace('#\.gz$#usi', '', $filename);
            @touch($filename);
            return true;
        }

        return false;
    }

    private function getNginxFilename(Request $request)
    {
        $dir = $this->storageDir.'/nginx_page_cache/'.$request->getHost();
        $url = FilterStatic::filterValue($request->getRequestUri(), RemoveUtm::class);
        $parts = explode('?', $url);
        $path = $parts[0];
        $params = '';
        if (count($parts) == 2) {
            $params = $parts[1];
        }

        $path = trim($path, '/');
        if ($params) $path .= $params;
        $path = trim($path, '/');

        if (str_contains($path, '..') || str_contains($path, './')) {
            return null;
        }

        $filename = $dir . '/' . $path . '.html.gz';
        if (!($basename = basename($filename)) || strlen($basename) > 128) {
            return null;
        }

        $dir = dirname($filename);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $filename;
    }

    public function getNginxFilanameByUrl(string $url)
    {
        if (!$url = FilterStatic::filterValue($url, RemoveUtm::class)) {
            return null;
        }

        $domain = null;
        if (preg_match('#^https?://([^/]+)#si', $url, $m)) {
            $domain = $m[1];
        }

        if (!$domain) {
            return null;
        }

        $dir = $this->storageDir.'/nginx_page_cache/'.$domain;

        $path = preg_replace('#^https?://[^/]+#usi', '', $url);

        $parts = explode('?', $path);
        $path = $parts[0];

        $params = '';
        if (count($parts) == 2) {
            $params = $parts[1];
        }

        $path = trim($path, '/');
        if ($params) $path .= $params;
        $path = trim($path, '/');

        if (str_contains($path, '..') || str_contains($path, './')) {
            return null;
        }

        return $dir . '/' . $path . '.html.gz';
    }

    private function saveZipped($file, $content)
    {
        $fp = gzopen($file, 'w9');

        if ($fp !== FALSE) {
            gzwrite($fp, $content);
            gzclose($fp);
            return $content;
        } else {
            return false;
        }
    }

    private function isUser()
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return false;
        }

        if (!is_object($token->getUser())) {
            // e.g. anonymous authentication
            return false;
        }

        return true;
    }
}
