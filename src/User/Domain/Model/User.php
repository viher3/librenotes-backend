<?php

declare(strict_types=1);

namespace App\User\Domain\Model;

use App\Core\Domain\Aggregate\AggregateRoot;
use App\Core\Domain\Bus\Event\EventId;
use App\Core\Domain\ValueObject\BoolValueObject;
use App\Core\Domain\ValueObject\ContactData\EmailValueObject;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Shared\Domain\Time\Date;
use App\User\Domain\Event\UserCreated;
use App\User\Domain\Event\UserPasswordChanged;
use App\User\Domain\Specification\UniqueEmailSpecificationInterface;
use App\User\Domain\ValueObject\ActivationToken;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\PasswordRecoveryToken;
use App\User\Domain\ValueObject\TokenVersion;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserLocale;
use App\User\Domain\ValueObject\Username;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User extends AggregateRoot implements PasswordAuthenticatedUserInterface, UserInterface
{
    public const MAX_TOKEN_VALID_TIME_IN_HOURS = 2;
    public const MAX_ACTIVATION_TOKEN_VALID_TIME_IN_HOURS = 48;

    private const VALID_PARTIAL_UPDATE_FIELDS = [
        'username',
        'email',
        'active',
        'emailValidated',
        'locale',
    ];

    private UserId $id;
    private HashedPassword $password;
    protected ?PasswordRecoveryToken $passwordRecoveryToken = null;
    protected ?DateTimeValueObject $passwordRecoveryRequestedAt = null;
    protected ?ActivationToken $activationToken = null;
    protected ?DateTimeValueObject $activationTokenRequestedAt = null;
    /** @var string[] Roles granted via UserRoleAssignment, resolved and set by the security layer per request — never persisted on this entity. */
    private array $roleNames = [];
    protected EmailValueObject $email;
    protected BoolValueObject $emailValidated;
    protected Username $username;
    protected UserLocale $locale;
    protected DateTimeValueObject $createdAt;
    protected BoolValueObject $active;
    protected ?DateTimeValueObject $lastLoginAt = null;
    private ?DateTimeValueObject $deletedAt = null;
    private TokenVersion $tokenVersion;

    public function __construct(
        UniqueEmailSpecificationInterface $uniqueEmailSpecification,
        UserId                            $id,
        EmailValueObject                  $email,
        \DateTimeInterface                $createdAt,
        bool                               $active = false,
        ?string                            $username = null,
        ?\DateTimeInterface                $lastLoginAt = null
    )
    {
        $this->id = $id;
        $this->email = $email;
        $this->username = new Username($username ?? explode("@", $this->email->value())[0]);
        $this->createdAt = new DateTimeValueObject($createdAt);
        $this->active = new BoolValueObject($active);
        $this->emailValidated = new BoolValueObject(false);
        $this->locale = UserLocale::default();
        $this->tokenVersion = new TokenVersion(1);
        $this->lastLoginAt = $lastLoginAt !== null ? new DateTimeValueObject($lastLoginAt) : null;

        $uniqueEmailSpecification->isUnique($email);

        $this->record(
            new UserCreated(
                id: $this->getId()->value(),
                username: $this->getUsername()->value(),
                email: $this->getEmail()->value(),
                active: $this->isActive(),
                emailValidated: $this->isEmailValidated(),
                eventId: EventId::random()->value(),
                occurredOn: $createdAt->format(Date::DATE_DEFAULT_RENDER_FORMAT),
            )
        );
    }

    public function update(
        array $fields,
        UniqueEmailSpecificationInterface $uniqueEmailSpecification,
    ): void
    {
        if (!empty($fields['email']) && $fields['email'] !== $this->email->value()) {
            $uniqueEmailSpecification->isUnique(new EmailValueObject($fields['email']));
        }

        $this->partialUpdate(self::VALID_PARTIAL_UPDATE_FIELDS, $fields);
    }

    protected function castPartialUpdateValue(string $field, mixed $value): mixed
    {
        return match ($field) {
            'username' => new Username($value),
            'email' => new EmailValueObject($value),
            'active', 'emailValidated' => new BoolValueObject($value),
            'locale' => new UserLocale($value),
            default => $value,
        };
    }

    public function changePassword(HashedPassword $password): void
    {
        $this->password = $password;
        $this->passwordRecoveryRequestedAt = null;
    }

    /**
     * Replaces an already established password: revokes the sessions issued so far (token version) and
     * records UserPasswordChanged. Setting the initial password (signup, admin creation) uses changePassword().
     */
    public function updatePassword(HashedPassword $password, \DateTimeInterface $now): void
    {
        $this->changePassword($password);
        $this->increaseTokenVersion();

        $this->record(
            new UserPasswordChanged(
                id: $this->getId()->value(),
                email: $this->getEmail()->value(),
                locale: $this->getLocale()->value(),
                eventId: EventId::random()->value(),
                occurredOn: $now->format(Date::DATE_DEFAULT_RENDER_FORMAT),
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function isPasswordRecoveryTokenValid(
        string             $token,
        \DateTimeInterface $now
    ): bool
    {
        // check if it is the same token as db
        if ($this->passwordRecoveryToken === null || $this->passwordRecoveryToken->value() !== $token) {
            return false;
        }

        if ($this->passwordRecoveryRequestedAt === null) {
            return false;
        }

        // check expiration time
        $maxTimeForPasswordRecovery = \DateTimeImmutable::createFromInterface($this->passwordRecoveryRequestedAt->value())
            ->add(new \DateInterval('PT' . self::MAX_TOKEN_VALID_TIME_IN_HOURS . 'H'));

        if ($now > $maxTimeForPasswordRecovery) {
            return false;
        }

        return true;
    }

    public function isEmailAvailable(UniqueEmailSpecificationInterface $uniqueEmailSpecification): void
    {
        $uniqueEmailSpecification->isUnique($this->email);
    }

    public function activate(): self
    {
        $this->active = new BoolValueObject(true);
        $this->emailValidated = new BoolValueObject(true);
        $this->activationToken = null;
        $this->activationTokenRequestedAt = null;
        return $this;
    }

    /**
     * Generates and stores a new activation token, returning the raw value so the
     * caller can put it in the activation email — only the token itself is persisted.
     */
    public function requestEmailActivation(\DateTimeInterface $now): string
    {
        $rawToken = bin2hex(random_bytes(32));

        $this->activationToken = new ActivationToken($rawToken);
        $this->activationTokenRequestedAt = new DateTimeValueObject($now);

        return $rawToken;
    }

    public function isActivationTokenValid(\DateTimeInterface $now): bool
    {
        if ($this->activationToken === null || $this->activationTokenRequestedAt === null) {
            return false;
        }

        $maxTimeForActivation = \DateTimeImmutable::createFromInterface($this->activationTokenRequestedAt->value())
            ->add(new \DateInterval('PT' . self::MAX_ACTIVATION_TOKEN_VALID_TIME_IN_HOURS . 'H'));

        return $now <= $maxTimeForActivation;
    }

    public function deactivate(): self
    {
        $this->active = new BoolValueObject(false);
        return $this;
    }

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->id->value();
    }

    public function getRoles(): array
    {
        return array_values(array_unique(array_merge(['ROLE_USER'], $this->roleNames)));
    }

    /**
     * @param string[] $roleNames
     */
    public function grantRoles(array $roleNames): void
    {
        $this->roleNames = $roleNames;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUsername(): Username
    {
        return $this->username;
    }

    public function getLocale(): UserLocale
    {
        return $this->locale;
    }

    public function getEmail(): EmailValueObject
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password->value();
    }

    public function requestPasswordRecovery(\DateTimeInterface $requestTime): void
    {
        $this->passwordRecoveryToken = new PasswordRecoveryToken(uniqid('pwd', false) . rand(0, 99999));
        $this->passwordRecoveryRequestedAt = new DateTimeValueObject($requestTime);
    }

    public function getPasswordRecoveryToken(): ?PasswordRecoveryToken
    {
        return $this->passwordRecoveryToken;
    }

    public function getPasswordRecoveryRequestedAt(): ?DateTimeValueObject
    {
        return $this->passwordRecoveryRequestedAt;
    }

    public function getActivationToken(): ?ActivationToken
    {
        return $this->activationToken;
    }

    public function getActivationTokenRequestedAt(): ?DateTimeValueObject
    {
        return $this->activationTokenRequestedAt;
    }

    public function getCreatedAt(): DateTimeValueObject
    {
        return $this->createdAt;
    }

    public function loggedAt(\DateTimeInterface $clock): void
    {
        $this->lastLoginAt = new DateTimeValueObject($clock);
    }

    public function deletedAt(\DateTimeInterface $clock): void
    {
        $this->deletedAt = new DateTimeValueObject($clock);
    }

    public function getLastLoginAt(): ?DateTimeValueObject
    {
        return $this->lastLoginAt;
    }

    public function isEmailValidated(): bool
    {
        return $this->emailValidated->value();
    }

    public function markEmailAsValidated(): void
    {
        $this->emailValidated = new BoolValueObject(true);
    }

    public function isActive(): bool
    {
        return $this->active->value();
    }

    public function getDeletedAt(): ?DateTimeValueObject
    {
        return $this->deletedAt;
    }

    public function getTokenVersion(): TokenVersion
    {
        return $this->tokenVersion;
    }

    public function increaseTokenVersion(): void
    {
        $this->tokenVersion = new TokenVersion($this->tokenVersion->value() + 1);
    }
}
