<?php
session_start();
require_once __DIR__ . "/config/conexao.php";
require_once __DIR__ . "/config/helpers.php";
if (!isset($_SESSION['id_usuario'])) { header("Location: login.php"); exit; }

$titulo_pagina = 'Favoritos';
$id = $_SESSION['id_usuario'];

$stmt = $pdo->prepare(
    "SELECT a.*, u.nome AS doador, u.foto_perfil AS doador_foto,
            c.nome_categoria, c.icone AS cat_icone, p.tipo_pet, p.porte
     FROM favorito f
     INNER JOIN acessorio a ON f.id_acessorio=a.id_acessorio
     INNER JOIN usuario u ON a.id_usuario=u.id_usuario
     INNER JOIN categoria_acessorio c ON a.id_categoria=c.id_categoria
     INNER JOIN pet p ON a.id_pet=p.id_pet
     WHERE f.id_usuario=? ORDER BY f.data_favorito DESC"
);
$stmt->execute([$id]);
$favs = $stmt->fetchAll();

include __DIR__ . '/config/header.php';
?>

<div class="mini-hero">
    <p class="mh-breadcrumb"><a href="index.php">Início</a> › Favoritos</p>
    <h1>❤️ Meus Favoritos</h1>
    <p><?= count($favs) ?> item<?= count($favs)!==1?'s':'' ?> salvo<?= count($favs)!==1?'s':'' ?></p>
</div>

<div class="secao-cards">
    <div class="cards-grid">
        <?php if (!$favs): ?>
            <div class="estado-vazio">
                <span class="ev-icon">🤍</span>
                <h3>Nenhum favorito ainda</h3>
                <p>Clique no 🤍 nos cards para salvar itens que você gostou.</p>
                <a href="acessorios.php" class="btn btn-primario">🔍 Explorar acessórios</a>
            </div>
        <?php endif; ?>
        <?php foreach ($favs as $item): ?>
        <div class="card-acessorio fade-up">
            <div class="card-foto">
                <?php if ($item['foto']): ?><img src="uploads/<?= e($item['foto']) ?>" alt="<?= e($item['nome']) ?>" loading="lazy"><?php else: ?><div class="card-foto-placeholder"><?= $item['cat_icone'] ?></div><?php endif; ?>
                <span class="card-badge-status badge-<?= $item['status'] ?>"><?= ucfirst($item['status']) ?></span>
                <a href="toggle_favorito.php?id=<?= $item['id_acessorio'] ?>&volta=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="card-fav-btn ativo" title="Remover">❤️</a>
            </div>
            <div class="card-body">
                <span class="card-categoria"><?= e($item['cat_icone'].' '.$item['nome_categoria']) ?></span>
                <a href="acessorio.php?id=<?= $item['id_acessorio'] ?>" class="card-nome"><?= e($item['nome']) ?></a>
                <p class="card-desc"><?= e(truncar($item['descricao'],85)) ?></p>
                <div class="card-meta">
                    <div class="card-meta-item">🐾 <?= e($item['tipo_pet'].' — '.$item['porte']) ?></div>
                    <div class="card-meta-item">📍 <?= e($item['cidade']?($item['bairro']?$item['bairro'].', ':'').$item['cidade'].($item['estado']?' - '.$item['estado']:''):'—') ?></div>
                </div>
            </div>
            <div class="card-footer">
                <div class="card-doador">
                    <div class="card-doador-avatar"><?php if ($item['doador_foto']): ?><img src="uploads/<?= e($item['doador_foto']) ?>" alt=""><?php else: ?>🐾<?php endif; ?></div>
                    <span class="card-doador-nome"><?= e($item['doador']) ?></span>
                </div>
                <?php if ($item['status']==='disponivel' && $_SESSION['id_usuario']!=$item['id_usuario']): ?>
                    <a href="acessorio.php?id=<?= $item['id_acessorio'] ?>" class="btn btn-primario btn-sm">Solicitar</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/config/footer.php'; ?>
