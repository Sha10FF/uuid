<?php

namespace UUID {

    function socket_create()
    {

    }

    class UuidTest extends \PHPUnit\Framework\TestCase
    {
        public function testCreate()
        {

            $uuid1 = Uuid::uuid1();
            $uuid2 = Uuid::uuid1($uuid1->getDateTime());

            $this->assertNotEquals($uuid1, $uuid2);
            $this->assertEquals($uuid1->getTime(), $uuid2->getTime());
            $this->assertEquals($uuid1->getMicrotime(true), $uuid2->getMicrotime(true));

            $uuid11 = Uuid::fromString($uuid1->toString());
            $this->assertEquals($uuid1, $uuid11);

            $uuid4 = Uuid::uuid4();
            $uuid44 = Uuid::fromString($uuid4->toString());
            $this->assertEquals($uuid4, $uuid44);
            $uuid44 = Uuid::fromBinary($uuid4->toBinary());
            $this->assertEquals($uuid4, $uuid44);
            $uuid44 = Uuid::fromBase64($uuid4->toBase64());
            $this->assertEquals($uuid4, $uuid44);

            $uuid0 = Uuid::fromString(json_decode(json_encode($uuid1)));
            $this->assertEquals($uuid0, $uuid1);
            $this->assertEquals((string)$uuid0, $uuid1->toString());
        }

        public function testGetters()
        {
            $uuid1 = Uuid::uuid1();
            $time = $uuid1->getTime();
            $uuid2 = Uuid::uuid1($time);

            $this->assertEquals($uuid2->getTime(), $time);
            $this->assertEquals($uuid1->getSeq() + 1, $uuid2->getSeq());
            $this->assertEquals($uuid1->getVariant(), $uuid2->getVariant());
            $this->assertEquals($uuid1->getVersion(), $uuid2->getVersion());
            $this->assertEquals($uuid1->getNode(), $uuid2->getNode());
        }
    }
}

