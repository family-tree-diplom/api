<?php

namespace OpenCCK\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\Annotated\Annotation\Relation\HasOne;
use Cycle\Annotated\Annotation\Table\Index;
use OpenCCK\Domain\Repository\UserRepository;
use OpenCCK\Message;
use function OpenCCK\getEnv;

use Exception;

#[Entity(repository: UserRepository::class, table: UserRepository::TABLE)]
#[Index(columns: ['username'], unique: true)]
#[Index(columns: ['email'], unique: true)]
final class User implements EntityInterface {
    public function __construct(
        #[Column(type: 'primary')] public int $id,
        #[Column(type: 'enum(active,disabled)', default: 'active')] private string $state,
        #[Column(type: 'string(255)', nullable: true)] public string $username,
        #[Column(type: 'string(255)', default: '')] private string $password,
        #[Column(type: 'string(16)', default: '')] private string $sold,
        #[Column(type: 'string(255)', nullable: true)] public string $email,
        #[Column(type: 'enum(user,manager,admin,superadmin)', default: 'user')] public string $role,
        #[Column(type: 'string(255)', default: '')] public string $name,
        #[
            Column(type: 'datetime', name: 'date_create', default: 'CURRENT_TIMESTAMP')
        ]
        private ?\DateTimeImmutable $date_create,
        #[
            Column(type: 'datetime', name: 'date_modify', default: 'CURRENT_TIMESTAMP')
        ]
        private ?\DateTimeImmutable $date_modify
    ) {
    }

    public function getPrimaryKey(): array {
        return ['id' => $this->getId()];
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'state' => $this->state,
            'username' => $this->username ?: null,
            'password' => $this->password,
            'sold' => $this->sold,
            'email' => $this->email ?: null,
            'role' => $this->role,
            'name' => $this->name,
            'date_create' => date_format($this->date_create, 'Y-m-d H:i:s'),
            'date_modify' => date_format($this->date_modify, 'Y-m-d H:i:s'),
        ];
    }

    public function toObject(): object {
        return (object) [
            'id' => $this->id,
            'state' => $this->state,
            'username' => $this->username ?: null,
            'email' => $this->email ?: null,
            'role' => $this->role,
            'name' => $this->name,
            'date_create' => $this->date_create->getTimestamp(),
        ];
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    public function setId(int $id): self {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string {
        return $this->username;
    }

    /**
     * @param string $username
     * @return User
     * @throws Exception
     */
    public function setUsername(string $username): self {
        if (\strlen($username) < 3) {
            throw new Exception(\sprintf(Message::USER_USERNAME_SHORT, '3'));
        }
        if (preg_match('/^[a-zA-Z0-9]+$/', $username) !== 1) {
            throw new Exception(Message::USER_USERNAME_FORMAT);
        }

        $this->username = $username;
        return $this;
    }

    /**
     * @param string $state
     * @return User
     */
    public function setState(string $state): self {
        $this->state = $state;
        return $this;
    }

    /**
     * Set password base64 sha256 hash
     * @param string $password
     * @return User
     * @throws Exception
     */
    public function setPassword(string $password): self {
        if (\strlen($password) < 6) {
            throw new Exception(\sprintf(Message::USER_PASSWORD_SHORT, '6'));
        }

        $this->sold = \substr(\base64_encode(\random_bytes(32)), 0, 16);
        $this->password = \base64_encode(
            \hash_hmac('sha256', $this->sold . '.' . $password, getEnv('SYS_SECRET') ?? '', true)
        );
        return $this;
    }

    /**
     * @param string $password
     * @return bool
     */
    public function checkPassword(string $password): bool {
        return $this->password ==
            base64_encode(hash_hmac('sha256', $this->sold . '.' . $password, getEnv('SYS_SECRET'), true));
    }

    public function setEmail(string $email): self {
        $this->email = $email;
        return $this;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function setRole(string $role): self {
        $this->role = $role;
        return $this;
    }

    public function getRole(): string {
        return $this->role;
    }

    public function getDateModify(): ?\DateTimeImmutable {
        return $this->date_modify;
    }

    public function setDateModify(\DateTimeImmutable $date_modify): self {
        $this->date_modify = $date_modify;
        return $this;
    }
}
