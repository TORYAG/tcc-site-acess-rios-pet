<?php
session_start();
require_once __DIR__ . "/config/conexao.php";
require_once __DIR__ . "/config/helpers.php";

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php?volta=" . urlencode($_SERVER['REQUEST_URI'])); exit;
}

$titulo_pagina = 'Cadastrar Acessório';
$categorias = $pdo->query("SELECT * FROM categoria_acessorio ORDER BY nome_categoria")->fetchAll();
$pets       = $pdo->query("SELECT * FROM pet ORDER BY tipo_pet, porte")->fetchAll();
$erro = $sucesso = ''; $id_novo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome        = trim($_POST['nome']               ?? '');
    $descricao   = trim($_POST['descricao']          ?? '');
    $estado_cons = trim($_POST['estado_conservacao'] ?? '');
    $id_categoria = (int)($_POST['id_categoria']     ?? 0);
    $id_pet       = (int)($_POST['id_pet']           ?? 0);
    $cep          = preg_replace('/\D/', '', $_POST['cep'] ?? '');
    $rua          = trim($_POST['rua']               ?? '');
    $numero       = trim($_POST['numero']            ?? '');
    $complemento  = trim($_POST['complemento']       ?? '');
    $bairro       = trim($_POST['bairro']            ?? '');
    $cidade       = trim($_POST['cidade']            ?? '');
    $estado_uf    = trim($_POST['estado']            ?? '');

    if (!$nome || !$estado_cons || !$cidade || !$id_categoria || !$id_pet) {
        $erro = 'Preencha todos os campos obrigatórios (nome, estado de conservação, cidade via CEP, categoria e tipo de pet).';
    } else {
        $foto_nome = null;
        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto_nome = salvar_imagem($_FILES['foto'], 'acessorio');
            if (!$foto_nome) $erro = 'Erro no upload. Use JPG, PNG, GIF ou WebP — máx. 5 MB.';
        }

        if (!$erro) {
            $cep_fmt = strlen($cep)===8 ? substr($cep,0,5).'-'.substr($cep,5,3) : '';
            $sql = "INSERT INTO acessorio (nome,descricao,estado_conservacao,cep,rua,numero,complemento,bairro,cidade,estado,status,id_usuario,id_categoria,id_pet,foto)
                    VALUES (?,?,?,?,?,?,?,?,?,?,'disponivel',?,?,?,?)";
            $pdo->prepare($sql)->execute([$nome,$descricao,$estado_cons,$cep_fmt,$rua,$numero,$complemento,$bairro,$cidade,$estado_uf,
                                          $_SESSION['id_usuario'],$id_categoria,$id_pet,$foto_nome]);
            $id_novo = (int)$pdo->lastInsertId();
            $sucesso = 'Acessório publicado com sucesso!';
        }
    }
}

include __DIR__ . '/config/header.php';
?>

<div class="mini-hero">
    <p class="mh-breadcrumb"><a href="index.php">Início</a> › Cadastrar Acessório</p>
    <h1>🎁 Publicar Acessório para Doação</h1>
    <p>Preencha as informações do item. O CEP preencherá os dados de localização automaticamente.</p>
</div>

