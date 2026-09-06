<?php
session_start();
require_once __DIR__ . "/config/conexao.php";
require_once __DIR__ . "/config/helpers.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: acessorios.php"); exit; }

// Incrementar visualizações (ignora dono e sessão repetida)
$vk = "visto_$id";
if (!isset($_SESSION[$vk]) || $_SESSION[$vk] < time() - 1800) {
    if (!isset($_SESSION['id_usuario']) || $_SESSION['id_usuario'] != (int)$_SESSION['id_usuario']) {
        $pdo->prepare("UPDATE acessorio SET visualizacoes=visualizacoes+1 WHERE id_acessorio=?")->execute([$id]);
        $_SESSION[$vk] = time();
    }
}

$stmt = $pdo->prepare(
    "SELECT a.*, u.nome AS doador, u.foto_perfil AS doador_foto, u.telefone AS doador_tel,
            u.bio AS doador_bio, u.tipo_usuario AS doador_tipo, u.data_cadastro AS doador_desde,
            c.nome_categoria, c.icone AS cat_icone, p.tipo_pet, p.porte
     FROM acessorio a
     INNER JOIN usuario u ON a.id_usuario=u.id_usuario
     INNER JOIN categoria_acessorio c ON a.id_categoria=c.id_categoria
     INNER JOIN pet p ON a.id_pet=p.id_pet
     WHERE a.id_acessorio=?"
);
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) { header("Location: acessorios.php"); exit; }

$titulo_pagina = $item['nome'];

// Avaliações e média do doador
$aval_stmt = $pdo->prepare(
    "SELECT av.*, u.nome AS avaliador_nome, u.foto_perfil AS avaliador_foto
     FROM avaliacao av INNER JOIN usuario u ON av.id_avaliador=u.id_usuario
     WHERE av.id_avaliado=? ORDER BY av.data_avaliacao DESC LIMIT 6"
);
$aval_stmt->execute([$item['id_usuario']]);
$avaliacoes = $aval_stmt->fetchAll();
$media_nota  = count($avaliacoes) ? array_sum(array_column($avaliacoes,'nota')) / count($avaliacoes) : 0;
$total_aval  = (int)$pdo->prepare("SELECT COUNT(*) FROM avaliacao WHERE id_avaliado=?")->execute([$item['id_usuario']]) ?
               $pdo->query("SELECT COUNT(*) FROM avaliacao WHERE id_avaliado=".(int)$item['id_usuario'])->fetchColumn() : 0;

// Outros itens do doador
$outros = $pdo->prepare("SELECT * FROM acessorio WHERE id_usuario=? AND id_acessorio!=? AND status='disponivel' LIMIT 4");
$outros->execute([$item['id_usuario'], $id]);
$outros_itens = $outros->fetchAll();

$e_fav = $ja_solicitou = false;
if (isset($_SESSION['id_usuario'])) {
    $sf = $pdo->prepare("SELECT 1 FROM favorito WHERE id_usuario=? AND id_acessorio=?");
    $sf->execute([$_SESSION['id_usuario'], $id]); $e_fav = (bool)$sf->fetchColumn();
    $ss = $pdo->prepare("SELECT 1 FROM solicitacao_doacao WHERE id_usuario=? AND id_acessorio=? AND status_solicitacao='pendente'");
    $ss->execute([$_SESSION['id_usuario'], $id]); $ja_solicitou = (bool)$ss->fetchColumn();
}

$pode_solicitar = isset($_SESSION['id_usuario'])
    && $_SESSION['id_usuario'] != $item['id_usuario']
    && $item['status'] === 'disponivel'
    && !$ja_solicitou;

$erro = $sucesso = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar']) && $pode_solicitar) {
    $mensagem = trim($_POST['mensagem'] ?? '');
    if (!$mensagem) { $erro = 'Escreva uma mensagem para o doador.'; }
    elseif (strlen($mensagem) > 1000) { $erro = 'Mensagem muito longa (máx. 1000 caracteres).'; }
    else {
        $pdo->prepare("INSERT INTO solicitacao_doacao (mensagem,id_usuario,id_acessorio) VALUES (?,?,?)")
            ->execute([$mensagem, $_SESSION['id_usuario'], $id]);
        $pdo->prepare("UPDATE acessorio SET status='solicitado' WHERE id_acessorio=? AND status='disponivel'")->execute([$id]);
        criar_notificacao($pdo, $item['id_usuario'],
            "📬 {$_SESSION['nome']} solicitou seu item \"{$item['nome']}\"",
            "solicitacoes_recebidas.php", 'solicitacao');
        $sucesso = 'Solicitação enviada! O doador será notificado.';
        $ja_solicitou = true; $pode_solicitar = false;
    }
}

include __DIR__ . '/config/header.php';
?>

