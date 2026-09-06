<?php
session_start();
require_once __DIR__ . "/config/conexao.php";
require_once __DIR__ . "/config/helpers.php";

$titulo_pagina = 'Início';

// Filtros
$busca     = trim($_GET['busca']     ?? '');
$cidade    = trim($_GET['cidade']    ?? '');
$estado_uf = trim($_GET['estado_uf'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');
$conserv   = trim($_GET['conserv']   ?? '');
$ordem     = in_array($_GET['ordem'] ?? '', ['recente','views','az']) ? $_GET['ordem'] : 'recente';

// Paginação
$por_pagina = 9;
$pagina     = max(1, (int)($_GET['pagina'] ?? 1));
$inicio     = ($pagina - 1) * $por_pagina;

$where  = "WHERE a.status = 'disponivel'";
$params = [];

if ($busca)     { $where .= " AND (a.nome LIKE ? OR a.descricao LIKE ?)"; $params[] = "%$busca%"; $params[] = "%$busca%"; }
if ($cidade)    { $where .= " AND a.cidade = ?";   $params[] = $cidade; }
if ($estado_uf) { $where .= " AND a.estado = ?";   $params[] = $estado_uf; }
if ($categoria) { $where .= " AND a.id_categoria = ?"; $params[] = $categoria; }
if ($conserv)   { $where .= " AND a.estado_conservacao = ?"; $params[] = $conserv; }

$orderby = match($ordem) {
    'views'  => 'a.visualizacoes DESC',
    'az'     => 'a.nome ASC',
    default  => 'a.data_cadastro DESC',
};

$sql = "SELECT a.*, u.nome AS doador, u.foto_perfil AS doador_foto,
               c.nome_categoria, c.icone AS cat_icone, p.tipo_pet, p.porte
        FROM acessorio a
        INNER JOIN usuario u             ON a.id_usuario   = u.id_usuario
        INNER JOIN categoria_acessorio c ON a.id_categoria = c.id_categoria
        INNER JOIN pet p                 ON a.id_pet       = p.id_pet
        $where ORDER BY $orderby LIMIT $inicio, $por_pagina";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$acessorios = $stmt->fetchAll();

$total   = (int)$pdo->prepare("SELECT COUNT(*) FROM acessorio a $where")->execute($params) ? $pdo->prepare("SELECT COUNT(*) FROM acessorio a $where")->execute($params) : 0;
$st2 = $pdo->prepare("SELECT COUNT(*) FROM acessorio a $where");
$st2->execute($params);
$total   = (int)$st2->fetchColumn();
$paginas = (int)ceil($total / $por_pagina);

$cidades    = $pdo->query("SELECT DISTINCT cidade FROM acessorio WHERE status='disponivel' AND cidade IS NOT NULL ORDER BY cidade")->fetchAll(PDO::FETCH_COLUMN);
$estados_uf = $pdo->query("SELECT DISTINCT estado FROM acessorio WHERE status='disponivel' AND estado IS NOT NULL ORDER BY estado")->fetchAll(PDO::FETCH_COLUMN);
$categorias = $pdo->query("SELECT * FROM categoria_acessorio ORDER BY nome_categoria")->fetchAll();
$ec_lista   = ['Novo','Ótimo','Bom','Regular','Precisa de reparo'];

$favs = [];
if (isset($_SESSION['id_usuario'])) {
    $sf = $pdo->prepare("SELECT id_acessorio FROM favorito WHERE id_usuario=?");
    $sf->execute([$_SESSION['id_usuario']]);
    $favs = $sf->fetchAll(PDO::FETCH_COLUMN);
}

// Stats hero
$stat_itens   = $pdo->query("SELECT COUNT(*) FROM acessorio WHERE status='disponivel'")->fetchColumn();
$stat_doacoes = $pdo->query("SELECT COUNT(*) FROM acessorio WHERE status='doado'")->fetchColumn();
$stat_users   = $pdo->query("SELECT COUNT(*) FROM usuario WHERE ativo=1")->fetchColumn();
$stat_cidades = $pdo->query("SELECT COUNT(DISTINCT cidade) FROM acessorio WHERE status='disponivel' AND cidade IS NOT NULL")->fetchColumn();

include __DIR__ . '/config/header.php';
?>

<!-- HERO -->
<section class="hero">
    <div class="hero-texto">
        <div class="hero-tag">🐾 Plataforma gratuita de doação pet · Brasil</div>
        <h1>Doe com carinho,<br><em>receba com amor</em></h1>
        <p class="hero-sub">Conectamos doadores e receptores de acessórios para pets em todo o Brasil. Coleiras, caminhas, brinquedos e muito mais — 100% gratuito.</p>

        <div class="hero-stats">
            <div class="hero-stat">
                <strong><?= number_format($stat_itens,0,',','.') ?></strong>
                <span>Itens disponíveis</span>
            </div>
            <div class="hero-stat">
                <strong><?= number_format($stat_doacoes,0,',','.') ?></strong>
                <span>Doações realizadas</span>
            </div>
            <div class="hero-stat">
                <strong><?= number_format($stat_users,0,',','.') ?></strong>
                <span>Usuários</span>
            </div>
            <div class="hero-stat">
                <strong><?= number_format($stat_cidades,0,',','.') ?></strong>
                <span>Cidades</span>
            </div>
        </div>

        <div class="hero-btns">
            <a href="acessorios.php" class="btn btn-primario btn-lg">🔍 Ver Acessórios</a>
            <a href="<?= isset($_SESSION['id_usuario']) ? 'cadastrar_acessorio.php' : 'cadastro.php' ?>" class="btn btn-secundario btn-lg">🎁 Quero Doar</a>
        </div>
    </div>

    <div class="hero-visual">
        <div class="hero-card">
            <div class="hero-badge-float">✅ 100% Gratuito</div>
            <div class="hero-card-icon">🐶🐱🐰</div>
            <h3>Ajude um pet hoje</h3>
            <p>Itens que você não usa mais podem transformar a vida de um animal carente. Doe agora em minutos.</p>
            <br>
            <a href="acessorios.php" class="btn btn-primario btn-block" style="margin-top:4px;">Ver itens disponíveis →</a>
        </div>
        <div class="hero-mini-stats">
            <div class="hms-item"><strong><?= $stat_cidades ?></strong><span>Cidades ativas</span></div>
            <div class="hms-item"><strong>⭐ 4.8</strong><span>Avaliação média</span></div>
        </div>
    </div>
</section>

<!-- COMO FUNCIONA -->
<section class="como-funciona">
    <div class="secao-titulo">Como funciona?</div>
    <p class="secao-sub">Em 3 passos simples você já pode ajudar um pet</p>
    <div class="cf-grid">
        <div class="cf-card fade-up fade-up-1">
            <span class="cf-icon">📝</span>
            <div class="cf-num">1</div>
            <h4>Cadastre-se</h4>
            <p>Crie sua conta em menos de 1 minuto. Informe seu CEP para doações na sua região.</p>
        </div>
        <div class="cf-card fade-up fade-up-2">
            <span class="cf-icon">🎁</span>
            <div class="cf-num">2</div>
            <h4>Anuncie ou solicite</h4>
            <p>Cadastre itens para doação ou solicite aquele que você precisa — com foto e localização.</p>
        </div>
        <div class="cf-card fade-up fade-up-3">
            <span class="cf-icon">🤝</span>
            <div class="cf-num">3</div>
            <h4>Conecte-se</h4>
            <p>Doador e receptor combinam a entrega, avaliam a experiência e fazem a diferença juntos.</p>
        </div>
    </div>
</section>

<!-- FILTROS -->
<div class="filtros-barra">
    <form method="GET" class="filtros-form">
        <input type="text" name="busca" placeholder="🔍 Buscar acessório..." value="<?= e($busca) ?>">

        <select name="cidade">
            <option value="">📍 Qualquer cidade</option>
            <?php foreach ($cidades as $c): ?>
                <option value="<?= e($c) ?>" <?= $c === $cidade ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="estado_uf">
            <option value="">🗺️ Qualquer estado</option>
            <?php foreach ($estados_uf as $uf): ?>
                <option value="<?= e($uf) ?>" <?= $uf === $estado_uf ? 'selected' : '' ?>><?= e($uf) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="categoria">
            <option value="">🏷️ Todas as categorias</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id_categoria'] ?>" <?= (string)$cat['id_categoria'] === $categoria ? 'selected' : '' ?>>
                    <?= e($cat['icone'].' '.$cat['nome_categoria']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="conserv">
            <option value="">⭐ Qualquer estado</option>
            <?php foreach ($ec_lista as $ec): ?>
                <option value="<?= e($ec) ?>" <?= $ec === $conserv ? 'selected' : '' ?>><?= e($ec) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-primario">Filtrar</button>
        <?php if ($busca || $cidade || $estado_uf || $categoria || $conserv): ?>
            <a href="index.php" class="btn-limpar">✕ Limpar</a>
        <?php endif; ?>
    </form>

    <div class="filtros-meta">
        <span class="filtros-resultado"><?= $total ?> item<?= $total !== 1 ? 's' : '' ?> encontrado<?= $total !== 1 ? 's' : '' ?></span>
        <div class="filtros-ordenar">
            Ordenar:
            <select onchange="window.location.href=this.value">
                <option value="?<?= http_build_query(array_merge($_GET,['ordem'=>'recente'])) ?>" <?= $ordem==='recente'?'selected':'' ?>>Mais recentes</option>
                <option value="?<?= http_build_query(array_merge($_GET,['ordem'=>'views'])) ?>"   <?= $ordem==='views'  ?'selected':'' ?>>Mais vistos</option>
                <option value="?<?= http_build_query(array_merge($_GET,['ordem'=>'az'])) ?>"      <?= $ordem==='az'     ?'selected':'' ?>>A–Z</option>
            </select>
        </div>
    </div>
</div>

<!-- GRID -->
<div class="secao-cards">
    <div class="cards-grid">
        <?php if (!$acessorios): ?>
            <div class="estado-vazio">
                <span class="ev-icon">📦</span>
                <h3>Nenhum acessório encontrado</h3>
                <p>Tente outros filtros ou seja o primeiro a anunciar!</p>
                <a href="cadastrar_acessorio.php" class="btn btn-primario">🎁 Cadastrar acessório</a>
            </div>
        <?php endif; ?>

        <?php foreach ($acessorios as $item): $fav = in_array($item['id_acessorio'], $favs); ?>
        <div class="card-acessorio fade-up">
            <div class="card-foto">
                <?php if ($item['foto']): ?>
                    <img src="uploads/<?= e($item['foto']) ?>" alt="<?= e($item['nome']) ?>" loading="lazy">
                <?php else: ?>
                    <div class="card-foto-placeholder"><?= $item['cat_icone'] ?? '🎁' ?></div>
                <?php endif; ?>

                <span class="card-badge-status badge-<?= $item['status'] ?>"><?= ucfirst($item['status']) ?></span>

                <?php if (isset($_SESSION['id_usuario'])): ?>
                    <a href="toggle_favorito.php?id=<?= $item['id_acessorio'] ?>&volta=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                       class="card-fav-btn <?= $fav ? 'ativo' : '' ?>"
                       title="<?= $fav ? 'Remover dos favoritos' : 'Adicionar aos favoritos' ?>">
                        <?= $fav ? '❤️' : '🤍' ?>
                    </a>
                <?php endif; ?>

                <?php if ($item['visualizacoes'] > 0): ?>
                    <span class="card-views">👁 <?= $item['visualizacoes'] ?></span>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <span class="card-categoria"><?= e($item['cat_icone'].' '.$item['nome_categoria']) ?></span>
                <a href="acessorio.php?id=<?= $item['id_acessorio'] ?>" class="card-nome"><?= e($item['nome']) ?></a>
                <p class="card-desc"><?= e(truncar($item['descricao'], 85)) ?></p>
                <div class="card-meta">
                    <div class="card-meta-item">🐾 <?= e($item['tipo_pet'].' — '.$item['porte']) ?></div>
                    <div class="card-meta-item">📍 <?= e($item['cidade'] ? ($item['bairro'] ? $item['bairro'].', ' : '').$item['cidade'].($item['estado'] ? ' - '.$item['estado'] : '') : '—') ?></div>
                    <div class="card-meta-item">⭐ <?= e($item['estado_conservacao'] ?? '—') ?></div>
                </div>
            </div>

            <div class="card-footer">
                <div class="card-doador">
                    <div class="card-doador-avatar">
                        <?php if ($item['doador_foto']): ?>
                            <img src="uploads/<?= e($item['doador_foto']) ?>" alt="">
                        <?php else: ?>🐾<?php endif; ?>
                    </div>
                    <span class="card-doador-nome"><?= e($item['doador']) ?></span>
                </div>
                <?php if (isset($_SESSION['id_usuario'])): ?>
                    <?php if ($_SESSION['id_usuario'] != $item['id_usuario']): ?>
                        <a href="acessorio.php?id=<?= $item['id_acessorio'] ?>" class="btn btn-primario btn-sm">Solicitar</a>
                    <?php else: ?>
                        <span style="font-size:12px;color:var(--cinza-sub)">Seu item</span>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn btn-ghost btn-sm">Login</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- PAGINAÇÃO -->
    <?php if ($paginas > 1): ?>
    <div class="paginacao">
        <?php if ($pagina > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET,['pagina'=>$pagina-1])) ?>">‹</a>
        <?php endif; ?>
        <?php for ($i=1; $i<=$paginas; $i++): ?>
            <?php if ($i===$pagina): ?>
                <span class="atual"><?= $i ?></span>
            <?php elseif (abs($i-$pagina)<=2): ?>
                <a href="?<?= http_build_query(array_merge($_GET,['pagina'=>$i])) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($pagina < $paginas): ?>
            <a href="?<?= http_build_query(array_merge($_GET,['pagina'=>$pagina+1])) ?>">›</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- BANNER CTA -->
<div class="banner-cta">
    <h2>Tem itens que seu pet não usa mais?</h2>
    <p>Doe agora e ajude um pet que precisa. É rápido, gratuito e feito com amor.</p>
    <a href="<?= isset($_SESSION['id_usuario']) ? 'cadastrar_acessorio.php' : 'cadastro.php' ?>" class="btn btn-lg">🎁 Quero Doar Agora</a>
</div>

<?php include __DIR__ . '/config/footer.php'; ?>
