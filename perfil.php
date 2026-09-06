<?php
session_start();
require_once __DIR__ . "/config/conexao.php";
require_once __DIR__ . "/config/helpers.php";
if (!isset($_SESSION['id_usuario'])) { header("Location: login.php"); exit; }

$titulo_pagina = 'Meu Perfil';
$id = $_SESSION['id_usuario'];

// Busca dados completos
$stmt = $pdo->prepare("SELECT u.*, e.cep, e.rua, e.numero, e.complemento, e.bairro, e.cidade AS end_cidade, e.estado AS end_estado
                        FROM usuario u LEFT JOIN endereco_usuario e ON e.id_usuario=u.id_usuario WHERE u.id_usuario=?");
$stmt->execute([$id]); $u = $stmt->fetch();

// Stats
function stat(PDO $pdo, string $sql, array $p=[]) { $s=$pdo->prepare($sql); $s->execute($p); return (int)$s->fetchColumn(); }
$st_doados    = stat($pdo,"SELECT COUNT(*) FROM acessorio WHERE id_usuario=? AND status='doado'",[$id]);
$st_anuncios  = stat($pdo,"SELECT COUNT(*) FROM acessorio WHERE id_usuario=? AND status='disponivel'",[$id]);
$st_recebidas = stat($pdo,"SELECT COUNT(*) FROM solicitacao_doacao s INNER JOIN acessorio a ON s.id_acessorio=a.id_acessorio WHERE a.id_usuario=?",[$id]);
$st_feitas    = stat($pdo,"SELECT COUNT(*) FROM solicitacao_doacao WHERE id_usuario=?",[$id]);

[$media_nota,$total_aval] = $pdo->prepare("SELECT AVG(nota),COUNT(*) FROM avaliacao WHERE id_avaliado=?")->execute([$id]) ?
    $pdo->query("SELECT AVG(nota),COUNT(*) FROM avaliacao WHERE id_avaliado=$id")->fetch(PDO::FETCH_NUM) : [0,0];
$media_nota = round((float)($media_nota??0),1);

// Meus itens
$meus = $pdo->prepare("SELECT a.*,c.icone FROM acessorio a INNER JOIN categoria_acessorio c ON a.id_categoria=c.id_categoria WHERE a.id_usuario=? ORDER BY a.data_cadastro DESC LIMIT 12");
$meus->execute([$id]); $meus_itens = $meus->fetchAll();

$erro_perfil = $suc_perfil = '';

// Salvar perfil
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['salvar_perfil'])) {
    $nome        = trim($_POST['nome']       ?? '');
    $telefone    = trim($_POST['telefone']   ?? '');
    $bio         = trim($_POST['bio']        ?? '');
    $senha_nova  = $_POST['senha_nova']      ?? '';
    $senha_atual = $_POST['senha_atual']     ?? '';
    $cep         = preg_replace('/\D/','',$_POST['cep']??'');
    $rua         = trim($_POST['rua']        ?? '');
    $numero      = trim($_POST['numero']     ?? '');
    $complemento = trim($_POST['complemento']?? '');
    $bairro      = trim($_POST['bairro']     ?? '');
    $cidade_end  = trim($_POST['cidade']     ?? '');
    $estado_end  = trim($_POST['estado']     ?? '');

    if (!$nome) {
        $erro_perfil = 'Nome não pode ser vazio.';
    } else {
        if ($senha_nova) {
            if (strlen($senha_nova) < 6) { $erro_perfil = 'Nova senha deve ter pelo menos 6 caracteres.'; }
            elseif (!password_verify($senha_atual, $u['senha'])) { $erro_perfil = 'Senha atual incorreta.'; }
        }
        if (!$erro_perfil) {
            $pdo->beginTransaction();
            try {
                $foto_nome = $u['foto_perfil'];
                if (!empty($_FILES['foto_perfil']['name']) && $_FILES['foto_perfil']['error']===UPLOAD_ERR_OK) {
                    $nf = salvar_imagem($_FILES['foto_perfil'],'perfil');
                    if ($nf) {
                        if ($foto_nome && file_exists(__DIR__.'/uploads/'.$foto_nome)) unlink(__DIR__.'/uploads/'.$foto_nome);
                        $foto_nome = $nf;
                    }
                }
                $sql_u = "UPDATE usuario SET nome=?,telefone=?,bio=?,foto_perfil=?" . ($senha_nova?",senha=?":"") . " WHERE id_usuario=?";
                $pars  = [$nome,$telefone,$bio,$foto_nome];
                if ($senha_nova) $pars[] = password_hash($senha_nova,PASSWORD_DEFAULT);
                $pars[] = $id;
                $pdo->prepare($sql_u)->execute($pars);
                $_SESSION['nome'] = $nome;
                $_SESSION['foto_perfil'] = $foto_nome;

                if (!empty($cep) && strlen($cep)===8) {
                    $cep_fmt = substr($cep,0,5).'-'.substr($cep,5,3);
                    $existe = $pdo->prepare("SELECT 1 FROM endereco_usuario WHERE id_usuario=?");
                    $existe->execute([$id]);
                    if ($existe->fetchColumn()) {
                        $pdo->prepare("UPDATE endereco_usuario SET cep=?,rua=?,numero=?,complemento=?,bairro=?,cidade=?,estado=? WHERE id_usuario=?")
                            ->execute([$cep_fmt,$rua,$numero,$complemento,$bairro,$cidade_end,$estado_end,$id]);
                    } else {
                        $pdo->prepare("INSERT INTO endereco_usuario (cep,rua,numero,complemento,bairro,cidade,estado,id_usuario) VALUES (?,?,?,?,?,?,?,?)")
                            ->execute([$cep_fmt,$rua,$numero,$complemento,$bairro,$cidade_end,$estado_end,$id]);
                    }
                }
                $pdo->commit();
                $suc_perfil = 'Perfil atualizado com sucesso!';
                // Recarregar dados
                $stmt->execute([$id]); $u = $stmt->fetch();
            } catch(Exception $ex) { $pdo->rollBack(); $erro_perfil = 'Erro ao salvar. Tente novamente.'; }
        }
    }
}

