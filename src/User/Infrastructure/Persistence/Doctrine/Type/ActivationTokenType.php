<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\User\Domain\ValueObject\ActivationToken;

/**
 * @extends AbstractStringValueObjectType<ActivationToken>
 */
final class ActivationTokenType extends AbstractStringValueObjectType
{
    public const string NAME = 'activation_token';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return ActivationToken::class;
    }
}