<div class="mini-hero">
    <p class="mh-breadcrumb">
        <a href="index.php">Início</a> › <a href="acessorios.php">Acessórios</a> › <?= e(truncar($item['nome'],40)) ?>
    </p>
    <h1><?= e($item['nome']) ?></h1>
    <p><?= e($item['cat_icone'].' '.$item['nome_categoria']) ?> · <?= e($item['tipo_pet'].' — '.$item['porte']) ?></p>
</div>

<div class="acessorio-layout">
    <!-- Esquerda: foto + detalhes -->
    <div>
        <div class="acessorio-foto-wrap">
            <?php if ($item['foto']): ?>
                <img class="acessorio-foto" src="uploads/<?= e($item['foto']) ?>" alt="<?= e($item['nome']) ?>">
            <?php else: ?>
                <div class="acessorio-foto-placeholder"><?= $item['cat_icone'] ?></div>
            <?php endif; ?>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:16px;">
            <div class="acessorio-meta">
                <span class="acessorio-tag">🏷️ <?= e($item['cat_icone'].' '.$item['nome_categoria']) ?></span>
                <span class="acessorio-tag">🐾 <?= e($item['tipo_pet'].' — '.$item['porte']) ?></span>
                <span class="acessorio-tag card-badge-status badge-<?= $item['status'] ?>"><?= ucfirst($item['status']) ?></span>
                <span class="acessorio-tag">⭐ <?= e($item['estado_conservacao']) ?></span>
                <span class="acessorio-tag">👁 <?= $item['visualizacoes'] ?> visualizações</span>
            </div>
            <?php if (isset($_SESSION['id_usuario'])): ?>
                <a href="toggle_favorito.php?id=<?= $id ?>&volta=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                   class="btn <?= $e_fav ? 'btn-perigo' : 'btn-ghost' ?>">
                    <?= $e_fav ? '❤️ Salvo' : '🤍 Salvar' ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if ($item['descricao']): ?>
        <div style="margin-top:24px;">
            <h3 style="font-size:17px;font-weight:800;margin-bottom:10px;">📝 Descrição</h3>
            <p class="acessorio-desc"><?= nl2br(e($item['descricao'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Localização -->
        <?php if ($item['cidade']): ?>
        <div class="acessorio-localizacao" style="margin-top:20px;">
            <h4>📍 Localização</h4>
            <?php if ($item['rua']): ?>
                <div class="loc-linha"><span>🏠</span><?= e($item['rua'].($item['numero'] ? ', '.$item['numero'] : '').($item['complemento'] ? ' '.$item['complemento'] : '')) ?></div>
            <?php endif; ?>
            <?php if ($item['bairro']): ?>
                <div class="loc-linha"><span>🏘️</span><?= e($item['bairro']) ?></div>
            <?php endif; ?>
            <div class="loc-linha"><span>🗺️</span><?= e($item['cidade'].($item['estado'] ? ' - '.$item['estado'] : '')) ?></div>
            <?php if ($item['cep']): ?>
                <div class="loc-linha"><span>📮</span>CEP <?= e($item['cep']) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Avaliações -->
        <?php if ($avaliacoes): ?>
        <div style="margin-top:36px;">
            <h3 style="font-size:17px;font-weight:800;margin-bottom:16px;">
                ⭐ Avaliações do doador
                <span style="font-size:14px;color:var(--cinza-sub);font-weight:600;margin-left:6px;">
                    <?= number_format($media_nota,1,',','.') ?>/5 (<?= count($avaliacoes) ?> avaliações)
                </span>
            </h3>
            <div class="avaliacao-lista">
                <?php foreach ($avaliacoes as $av): ?>
                <div class="avaliacao-item">
                    <div class="avaliacao-header">
                        <div class="avaliacao-avatar" style="background:var(--laranja-lt);display:flex;align-items:center;justify-content:center;font-size:14px;">
                            <?php if ($av['avaliador_foto']): ?>
                                <img src="uploads/<?= e($av['avaliador_foto']) ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                            <?php else: ?>🐾<?php endif; ?>
                        </div>
                        <span class="avaliacao-nome"><?= e($av['avaliador_nome']) ?></span>
                        <?php echo estrelas_html((float)$av['nota']); ?>
                        <span class="avaliacao-data"><?= tempo_relativo($av['data_avaliacao']) ?></span>
                    </div>
                    <?php if ($av['comentario']): ?>
                        <p class="avaliacao-texto"><?= e($av['comentario']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Outros itens do doador -->
        <?php if ($outros_itens): ?>
        <div style="margin-top:40px;">
            <h3 style="font-size:17px;font-weight:800;margin-bottom:16px;">🎁 Outros itens de <?= e(explode(' ',$item['doador'])[0]) ?></h3>
            <div class="cards-grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;">
                <?php foreach ($outros_itens as $o): ?>
                <div class="card-acessorio">
                    <div class="card-foto" style="height:160px;">
                        <?php if ($o['foto']): ?>
                            <img src="uploads/<?= e($o['foto']) ?>" alt="<?= e($o['nome']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="card-foto-placeholder" style="font-size:40px;"><?= $item['cat_icone'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body" style="padding:12px;">
                        <a href="acessorio.php?id=<?= $o['id_acessorio'] ?>" class="card-nome" style="font-size:14px;"><?= e($o['nome']) ?></a>
                        <div class="card-meta-item" style="font-size:11px;">📍 <?= e($o['cidade'] ?? '—') ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Direita: card doador + solicitar -->
    <div>
        <div class="doador-card">
            <!-- Doador -->
            <div class="doador-header">
                <div class="doador-avatar-ph">
                    <?php if ($item['doador_foto']): ?>
                        <img class="doador-avatar" src="uploads/<?= e($item['doador_foto']) ?>" alt="">
                    <?php else: ?>🐾<?php endif; ?>
                </div>
                <div>
                    <div class="doador-nome"><?= e($item['doador']) ?></div>
                    <div class="doador-tipo"><?= icone_tipo($item['doador_tipo']).' '.ucfirst($item['doador_tipo']) ?></div>
                    <?php if ($media_nota > 0): echo estrelas_html($media_nota); endif; ?>
                </div>
            </div>
            <?php if ($item['doador_bio']): ?>
                <p style="font-size:13px;color:var(--cinza-sub);margin-bottom:14px;"><?= e(truncar($item['doador_bio'],120)) ?></p>
            <?php endif; ?>
            <div style="font-size:12px;color:var(--cinza-sub);margin-bottom:16px;">
                📅 Membro desde <?= date('M/Y', strtotime($item['doador_desde'])) ?>
            </div>

            <hr class="form-divider">

            <!-- Solicitar -->
            <?php if ($erro): ?><div class="alerta alerta-erro"><span class="alerta-icon">⚠️</span><?= e($erro) ?></div><?php endif; ?>
            <?php if ($sucesso): ?><div class="alerta alerta-sucesso"><span class="alerta-icon">✅</span><?= e($sucesso) ?></div><?php endif; ?>

            <?php if ($item['status'] === 'doado'): ?>
                <div class="alerta alerta-info"><span class="alerta-icon">ℹ️</span>Este item já foi doado.</div>

            <?php elseif (!isset($_SESSION['id_usuario'])): ?>
                <p style="font-size:14px;color:var(--cinza-sub);margin-bottom:14px;">Faça login para solicitar este item.</p>
                <a href="login.php?volta=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primario btn-block">Fazer Login</a>

            <?php elseif ($_SESSION['id_usuario'] == $item['id_usuario']): ?>
                <div class="alerta alerta-aviso"><span class="alerta-icon">⚡</span>Este é seu item.</div>
                <a href="perfil.php" class="btn btn-ghost btn-block">Gerenciar no perfil</a>

            <?php elseif ($ja_solicitou): ?>
                <div class="alerta alerta-sucesso"><span class="alerta-icon">✅</span>Você já solicitou este item.</div>
                <a href="minhas_solicitacoes.php" class="btn btn-secundario btn-block">Ver minhas solicitações</a>

            <?php elseif ($pode_solicitar): ?>
                <h4 style="font-size:15px;font-weight:800;margin-bottom:10px;">💌 Enviar solicitação</h4>
                <form method="POST">
                    <div class="form-grupo" style="margin-bottom:12px;">
                        <label>Sua mensagem para o doador *</label>
                        <textarea name="mensagem" rows="4" placeholder="Explique por que você precisa do item, onde mora, quando poderia retirar..." required maxlength="1000"></textarea>
                    </div>
                    <button type="submit" name="solicitar" class="btn btn-primario btn-block">📬 Enviar Solicitação</button>
                </form>

            <?php else: ?>
                <div class="alerta alerta-aviso"><span class="alerta-icon">⚡</span>Item indisponível no momento.</div>
            <?php endif; ?>

            <?php if ($item['doador_tel'] && isset($_SESSION['id_usuario']) && ($ja_solicitou || $_SESSION['id_usuario'] == $item['id_usuario'])): ?>
                <div style="margin-top:14px;padding:12px;background:var(--verde-lt);border-radius:var(--radius-sm);font-size:13px;text-align:center;">
                    📱 <strong>Contato:</strong> <?= e($item['doador_tel']) ?>
                </div>
            <?php endif; ?>

            <hr class="form-divider">
            <div style="font-size:12px;color:var(--cinza-sub);text-align:center;">
                Publicado <?= tempo_relativo($item['data_cadastro']) ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/config/footer.php'; ?>