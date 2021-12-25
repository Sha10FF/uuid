<?php

namespace UUID {

    use PHPUnit\Framework\TestCase;

    class MicroUuidTest extends TestCase
    {
        public function testCreate()
        {
            $uuid1 = MicroUuid::get();
            $uuid2 = MicroUuid::get($uuid1->getTime());
            $this->assertNotEquals($uuid1, $uuid2);
            $this->assertEquals($uuid1->getTime(), $uuid2->getTime());

            $uuid0 = MicroUuid::fromString($uuid1);
            $this->assertEquals($uuid0, $uuid1);

            $uuid0 = MicroUuid::fromBinary($uuid1->toBinary());
            $this->assertEquals($uuid0, $uuid1);

            $uuid0 = MicroUuid::fromBase64($uuid1->toBase64());
            $this->assertEquals($uuid0, $uuid1);

            $uuid0 = MicroUuid::fromString(json_decode(json_encode($uuid1)));
            $this->assertEquals($uuid0, $uuid1);
        }

        public function testGetters()
        {
            $uuid1 = MicroUuid::get();
            $time = $uuid1->getTime();
            $server = rand(0, 0xffff);

            ServerId::set($server);
            $uuid2 = MicroUuid::get($time);
            $this->assertEquals($uuid2->getTime(), $time);
            $this->assertEquals($uuid2->getServer(), $server);
            $this->assertEquals($uuid1->getPid(), $uuid2->getPid());
            $this->assertEquals($uuid1->getSeq() + 1, $uuid2->getSeq());
        }

        public function testValidators()
        {
            $null = MicroUuid::fromString("");
            $this->assertTrue($null->isNull());
            $this->assertTrue($null->isValid());
            $this->assertFalse($null->isOk());

            $malformed = MicroUuid::fromString('12345678-9abcd-baddata-1234');
            $this->assertTrue($malformed->isNull());
            $this->assertTrue($malformed->isValid());
            $this->assertFalse($malformed->isOk());

            $invalid = MicroUuid::fromBinary(hex2bin('ffffffffffffffffffffffff'));
            $this->assertEquals($invalid->getTime()->format('u'), 999999);
            $this->assertFalse($invalid->isNull());
            $this->assertFalse($invalid->isValid());
            $this->assertFalse($invalid->isOk());

            $invalid1e6 = MicroUuid::fromString('00000000-f4240-0000000-0000');
            $this->assertFalse($invalid1e6->isValid());

            $this->assertFalse(MicroUuid::validateString('bullshit'));
            $this->assertFalse(MicroUuid::validateString('12345678-9abcd-baddata-1234'));
            $this->assertTrue(MicroUuid::validateString($invalid->toString()));
            $this->assertTrue(MicroUuid::validateString(strtoupper($invalid->toString())));
        }

        public function testY2038()
        {
            $date1 = new \DateTime('2038-01-01');
            $date2 = new \DateTime('2038-02-01');
            $uuid1 = MicroUuid::get($date1);
            $uuid2 = MicroUuid::get($date2);
            $this->assertEquals($date1, $uuid1->getTime());
            $this->assertEquals($date2, $uuid2->getTime());
            $this->assertTrue(strcmp($uuid2->toString(), $uuid1->toString()) > 0);
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
