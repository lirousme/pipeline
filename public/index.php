<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$basePath = $basePath === '.' ? '' : $basePath;
$homeUrl = $basePath === '' ? '/' : $basePath . '/';
$authUrl = $basePath . '/auth.php';
$apiUrl = $basePath . '/pipeline_api.php';

if (isset($_GET['logout'])) { session_destroy(); header('Location: ' . $homeUrl); exit; }
$loggedUser = $_SESSION['auth_user'] ?? null;
?>
<!doctype html><html lang="pt-BR" class="dark"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pipeline</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
<?php if(!$loggedUser): ?>
<div class="min-h-screen flex items-center justify-center p-6"><div class="w-full max-w-md bg-slate-900/70 border border-white/10 rounded-2xl p-6"><h1 class="text-xl font-semibold mb-4">Entrar</h1><form id="authForm" class="space-y-3"><input id="mode" type="hidden" value="login"><input id="username" class="w-full rounded bg-slate-800 p-2" placeholder="Usuário" required><input id="password" type="password" class="w-full rounded bg-slate-800 p-2" placeholder="Senha" required><button class="w-full bg-indigo-500 rounded p-2">Entrar</button></form><button id="toggleMode" class="mt-3 text-sm text-slate-300">Não tem conta? Criar</button><p id="feedback" class="text-sm mt-2"></p></div></div>
<script>
let mode='login';toggleMode.onclick=()=>{mode=mode==='login'?'register':'login';document.getElementById('mode').value=mode;toggleMode.textContent=mode==='login'?'Não tem conta? Criar':'Já tem conta? Entrar'};
authForm.onsubmit=async(e)=>{e.preventDefault();const r=await fetch(<?= json_encode($authUrl) ?>,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({mode,username:username.value,password:password.value})});const d=await r.json();feedback.textContent=d.message||'Erro';if(r.ok)location.reload();};
</script>
<?php else: ?>
<header class="border-b border-white/10 p-4 flex justify-between"><strong>Pipeline de <?= htmlspecialchars((string)$loggedUser) ?></strong><div class="flex gap-2"><button id="openModal" class="bg-indigo-500 px-3 py-1 rounded">+ Problema</button><a href="?logout=1" class="bg-slate-700 px-3 py-1 rounded">Sair</a></div></header>
<main class="p-4 grid md:grid-cols-2 gap-4"><section><h2 class="font-semibold mb-2">Problemas</h2><ul id="problemList" class="space-y-2"></ul></section><section><h2 class="font-semibold mb-2">Pipeline (vertical)</h2><div id="pipelineView" class="space-y-3"></div></section></main>
<div id="modal" class="hidden fixed inset-0 bg-black/70 items-center justify-center"><div class="bg-slate-900 p-4 rounded w-full max-w-md"><h3 class="mb-2">Novo problema</h3><input id="problemText" class="w-full bg-slate-800 rounded p-2" placeholder="Texto"><button id="saveProblem" class="mt-3 bg-emerald-600 rounded px-3 py-1">Salvar</button></div></div>
<div id="conditionalModal" class="hidden fixed inset-0 bg-black/70 items-center justify-center"><div class="bg-slate-900 p-4 rounded w-full max-w-lg border border-white/10"><h3 class="mb-3 text-lg font-semibold">Adicionar condicional</h3><div class="space-y-3"><input id="conditionalText" class="w-full bg-slate-800 rounded p-2" placeholder="Texto da condicional"><div><input id="nextProblemSearch" class="w-full bg-slate-800 rounded p-2" placeholder="Digite para buscar ou criar problema"><ul id="searchSuggestions" class="mt-2 space-y-1"></ul></div><p id="selectedProblemInfo" class="text-sm text-emerald-300 hidden"></p><button id="saveConditional" class="bg-indigo-600 rounded px-3 py-1">Salvar condicional</button></div></div></div>
<script>
const api=<?= json_encode($apiUrl) ?>;
const problemList=document.getElementById('problemList');const pipelineView=document.getElementById('pipelineView');
const conditionalModal=document.getElementById('conditionalModal');
const conditionalText=document.getElementById('conditionalText');
const nextProblemSearch=document.getElementById('nextProblemSearch');
const searchSuggestions=document.getElementById('searchSuggestions');
const saveConditional=document.getElementById('saveConditional');
const selectedProblemInfo=document.getElementById('selectedProblemInfo');
let conditionalFatherId=null;let selectedProblem=null;
openModal.onclick=()=>modal.classList.remove('hidden');modal.onclick=(e)=>{if(e.target===modal)modal.classList.add('hidden')};
conditionalModal.onclick=(e)=>{if(e.target===conditionalModal)closeConditionalModal();};
saveProblem.onclick=async()=>{const r=await fetch(api+'?action=create-problem',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({text:problemText.value})});if(r.ok){problemText.value='';modal.classList.add('hidden');loadProblems();}};
function closeConditionalModal(){conditionalModal.classList.add('hidden');conditionalText.value='';nextProblemSearch.value='';searchSuggestions.innerHTML='';selectedProblem=null;selectedProblemInfo.classList.add('hidden');conditionalFatherId=null;}
function selectProblem(problem){selectedProblem=problem;selectedProblemInfo.textContent=`Usando problema existente: #${problem.id} ${problem.text}`;selectedProblemInfo.classList.remove('hidden');nextProblemSearch.value=problem.text;searchSuggestions.innerHTML='';}
nextProblemSearch.oninput=async()=>{const q=nextProblemSearch.value.trim();selectedProblem=null;selectedProblemInfo.classList.add('hidden');if(q.length<2){searchSuggestions.innerHTML='';return;}const r=await fetch(api+'?action=search-problems&q='+encodeURIComponent(q));const d=await r.json();searchSuggestions.innerHTML='';(d.items||[]).forEach(item=>{const li=document.createElement('li');li.className='bg-slate-800 border border-white/10 rounded px-2 py-1 text-sm cursor-pointer hover:bg-slate-700';li.textContent=`#${item.id} ${item.text}`;li.onclick=()=>selectProblem(item);searchSuggestions.appendChild(li);});};
saveConditional.onclick=async()=>{const text=conditionalText.value.trim();const searchValue=nextProblemSearch.value.trim();if(!text||!conditionalFatherId||(!selectedProblem&&searchValue===''))return;const payload={id_father_problem:conditionalFatherId,text};if(selectedProblem){payload.id_next_problem=Number(selectedProblem.id);}else{payload.next_problem_text=searchValue;}const r=await fetch(api+'?action=add-conditional',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});if(r.ok){closeConditionalModal();loadProblems();openPipeline(conditionalFatherId);}};
async function loadProblems(){const r=await fetch(api+'?action=list-problems');const d=await r.json();problemList.innerHTML='';(d.items||[]).forEach(p=>{const li=document.createElement('li');li.className='bg-slate-900 border border-white/10 p-3 rounded';li.innerHTML=`<div class='font-medium'>#${p.id} ${p.text}</div><div class='mt-2 flex gap-2'><button class='bg-indigo-600 rounded px-2 py-1 text-sm' onclick='openPipeline(${p.id})'>Abrir pipeline</button><button class='bg-slate-700 rounded px-2 py-1 text-sm' onclick='addConditional(${p.id})'>+ Condicional</button></div>`;problemList.appendChild(li);});}
async function openPipeline(id){const r=await fetch(api+'?action=pipeline&problem_id='+id);const d=await r.json();pipelineView.innerHTML='';(d.nodes||[]).forEach((n,idx)=>{const card=document.createElement('div');card.className='relative pl-6';card.innerHTML=`<div class='absolute left-2 top-0 bottom-0 w-px bg-slate-700'></div><div class='bg-slate-900 border border-white/10 rounded p-3'><div class='font-semibold'>Problema #${n.problem.id}</div><div>${n.problem.text}</div><ul class='mt-2 text-sm text-slate-300'>${n.conditionals.map(c=>`<li>Se "${c.text}" → abre #${c.id_next_problem}</li>`).join('')}</ul></div>`;pipelineView.appendChild(card);});}
function addConditional(idFather){conditionalFatherId=idFather;conditionalModal.classList.remove('hidden');}
loadProblems();
</script>
<?php endif; ?></body></html>
