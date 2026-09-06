<?php
session_start();
require_once __DIR__ . "/config/conexao.php";
require_once __DIR__ . "/config/helpers.php";
if (!isset($_SESSION['id_usuario'])) { header("Location: login.php"); exit; }

$id_solic = (int)($_GET['id']   ?? 0);
$acao     = $_GET['acao'] ?? '';
$id_user  = $_SESSION['id_usuario'];

if (!$id_solic || !in_array($acao,['aceitar','recusar','finalizar'])) {
    header("Location: solicitacoes_recebidas.php"); exit;
}

// Verifica propriedade
$stmt = $pdo->prepare(
    "SELECT s.*, a.nome AS item_nome, a.id_usuario AS dono_id, u.nome AS solicitante_nome
     FROM solicitacao_doacao s
     INNER JOIN acessorio a ON s.id_acessorio=a.id_acessorio
     INNER JOIN usuario u   ON s.id_usuario=u.id_usuario
     WHERE s.id_solicitacao=?"
);
$stmt->execute([$id_solic]);
$s = $stmt->fetch();

if (!$s || $s['dono_id'] != $id_user) {
    header("Location: solicitacoes_recebidas.php"); exit;
}

$pdo->beginTransaction();
try {
    if ($acao === 'aceitar' && $s['status_solicitacao']==='pendente') {
        $pdo->prepare("UPDATE solicitacao_doacao SET status_solicitacao='aceita' WHERE id_solicitacao=?")->execute([$id_solic]);
        $pdo->prepare("UPDATE acessorio SET status='solicitado' WHERE id_acessorio=?")->execute([$s['id_acessorio']]);
        // Recusa as demais pendentes
        $outras = $pdo->prepare("SELECT id_solicitacao,id_usuario FROM solicitacao_doacao WHERE id_acessorio=? AND id_solicitacao!=? AND status_solicitacao='pendente'");
        $outras->execute([$s['id_acessorio'], $id_solic]);
        foreach ($outras->fetchAll() as $o) {
            $pdo->prepare("UPDATE solicitacao_doacao SET status_solicitacao='recusada' WHERE id_solicitacao=?")->execute([$o['id_solicitacao']]);
            criar_notificacao($pdo, $o['id_usuario'], "😔 Sua solicitação de \"{$s['item_nome']}\" foi recusada.", "minhas_solicitacoes.php", 'recusa');
        }
        criar_notificacao($pdo, $s['id_usuario'], "🎉 Sua solicitação de \"{$s['item_nome']}\" foi aceita! Entre em contato com o doador.", "minhas_solicitacoes.php", 'aceite');
        $msg = 'Solicitação aceita! O solicitante foi notificado.';

    } elseif ($acao === 'recusar' && $s['status_solicitacao']==='pendente') {
        $pdo->prepare("UPDATE solicitacao_doacao SET status_solicitacao='recusada' WHERE id_solicitacao=?")->execute([$id_solic]);
        criar_notificacao($pdo, $s['id_usuario'], "😔 Sua solicitação de \"{$s['item_nome']}\" foi recusada.", "minhas_solicitacoes.php", 'recusa');
        $msg = 'Solicitação recusada.';

    } elseif ($acao === 'finalizar' && $s['status_solicitacao']==='aceita') {
        $pdo->prepare("UPDATE solicitacao_doacao SET status_solicitacao='finalizada' WHERE id_solicitacao=?")->execute([$id_solic]);
        $pdo->prepare("UPDATE acessorio SET status='doado' WHERE id_acessorio=?")->execute([$s['id_acessorio']]);
        criar_notificacao($pdo, $s['id_usuario'], "✅ Doação de \"{$s['item_nome']}\" finalizada! Por favor, avalie o doador.", "minhas_solicitacoes.php", 'avaliacao');
        $msg = 'Doação finalizada! O solicitante foi convidado a avaliar.';
    } else {
        $msg = 'Ação não aplicável neste momento.';
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    $msg = 'Erro ao processar. Tente novamente.';
}

header("Location: solicitacoes_recebidas.php?msg=" . urlencode($msg));
exit;
