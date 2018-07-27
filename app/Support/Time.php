<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class Time
{
    /**
     * @var int
     */
    protected $hours;

    /**
     * @var int
     */
    protected $minutes;

    /**
     * @var int
     */
    protected $seconds;

    /**
     * Time constructor.
     *
     * @param string $time
     */
    public function __construct(string $time)
    {
        list($hours, $minutes, $seconds) = explode(':', $time);

        $this->hours = (int)$hours;
        $this->minutes = (int)$minutes;
        $this->seconds = (int)$seconds;
    }

    /**
     * @param string $time
     * @return \App\Support\Time
     */
    public static function create(string $time)
    {
        return new static($time);
    }

    /**
     * @param string $format
     * @param string $time
     * @return \App\Support\Time
     */
    public static function createFromFormat(string $format, string $time): self
    {
        $carbon = Carbon::createFromFormat($format, $time);

        return new static($carbon->format('H:i:s'));
    }

    /**
     * @param string $format
     * @return string
     */
    public function format(string $format): string
    {
        $carbon = Carbon::createFromFormat('H:i:s', $this->toString());

        return $carbon->format($format);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return sprintf('%02d:%02d:%02d', $this->hours, $this->minutes, $this->seconds);
    }
}