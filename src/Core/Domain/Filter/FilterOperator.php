<?php

namespace App\Core\Domain\Filter;

interface FilterOperator
{
    public const EMPTY = '';
    public const EQUALS = 'eq';
    public const NOT_EQUALS = 'neq';
    public const LIKE = 'like';
    public const GREATER_THAN = 'gt';
    public const GREATER_OR_EQUAL_THAN = 'get';
    public const LESS_THAN = 'lt';
    public const LESS_OR_EQUAL_THAN = 'let';
    public const IN = 'in';
    public const NOT_IN = 'nin';
    public const NULL = 'null';
    public const NOT_NULL = 'notNull';
    public const AVAILABLES = [
        self::EQUALS,
        self::LIKE,
        self::GREATER_THAN,
        self::LESS_THAN,
        self::NULL,
        self::IN,
        self::NOT_NULL,
        self::NOT_EQUALS,
        self::EMPTY,
        self::GREATER_OR_EQUAL_THAN,
	self::LESS_OR_EQUAL_THAN,
	self::NOT_IN
    ];
}
