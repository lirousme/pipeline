<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$basePath = $basePath === '.' ? '' : $basePath;
$homeUrl = $basePath === '' ? '/' : $basePath . '/';
$apiUrl = $basePath . '/pipeline_api.php';

$loggedUser = $_SESSION['auth_user'] ?? null;
$userId = (int) ($_SESSION['auth_user_id'] ?? 0);
$problemId = (int) ($_GET['problem_id'] ?? 0);

if (!$loggedUser || $userId <= 0) {
    header('Location: ' . $homeUrl);
    exit;
}

if ($problemId <= 0) {
    header('Location: ' . $homeUrl);
    exit;
}
?>
<!doctype html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pipeline do Problema #<?= htmlspecialchars((string) $problemId) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
<header class="border-b border-white/10 p-4 flex justify-between items-center">
    <div>
        <strong>Pipeline de <?= htmlspecialchars((string) $loggedUser) ?></strong>
        <p class="text-sm text-slate-300">Problema raiz #<?= htmlspecialchars((string) $problemId) ?></p>
    </div>
    <div class="flex gap-2">
        <a href="<?= htmlspecialchars($homeUrl) ?>" class="bg-slate-700 px-3 py-1 rounded">Voltar</a>
    </div>
</header>

<main class="p-4">
    <section>
        <div class="flex justify-between items-center mb-3">
            <h2 class="font-semibold">Fluxograma condicional (vertical)</h2>
            <button id="openConditionalModal" class="bg-indigo-600 rounded px-3 py-1 text-sm">+ Condicional neste problema</button>
        </div>
        <div id="pipelineView" class="space-y-3"></div>
    </section>
</main>

<div id="conditionalModal" class="hidden fixed inset-0 bg-black/70 items-center justify-center">
    <div class="bg-slate-900 p-4 rounded w-full max-w-lg border border-white/10">
        <h3 class="mb-3 text-lg font-semibold">Adicionar condicional</h3>
        <div class="space-y-3">
            <input id="nextProblemSearch" class="w-full bg-slate-800 rounded p-2" placeholder="Busque um problema existente ou digite para criar um novo">
            <ul id="searchSuggestions" class="mt-2 space-y-1"></ul>
            <p id="selectedProblemInfo" class="text-sm text-emerald-300 hidden"></p>
            <button id="saveConditional" class="bg-indigo-600 rounded px-3 py-1">Salvar condicional</button>
        </div>
    </div>
</div>

<script>
const api = <?= json_encode($apiUrl) ?>;
const rootProblemId = <?= json_encode($problemId) ?>;

const pipelineView = document.getElementById('pipelineView');
const conditionalModal = document.getElementById('conditionalModal');
const nextProblemSearch = document.getElementById('nextProblemSearch');
const searchSuggestions = document.getElementById('searchSuggestions');
const saveConditional = document.getElementById('saveConditional');
const selectedProblemInfo = document.getElementById('selectedProblemInfo');
const openConditionalModal = document.getElementById('openConditionalModal');

let selectedProblem = null;

openConditionalModal.onclick = () => conditionalModal.classList.remove('hidden');
conditionalModal.onclick = (e) => { if (e.target === conditionalModal) closeConditionalModal(); };

function closeConditionalModal() {
    conditionalModal.classList.add('hidden');
    nextProblemSearch.value = '';
    searchSuggestions.innerHTML = '';
    selectedProblem = null;
    selectedProblemInfo.classList.add('hidden');
}

function selectProblem(problem) {
    selectedProblem = problem;
    selectedProblemInfo.textContent = `Usando problema existente: #${problem.id} ${problem.text}`;
    selectedProblemInfo.classList.remove('hidden');
    nextProblemSearch.value = problem.text;
    searchSuggestions.innerHTML = '';
}

nextProblemSearch.oninput = async () => {
    const q = nextProblemSearch.value.trim();
    selectedProblem = null;
    selectedProblemInfo.classList.add('hidden');

    if (q.length < 2) {
        searchSuggestions.innerHTML = '';
        return;
    }

    const r = await fetch(api + '?action=search-problems&q=' + encodeURIComponent(q));
    const d = await r.json();
    searchSuggestions.innerHTML = '';

    (d.items || []).forEach(item => {
        const li = document.createElement('li');
        li.className = 'bg-slate-800 border border-white/10 rounded px-2 py-1 text-sm cursor-pointer hover:bg-slate-700';
        li.textContent = `#${item.id} ${item.text}`;
        li.onclick = () => selectProblem(item);
        searchSuggestions.appendChild(li);
    });
};

saveConditional.onclick = async () => {
    const searchValue = nextProblemSearch.value.trim();
    if (searchValue === '') return;

    const payload = { id_father_problem: rootProblemId, text: searchValue };
    if (selectedProblem) {
        payload.id_next_problem = Number(selectedProblem.id);
    } else {
        payload.next_problem_text = searchValue;
    }

    const r = await fetch(api + '?action=add-conditional', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    if (r.ok) {
        closeConditionalModal();
        loadPipeline(rootProblemId);
    }
};

async function loadPipeline(id) {
    const r = await fetch(api + '?action=pipeline&problem_id=' + id);
    if (!r.ok) {
        pipelineView.innerHTML = '<p class="text-red-300">Não foi possível carregar o pipeline.</p>';
        return;
    }

    const d = await r.json();
    pipelineView.innerHTML = '';

    (d.nodes || []).forEach((n) => {
        const card = document.createElement('div');
        card.className = 'relative pl-6 space-y-2';

        const line = document.createElement('div');
        line.className = 'absolute left-2 top-0 bottom-0 w-px bg-slate-700';

        const problemCard = document.createElement('div');
        problemCard.className = 'bg-slate-900 border border-white/10 rounded p-3';
        const problemText = document.createElement('div');
        problemText.textContent = n.problem.text || '';
        problemCard.appendChild(problemText);

        const conditionalsCard = document.createElement('div');
        conditionalsCard.className = 'bg-slate-900 border border-white/10 rounded p-3';
        const conditionalsGrid = document.createElement('div');
        conditionalsGrid.className = 'grid grid-cols-1 md:grid-cols-3 gap-2';

        if ((n.conditionals || []).length > 0) {
            n.conditionals.forEach((c) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'bg-slate-800 border border-white/10 rounded px-3 py-2 text-sm text-left hover:bg-slate-700 transition';
                button.textContent = `Se "${c.text}" → abre #${c.id_next_problem}`;
                conditionalsGrid.appendChild(button);
            });
        } else {
            const emptyText = document.createElement('p');
            emptyText.className = 'text-sm text-slate-400';
            emptyText.textContent = 'Sem condicionais para este problema.';
            conditionalsGrid.appendChild(emptyText);
        }

        conditionalsCard.appendChild(conditionalsGrid);
        card.append(line, problemCard, conditionalsCard);
        pipelineView.appendChild(card);
    });
};

loadPipeline(rootProblemId);
</script>
</body>
</html>
