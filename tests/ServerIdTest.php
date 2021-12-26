<?php

namespace UUID {

    use PHPUnit\Framework\TestCase;

    class ServerIdTest extends TestCase
    {
        public function testGet()
        {
            ServerId::$id = null;
            $this->assertEquals(crc32(php_uname('a')), ServerId::get());

            ServerId::$id = null;
            file_exists('/etc/machine-id', true);
            $this->assertEquals(crc32('test'), ServerId::get());

            ServerId::$id = null;
            $srv = rand(0, 0xffff);
            putenv("SERVER_ID={$srv}");
            $this->assertEquals($srv, ServerId::get());
        }
    }

    function file_exists($name, $set = null)
    {
        static $data = [];
        if ($set) {
            $data[$name] = $set;
        }

        return $data[$name] ?? false;
    }

    function file_get_contents()
    {
        return 'test';
    }
}