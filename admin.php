<?php
session_start();
require_once __DIR__ . "/config/conexao.php";
require_once __DIR__ . "/config/helpers.php";
if (!isset($_SESSION['id_usuario']) || !e_admin()) {
    header("Location: index.php"); exit;
}

$titulo_pagina = 'Admin';
$aba = $_GET['aba'] ?? 'usuarios';

// Ações
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (isset($_POST['toggle_usuario'])) {
        $uid = (int)$_POST['id'];
        $pdo->prepare("UPDATE usuario SET ativo = 1-ativo WHERE id_usuario=?")->execute([$uid]);
    }
    if (isset($_POST['toggle_item'])) {
        $aid = (int)$_POST['id'];
        $pdo->prepare("UPDATE acessorio SET status = IF(status='inativo','disponivel','inativo') WHERE id_acessorio=?")->execute([$aid]);
    }
    header("Location: admin.php?aba=$aba"); exit;
}

// Stats
$s_users   = $pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
$s_itens   = $pdo->query("SELECT COUNT(*) FROM acessorio")->fetchColumn();
$s_doacoes = $pdo->query("SELECT COUNT(*) FROM acessorio WHERE status='doado'")->fetchColumn();
$s_solic   = $pdo->query("SELECT COUNT(*) FROM solicitacao_doacao")->fetchColumn();
$s_avals   = $pdo->query("SELECT COUNT(*) FROM avaliacao")->fetchColumn();

// Dados por aba
$dados = [];
if ($aba==='usuarios') {
    $dados = $pdo->query("SELECT u.*, COUNT(a.id_acessorio) AS total_itens FROM usuario u LEFT JOIN acessorio a ON a.id_usuario=u.id_usuario GROUP BY u.id_usuario ORDER BY u.data_cadastro DESC LIMIT 100")->fetchAll();
} elseif ($aba==='itens') {
    $dados = $pdo->query("SELECT a.*, u.nome AS doador, c.nome_categoria FROM acessorio a INNER JOIN usuario u ON a.id_usuario=u.id_usuario INNER JOIN categoria_acessorio c ON a.id_categoria=c.id_categoria ORDER BY a.data_cadastro DESC LIMIT 100")->fetchAll();
} elseif ($aba==='solicitacoes') {
    $dados = $pdo->query("SELECT s.*, a.nome AS item, u.nome AS solicitante FROM solicitacao_doacao s INNER JOIN acessorio a ON s.id_acessorio=a.id_acessorio INNER JOIN usuario u ON s.id_usuario=u.id_usuario ORDER BY s.data_solicitacao DESC LIMIT 100")->fetchAll();
}

include __DIR__ . '/config/header.php';
?>

