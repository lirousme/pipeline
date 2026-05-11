<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use PDO;

final class MySQLPipelineRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createProblem(int $userId, string $text, int $home = 1): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO problems (user_id, text, home) VALUES (:user_id, :text, :home)');
        $stmt->execute(['user_id' => $userId, 'text' => $text, 'home' => $home]);

        return (int) $this->pdo->lastInsertId();
    }

    public function listProblems(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, text FROM problems WHERE user_id = :user_id AND home = 1 ORDER BY id DESC');
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }


    public function searchProblems(int $userId, string $query, int $limit = 8): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, text FROM problems WHERE user_id = :user_id AND text LIKE :query ORDER BY id DESC LIMIT :limit'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findProblem(int $userId, int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, text, home FROM problems WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }


    public function updateProblem(int $userId, int $id, string $text, int $home): bool
    {
        $stmt = $this->pdo->prepare('UPDATE problems SET text = :text, home = :home WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['text' => $text, 'home' => $home, 'id' => $id, 'user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function deleteProblem(int $userId, int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM problems WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function addConditional(int $father, int $next, string $text): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO conditionals (id_father_problem, id_next_problem, text) VALUES (:father, :next, :text)');
        $stmt->execute(['father' => $father, 'next' => $next, 'text' => $text]);

        return (int) $this->pdo->lastInsertId();
    }

    public function getConditionalsFrom(int $problemId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, id_father_problem, id_next_problem, text FROM conditionals WHERE id_father_problem = :id ORDER BY id ASC');
        $stmt->execute(['id' => $problemId]);

        return $stmt->fetchAll();
    }

    public function updateConditional(int $userId, int $conditionalId, string $text): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE conditionals c
            INNER JOIN problems p ON p.id = c.id_father_problem
            SET c.text = :text
            WHERE c.id = :conditional_id AND p.user_id = :user_id'
        );
        $stmt->execute(['text' => $text, 'conditional_id' => $conditionalId, 'user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function deleteConditional(int $userId, int $conditionalId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE c FROM conditionals c
            INNER JOIN problems p ON p.id = c.id_father_problem
            WHERE c.id = :conditional_id AND p.user_id = :user_id'
        );
        $stmt->execute(['conditional_id' => $conditionalId, 'user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }
}
