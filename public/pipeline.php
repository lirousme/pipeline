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
        <p id="currentProblemLabel" class="text-sm text-slate-300"></p>
    </div>
    <div class="flex gap-2">
        <button id="backProblem" class="bg-slate-700 px-3 py-1 rounded disabled:opacity-50" disabled>Voltar problema</button>
        <a href="<?= htmlspecialchars($homeUrl) ?>" class="bg-slate-700 px-3 py-1 rounded">Voltar início</a>
    </div>
</header>

<main class="p-4">
    <section>
        <div class="flex justify-between items-center mb-3">
            <h2 class="font-semibold">Fluxograma condicional (um problema por vez)</h2>
            <button id="openConditionalModal" class="bg-indigo-600 rounded px-3 py-1 text-sm">+ Condicional neste problema</button>
        </div>
        <div id="pipelineView"></div>
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


<div id="problemActionsModal" class="hidden fixed inset-0 bg-black/70 items-center justify-center">
    <div class="bg-slate-900 p-4 rounded w-full max-w-lg border border-white/10">
        <h3 class="mb-3 text-lg font-semibold">Configurar problema</h3>
        <div class="space-y-3">
            <textarea id="problemTextEdit" class="w-full bg-slate-800 rounded p-2 min-h-28" placeholder="Texto do problema"></textarea>
            <div>
                <label for="problemHomeEdit" class="block text-sm mb-1">Exibir na home</label>
                <select id="problemHomeEdit" class="w-full bg-slate-800 rounded p-2">
                    <option value="1">Mostrar na home</option>
                    <option value="2">Não mostrar na home</option>
                </select>
            </div>
            <div class="flex justify-between gap-2">
                <button id="deleteProblemBtn" class="bg-red-700 rounded px-3 py-1 text-sm">Excluir problema</button>
                <div class="flex gap-2">
                    <button id="cancelProblemEdit" class="bg-slate-700 rounded px-3 py-1 text-sm">Cancelar</button>
                    <button id="saveProblemEdit" class="bg-indigo-600 rounded px-3 py-1 text-sm">Salvar alterações</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="conditionalActionsModal" class="hidden fixed inset-0 bg-black/70 items-center justify-center">
    <div class="bg-slate-900 p-4 rounded w-full max-w-lg border border-white/10">
        <h3 class="mb-3 text-lg font-semibold">Configurar condicional</h3>
        <div class="space-y-3">
            <textarea id="conditionalTextEdit" class="w-full bg-slate-800 rounded p-2 min-h-24" placeholder="Texto da condicional"></textarea>
            <div class="flex justify-between gap-2">
                <button id="deleteConditionalBtn" class="bg-red-700 rounded px-3 py-1 text-sm">Excluir condicional</button>
                <div class="flex gap-2">
                    <button id="cancelConditionalEdit" class="bg-slate-700 rounded px-3 py-1 text-sm">Cancelar</button>
                    <button id="saveConditionalEdit" class="bg-indigo-600 rounded px-3 py-1 text-sm">Salvar alterações</button>
                </div>
            </div>
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
const backProblem = document.getElementById('backProblem');
const currentProblemLabel = document.getElementById('currentProblemLabel');
const problemActionsModal = document.getElementById('problemActionsModal');
const problemTextEdit = document.getElementById('problemTextEdit');
const saveProblemEdit = document.getElementById('saveProblemEdit');
const problemHomeEdit = document.getElementById('problemHomeEdit');
const deleteProblemBtn = document.getElementById('deleteProblemBtn');
const cancelProblemEdit = document.getElementById('cancelProblemEdit');
const conditionalActionsModal = document.getElementById('conditionalActionsModal');
const conditionalTextEdit = document.getElementById('conditionalTextEdit');
const deleteConditionalBtn = document.getElementById('deleteConditionalBtn');
const cancelConditionalEdit = document.getElementById('cancelConditionalEdit');
const saveConditionalEdit = document.getElementById('saveConditionalEdit');

let selectedProblem = null;
let currentProblemId = Number(rootProblemId);
const problemHistory = [];
let editingProblemId = null;
let editingConditionalId = null;

problemActionsModal.onclick = (e) => { if (e.target === problemActionsModal) closeProblemActionsModal(); };
cancelProblemEdit.onclick = () => closeProblemActionsModal();
conditionalActionsModal.onclick = (e) => { if (e.target === conditionalActionsModal) closeConditionalActionsModal(); };
cancelConditionalEdit.onclick = () => closeConditionalActionsModal();

function openConditionalActionsModal(conditional) {
    editingConditionalId = Number(conditional.id);
    conditionalTextEdit.value = conditional.text || '';
    conditionalActionsModal.classList.remove('hidden');
}

function closeConditionalActionsModal() {
    editingConditionalId = null;
    conditionalTextEdit.value = '';
    conditionalActionsModal.classList.add('hidden');
}

function openProblemActionsModal(problem) {
    editingProblemId = Number(problem.id);
    problemTextEdit.value = problem.text || '';
    problemHomeEdit.value = String(problem.home || 1);
    problemActionsModal.classList.remove('hidden');
}

function closeProblemActionsModal() {
    editingProblemId = null;
    problemTextEdit.value = '';
    problemHomeEdit.value = '1';
    problemActionsModal.classList.add('hidden');
}

openConditionalModal.onclick = () => conditionalModal.classList.remove('hidden');
conditionalModal.onclick = (e) => { if (e.target === conditionalModal) closeConditionalModal(); };

