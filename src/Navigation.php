<?php

namespace BrainDump;

class Navigation
{
    public static function scanMenuItems($dir, $siteUrl, $basePath = '')
    {
        $items = [];
        foreach (new \DirectoryIterator($dir) as $file) {
            if ($file->isDot() || !$file->isDir()) continue;

            $relativePath = trim($basePath . '/' . $file->getFilename(), '/');
            $url = "{$siteUrl}/{$relativePath}/";

            $menuItem = [
                'name' => ucfirst($file->getFilename()),
                'url' => $url,
                'children' => self::scanMenuItems($file->getPathname(), $siteUrl, $relativePath),
            ];

            $items[] = $menuItem;
        }
        return $items;
    }
    
    public static function breadcrumbsArray($pageUrl, $siteUrl)
    {
        $crumbs = [];
        $segments = explode('/', trim($pageUrl, '/'));
        $path = '';

        foreach ($segments as $segment) {
            $name = ucfirst(str_replace('-', ' ', $segment));

            // Only add the breadcrumb if it has a valid name
            if (!empty($name) && trim($name) !== '') {
                $path .= '/' . $segment;
                $crumbs[] = [
                    'name' => $name,
                    'url' => $siteUrl . $path . '/',
                    'active' => rtrim($siteUrl . $path, '/') === rtrim($siteUrl . $pageUrl, '/')
                ];
            }
        }

        return !empty($crumbs) ? $crumbs : null;
    }

    public static function menuArray($menuItems, $currentUrl)
    {
        return array_map(function ($item) use ($currentUrl) {
            return [
                'name' => $item['name'],
                'url' => $item['url'],
                'active' => rtrim($item['url'], '/') === rtrim($currentUrl, '/') ? true : false // ✅ Explicitly set false
            ];
        }, $menuItems);
    }



}
