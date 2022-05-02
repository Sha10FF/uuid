<?php

namespace UUID;

use DateTime;
use JsonSerializable;

class NanoUuid implements JsonSerializable
{
    protected const MASK_SEQ = 0x3ff;      //  10bit
    protected const MASK_PID = 0x3fffff;  // 22bit
    protected const FORMAT = '%08x-%08x';
    public const NULL = '00000000-00000000';

    /** @var int 32bit */
    protected $ts;

    /** @var int 10bit */
    protected $seq;

    /** @var int 22bit */
    protected $pid;

    public static function get(?DateTime $time = null): self
    {

        if (is_null($time)) {
            $ts = time();
        } else {
            $ts = (int)$time->format('U');
        }

        return new static(
            $ts,
            static::genSeq(),
            static::genPid()
        );
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
     * @param string $str 8 chars
     * @return static
     */
    public static function fromBinary(string $str): self
    {
        $str = str_pad(bin2hex($str), 16, '0', STR_PAD_LEFT);
        $ts = substr($str, 0, 8);
        $seqPid = substr($str, 8, 8);

        return static::fromString($ts . '-' . $seqPid);
    }

    /**
     * Create from human readable string
     *
     * @param string $str 17 chars
     * @return static
     */
    public static function fromString(string $str): self
    {

        $res = sscanf($str, self::FORMAT, $ts, $seqPid);

        if ($res != 2) {
            return new static(0, 0, 0);
        }

        $seq = ($seqPid >> 22) & self::MASK_SEQ;
        $pid = $seqPid & self::MASK_PID;

        return new static($ts, $seq, $pid);
    }

    /**
     * Check string to be correct
     *
     * @param string $str
     * @return bool
     */
    public static function validateString(string $str): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}\-[0-9a-f]{8}$/i', $str);
    }

    protected function __construct(
        int $ts,
        int $seq,
        int $pid
    ) {
        $this->ts = $ts;
        $this->seq = $seq;
        $this->pid = $pid;
    }

    public function getSeq(): int
    {
        return $this->seq;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * Extract time from MicroUuid
     *
     * @return DateTime
     */
    public function getTime(): DateTime
    {
        return DateTime::createFromFormat('U', $this->ts);
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
     * Hex NanoUuid presentation
     * "01234567-89abcdef0"
     *
     * @return string 17 chars
     */
    public function toString(): string
    {
        return sprintf(static::FORMAT,
            $this->ts,
            (($this->seq << 22) | $this->pid)
        );
    }

    /**
     * Base64 encoded binary MicroUuid
     * "YF+MLyVXwECNZ0PA"
     *
     * @return string 11 chars
     */
    public function toBase64(): string
    {
        return rtrim(base64_encode($this->toBinary()), '=');
    }

    /**
     * Binary MicroUuid
     *
     * @return string 8 chars
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
}