function closeConditionalModal() {
    conditionalModal.classList.add('hidden');
    nextProblemSearch.value = '';
    searchSuggestions.innerHTML = '';
    selectedProblem = null;
    selectedProblemInfo.classList.add('hidden');
}

function updateBackButton() {
    backProblem.disabled = problemHistory.length === 0;
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

    const payload = { id_father_problem: currentProblemId, text: searchValue };
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
        loadProblem(currentProblemId);
    }
};


saveProblemEdit.onclick = async () => {
    const text = problemTextEdit.value.trim();
    if (!editingProblemId || text === '') return;

    const r = await fetch(api + '?action=update-problem', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ problem_id: editingProblemId, text, home: Number(problemHomeEdit.value) })
    });

    if (r.ok) {
        closeProblemActionsModal();
        loadProblem(currentProblemId);
    }
};

deleteProblemBtn.onclick = async () => {
    if (!editingProblemId) return;
    if (!confirm('Tem certeza que deseja excluir este problema?')) return;

    const r = await fetch(api + '?action=delete-problem', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ problem_id: editingProblemId })
    });

    if (r.ok) {
        window.location.href = <?= json_encode($homeUrl) ?>;
    }
};

saveConditionalEdit.onclick = async () => {
    const text = conditionalTextEdit.value.trim();
    if (!editingConditionalId || text === '') return;

    const r = await fetch(api + '?action=update-conditional', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ conditional_id: editingConditionalId, text })
    });

    if (r.ok) {
        closeConditionalActionsModal();
        loadProblem(currentProblemId);
    }
};

deleteConditionalBtn.onclick = async () => {
    if (!editingConditionalId) return;
    if (!confirm('Tem certeza que deseja excluir esta condicional?')) return;

    const r = await fetch(api + '?action=delete-conditional', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ conditional_id: editingConditionalId })
    });

    if (r.ok) {
        closeConditionalActionsModal();
        loadProblem(currentProblemId);
    }
};

backProblem.onclick = () => {
    if (problemHistory.length === 0) return;
    currentProblemId = problemHistory.pop();
    updateBackButton();
    loadProblem(currentProblemId);
};

function renderProblem(problem, conditionals) {
    currentProblemLabel.textContent = `Problema atual #${problem.id}`;
    pipelineView.innerHTML = '';

    const wrapper = document.createElement('div');
    wrapper.className = 'space-y-3';

    const problemCard = document.createElement('div');
    problemCard.className = 'bg-slate-900 border border-white/10 rounded p-3';

    const problemCardHeader = document.createElement('div');
    problemCardHeader.className = 'flex justify-end';

    const configureProblemBtn = document.createElement('button');
    configureProblemBtn.type = 'button';
    configureProblemBtn.className = 'text-slate-300 hover:text-white';
    configureProblemBtn.title = 'Configurar problema';
    configureProblemBtn.innerHTML = '⚙️';
    configureProblemBtn.onclick = () => openProblemActionsModal(problem);

    const problemText = document.createElement('p');
    problemText.className = 'mt-2';
    problemText.textContent = problem.text || '';

    problemCardHeader.appendChild(configureProblemBtn);
    problemCard.append(problemCardHeader, problemText);

    const conditionalsCard = document.createElement('div');
    conditionalsCard.className = 'bg-slate-900 border border-white/10 rounded p-3';

    const conditionalsGrid = document.createElement('div');
    conditionalsGrid.className = 'grid grid-cols-1 md:grid-cols-3 gap-2';

    if ((conditionals || []).length === 0) {
        const emptyText = document.createElement('p');
        emptyText.className = 'text-sm text-slate-400';
        emptyText.textContent = 'Sem condicionais para este problema.';
        conditionalsGrid.appendChild(emptyText);
    } else {
        conditionals.forEach((c) => {
            const conditionalCard = document.createElement('div');
            conditionalCard.className = 'bg-slate-800 border border-white/10 rounded px-3 py-2 text-sm transition hover:bg-slate-700';

            const conditionalHeader = document.createElement('div');
            conditionalHeader.className = 'flex justify-end';

            const configureConditionalBtn = document.createElement('button');
            configureConditionalBtn.type = 'button';
            configureConditionalBtn.className = 'text-slate-300 hover:text-white';
            configureConditionalBtn.title = 'Configurar condicional';
            configureConditionalBtn.innerHTML = '⚙️';
            configureConditionalBtn.onclick = (e) => {
                e.stopPropagation();
                openConditionalActionsModal(c);
            };

            const conditionalText = document.createElement('p');
            conditionalText.className = 'mt-1';
            conditionalText.textContent = c.text;

            conditionalCard.onclick = () => {
                problemHistory.push(currentProblemId);
                currentProblemId = Number(c.id_next_problem);
                updateBackButton();
                loadProblem(currentProblemId);
            };

            conditionalHeader.appendChild(configureConditionalBtn);
            conditionalCard.append(conditionalHeader, conditionalText);
            conditionalsGrid.appendChild(conditionalCard);
        });
    }

    conditionalsCard.appendChild(conditionalsGrid);
    wrapper.append(problemCard, conditionalsCard);
    pipelineView.appendChild(wrapper);
}

async function loadProblem(id) {
    const r = await fetch(api + '?action=problem-detail&problem_id=' + id);
    if (!r.ok) {
        pipelineView.innerHTML = '<p class="text-red-300">Não foi possível carregar o problema.</p>';
        return;
    }

    const d = await r.json();
    renderProblem(d.problem || {}, d.conditionals || []);
}

updateBackButton();
loadProblem(rootProblemId);
</script>
</body>
</html>
