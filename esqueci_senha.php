<?php
session_start();
require_once __DIR__ . "/config/conexao.php";
require_once __DIR__ . "/config/helpers.php";

$msg  = '';
$tipo = 'info';
$etapa = 'email'; // email | nova_senha

$token = trim($_GET['token'] ?? '');
if ($token) {
    // Verificar token
    $st = $pdo->prepare("SELECT * FROM usuario WHERE token_reset=? AND token_expira > NOW()");
    $st->execute([$token]);
    $u = $st->fetch();
    if ($u) {
        $etapa = 'nova_senha';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_senha'])) {
            $nova = $_POST['nova_senha'] ?? '';
            $conf = $_POST['confirmar']  ?? '';
            if (strlen($nova) < 6) {
                $msg = 'A senha deve ter pelo menos 6 caracteres.'; $tipo = 'erro';
            } elseif ($nova !== $conf) {
                $msg = 'As senhas não coincidem.'; $tipo = 'erro';
            } else {
                $hash = password_hash($nova, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE usuario SET senha=?, token_reset=NULL, token_expira=NULL WHERE id_usuario=?")->execute([$hash, $u['id_usuario']]);
                header("Location: login.php?msg=" . urlencode('Senha alterada com sucesso! Faça login.'));
                exit;
            }
        }
    } else {
        $msg = 'Link inválido ou expirado. Solicite um novo.'; $tipo = 'erro';
        $etapa = 'email';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $etapa === 'email') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'E-mail inválido.'; $tipo = 'erro';
    } else {
        $st = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email=? AND ativo=1");
        $st->execute([$email]);
        $u = $st->fetch();
        if ($u) {
            $token_val = bin2hex(random_bytes(32));
            $expira    = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $pdo->prepare("UPDATE usuario SET token_reset=?, token_expira=? WHERE id_usuario=?")->execute([$token_val, $expira, $u['id_usuario']]);
            // Em produção: enviar e-mail. Por ora, mostramos o link.
            $link = "http://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['REQUEST_URI']) . "/esqueci_senha.php?token=$token_val";
            $msg  = "Link de redefinição gerado! Em produção isso seria enviado ao e-mail. Link: $link";
            $tipo = 'sucesso';
        } else {
            // Por segurança, sempre mostra mensagem de sucesso
            $msg  = 'Se este e-mail estiver cadastrado, você receberá as instruções em breve.';
            $tipo = 'sucesso';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Recuperar Senha — Conecta Pet Web</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
</head>
<body>
<div class="senha-layout">
    <div class="senha-card">
        <span class="icon"><?= $etapa === 'nova_senha' ? '🔑' : '🔒' ?></span>
        <h1><?= $etapa === 'nova_senha' ? 'Nova senha' : 'Esqueceu sua senha?' ?></h1>
        <p><?= $etapa === 'nova_senha' ? 'Digite sua nova senha abaixo.' : 'Digite seu e-mail e enviaremos as instruções.' ?></p>

        <?php if ($msg): ?>
            <div class="alerta alerta-<?= $tipo ?>">
                <span class="alerta-icon"><?= $tipo==='erro'?'⚠️':'✅' ?></span><?= e($msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($etapa === 'nova_senha'): ?>
            <form method="POST" style="text-align:left">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="form-grupo" style="margin-bottom:12px;">
                    <label>Nova senha *</label>
                    <input type="password" name="nova_senha" placeholder="Mínimo 6 caracteres" required>
                </div>
                <div class="form-grupo" style="margin-bottom:16px;">
                    <label>Confirmar senha *</label>
                    <input type="password" name="confirmar" placeholder="Repita a senha" required>
                </div>
                <button type="submit" class="btn-auth">Salvar nova senha</button>
            </form>
        <?php elseif ($tipo !== 'sucesso'): ?>
            <form method="POST" style="text-align:left">
                <div class="form-grupo" style="margin-bottom:16px;">
                    <label>Seu e-mail</label>
                    <input type="email" name="email" placeholder="seu@email.com" required autocomplete="email">
                </div>
                <button type="submit" class="btn-auth">Enviar instruções</button>
            </form>
        <?php endif; ?>

        <p style="margin-top:20px;font-size:14px;"><a href="login.php" style="color:var(--laranja);font-weight:700;text-decoration:none;">← Voltar ao login</a></p>
    </div>
</div>
</body></html>
