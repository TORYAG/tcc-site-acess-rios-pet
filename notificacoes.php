<?php
session_start();
require_once __DIR__ . "/config/conexao.php";
require_once __DIR__ . "/config/helpers.php";
if (!isset($_SESSION['id_usuario'])) { header("Location: login.php"); exit; }

$titulo_pagina = 'Notificações';
$id = $_SESSION['id_usuario'];

// Marcar todas como lidas
if (isset($_GET['marcar_lidas'])) {
    $pdo->prepare("UPDATE notificacao SET lida=1 WHERE id_usuario=?")->execute([$id]);
    header("Location: notificacoes.php"); exit;
}

$stmt = $pdo->prepare("SELECT * FROM notificacao WHERE id_usuario=? ORDER BY data_criacao DESC LIMIT 80");
$stmt->execute([$id]);
$notifs = $stmt->fetchAll();
$nao_lidas = array_filter($notifs, fn($n)=>!$n['lida']);

// Marcar como lidas ao visualizar
$pdo->prepare("UPDATE notificacao SET lida=1 WHERE id_usuario=? AND lida=0")->execute([$id]);

$icones = ['solicitacao'=>'📬','aceite'=>'🎉','recusa'=>'😔','avaliacao'=>'⭐','mensagem'=>'💬','sistema'=>'🔔'];

include __DIR__ . '/config/header.php';
?>

<div class="mini-hero">
    <p class="mh-breadcrumb"><a href="index.php">Início</a> › Notificações</p>
    <h1>🔔 Notificações</h1>
    <p><?= count($nao_lidas) ?> não lida<?= count($nao_lidas)!==1?'s':'' ?> · <?= count($notifs) ?> total</p>
</div>

<div style="padding:32px 6%;max-width:700px;margin:0 auto;">
    <?php if ($notifs): ?>
        <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
            <a href="?marcar_lidas=1" class="btn btn-ghost btn-sm">✓ Marcar todas como lidas</a>
        </div>
    <?php endif; ?>

    <div class="notif-lista">
        <?php if (!$notifs): ?>
            <div class="estado-vazio">
                <span class="ev-icon">🔔</span>
                <h3>Nenhuma notificação</h3>
                <p>Suas notificações aparecerão aqui quando houver novidades.</p>
            </div>
        <?php endif; ?>
        <?php foreach ($notifs as $n): ?>
            <a href="<?= e($n['link'] ?: '#') ?>" class="notif-item <?= !$n['lida']?'nao-lida':'' ?>">
                <span class="notif-icone"><?= $icones[$n['tipo']] ?? '🔔' ?></span>
                <div class="notif-corpo">
                    <div class="notif-msg"><?= e($n['mensagem']) ?></div>
                    <div class="notif-tempo"><?= tempo_relativo($n['data_criacao']) ?></div>
                </div>
                <?php if (!$n['lida']): ?><div class="notif-dot"></div><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/config/footer.php'; ?>
