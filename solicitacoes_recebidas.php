<?php
session_start();
require_once __DIR__ . "/config/conexao.php";
require_once __DIR__ . "/config/helpers.php";
if (!isset($_SESSION['id_usuario'])) { header("Location: login.php"); exit; }

$titulo_pagina = 'Solicitações Recebidas';
$id = $_SESSION['id_usuario'];
$filtro = $_GET['filtro'] ?? 'todas';
$validos = ['todas','pendente','aceita','recusada','finalizada'];
if (!in_array($filtro,$validos)) $filtro = 'todas';

$where = "WHERE a.id_usuario=?";
$params = [$id];
if ($filtro !== 'todas') { $where.=" AND s.status_solicitacao=?"; $params[]=$filtro; }

$stmt = $pdo->prepare(
    "SELECT s.*, a.nome AS item_nome, a.foto AS item_foto,
            u.nome AS solicitante, u.foto_perfil AS sol_foto, u.telefone AS sol_tel, u.email AS sol_email
     FROM solicitacao_doacao s
     INNER JOIN acessorio a ON s.id_acessorio=a.id_acessorio
     INNER JOIN usuario u   ON s.id_usuario=u.id_usuario
     $where ORDER BY s.data_solicitacao DESC"
);
$stmt->execute($params);
$solic = $stmt->fetchAll();

// Contadores por status
$contadores = [];
$sc = $pdo->prepare("SELECT status_solicitacao,COUNT(*) FROM solicitacao_doacao s INNER JOIN acessorio a ON s.id_acessorio=a.id_acessorio WHERE a.id_usuario=? GROUP BY status_solicitacao");
$sc->execute([$id]);
foreach ($sc->fetchAll() as $r) $contadores[$r['status_solicitacao']] = $r['COUNT(*)'];

include __DIR__ . '/config/header.php';
?>

<div class="mini-hero">
    <p class="mh-breadcrumb"><a href="index.php">Início</a> › Solicitações Recebidas</p>
    <h1>📬 Solicitações Recebidas</h1>
    <p>Gerencie quem quer receber seus itens doados.</p>
</div>

<div style="padding:32px 6%;max-width:900px;margin:0 auto;">

    <!-- Filtros por status -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;">
        <?php
        $tabs = ['todas'=>'Todas','pendente'=>'Pendentes','aceita'=>'Aceitas','recusada'=>'Recusadas','finalizada'=>'Finalizadas'];
        foreach ($tabs as $k=>$v):
            $cnt = $k==='todas' ? array_sum($contadores) : ($contadores[$k]??0);
            $ativo = $filtro===$k ? 'btn-primario' : 'btn-ghost';
        ?>
            <a href="?filtro=<?= $k ?>" class="btn btn-sm <?= $ativo ?>">
                <?= $v ?> <?php if ($cnt>0): ?><span style="background:rgba(255,255,255,.3);border-radius:999px;padding:1px 7px;font-size:11px;"><?= $cnt ?></span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!$solic): ?>
        <div class="estado-vazio">
            <span class="ev-icon">📭</span>
            <h3>Nenhuma solicitação <?= $filtro!=='todas'?'com status "'.$filtro.'"':'' ?></h3>
            <p>Quando alguém solicitar seus itens, as solicitações aparecerão aqui.</p>
            <a href="cadastrar_acessorio.php" class="btn btn-primario">🎁 Cadastrar item para doação</a>
        </div>
    <?php else: ?>
    <div class="solic-lista">
        <?php foreach ($solic as $s): ?>
        <div class="solic-card">
            <div class="solic-header">
                <div class="solic-foto-ph">
                    <?php if ($s['item_foto']): ?><img src="uploads/<?= e($s['item_foto']) ?>" class="solic-foto" alt=""><?php else: ?>🎁<?php endif; ?>
                </div>
                <div style="flex:1;min-width:0;">
                    <div class="solic-titulo"><?= e($s['item_nome']) ?></div>
                    <div class="solic-sub">
                        Solicitado por <strong><?= e($s['solicitante']) ?></strong> · <?= tempo_relativo($s['data_solicitacao']) ?>
                    </div>
                </div>
                <span class="status-pill status-<?= $s['status_solicitacao'] ?>"><?= ucfirst($s['status_solicitacao']) ?></span>
            </div>

            <?php if ($s['mensagem']): ?>
                <div class="solic-mensagem">"<?= e($s['mensagem']) ?>"</div>
            <?php endif; ?>

            <?php if ($s['status_solicitacao']==='aceita' && $s['sol_tel']): ?>
                <div class="alerta alerta-sucesso" style="margin-bottom:12px;">
                    <span class="alerta-icon">📱</span>
                    Contato do solicitante: <strong><?= e($s['sol_tel']) ?></strong>
                    <?php if ($s['sol_email']): ?> · <a href="mailto:<?= e($s['sol_email']) ?>" style="color:var(--verde-esc);"><?= e($s['sol_email']) ?></a><?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="solic-acoes">
                <?php if ($s['status_solicitacao']==='pendente'): ?>
                    <a href="solicitacoes_recebidas_status.php?id=<?= $s['id_solicitacao'] ?>&acao=aceitar" class="btn btn-secundario btn-sm"
                       onclick="return confirm('Aceitar esta solicitação? As demais pendentes para este item serão recusadas.')">
                        ✅ Aceitar
                    </a>
                    <a href="solicitacoes_recebidas_status.php?id=<?= $s['id_solicitacao'] ?>&acao=recusar" class="btn btn-perigo btn-sm"
                       onclick="return confirm('Recusar esta solicitação?')">
                        ❌ Recusar
                    </a>
                <?php elseif ($s['status_solicitacao']==='aceita'): ?>
                    <a href="solicitacoes_recebidas_status.php?id=<?= $s['id_solicitacao'] ?>&acao=finalizar" class="btn btn-primario btn-sm"
                       onclick="return confirm('Marcar doação como finalizada?')">
                        🏁 Finalizar doação
                    </a>
                <?php endif; ?>
                <a href="acessorio.php?id=<?= $s['id_acessorio'] ?>" class="btn btn-ghost btn-sm">👁️ Ver item</a>
                <span style="font-size:12px;color:var(--cinza-sub);margin-left:auto;"><?= formatar_data($s['data_solicitacao']) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/config/footer.php'; ?>
