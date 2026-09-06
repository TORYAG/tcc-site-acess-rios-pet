<?php
// config/header.php v3
if (!isset($titulo_pagina)) $titulo_pagina = 'Conecta Pet Web';

$notif_count = 0;
$msg_count   = 0;
if (isset($_SESSION['id_usuario'])) {
    $sn = $pdo->prepare("SELECT COUNT(*) FROM notificacao WHERE id_usuario=? AND lida=0");
    $sn->execute([$_SESSION['id_usuario']]);
    $notif_count = (int)$sn->fetchColumn();

    $sm = $pdo->prepare("SELECT COUNT(*) FROM mensagem WHERE id_destinatario=? AND lida=0");
    $sm->execute([$_SESSION['id_usuario']]);
    $msg_count = (int)$sm->fetchColumn();
}
$base = $base_url ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Conecta Pet Web — Plataforma gratuita de doação de acessórios para pets em todo o Brasil.">
    <title><?= e($titulo_pagina) ?> — Conecta Pet Web</title>
    <link rel="stylesheet" href="<?= $base ?>style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;0,900;1,700&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐾</text></svg>">
</head>
<body>

<header class="topo" id="topo">
    <a href="<?= $base ?>index.php" class="logo">
        <span class="logo-pata">🐾</span>
        <span class="logo-txt">Conecta <em>Pet</em></span>
    </a>

    <nav id="nav">
        <a href="<?= $base ?>index.php" class="nav-link">Início</a>
        <a href="<?= $base ?>acessorios.php" class="nav-link">Acessórios</a>
        <a href="<?= $base ?>sobre.php" class="nav-link">Sobre</a>

        <?php if (isset($_SESSION['id_usuario'])): ?>
            <a href="<?= $base ?>cadastrar_acessorio.php" class="nav-link nav-doar">➕ Doar</a>

            <div class="nav-dropdown">
                <button class="nav-link nav-dropdown-trigger">
                    <?php if (!empty($_SESSION['foto_perfil'])): ?>
                        <img src="<?= $base ?>uploads/<?= e($_SESSION['foto_perfil']) ?>" alt="" class="nav-avatar">
                    <?php else: ?>
                        <span class="nav-avatar-placeholder">🐾</span>
                    <?php endif; ?>
                    <?= e(explode(' ', $_SESSION['nome'])[0]) ?>
                    <?php $total_badges = $notif_count + $msg_count; if ($total_badges > 0): ?>
                        <span class="nav-badge"><?= $total_badges > 9 ? '9+' : $total_badges ?></span>
                    <?php endif; ?>
                    <span class="dropdown-arrow">▾</span>
                </button>
                <div class="nav-dropdown-menu">
                    <a href="<?= $base ?>perfil.php">👤 Meu Perfil</a>
                    <a href="<?= $base ?>minhas_solicitacoes.php">📋 Minhas Solicitações</a>
                    <a href="<?= $base ?>solicitacoes_recebidas.php">📬 Solicitações Recebidas</a>
                    <a href="<?= $base ?>favoritos.php">❤️ Favoritos</a>
                    <div class="dropdown-divider"></div>
                    <a href="<?= $base ?>notificacoes.php" class="dropdown-notif">
                        🔔 Notificações
                        <?php if ($notif_count > 0): ?>
                            <span class="dropdown-badge"><?= $notif_count ?></span>
                        <?php endif; ?>
                    </a>
                    <?php if (e_admin()): ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?= $base ?>admin.php">⚙️ Painel Admin</a>
                    <?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <a href="<?= $base ?>logout.php" class="dropdown-sair">🚪 Sair</a>
                </div>
            </div>

        <?php else: ?>
            <a href="<?= $base ?>login.php" class="nav-btn-outline">Login</a>
            <a href="<?= $base ?>cadastro.php" class="nav-btn">Cadastre-se</a>
        <?php endif; ?>
    </nav>

    <button class="hamburger" id="hamburger" aria-label="Menu" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>
</header>

<div class="nav-overlay" id="nav-overlay"></div>
