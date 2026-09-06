<?php
session_start();
require_once __DIR__ . "/config/conexao.php";
require_once __DIR__ . "/config/helpers.php";
$titulo_pagina = 'Sobre';
$stat_itens   = $pdo->query("SELECT COUNT(*) FROM acessorio")->fetchColumn();
$stat_doacoes = $pdo->query("SELECT COUNT(*) FROM acessorio WHERE status='doado'")->fetchColumn();
$stat_users   = $pdo->query("SELECT COUNT(*) FROM usuario WHERE ativo=1")->fetchColumn();
include __DIR__ . '/config/header.php';
?>

<div class="sobre-hero">
    <h1>🐾 Conecta Pet Web</h1>
    <p>Uma plataforma criada com amor para conectar pessoas que querem ajudar animais. 100% gratuita, feita para o bem.</p>
</div>

<div class="sobre-content">
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin:40px 0;text-align:center;">
        <div style="padding:28px;background:var(--branco);border-radius:var(--radius-lg);border:1.5px solid var(--creme-borda);">
            <div style="font-size:36px;font-weight:900;color:var(--laranja);"><?= number_format($stat_itens,0,',','.') ?></div>
            <div style="color:var(--cinza-sub);font-weight:600;">Itens cadastrados</div>
        </div>
        <div style="padding:28px;background:var(--branco);border-radius:var(--radius-lg);border:1.5px solid var(--creme-borda);">
            <div style="font-size:36px;font-weight:900;color:var(--verde-esc);"><?= number_format($stat_doacoes,0,',','.') ?></div>
            <div style="color:var(--cinza-sub);font-weight:600;">Doações realizadas</div>
        </div>
        <div style="padding:28px;background:var(--branco);border-radius:var(--radius-lg);border:1.5px solid var(--creme-borda);">
            <div style="font-size:36px;font-weight:900;color:var(--terra);"><?= number_format($stat_users,0,',','.') ?></div>
            <div style="color:var(--cinza-sub);font-weight:600;">Usuários cadastrados</div>
        </div>
    </div>

    <h2>Nossa Missão</h2>
    <p>O Conecta Pet Web nasceu da ideia simples de que muitos tutores de pets têm acessórios que seus animais não usam mais — coleiras, caminhas, brinquedos, roupinhas — enquanto outros precisam exatamente desses itens e não têm condições de comprá-los.</p>
    <p>Nossa plataforma conecta doadores e receptores de forma prática, segura e completamente gratuita, fortalecendo a comunidade pet em todo o Brasil.</p>

    <div class="sobre-missao">
        <div class="sobre-missao-card">
            <span>🎁</span>
            <h4>Doação facilitada</h4>
            <p>Cadastre itens em minutos com foto e localização.</p>
        </div>
        <div class="sobre-missao-card">
            <span>📍</span>
            <h4>Conexão local</h4>
            <p>Encontre doações perto de você</p>
        </div>
        <div class="sobre-missao-card">
            <span>🔒</span>
            <h4>Plataforma segura</h4>
            <p>Avaliações, verificações e comunicação protegida entre usuários.</p>
        </div>
    </div>
    <div style="margin-top:48px;padding:40px;background:linear-gradient(135deg,var(--laranja),#EA580C);border-radius:var(--radius-xl);text-align:center;color:#fff;">
        <h2 style="color:#fff;margin-top:0;">Faça parte desta causa 🐾</h2>
        <p style="opacity:.9;margin-bottom:24px;">Seja doador, receptor ou ONG — todos podem ajudar.</p>
        <a href="cadastro.php" class="btn" style="background:#fff;color:var(--laranja);font-weight:800;">Criar conta gratuita →</a>
    </div>
</div>

<?php include __DIR__ . '/config/footer.php'; ?>