<div style="padding:36px 6%;display:grid;grid-template-columns:1fr 300px;gap:28px;max-width:1100px;margin:0 auto;" class="cad-layout">

    <div>
        <?php if ($erro): ?><div class="alerta alerta-erro"><span class="alerta-icon">⚠️</span><?= e($erro) ?></div><?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="alerta alerta-sucesso"><span class="alerta-icon">✅</span><?= e($sucesso) ?></div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px;">
                <a href="acessorio.php?id=<?= $id_novo ?>" class="btn btn-primario">👁️ Ver publicação</a>
                <a href="cadastrar_acessorio.php" class="btn btn-secundario">➕ Cadastrar outro</a>
                <a href="index.php" class="btn btn-ghost">🏠 Início</a>
            </div>
        <?php else: ?>

        <div class="form-card" style="max-width:none">
            <form method="POST" enctype="multipart/form-data">
                <p class="form-titulo">📦 Informações do Acessório</p>

                <div class="form-grupo">
                    <label>Nome do acessório *</label>
                    <input type="text" name="nome" placeholder="Ex: Coleira ajustável para cão médio" required maxlength="100" value="<?= e($_POST['nome'] ?? '') ?>">
                </div>
                <div class="form-grupo">
                    <label>Descrição</label>
                    <textarea name="descricao" placeholder="Descreva o item: cor, tamanho, material, motivo da doação..." rows="3"><?= e($_POST['descricao'] ?? '') ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-grupo">
                        <label>Estado de conservação *</label>
                        <select name="estado_conservacao" required>
                            <option value="">Selecione...</option>
                            <?php foreach (['Novo','Ótimo','Bom','Regular','Precisa de reparo'] as $ec): ?>
                                <option value="<?= e($ec) ?>" <?= ($_POST['estado_conservacao']??'')===$ec?'selected':'' ?>><?= e($ec) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-grupo">
                        <label>Categoria *</label>
                        <select name="id_categoria" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id_categoria'] ?>" <?= (int)($_POST['id_categoria']??0)===(int)$cat['id_categoria']?'selected':'' ?>>
                                    <?= e($cat['icone'].' '.$cat['nome_categoria']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-grupo">
                    <label>Para qual tipo de pet? *</label>
                    <select name="id_pet" required>
                        <option value="">Selecione...</option>
                        <?php $tipo_a = ''; foreach ($pets as $p): ?>
                            <?php if ($p['tipo_pet'] !== $tipo_a): if ($tipo_a) echo '</optgroup>'; echo '<optgroup label="'.e($p['tipo_pet']).'">'; $tipo_a = $p['tipo_pet']; endif; ?>
                            <option value="<?= $p['id_pet'] ?>" <?= (int)($_POST['id_pet']??0)===(int)$p['id_pet']?'selected':'' ?>><?= e($p['porte']) ?></option>
                        <?php endforeach; if ($tipo_a) echo '</optgroup>'; ?>
                    </select>
                </div>

                <hr class="form-divider">
                <p class="form-titulo">📸 Foto do Acessório</p>

                <div class="upload-area" id="upload-area">
                    <input type="file" name="foto" accept="image/*" id="foto-input" onchange="previewFoto(this)">
                    <span class="up-icon">📷</span>
                    <p>Arraste uma foto ou <strong>clique para selecionar</strong></p>
                    <p style="font-size:12px;color:var(--cinza-sub);margin-top:4px;">JPG, PNG, GIF ou WebP — máx. 5 MB</p>
                </div>
                <div class="upload-preview" id="upload-preview"><img id="preview-img" src="" alt="Preview"></div>

                <hr class="form-divider">
                <p class="form-titulo">📍 Localização do Item</p>
                <p class="form-hint" style="margin-bottom:16px;">Informe o CEP — rua, bairro, cidade e estado serão preenchidos automaticamente.</p>

                <?php
                $cep_prefix   = 'item';
                $cep_dados    = [];
                $cep_required = true;
                include __DIR__ . '/config/cep_fields.php';
                ?>

                <button type="submit" class="btn btn-primario btn-lg btn-block" style="margin-top:16px;">
                    🎁 Publicar Acessório para Doação
                </button>
            </form>
        </div>

        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div>
        <div class="form-card" style="position:sticky;top:84px;">
            <div style="text-align:center;font-size:48px;margin-bottom:14px;">💡</div>
            <h3 style="font-family:'Playfair Display',serif;font-size:17px;margin-bottom:14px;color:#1A1A1A;">Dicas para uma boa doação</h3>
            <ul style="font-size:13px;color:var(--cinza-sub);line-height:1.8;padding-left:16px;">
                <li>Tire fotos com boa iluminação</li>
                <li>Descreva o estado real do item</li>
                <li>Informe dimensões se relevante</li>
                <li>Responda rápido às solicitações</li>
                <li>O CEP correto facilita a retirada</li>
            </ul>
            <hr class="form-divider">
            <h4 style="font-size:13px;font-weight:800;margin-bottom:10px;">O que posso doar?</h4>
            <div style="display:flex;flex-wrap:wrap;gap:6px;">
                <?php foreach ($categorias as $cat): ?>
                    <span class="tag-pill"><?= e($cat['icone'].' '.$cat['nome_categoria']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>@media(max-width:768px){.cad-layout{grid-template-columns:1fr!important}}</style>
<script src="config/cep.js"></script>
<script>
function previewFoto(input) {
    const prev = document.getElementById('upload-preview');
    const img  = document.getElementById('preview-img');
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => { img.src = e.target.result; prev.style.display='block'; };
        r.readAsDataURL(input.files[0]);
    }
}
const area = document.getElementById('upload-area');
if (area) {
    area.addEventListener('dragover', e=>{e.preventDefault();area.classList.add('drag-over');});
    area.addEventListener('dragleave', ()=>area.classList.remove('drag-over'));
    area.addEventListener('drop', e=>{
        e.preventDefault(); area.classList.remove('drag-over');
        const fi = document.getElementById('foto-input');
        fi.files = e.dataTransfer.files; previewFoto(fi);
    });
}
</script>
<?php include __DIR__ . '/config/footer.php'; ?>
