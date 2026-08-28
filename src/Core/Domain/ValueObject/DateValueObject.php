<?php

declare(strict_types=1);

namespace App\Core\Domain\ValueObject;

abstract class DateValueObject
{
    public const DATE_LABEL_FORMAT = 'd/m/Y';
    public const DATETIME_DEFAULT_FORMAT = 'Y-m-d H:i:s';
    public const DDMMYYYYHIS_LABEL_FORMAT = 'd/m/Y H:i:s';

    protected \DateTimeInterface $value;

    /**
     * @param \DateTimeInterface $value
     */
    public function __construct(\DateTimeInterface $value)
    {
        $this->value = $value;
    }

    /**
     * @return \DateTimeInterface
     */
    public function value(): \DateTimeInterface
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value()->format('Y-m-d H:i:s');
    }

    /**
     * @return string
     */
    public function toDateTimeString(): string
    {
        return $this->value()->format('d/m/Y H:i:s');
    }

    /**
     * @return string
     */
    public function toDateString(): string
    {
        return $this->value()->format('d/m/Y');
    }
}
