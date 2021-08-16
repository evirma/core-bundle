<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Filter\FilterRule;

class RemoveUtm extends FilterRule
{
    public function filter($value)
    {
        if (!strpos($value, '?')) {
            return $value;
        }

        $parts = parse_url($value);

        $queryItems = [];
        parse_str($parts['query'], $queryItems);

        foreach ($queryItems as $id => $null)
        {
            if (str_starts_with($id, 'utm_')) {
                unset($queryItems[$id]);
            }

            if (in_array($id, ['block', 'position', 'yclid'])) {
                unset($queryItems[$id]);
            }
        }

        if (!empty($queryItems)) {
            $parts['query'] = http_build_query($queryItems);
        } else {
            unset($parts['query']);
        }

        return $this->unparseUrl($parts);
    }

    private function unparseUrl($parsed_url)
    {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = $parsed_url['host'] ?? '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = $parsed_url['user'] ?? '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = $parsed_url['path'] ?? '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}