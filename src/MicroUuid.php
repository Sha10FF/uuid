<?php

namespace UUID;

use DateTime;
use JsonSerializable;

class MicroUuid implements JsonSerializable
{
    protected const MASK_TS = 0xffff;     // 16 bit
    protected const MASK_MS = 0xfffff;    // 20 bit
    protected const MASK_SEQ = 0x3f;      //  6 bit
    protected const MASK_PID = 0x3fffff;  // 22 bit
    protected const MASK_SERVER = 0xffff; // 16 bit
    protected const FORMAT = '%04x%04x-%05x-%07x-%04x';
    public const NULL = '00000000-00000-0000000-0000';

    /**
     * @var int
     * 16bit
     * SERVER_ID=[0-65535] in /etc/environment
     */
    public static $defaultServer;

    /**
     * @var int
     * 16bit
     * seconds since 1970-01-01 00:00:00 UTC to 2106-02-07 06:28:15 UTC
     */
    protected $secondsLow;

    /**
     * @var int
     * 16bit
     * seconds since 1970-01-01 00:00:00 UTC to 2106-02-07 06:28:15 UTC
     */
    protected $secondsHigh;

    /**
     * @var int
     * 20bit
     * microtime() return ts with 6-digits precision 1`000`000 < 2^20
     */
    protected $micros;

    /**
     * @var int
     * 6bit
     * short counter for prevention uuid duplication [0-63]
     */
    protected $seq;

    /**
     * @var int
     * 22bit
     * PID_MAX_LIMIT in linux kernel
     */
    protected $pid;

    /**
     * @var int
     * 16bit
     */
    protected $server;

    protected function __construct(
        int $secondsHigh,
        int $secondsLow,
        int $micros,
        int $seq,
        int $pid,
        int $server
    )
    {
        $this->secondsHigh = $secondsHigh;
        $this->secondsLow = $secondsLow;
        $this->micros = $micros;
        $this->seq = $seq;
        $this->pid = $pid;
        $this->server = $server;
    }

    public static function get(?DateTime $time = null): self
    {

        if (is_null($time)) {
            $rawtime = microtime();
        } else {
            $rawtime = $time->format('0.u U');
        }

        list($ms, $ts) = explode(' ', $rawtime);

        $hexTs = base_convert($ts, 10, 16);
        $hexTs = str_pad($hexTs, 8, '0', STR_PAD_LEFT);

        return new static(
            hexdec(substr($hexTs, 0, 4)),
            hexdec(substr($hexTs, 4, 8)),
            intval(substr($ms, 2, 6)),
            static::genSeq(),
            static::genPid(),
            static::genServer()
        );
    }

    protected static function genSeq(): int
    {
        static $seq = 0;
        $seq = ($seq + 1) & self::MASK_SEQ;

        return $seq;
    }

    protected static function genPid(): int
    {
        static $pid = null;
        if (is_null($pid)) {
            $pid = getmypid() & self::MASK_PID;
        }

        return $pid;
    }

    protected static function genServer(): int
    {
        if (empty(self::$defaultServer)) {
            /**
             * it is recommended to add SERVER_ID=[0-65535] in /etc/environment
             */
            $sid = getenv('SERVER_ID');
            if ($sid !== false && is_numeric($sid)) {
                self::$defaultServer = intval($sid) & self::MASK_SERVER;
            } elseif (file_exists('/etc/machine-id')) {
                self::$defaultServer = crc32(file_get_contents('/etc/machine-id')) & self::MASK_SERVER;
            } else {
                self::$defaultServer = crc32(php_uname('a')) & self::MASK_SERVER;
            }
        }

        return self::$defaultServer;
    }

    /**
     * Create from base64 encoded string
     *
     * @param string $str 16 chars
     * @return static
     */
    public static function fromBase64(string $str): self
    {
        return static::fromBinary(base64_decode($str));
    }

