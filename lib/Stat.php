<?php

namespace Slim;

class Stat
{
    const STAT_FILE = "storage/stats.txt";
    private $stats;

    /**
     * Stat constructor.
     */
    public function __construct()
    {
        $this->stats = [];
    }

    public function loadStats()
    {
        $this->stats = file(self::STAT_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    public function isDuplicated($value)
    {
        $value = (string)$value;
        return in_array($value, $this->stats);
    }

    public function addStats($value)
    {
        $this->stats[] = $value;
        file_put_contents(static::STAT_FILE, $value . "\n", FILE_APPEND);
    }
}

