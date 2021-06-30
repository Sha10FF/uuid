<?php

namespace UUID;

use DateInterval;
use DateTime;
use JsonSerializable;

class Uuid implements JsonSerializable
{
    /**
     * time in 100 nanosecond intervals since 15 October 1582,
     * but for instance in millisecond precision since 1 January 1970
     * 60bit RFC4122 timestamp
     */
    protected const TIMESTAMP_DIFF = 0x01b21dd213814000;
    protected const FORMAT = '%04x%04x-%04x-%04x-%04x-%06x%06x';
    public const NULL = '00000000-0000-0000-0000-000000000000';

    /**
     * @var int 16 bit
     */
    protected $timeLow;
    /**
     * @var int 16 bit
     */
    protected $timeMid;
    /**
     * @var int 28 bit
     */
    protected $timeHigh;

    /**
     * @var int 4 bit
     */
    protected $version;

    /**
     * @var int [1-16] bit
     */
    protected $variant;

    /**
     * @var int [15-variant]bit looped autoincrement
     */
    protected $seq;

    /**
     * @var int 24 bit
     */
    protected $nodeHi;

    /**
     * @var int 24 bit
     */
    protected $nodeLow;

    protected function __construct(
        int $timeLow,
        int $timeMid,
        int $timeHigh,
        int $version,
        int $variant,
        int $seq,
        int $nodeHi,
        int $nodeLow
    )
    {
        $this->timeLow = $timeLow;
        $this->timeMid = $timeMid;
        $this->timeHigh = $timeHigh;
        $this->version = $version;
        $this->variant = $variant;
        $this->seq = $seq;
        $this->nodeHi = $nodeHi;
        $this->nodeLow = $nodeLow;
    }

    /**
     * @param string $str
     * @return Uuid
     */
    public static function fromBase64(string $str): Uuid
    {
        return static::fromBinary(base64_decode($str));
    }

    /**
     * @param string $str
     * @return Uuid
     */
    public static function fromBinary(string $str): Uuid
    {
        $hex = bin2hex($str);
        $hex = str_pad($hex, 32, '0', STR_PAD_LEFT);
        //8-4-4-4-12
        $uuid = sprintf('%s%s-%s-%s-%s-%s%s%s', ...str_split($hex, 4));

        return static::fromString($uuid);
    }

    /**
     * @param string $uuid
     * @return Uuid
     */
    public static function fromString(string $uuid): Uuid
    {
        $res = sscanf(
            $uuid,
            self::FORMAT,
            $time_low,
            $time_mid,
            $time_hi,
            $time_hi_and_version,
            $variant_clock_seq,
            $nodeHi,
            $nodeLow
        );

        if ($res != 7) {
            return new static(0, 0, 0, 0, 0, 0, 0, 0);
        }

        $time_hi = $time_hi | (($time_hi_and_version & 0x0fff) << 16);
        $version = $time_hi_and_version >> 12; //4bit

        for ($variant = 0; $variant <= 8; $variant++) {
            if (!((1 << 15 - $variant) & $variant_clock_seq)) {
                break; //zero bit found
            }
        }
        $seq = $variant_clock_seq & (0x7fff >> $variant);

        return new static($time_low, $time_mid, $time_hi, $version, $variant, $seq, $nodeHi, $nodeLow);
    }

    /**
     * @param string $str
     * @return bool
     */
    public static function validateString(string $str): bool
    {
        return (bool)preg_match('/^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$/i', $str);
    }

    /**
     * @param DateTime|int $time
     * @return Uuid
     */
    public static function uuid1($time = null): Uuid
    {
        return static::genTimeUuid(1, 1, $time);
    }

    /**
     * @param $version
     * @param $variant
     * @param DateTime|int $time
     * @return Uuid
     */
    protected static function genTimeUuid(int $version, int $variant, $time = null): Uuid
    {
        if (!is_int($time)) {
            $time = static::rfc4122Timestamp($time);
        }
        $seq = static::genSeq() & (0x7fff >> $variant);
        $node = static::genNode();

        return new static($time, $version, $variant, $seq, $node);
    }

