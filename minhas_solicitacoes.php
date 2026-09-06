<?php
session_start();
require_once __DIR__ . "/config/conexao.php";
require_once __DIR__ . "/config/helpers.php";
if (!isset($_SESSION['id_usuario'])) { header("Location: login.php"); exit; }

$titulo_pagina = 'Minhas Solicitações';
$id = $_SESSION['id_usuario'];
$filtro = $_GET['filtro'] ?? 'todas';

$where = "WHERE s.id_usuario=?"; $params = [$id];
if (in_array($filtro,['pendente','aceita','recusada','finalizada'])) { $where.=" AND s.status_solicitacao=?"; $params[]=$filtro; }

$stmt = $pdo->prepare(
    "SELECT s.*, a.nome AS item_nome, a.foto AS item_foto, a.id_usuario AS dono_id,
            u.nome AS doador, u.telefone AS doador_tel, u.email AS doador_email, u.foto_perfil AS doador_foto,
            av.nota AS minha_nota, av.comentario AS meu_comentario
     FROM solicitacao_doacao s
     INNER JOIN acessorio a ON s.id_acessorio=a.id_acessorio
     INNER JOIN usuario u   ON a.id_usuario=u.id_usuario
     LEFT JOIN avaliacao av ON av.id_solicitacao=s.id_solicitacao AND av.id_avaliador=?
     $where ORDER BY s.data_solicitacao DESC"
);
$stmt->execute(array_merge([$id],$params));
$solic = $stmt->fetchAll();

// Processar avaliação
$erro_av = $suc_av = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['avaliar'])) {
    $id_solic_av = (int)($_POST['id_solicitacao']??0);
    $nota        = (int)($_POST['nota']??0);
    $comentario  = trim($_POST['comentario']??'');

    // Verifica: é uma solicitação finalizada do usuário
    $sv = $pdo->prepare("SELECT s.id_solicitacao, a.id_usuario AS id_avaliado FROM solicitacao_doacao s INNER JOIN acessorio a ON s.id_acessorio=a.id_acessorio WHERE s.id_solicitacao=? AND s.id_usuario=? AND s.status_solicitacao='finalizada'");
    $sv->execute([$id_solic_av,$id]);
    $sv_row = $sv->fetch();

    if (!$sv_row) { $erro_av='Solicitação inválida.'; }
    elseif ($nota<1||$nota>5) { $erro_av='Nota deve ser entre 1 e 5.'; }
    else {
        // Verifica se já avaliou
        $ja = $pdo->prepare("SELECT 1 FROM avaliacao WHERE id_solicitacao=? AND id_avaliador=?");
        $ja->execute([$id_solic_av,$id]);
        if ($ja->fetchColumn()) { $erro_av='Você já avaliou esta doação.'; }
        else {
            $pdo->prepare("INSERT INTO avaliacao (nota,comentario,id_avaliador,id_avaliado,id_solicitacao) VALUES (?,?,?,?,?)")
                ->execute([$nota,$comentario,$id,$sv_row['id_avaliado'],$id_solic_av]);
            criar_notificacao($pdo,$sv_row['id_avaliado'],"⭐ Você recebeu uma avaliação de {$_SESSION['nome']}!","perfil.php",'avaliacao');
            $suc_av = 'Avaliação enviada! Obrigado.';
        }
    }
}

include __DIR__ . '/config/header.php';
?>

<div class="mini-hero">
    <p class="mh-breadcrumb"><a href="index.php">Início</a> › Minhas Solicitações</p>
    <h1>📋 Minhas Solicitações</h1>
    <p>Acompanhe os itens que você solicitou para adoção.</p>
</div>

