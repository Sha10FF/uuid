<?php

namespace UUID;

abstract class ServerId
{
    /** @var int */
    protected static $id;

    public static function get(): int
    {
        if (isset(self::$id)) {
            return self::$id;
        }
        $sid = getenv('SERVER_ID');
        if ($sid !== false && is_numeric($sid)) {
            return self::$id = $sid;
        } elseif (file_exists('/etc/machine-id')) {
            return self::$id = crc32(file_get_contents('/etc/machine-id'));
        } else {
            return self::$id = crc32(php_uname('a'));
        }
    }

    public static function set(int $id): void
    {
        self::$id = $id;
    }
}