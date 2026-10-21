<?php

namespace App\Core\Database;

/**
 * RawSql class 
 * Automatic database driver detection (MySQL/PostgreSQL)
 * @author Lutvi <lutvip19@gmail.com>
 * @package: Backend-PHP
 */
class RawSql
{
    protected string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}