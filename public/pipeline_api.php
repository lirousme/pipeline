<?php

declare(strict_types=1);

use App\Infrastructure\Database\Connection;
use App\Infrastructure\Repository\MySQLPipelineRepository;

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (int) ($_SESSION['auth_user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['message' => 'Não autenticado']);
    exit;
}

$repo = new MySQLPipelineRepository(Connection::make());
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = (string) ($_GET['action'] ?? 'list-problems');
$body = json_decode(file_get_contents('php://input') ?: '{}', true);
$body = is_array($body) ? $body : [];

if ($method === 'GET' && $action === 'list-problems') {
    echo json_encode(['items' => $repo->listProblems($userId)], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'GET' && $action === 'pipeline') {
    $rootId = (int) ($_GET['problem_id'] ?? 0);
    $root = $repo->findProblem($userId, $rootId);
    if (!$root) {
        http_response_code(404);
        echo json_encode(['message' => 'Problema não encontrado']);
        exit;
    }

    $queue = [$root];
    $seen = [];
    $tree = [];

    while ($queue) {
        $current = array_shift($queue);
        $currentId = (int) $current['id'];
        if (isset($seen[$currentId])) {
            continue;
        }
        $seen[$currentId] = true;

        $conds = $repo->getConditionalsFrom($currentId);
        $tree[] = ['problem' => $current, 'conditionals' => $conds];

        foreach ($conds as $cond) {
            $next = $repo->findProblem($userId, (int) $cond['id_next_problem']);
            if ($next && !isset($seen[(int) $next['id']])) {
                $queue[] = $next;
            }
        }
    }

    echo json_encode(['nodes' => $tree], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST' && $action === 'create-problem') {
    $text = trim((string) ($body['text'] ?? ''));
    if ($text === '') {
        http_response_code(422);
        echo json_encode(['message' => 'Texto é obrigatório']);
        exit;
    }

    $id = $repo->createProblem($userId, $text);
    echo json_encode(['id' => $id, 'message' => 'Problema criado']);
    exit;
}

if ($method === 'POST' && $action === 'add-conditional') {
    $father = (int) ($body['id_father_problem'] ?? 0);
    $next = (int) ($body['id_next_problem'] ?? 0);
    $text = trim((string) ($body['text'] ?? ''));

    if (!$repo->findProblem($userId, $father) || !$repo->findProblem($userId, $next) || $text === '') {
        http_response_code(422);
        echo json_encode(['message' => 'Dados inválidos']);
        exit;
    }

    $id = $repo->addConditional($father, $next, $text);
    echo json_encode(['id' => $id, 'message' => 'Condicional criada']);
    exit;
}

http_response_code(404);
echo json_encode(['message' => 'Rota inválida']);
