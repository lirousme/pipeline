<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$basePath = $basePath === '.' ? '' : $basePath;
$homeUrl = $basePath === '' ? '/' : $basePath . '/';
$authUrl = $basePath . '/auth.php';
$apiUrl = $basePath . '/pipeline_api.php';
$pipelineUrl = $basePath . '/pipeline.php';

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
<main class="p-4 space-y-6">
    <section>
        <h2 class="font-semibold mb-2">Problemas em "tempo de assar"</h2>
        <ul id="bakingList" class="space-y-2"></ul>
    </section>
    <section>
        <h2 class="font-semibold mb-2">Problemas</h2>
        <ul id="problemList" class="space-y-2"></ul>
    </section>
</main>
<div id="modal" class="hidden fixed inset-0 bg-black/70 items-center justify-center"><div class="bg-slate-900 p-4 rounded w-full max-w-md"><h3 class="mb-2">Novo problema</h3><input id="problemText" class="w-full bg-slate-800 rounded p-2" placeholder="Texto"><button id="saveProblem" class="mt-3 bg-emerald-600 rounded px-3 py-1">Salvar</button></div></div>
<script>
const api=<?= json_encode($apiUrl) ?>;
const problemList=document.getElementById('problemList');
const bakingList=document.getElementById('bakingList');
const pipelineUrl=<?= json_encode($pipelineUrl) ?>;
let bakingTick = null;
openModal.onclick=()=>modal.classList.remove('hidden');modal.onclick=(e)=>{if(e.target===modal)modal.classList.add('hidden')};
saveProblem.onclick=async()=>{const r=await fetch(api+'?action=create-problem',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({text:problemText.value})});if(r.ok){problemText.value='';modal.classList.add('hidden');loadProblems();}};
async function loadProblems(){const r=await fetch(api+'?action=list-problems');const d=await r.json();problemList.innerHTML='';(d.items||[]).forEach(p=>{const li=document.createElement('li');li.className='bg-slate-900 border border-white/10 p-3 rounded';li.innerHTML=`<div class='font-medium'>#${p.id} ${p.text}</div><div class='mt-2 flex gap-2'><a class='bg-indigo-600 rounded px-2 py-1 text-sm inline-block' href='${pipelineUrl}?problem_id=${p.id}'>Abrir pipeline</a></div>`;problemList.appendChild(li);});}
function formatDiff(ms){if(ms<=0)return 'Pronto para retomar';const total=Math.floor(ms/1000);const h=Math.floor(total/3600);const m=Math.floor((total%3600)/60);const s=total%60;return `Disponível em ${h}h ${m}m ${s}s`;}
function renderBaking(items){bakingList.innerHTML='';if(!items.length){bakingList.innerHTML="<li class='text-slate-400 text-sm'>Nenhum problema assando agora.</li>";return;}items.forEach((p)=>{const li=document.createElement('li');li.className='bg-slate-900 border border-amber-500/30 p-3 rounded';const until=Date.parse(String(p.disponibilidade).replace(' ','T'));const blocked=Number.isFinite(until)&&until>Date.now();const actionClass=blocked?'bg-slate-600 pointer-events-none opacity-70':'bg-emerald-600';li.innerHTML=`<div class='font-medium'>#${p.id} ${p.text}</div><div class='text-xs text-amber-300 mt-1' data-until='${until}'>${formatDiff(until-Date.now())}</div><div class='mt-2'><a class='${actionClass} rounded px-2 py-1 text-sm inline-block' href='${pipelineUrl}?problem_id=${p.id}'>${blocked?'Aguardando':'Retomar agora'}</a></div>`;bakingList.appendChild(li);});}
async function loadBakingProblems(){const r=await fetch(api+'?action=list-baking-problems');const d=await r.json();renderBaking(d.items||[]);}
function startBakingCountdown(){if(bakingTick)clearInterval(bakingTick);bakingTick=setInterval(()=>{document.querySelectorAll('[data-until]').forEach((el)=>{const until=Number(el.dataset.until||0);el.textContent=formatDiff(until-Date.now());});},1000);}
loadProblems();
loadBakingProblems();
startBakingCountdown();
</script>
<?php endif; ?></body></html>
