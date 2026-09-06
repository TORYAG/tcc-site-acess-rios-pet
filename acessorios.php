<?php
session_start();
require_once __DIR__ . "/config/conexao.php";
require_once __DIR__ . "/config/helpers.php";

$titulo_pagina = 'Acessórios';
$busca     = trim($_GET['busca']     ?? '');
$cidade    = trim($_GET['cidade']    ?? '');
$estado_uf = trim($_GET['estado_uf'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');
$tipo_pet  = trim($_GET['tipo_pet']  ?? '');
$conserv   = trim($_GET['conserv']   ?? '');
$ordem     = in_array($_GET['ordem']??'',['recente','views','az'])?$_GET['ordem']:'recente';

$por_pagina = 12;
$pagina     = max(1,(int)($_GET['pagina'] ?? 1));
$inicio     = ($pagina-1)*$por_pagina;

$where = "WHERE a.status='disponivel'";
$params = [];
if ($busca)     { $where.=" AND (a.nome LIKE ? OR a.descricao LIKE ?)"; $params[]="%$busca%"; $params[]="%$busca%"; }
if ($cidade)    { $where.=" AND a.cidade=?";   $params[]=$cidade; }
if ($estado_uf) { $where.=" AND a.estado=?";   $params[]=$estado_uf; }
if ($categoria) { $where.=" AND a.id_categoria=?"; $params[]=$categoria; }
if ($tipo_pet)  { $where.=" AND p.tipo_pet=?"; $params[]=$tipo_pet; }
if ($conserv)   { $where.=" AND a.estado_conservacao=?"; $params[]=$conserv; }

$ob = match($ordem){ 'views'=>'a.visualizacoes DESC','az'=>'a.nome ASC',default=>'a.data_cadastro DESC' };

$stmt = $pdo->prepare("SELECT a.*, u.nome AS doador, u.foto_perfil AS doador_foto,
       c.nome_categoria, c.icone AS cat_icone, p.tipo_pet, p.porte
       FROM acessorio a
       INNER JOIN usuario u ON a.id_usuario=u.id_usuario
       INNER JOIN categoria_acessorio c ON a.id_categoria=c.id_categoria
       INNER JOIN pet p ON a.id_pet=p.id_pet
       $where ORDER BY $ob LIMIT $inicio,$por_pagina");
$stmt->execute($params);
$acessorios = $stmt->fetchAll();

$st2 = $pdo->prepare("SELECT COUNT(*) FROM acessorio a INNER JOIN pet p ON a.id_pet=p.id_pet $where");
$st2->execute($params);
$total   = (int)$st2->fetchColumn();
$paginas = (int)ceil($total/$por_pagina);

$cidades    = $pdo->query("SELECT DISTINCT cidade FROM acessorio WHERE status='disponivel' AND cidade IS NOT NULL ORDER BY cidade")->fetchAll(PDO::FETCH_COLUMN);
$estados_uf_list = $pdo->query("SELECT DISTINCT estado FROM acessorio WHERE status='disponivel' AND estado IS NOT NULL ORDER BY estado")->fetchAll(PDO::FETCH_COLUMN);
$categorias = $pdo->query("SELECT * FROM categoria_acessorio ORDER BY nome_categoria")->fetchAll();
$tipos_pet  = $pdo->query("SELECT DISTINCT tipo_pet FROM pet ORDER BY tipo_pet")->fetchAll(PDO::FETCH_COLUMN);
$ec_lista   = ['Novo','Ótimo','Bom','Regular','Precisa de reparo'];

$favs = [];
if (isset($_SESSION['id_usuario'])) {
    $sf=$pdo->prepare("SELECT id_acessorio FROM favorito WHERE id_usuario=?");
    $sf->execute([$_SESSION['id_usuario']]); $favs=$sf->fetchAll(PDO::FETCH_COLUMN);
}

include __DIR__ . '/config/header.php';
?>

<div class="mini-hero">
    <p class="mh-breadcrumb"><a href="index.php">Início</a> › Acessórios</p>
    <h1>🔍 Explorar Acessórios</h1>
    <p>Encontre acessórios para pets disponíveis para doação em todo o Brasil.</p>
</div>

<div class="filtros-barra">
    <form method="GET" class="filtros-form">
        <input type="text" name="busca" placeholder="🔍 Buscar..." value="<?= e($busca) ?>">
        <select name="cidade">
            <option value="">📍 Cidade</option>
            <?php foreach ($cidades as $c): ?><option value="<?= e($c) ?>" <?= $c===$cidade?'selected':'' ?>><?= e($c) ?></option><?php endforeach; ?>
        </select>
        <select name="estado_uf">
            <option value="">🗺️ Estado</option>
            <?php foreach ($estados_uf_list as $uf): ?><option value="<?= e($uf) ?>" <?= $uf===$estado_uf?'selected':'' ?>><?= e($uf) ?></option><?php endforeach; ?>
        </select>
        <select name="categoria">
            <option value="">🏷️ Categoria</option>
            <?php foreach ($categorias as $cat): ?><option value="<?= $cat['id_categoria'] ?>" <?= (string)$cat['id_categoria']===$categoria?'selected':'' ?>><?= e($cat['icone'].' '.$cat['nome_categoria']) ?></option><?php endforeach; ?>
        </select>
        <select name="tipo_pet">
            <option value="">🐾 Tipo de pet</option>
            <?php foreach ($tipos_pet as $tp): ?><option value="<?= e($tp) ?>" <?= $tp===$tipo_pet?'selected':'' ?>><?= e($tp) ?></option><?php endforeach; ?>
        </select>
        <select name="conserv">
            <option value="">⭐ Estado</option>
            <?php foreach ($ec_lista as $ec): ?><option value="<?= e($ec) ?>" <?= $ec===$conserv?'selected':'' ?>><?= e($ec) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primario">Filtrar</button>
        <?php if ($busca||$cidade||$estado_uf||$categoria||$tipo_pet||$conserv): ?>
            <a href="acessorios.php" class="btn-limpar">✕ Limpar</a>
        <?php endif; ?>
    </form>
    <div class="filtros-meta">
        <span class="filtros-resultado"><?= $total ?> item<?= $total!==1?'s':'' ?></span>
        <div class="filtros-ordenar">
            Ordenar:
            <select onchange="window.location.href=this.value">
                <option value="?<?= http_build_query(array_merge($_GET,['ordem'=>'recente'])) ?>" <?= $ordem==='recente'?'selected':'' ?>>Mais recentes</option>
                <option value="?<?= http_build_query(array_merge($_GET,['ordem'=>'views'])) ?>"   <?= $ordem==='views'?'selected':'' ?>>Mais vistos</option>
                <option value="?<?= http_build_query(array_merge($_GET,['ordem'=>'az'])) ?>"      <?= $ordem==='az'?'selected':'' ?>>A–Z</option>
            </select>
        </div>
    </div>
</div>

<div class="secao-cards">
    <div class="cards-grid">
        <?php if (!$acessorios): ?>
            <div class="estado-vazio">
                <span class="ev-icon">📦</span>
                <h3>Nenhum acessório encontrado</h3>
                <p>Tente outros filtros ou cadastre o primeiro item!</p>
                <a href="cadastrar_acessorio.php" class="btn btn-primario">🎁 Cadastrar acessório</a>
            </div>
        <?php endif; ?>
        <?php foreach ($acessorios as $item): $fav=in_array($item['id_acessorio'],$favs); ?>
        <div class="card-acessorio fade-up">
            <div class="card-foto">
                <?php if ($item['foto']): ?><img src="uploads/<?= e($item['foto']) ?>" alt="<?= e($item['nome']) ?>" loading="lazy"><?php else: ?><div class="card-foto-placeholder"><?= $item['cat_icone'] ?></div><?php endif; ?>
                <span class="card-badge-status badge-<?= $item['status'] ?>"><?= ucfirst($item['status']) ?></span>
                <?php if (isset($_SESSION['id_usuario'])): ?>
                    <a href="toggle_favorito.php?id=<?= $item['id_acessorio'] ?>&volta=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="card-fav-btn <?= $fav?'ativo':'' ?>"><?= $fav?'❤️':'🤍' ?></a>
                <?php endif; ?>
                <?php if ($item['visualizacoes']>0): ?><span class="card-views">👁 <?= $item['visualizacoes'] ?></span><?php endif; ?>
            </div>
            <div class="card-body">
                <span class="card-categoria"><?= e($item['cat_icone'].' '.$item['nome_categoria']) ?></span>
                <a href="acessorio.php?id=<?= $item['id_acessorio'] ?>" class="card-nome"><?= e($item['nome']) ?></a>
                <p class="card-desc"><?= e(truncar($item['descricao'],85)) ?></p>
                <div class="card-meta">
                    <div class="card-meta-item">🐾 <?= e($item['tipo_pet'].' — '.$item['porte']) ?></div>
                    <div class="card-meta-item">📍 <?= e($item['cidade']?($item['bairro']?$item['bairro'].', ':'').$item['cidade'].($item['estado']?' - '.$item['estado']:''):'—') ?></div>
                    <div class="card-meta-item">⭐ <?= e($item['estado_conservacao']??'—') ?></div>
                </div>
            </div>
            <div class="card-footer">
                <div class="card-doador">
                    <div class="card-doador-avatar"><?php if ($item['doador_foto']): ?><img src="uploads/<?= e($item['doador_foto']) ?>" alt=""><?php else: ?>🐾<?php endif; ?></div>
                    <span class="card-doador-nome"><?= e($item['doador']) ?></span>
                </div>
                <?php if (isset($_SESSION['id_usuario'])): ?>
                    <?php if ($_SESSION['id_usuario']!=$item['id_usuario']): ?>
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

    <?php if ($paginas>1): ?>
    <div class="paginacao">
        <?php if ($pagina>1): ?><a href="?<?= http_build_query(array_merge($_GET,['pagina'=>$pagina-1])) ?>">‹</a><?php endif; ?>
        <?php for ($i=1;$i<=$paginas;$i++): ?>
            <?php if ($i===$pagina): ?><span class="atual"><?= $i ?></span>
            <?php elseif(abs($i-$pagina)<=2): ?><a href="?<?= http_build_query(array_merge($_GET,['pagina'=>$i])) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($pagina<$paginas): ?><a href="?<?= http_build_query(array_merge($_GET,['pagina'=>$pagina+1])) ?>">›</a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/config/footer.php'; ?>
