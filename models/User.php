<?php

declare(strict_types=1);

final class User
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, email, password FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }
}