<div style="padding:32px 6%;max-width:900px;margin:0 auto;">
    <?php if ($suc_av): ?><div class="alerta alerta-sucesso"><span class="alerta-icon">✅</span><?= e($suc_av) ?></div><?php endif; ?>
    <?php if ($erro_av): ?><div class="alerta alerta-erro"><span class="alerta-icon">⚠️</span><?= e($erro_av) ?></div><?php endif; ?>

    <!-- Filtros -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;">
        <?php foreach (['todas'=>'Todas','pendente'=>'Pendentes','aceita'=>'Aceitas','recusada'=>'Recusadas','finalizada'=>'Finalizadas'] as $k=>$v): ?>
            <a href="?filtro=<?= $k ?>" class="btn btn-sm <?= $filtro===$k?'btn-primario':'btn-ghost' ?>"><?= $v ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (!$solic): ?>
        <div class="estado-vazio">
            <span class="ev-icon">📋</span>
            <h3>Nenhuma solicitação</h3>
            <p>Explore os acessórios disponíveis e solicite os que seu pet precisa.</p>
            <a href="acessorios.php" class="btn btn-primario">🔍 Ver acessórios</a>
        </div>
    <?php else: ?>
    <div class="solic-lista">
        <?php foreach ($solic as $s): ?>
        <div class="solic-card">
            <div class="solic-header">
                <div class="solic-foto-ph">
                    <?php if ($s['item_foto']): ?><img src="uploads/<?= e($s['item_foto']) ?>" class="solic-foto" alt=""><?php else: ?>🎁<?php endif; ?>
                </div>
                <div style="flex:1;min-width:0;">
                    <div class="solic-titulo"><a href="acessorio.php?id=<?= $s['id_acessorio'] ?>" style="color:inherit;text-decoration:none;"><?= e($s['item_nome']) ?></a></div>
                    <div class="solic-sub">Doador: <strong><?= e($s['doador']) ?></strong> · <?= tempo_relativo($s['data_solicitacao']) ?></div>
                </div>
                <span class="status-pill status-<?= $s['status_solicitacao'] ?>"><?= ucfirst($s['status_solicitacao']) ?></span>
            </div>

            <?php if ($s['mensagem']): ?>
                <div class="solic-mensagem"><?= e(truncar($s['mensagem'],200)) ?></div>
            <?php endif; ?>

            <?php if ($s['status_solicitacao']==='aceita'): ?>
                <div class="alerta alerta-sucesso" style="margin-bottom:12px;">
                    <span class="alerta-icon">🎉</span>
                    Sua solicitação foi aceita!
                    <?php if ($s['doador_tel']): ?> Contato: <strong><?= e($s['doador_tel']) ?></strong><?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Avaliar -->
            <?php if ($s['status_solicitacao']==='finalizada' && !$s['minha_nota']): ?>
                <details style="margin-bottom:12px;">
                    <summary style="cursor:pointer;font-weight:700;font-size:14px;color:var(--laranja);padding:8px 0;">⭐ Avaliar esta doação</summary>
                    <form method="POST" style="padding:14px;background:var(--creme);border-radius:var(--radius-sm);margin-top:8px;">
                        <input type="hidden" name="id_solicitacao" value="<?= $s['id_solicitacao'] ?>">
                        <div class="form-grupo" style="margin-bottom:12px;">
                            <label>Nota (1–5) *</label>
                            <div style="display:flex;gap:8px;">
                                <?php for($n=1;$n<=5;$n++): ?>
                                    <label style="cursor:pointer;font-size:24px;"><input type="radio" name="nota" value="<?= $n ?>" required style="display:none;"> ★</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="form-grupo" style="margin-bottom:12px;">
                            <label>Comentário (opcional)</label>
                            <textarea name="comentario" rows="2" placeholder="Conte como foi a experiência..."></textarea>
                        </div>
                        <button type="submit" name="avaliar" class="btn btn-primario btn-sm">Enviar avaliação</button>
                    </form>
                </details>
            <?php elseif ($s['minha_nota']): ?>
                <div style="font-size:13px;color:var(--cinza-sub);margin-bottom:8px;">
                    Sua avaliação: <?php echo estrelas_html((float)$s['minha_nota']); ?> <?= e($s['meu_comentario']?'"'.$s['meu_comentario'].'"':'') ?>
                </div>
            <?php endif; ?>

            <div class="solic-acoes">
                <a href="acessorio.php?id=<?= $s['id_acessorio'] ?>" class="btn btn-ghost btn-sm">👁️ Ver item</a>
                <span style="font-size:12px;color:var(--cinza-sub);margin-left:auto;"><?= formatar_data($s['data_solicitacao']) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
/* Estrelas interativas */
.form-grupo label:has(input[type=radio]) { color:#D1D5DB; transition:color .15s; }
.form-grupo label:has(input[type=radio]):hover,
.form-grupo label:has(input[type=radio]):hover ~ label { color:#F59E0B; }
.form-grupo label:has(input[type=radio]:checked) { color:#F59E0B; }
</style>

<?php include __DIR__ . '/config/footer.php'; ?>