    /**
     * Create from binary string
     *
     * @param string $str 12 chars
     * @return static
     */
    public static function fromBinary(string $str): self
    {
        $str = bin2hex($str);
        $str = str_pad($str, 24, '0', STR_PAD_LEFT);
        $ts = substr($str, 0, 8);
        $ms = substr($str, 8, 5);
        $seqPid = substr($str, 13, 7);
        $server = substr($str, 20, 4);

        return static::fromString($ts . '-' . $ms . '-' . $seqPid . '-' . $server);
    }

    /**
     * Create from human readable string
     *
     * @param string $str 27 chard
     * @return static
     */
    public static function fromString(string $str): self
    {

        $res = sscanf($str, self::FORMAT, $tsH, $tsL, $ms, $seqPid, $server);

        if ($res != 5) {
            return new static(0, 0, 0, 0, 0, 0);
        }

        $seq = ($seqPid >> 22) & self::MASK_SEQ;
        $pid = $seqPid & self::MASK_PID;

        return new static($tsH, $tsL, $ms, $seq, $pid, $server);
    }

    /**
     * Create from standart UUID
     *
     * @param Uuid $uuid
     * @return static
     */
    public static function fromUuid(Uuid $uuid): self
    {

        list($ms, $ts) = explode(' ', $uuid->getMicrotime());
        $hexTs = base_convert($ts, 10, 16);
        $hexTs = str_pad($hexTs, 8, '0', STR_PAD_LEFT);

        return new static(
            hexdec(substr($hexTs, 0, 4)),
            hexdec(substr($hexTs, 4, 8)),
            intval(substr($ms, 2, 6)),
            $uuid->getSeq() & self::MASK_SEQ,
            ($uuid->getNode() >> 26) & self::MASK_PID,
            $uuid->getNode() & self::MASK_SERVER
        );
    }


    /**
     * Check string to be correct MicroUuid
     *
     * @param string $str
     * @return bool
     */
    public static function validateString(string $str): bool
    {
        return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{5}-[0-9a-f]{7}-[0-9a-f]{4}$/i', $str);
    }

    public function getSeq(): int
    {
        return $this->seq;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getServer(): int
    {
        return $this->server;
    }

    /**
     * Extract time from MicroUuid
     *
     * @return DateTime
     */
    public function getTime(): DateTime
    {
        /** @see ::isValid() */
        $seconds = base_convert(sprintf('%04x%04x', $this->secondsHigh, $this->secondsLow), 16, 10);

        $timestamp = sprintf('%s.%06d', $seconds, min(999999, $this->micros));

        return DateTime::createFromFormat('U.u', $timestamp);
    }

    /**
     * Both valid and not null
     *
     * @return bool
     */
    public function isOk(): bool
    {
        return !$this->isNull() && $this->isValid();
    }

    /**
     * Check MicroUuid is equal to reserved value for NULL
     *
     * @return bool
     */
    public function isNull(): bool
    {
        return $this->toString() === self::NULL;
    }

    /**
     * Hex MicroUuid presentation
     * "01234567-89abc-def0123-4567"
     *
     * @return string 27 chars
     */
    public function toString(): string
    {
        return sprintf(static::FORMAT,
            $this->secondsHigh,
            $this->secondsLow,
            $this->micros,
            (($this->seq << 22) | $this->pid),
            $this->server
        );
    }

    /**
     * Is MicroUuid valid
     * if randomly generated there is ~5% chance to obtain not valid microsecond
     * can`t be generated and could only be in created by ::from*($str) methods
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if ($this->micros > 999999) {
            return false;
        }

        return true;
    }

    /**
     * Base64 encoded binary MicroUuid
     * "YF+MLyVXwECNZ0PA"
     *
     * @return string 16 chars
     */
    public function toBase64(): string
    {
        return base64_encode($this->toBinary());
    }

    /**
     * Binary MicroUuid
     *
     * @return string 12 chars
     */
    public function toBinary(): string
    {
        return hex2bin(str_replace('-', '', $this->toString()));
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function jsonSerialize()
    {
        return $this->toString();
    }
}
