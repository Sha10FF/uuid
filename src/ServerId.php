<?php

namespace UUID;

abstract class ServerId
{
    /** @var int */
    public static $id;

    public static function get(): int
    {
        if (isset(self::$id)) {
            return self::$id;
        }
        $sid = getenv('SERVER_ID');
        if ($sid !== false && is_numeric($sid)) {
            self::$id = $sid;
        } elseif (file_exists('/etc/machine-id')) {
            self::$id = crc32(file_get_contents('/etc/machine-id'));
        } else {
            self::$id = crc32(php_uname('a'));
        }

        return self::$id;
    }

    public static function set(int $id): void
    {
        self::$id = $id;
    }
}
