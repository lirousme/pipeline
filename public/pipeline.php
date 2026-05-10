<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$loggedUser = $_SESSION['auth_user'] ?? null;
if (!$loggedUser) {
    header('Location: ./index.php');
    exit;
}

$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$basePath = $basePath === '.' ? '' : $basePath;
$apiUrl = $basePath . '/pipeline_api.php';
$initialProblemId = (int) ($_GET['problem_id'] ?? 0);
?>
<!doctype html><html lang="pt-BR" class="dark"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pipeline</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
<header class="border-b border-white/10 p-4 flex justify-between"><strong>Pipeline de <?= htmlspecialchars((string)$loggedUser) ?></strong><a href="./index.php" class="bg-slate-700 px-3 py-1 rounded">Voltar</a></header>
<main class="p-4 grid md:grid-cols-2 gap-4"><section><h2 class="font-semibold mb-2">Cards vencidos (proxima_expansion <= agora UTC)</h2><ul id="dueList" class="space-y-2"></ul></section><section><h2 class="font-semibold mb-2">Pipeline (vertical)</h2><div id="pipelineView" class="space-y-3"></div></section></main>
<script>
const api=<?= json_encode($apiUrl) ?>;
const dueList=document.getElementById('dueList');
const pipelineView=document.getElementById('pipelineView');
const initialProblemId=<?= json_encode($initialProblemId) ?>;

async function openPipeline(id){const r=await fetch(api+'?action=pipeline&problem_id='+id);const d=await r.json();pipelineView.innerHTML='';(d.nodes||[]).forEach((n)=>{const card=document.createElement('div');card.className='relative pl-6';card.innerHTML=`<div class='absolute left-2 top-0 bottom-0 w-px bg-slate-700'></div><div class='bg-slate-900 border border-white/10 rounded p-3'><div class='font-semibold'>Problema #${n.problem.id}</div><div>${n.problem.text}</div><div class='text-xs text-slate-400 mt-1'>expansions: ${n.problem.expansions ?? 0} | próxima: ${n.problem.proxima_expansion ?? '-'}</div><ul class='mt-2 text-sm text-slate-300'>${n.conditionals.map(c=>`<li>Se "${c.text}" → abre #${c.id_next_problem}</li>`).join('')}</ul></div>`;pipelineView.appendChild(card);});}

async function updateExpansion(problemId,currentValue){
  const raw=prompt('Novo valor de expansions (dias):', String(currentValue ?? 0));
  if(raw===null)return;
  const expansions=Number(raw);
  if(!Number.isInteger(expansions)||expansions<0)return alert('Informe um inteiro >= 0');
  const r=await fetch(api+'?action=update-expansion',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({problem_id:problemId,expansions})});
  const d=await r.json();
  if(!r.ok)return alert(d.message||'Erro ao atualizar');
  await loadDueCards();
if(initialProblemId>0){openPipeline(initialProblemId);}
  await openPipeline(problemId);
}

async function loadDueCards(){const r=await fetch(api+'?action=list-due-problems');const d=await r.json();dueList.innerHTML='';(d.items||[]).forEach(p=>{const li=document.createElement('li');li.className='bg-slate-900 border border-white/10 p-3 rounded';li.innerHTML=`<div class='font-medium'>#${p.id} ${p.text}</div><div class='text-xs text-slate-400 mt-1'>expansions: ${p.expansions ?? 0} | próxima: ${p.proxima_expansion ?? '-'}</div><div class='mt-2 flex gap-2'><button class='bg-indigo-600 rounded px-2 py-1 text-sm' onclick='openPipeline(${p.id})'>Abrir pipeline</button><button class='bg-emerald-700 rounded px-2 py-1 text-sm' onclick='updateExpansion(${p.id}, ${p.expansions ?? 0})'>Atualizar expansion</button></div>`;dueList.appendChild(li);});}

loadDueCards();
if(initialProblemId>0){openPipeline(initialProblemId);}
</script>
</body></html>
