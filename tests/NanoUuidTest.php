<?php

namespace UUID {

    use DateTime;
    use PHPUnit\Framework\TestCase;

    class NanoUuidTest extends TestCase
    {
        public function testCreate()
        {
            $uuid1 = NanoUuid::get();
            $uuid2 = NanoUuid::get($uuid1->getTime());
            $this->assertNotEquals($uuid1, $uuid2);
            $this->assertEquals($uuid1->getTime(), $uuid2->getTime());
            $this->assertEquals($uuid1->getTimestamp(), $uuid2->getTimestamp());

            $uuid0 = NanoUuid::fromString($uuid1);
            $this->assertEquals($uuid0, $uuid1);

            $uuid0 = NanoUuid::fromBinary($uuid1->toBinary());
            $this->assertEquals($uuid0, $uuid1);

            $uuid0 = NanoUuid::fromBase64($uuid1->toBase64());
            $this->assertEquals($uuid0, $uuid1);

            //only for x64 or more
            if(PHP_INT_SIZE >= 8) {
                $uuid0 = NanoUuid::fromBigInt($uuid1->toBigInt());
                $this->assertEquals($uuid0, $uuid1);
            }

            $uuid0 = NanoUuid::fromString(json_decode(json_encode($uuid1)));
            $this->assertEquals($uuid0, $uuid1);
        }

        public function testGetters()
        {
            $uuid1 = NanoUuid::get();
            $time = $uuid1->getTime();

            $uuid2 = NanoUuid::get($time);
            $this->assertEquals($uuid2->getTime(), $time);
            $this->assertEquals($uuid1->getPid(), $uuid2->getPid());
            $this->assertEquals($uuid1->getSeq() + 1, $uuid2->getSeq());
        }

        public function testValidators()
        {
            $null = NanoUuid::fromString("");
            $this->assertTrue($null->isNull());
            $this->assertTrue(NanoUuid::validateString("12345678-9abcdef0"));
            $this->assertFalse(NanoUuid::validateString("bullshit"));
        }

        public function testY2038()
        {
            $date1 = new DateTime('2038-01-01');
            $date2 = new DateTime('2038-02-01');
            $uuid1 = NanoUuid::get($date1);
            $uuid2 = NanoUuid::get($date2);
            $this->assertEquals($date1, $uuid1->getTime());
            $this->assertEquals($date2, $uuid2->getTime());
            $this->assertTrue(strcmp($uuid2->toString(), $uuid1->toString()) > 0);
        }
    }
}