<div class="admin-grid">
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="admin-sidebar-title">Painel Admin</div>
        <a href="?aba=dashboard" class="<?= $aba==='dashboard'?'ativo':'' ?>">📊 Dashboard</a>
        <a href="?aba=usuarios"  class="<?= $aba==='usuarios'?'ativo':'' ?>">👥 Usuários</a>
        <a href="?aba=itens"     class="<?= $aba==='itens'?'ativo':'' ?>">🎁 Acessórios</a>
        <a href="?aba=solicitacoes" class="<?= $aba==='solicitacoes'?'ativo':'' ?>">📋 Solicitações</a>
        <div style="margin-top:auto;padding:20px;border-top:1px solid rgba(255,255,255,.1);">
            <a href="index.php" style="color:rgba(255,255,255,.5);font-size:13px;text-decoration:none;">← Voltar ao site</a>
        </div>
    </div>

    <!-- Main -->
    <div class="admin-main">
        <h2 style="font-family:'Playfair Display',serif;font-size:22px;font-weight:900;margin-bottom:24px;">
            <?= ['dashboard'=>'📊 Dashboard','usuarios'=>'👥 Usuários','itens'=>'🎁 Acessórios','solicitacoes'=>'📋 Solicitações'][$aba] ?? 'Admin' ?>
        </h2>

        <!-- Stats sempre visíveis -->
        <div class="admin-stats">
            <div class="admin-stat-card"><div class="numero"><?= $s_users ?></div><div class="label">Usuários</div></div>
            <div class="admin-stat-card"><div class="numero"><?= $s_itens ?></div><div class="label">Acessórios</div></div>
            <div class="admin-stat-card"><div class="numero"><?= $s_doacoes ?></div><div class="label">Doações concluídas</div></div>
            <div class="admin-stat-card"><div class="numero"><?= $s_solic ?></div><div class="label">Solicitações</div></div>
            <div class="admin-stat-card"><div class="numero"><?= $s_avals ?></div><div class="label">Avaliações</div></div>
        </div>

        <?php if ($aba==='usuarios' && $dados): ?>
        <div class="admin-table-wrap">
            <div class="admin-table-header">Usuários cadastrados <span style="color:var(--cinza-sub);font-weight:600;font-size:14px;"><?= count($dados) ?></span></div>
            <div style="overflow-x:auto;">
            <table>
                <thead><tr><th>ID</th><th>Nome</th><th>E-mail</th><th>Tipo</th><th>Itens</th><th>Cadastro</th><th>Status</th><th>Ação</th></tr></thead>
                <tbody>
                <?php foreach ($dados as $u): ?>
                <tr>
                    <td><?= $u['id_usuario'] ?></td>
                    <td><strong><?= e($u['nome']) ?></strong></td>
                    <td style="font-size:13px;"><?= e($u['email']) ?></td>
                    <td><?= icone_tipo($u['tipo_usuario']).' '.ucfirst($u['tipo_usuario']) ?></td>
                    <td><?= $u['total_itens'] ?></td>
                    <td style="font-size:12px;"><?= date('d/m/Y', strtotime($u['data_cadastro'])) ?></td>
                    <td><span class="status-pill <?= $u['ativo']?'status-aceita':'status-recusada' ?>"><?= $u['ativo']?'Ativo':'Inativo' ?></span></td>
                    <td>
                        <?php if ($u['id_usuario'] != $_SESSION['id_usuario']): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="id" value="<?= $u['id_usuario'] ?>">
                            <button name="toggle_usuario" class="btn btn-sm <?= $u['ativo']?'btn-perigo':'btn-secundario' ?>" style="font-size:12px;padding:5px 10px;">
                                <?= $u['ativo']?'Desativar':'Ativar' ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <?php elseif ($aba==='itens' && $dados): ?>
        <div class="admin-table-wrap">
            <div class="admin-table-header">Acessórios <span style="color:var(--cinza-sub);font-weight:600;font-size:14px;"><?= count($dados) ?></span></div>
            <div style="overflow-x:auto;">
            <table>
                <thead><tr><th>ID</th><th>Nome</th><th>Doador</th><th>Categoria</th><th>Cidade/UF</th><th>Status</th><th>Views</th><th>Ação</th></tr></thead>
                <tbody>
                <?php foreach ($dados as $a): ?>
                <tr>
                    <td><?= $a['id_acessorio'] ?></td>
                    <td><a href="acessorio.php?id=<?= $a['id_acessorio'] ?>" style="font-weight:700;color:var(--laranja);text-decoration:none;"><?= e(truncar($a['nome'],30)) ?></a></td>
                    <td><?= e($a['doador']) ?></td>
                    <td style="font-size:13px;"><?= e($a['nome_categoria']) ?></td>
                    <td style="font-size:13px;"><?= e($a['cidade']?$a['cidade'].($a['estado']?' - '.$a['estado']:''):'—') ?></td>
                    <td><span class="status-pill status-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td><?= $a['visualizacoes'] ?></td>
                    <td>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="id" value="<?= $a['id_acessorio'] ?>">
                            <button name="toggle_item" class="btn btn-sm <?= $a['status']==='inativo'?'btn-secundario':'btn-perigo' ?>" style="font-size:12px;padding:5px 10px;">
                                <?= $a['status']==='inativo'?'Reativar':'Inativar' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <?php elseif ($aba==='solicitacoes' && $dados): ?>
        <div class="admin-table-wrap">
            <div class="admin-table-header">Solicitações</div>
            <div style="overflow-x:auto;">
            <table>
                <thead><tr><th>ID</th><th>Item</th><th>Solicitante</th><th>Status</th><th>Data</th></tr></thead>
                <tbody>
                <?php foreach ($dados as $s): ?>
                <tr>
                    <td><?= $s['id_solicitacao'] ?></td>
                    <td><?= e(truncar($s['item'],30)) ?></td>
                    <td><?= e($s['solicitante']) ?></td>
                    <td><span class="status-pill status-<?= $s['status_solicitacao'] ?>"><?= ucfirst($s['status_solicitacao']) ?></span></td>
                    <td style="font-size:12px;"><?= date('d/m/Y', strtotime($s['data_solicitacao'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/config/footer.php'; ?>
