<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use PDO;

final class MySQLPipelineRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createProblem(int $userId, string $text): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO problems (user_id, text) VALUES (:user_id, :text)');
        $stmt->execute(['user_id' => $userId, 'text' => $text]);

        return (int) $this->pdo->lastInsertId();
    }

    public function listProblems(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, text, expansions, proxima_expansion FROM problems WHERE user_id = :user_id ORDER BY id DESC');
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function listDueProblems(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, text, expansions, proxima_expansion FROM problems WHERE user_id = :user_id AND proxima_expansion IS NOT NULL AND proxima_expansion <= UTC_TIMESTAMP() ORDER BY proxima_expansion ASC, id ASC');
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function updateExpansion(int $userId, int $problemId, int $expansions): ?array
    {
        $stmt = $this->pdo->prepare('UPDATE problems SET expansions = :expansions, proxima_expansion = DATE_ADD(COALESCE(proxima_expansion, UTC_TIMESTAMP()), INTERVAL :expansions DAY) WHERE id = :id AND user_id = :user_id');
        $stmt->bindValue(':expansions', $expansions, PDO::PARAM_INT);
        $stmt->bindValue(':id', $problemId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return null;
        }

        return $this->findProblem($userId, $problemId);
    }

    public function searchProblems(int $userId, string $query, int $limit = 8): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, text, expansions, proxima_expansion FROM problems WHERE user_id = :user_id AND text LIKE :query ORDER BY id DESC LIMIT :limit'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findProblem(int $userId, int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, text, expansions, proxima_expansion FROM problems WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
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
}