    /**
     * rfc4122 60-bit timestamp * in (100ns) since 15 October 1582
     *
     * @param DateTime
     * @return int 60-bit timestamp
     */
    public static function rfc4122Timestamp(?DateTime $time = null): int
    {

        if (is_null($time)) {
            $rawtime = microtime();
        } else {
            $rawtime = $time->format('0.u U');
        }

        list($ms, $ts) = explode(' ', $rawtime);

        return self::TIMESTAMP_DIFF + ($ts * 1000000 + intval(substr($ms, 2))) * 10;
    }

    /**
     * @return int
     */
    protected static function genSeq(): int
    {
        static $seq = 0;
        $seq = ($seq + 1) & 0x7fff; //15bit max

        return $seq;
    }

    /**
     * @return int
     */
    protected static function genNode(): int
    {
        return self::getMac();
    }

    /**
     * @return int
     */
    protected static function getMac(): int
    {
        $res = shell_exec('ip link');
        if (preg_match_all('/link\/\w+\s+(?<mac>([\da-f]{2}:){5}[\da-f]{2})/im', $res, $found, PREG_SET_ORDER)) {
            foreach ($found as $item) {
                $mac = (int)base_convert(str_replace(':', '', $item['mac']), 16, 10);
                if ($mac && $mac < (1 << 48) - 1) {
                    return $mac;
                }
            }
        }

        return 0;
    }

    public static function uuid4(): Uuid
    {
        return static::genRandomUuid(4, 1);
    }

    protected static function genRandomUuid(int $version, int $variant): Uuid
    {
        $timeLow = rand(0, (1 << 16) - 1);
        $timeMid = rand(0, (1 << 16) - 1);
        $timeHigh = rand(0, (1 << 28) - 1);
        $seq = rand(0, (1 << 15 - $variant) - 1);
        $nodeHi = rand(0, (1 << 24) - 1);
        $nodeLow = rand(0, (1 << 24) - 1);

        return new static($timeLow, $timeMid, $timeHigh, $version, $variant, $seq, $nodeHi, $nodeLow);
    }

    public function getDateTime(): DateTime
    {


        list ($ms, $ts) = explode(' ', $this->getMicrotime());

        $date = new DateTime('@' . $ts);
        $int = new DateInterval('PT0S');
        $int->f = floatval($ms);
        $date->add($int);

        return $date;
    }

    public function getMicrotime(bool $get_as_float = false)
    {
        $time = $this->time - self::TIMESTAMP_DIFF;
        $ms = sprintf('%07d', $time % 10000000);
        $ts = intval($time / 10000000);

        if ($get_as_float) {
            return floatval($ts . '.' . $ms);
        }

        return '0.' . $ms . ' ' . $ts;
    }


    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return int
     */
    public function getVariant(): int
    {
        return $this->variant;
    }

    /**
     * @return int
     */
    public function getSeq(): int
    {
        return $this->seq;
    }


    /**
     * Base64 encoded binary Uuid
     *
     * @return string 22 chars
     */
    public function toBase64(): string
    {
        return rtrim(base64_encode($this->toBinary()), '=');
    }

    /**
     * Binary Uuid
     *
     * @return string 16 chars
     */
    public function toBinary(): string
    {
        return hex2bin(str_replace('-', '', $this->toString()));
    }

    /**
     * @return string
     */
    public function toString(): string
    {

        $time_hi_and_version = (($this->version & 0x7) << 12)  // 4bit
            | (($this->timeHigh >> 16) & 0x0fff); // 12bit ;

        $variant_binary = 0xffff & (0xffff << (16 - $this->variant));
        $seq_mask = 0xffff >> ($this->variant + 1);
        $clock_seq_variant = $variant_binary | ($this->seq & $seq_mask); //16bit

        return sprintf(self::FORMAT,
            $this->timeLow,
            $this->timeMid,
            $this->timeHigh & 0xffff,
            $time_hi_and_version,
            $clock_seq_variant,
            $this->nodeHi,
            $this->nodeLow
        );
    }

    /**
     * for easy conversion
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * for correct json conversion
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->toString();
    }
}