// Excluir item
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['excluir_item'])) {
    $iid = (int)($_POST['id_item']??0);
    $verif = $pdo->prepare("SELECT foto FROM acessorio WHERE id_acessorio=? AND id_usuario=?");
    $verif->execute([$iid,$id]);
    $acc = $verif->fetch();
    if ($acc) {
        if ($acc['foto'] && file_exists(__DIR__.'/uploads/'.$acc['foto'])) unlink(__DIR__.'/uploads/'.$acc['foto']);
        $pdo->prepare("UPDATE acessorio SET status='inativo' WHERE id_acessorio=?")->execute([$iid]);
        $suc_perfil = 'Item removido com sucesso.';
        $meus->execute([$id]); $meus_itens = $meus->fetchAll();
    }
}

include __DIR__ . '/config/header.php';
?>

<div class="mini-hero">
    <p class="mh-breadcrumb"><a href="index.php">Início</a> › Meu Perfil</p>
    <h1>👤 Meu Perfil</h1>
</div>

<div class="dash-layout">
    <!-- Sidebar -->
    <div class="dash-sidebar">
        <div class="perfil-card">
            <div class="perfil-avatar-wrap">
                <?php if ($u['foto_perfil']): ?>
                    <img class="perfil-avatar" src="uploads/<?= e($u['foto_perfil']) ?>" alt="">
                <?php else: ?>
                    <div class="perfil-avatar-placeholder">🐾</div>
                <?php endif; ?>
                <label for="foto-trigger" class="perfil-edit-foto" title="Alterar foto">✏️</label>
            </div>
            <h2><?= e($u['nome']) ?></h2>
            <p class="perfil-tipo"><?= icone_tipo($u['tipo_usuario']).' '.ucfirst($u['tipo_usuario']) ?></p>
            <?php if ($media_nota > 0): ?>
                <div class="perfil-estrelas"><?php echo estrelas_html($media_nota); ?> <small style="color:var(--cinza-sub)">(<?= $total_aval ?>)</small></div>
            <?php endif; ?>
            <div class="dash-stats">
                <div class="dash-stat"><strong><?= $st_doados ?></strong><span>Doados</span></div>
                <div class="dash-stat"><strong><?= $st_anuncios ?></strong><span>Anúncios</span></div>
                <div class="dash-stat"><strong><?= $st_feitas ?></strong><span>Solicitações</span></div>
                <div class="dash-stat"><strong><?= $st_recebidas ?></strong><span>Recebidas</span></div>
            </div>
        </div>

        <nav class="dash-nav">
            <a href="#editar" class="ativo">✏️ Editar Dados</a>
            <a href="#endereco">📍 Endereço</a>
            <a href="#senha">🔒 Trocar Senha</a>
            <a href="#meus-itens">🎁 Meus Itens</a>
            <a href="minhas_solicitacoes.php">📋 Solicitações</a>
            <a href="solicitacoes_recebidas.php">📬 Recebidas</a>
            <a href="favoritos.php">❤️ Favoritos</a>
            <a href="notificacoes.php">🔔 Notificações</a>
        </nav>
    </div>

    <!-- Main -->
    <div class="dash-main">
        <?php if ($erro_perfil): ?><div class="alerta alerta-erro"><span class="alerta-icon">⚠️</span><?= e($erro_perfil) ?></div><?php endif; ?>
        <?php if ($suc_perfil): ?><div class="alerta alerta-sucesso"><span class="alerta-icon">✅</span><?= e($suc_perfil) ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="salvar_perfil" value="1">

            <!-- Foto oculta trigger -->
            <input type="file" id="foto-trigger" name="foto_perfil" accept="image/*" style="display:none" onchange="previewPerfil(this)">

            <!-- Dados pessoais -->
            <div class="dash-section" id="editar">
                <div class="dash-section-titulo">✏️ Dados Pessoais</div>
                <div class="form-row">
                    <div class="form-grupo">
                        <label>Nome completo *</label>
                        <input type="text" name="nome" required value="<?= e($u['nome']) ?>">
                    </div>
                    <div class="form-grupo">
                        <label>Telefone / WhatsApp</label>
                        <input type="tel" name="telefone" placeholder="(11) 99999-9999" value="<?= e($u['telefone']??'') ?>">
                    </div>
                </div>
                <div class="form-grupo">
                    <label>E-mail</label>
                    <input type="email" value="<?= e($u['email']) ?>" readonly style="background:var(--creme-esc);color:var(--cinza-sub)">
                    <span class="form-hint">O e-mail não pode ser alterado.</span>
                </div>
                <div class="form-grupo">
                    <label>Bio / Sobre você</label>
                    <textarea name="bio" rows="3" placeholder="Conte sobre você e seus pets..."><?= e($u['bio']??'') ?></textarea>
                </div>
            </div>

            <!-- Endereço via CEP -->
            <div class="dash-section" id="endereco">
                <div class="dash-section-titulo">📍 Endereço (via CEP)</div>
                <p class="form-hint" style="margin-bottom:16px;">Digite o CEP — os campos de rua, bairro, cidade e estado são preenchidos automaticamente.</p>
                <?php
                $cep_prefix = 'perf';
                $cep_dados  = [
                    'cep'         => $u['cep'] ?? '',
                    'rua'         => $u['rua'] ?? '',
                    'numero'      => $u['numero'] ?? '',
                    'complemento' => $u['complemento'] ?? '',
                    'bairro'      => $u['bairro'] ?? '',
                    'cidade'      => $u['end_cidade'] ?? '',
                    'estado'      => $u['end_estado'] ?? '',
                ];
                include __DIR__ . '/config/cep_fields.php';
                ?>
            </div>

            <!-- Trocar senha -->
            <div class="dash-section" id="senha">
                <div class="dash-section-titulo">🔒 Trocar Senha</div>
                <p class="form-hint" style="margin-bottom:16px;">Deixe em branco para não alterar a senha.</p>
                <div class="form-row">
                    <div class="form-grupo">
                        <label>Senha atual</label>
                        <input type="password" name="senha_atual" placeholder="••••••••">
                    </div>
                    <div class="form-grupo">
                        <label>Nova senha</label>
                        <input type="password" name="senha_nova" placeholder="Mínimo 6 caracteres">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primario btn-lg">💾 Salvar Alterações</button>
        </form>

        <!-- Meus itens -->
        <div class="dash-section" id="meus-itens">
            <div class="dash-section-titulo">🎁 Meus Acessórios</div>
            <?php if (!$meus_itens): ?>
                <div class="estado-vazio" style="padding:40px 20px;">
                    <span class="ev-icon">📦</span>
                    <h3>Nenhum item cadastrado</h3>
                    <a href="cadastrar_acessorio.php" class="btn btn-primario">🎁 Cadastrar agora</a>
                </div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <?php foreach ($meus_itens as $m): ?>
                    <div style="display:flex;align-items:center;gap:14px;padding:14px;background:var(--creme);border-radius:var(--radius-md);border:1px solid var(--creme-borda);">
                        <div style="width:52px;height:52px;border-radius:var(--radius-sm);background:var(--laranja-lt);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;overflow:hidden;">
                            <?php if ($m['foto']): ?><img src="uploads/<?= e($m['foto']) ?>" style="width:100%;height:100%;object-fit:cover;"><?php else: ?><?= $m['icone'] ?><?php endif; ?>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <a href="acessorio.php?id=<?= $m['id_acessorio'] ?>" style="font-weight:800;font-size:14px;color:#1A1A1A;text-decoration:none;"><?= e($m['nome']) ?></a>
                            <div style="display:flex;gap:8px;margin-top:4px;flex-wrap:wrap;">
                                <span class="status-pill status-<?= $m['status'] ?>"><?= ucfirst($m['status']) ?></span>
                                <span style="font-size:12px;color:var(--cinza-sub);">👁 <?= $m['visualizacoes'] ?> · <?= tempo_relativo($m['data_cadastro']) ?></span>
                            </div>
                        </div>
                        <?php if ($m['status']==='disponivel' || $m['status']==='inativo'): ?>
                        <form method="POST" onsubmit="return confirm('Tem certeza que deseja remover este item?')">
                            <input type="hidden" name="id_item" value="<?= $m['id_acessorio'] ?>">
                            <button type="submit" name="excluir_item" class="btn btn-perigo btn-sm">🗑️</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:16px;">
                    <a href="cadastrar_acessorio.php" class="btn btn-secundario">➕ Cadastrar novo item</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="config/cep.js"></script>
<script>
function previewPerfil(input) {
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => {
            const av = document.querySelector('.perfil-avatar, .perfil-avatar-placeholder');
            if (av) { av.src = e.target.result; av.style.display='block'; }
        };
        r.readAsDataURL(input.files[0]);
    }
}
// Scroll suave para seções
document.querySelectorAll('.dash-nav a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        e.preventDefault();
        const t = document.querySelector(a.getAttribute('href'));
        if (t) t.scrollIntoView({behavior:'smooth',block:'start'});
        document.querySelectorAll('.dash-nav a').forEach(x=>x.classList.remove('ativo'));
        a.classList.add('ativo');
    });
});
</script>
<?php include __DIR__ . '/config/footer.php'; ?>
