<?php

namespace BrainDump;

use Symfony\Component\Yaml\Yaml;

class Config {
    private static $config;

    public static function load($path) {
        self::$config = Yaml::parseFile($path);
    }

    public static function get($key, $default = null) {
        return self::$config[$key] ?? $default;
    }
}
