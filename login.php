<?php
session_start();
if (isset($_SESSION['id_usuario'])) { header("Location: index.php"); exit; }
require_once __DIR__ . "/config/conexao.php";
require_once __DIR__ . "/config/helpers.php";

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha e-mail e senha.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuario WHERE email=? AND ativo=1");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && password_verify($senha, $u['senha'])) {
            session_regenerate_id(true);
            $_SESSION['id_usuario']   = $u['id_usuario'];
            $_SESSION['nome']         = $u['nome'];
            $_SESSION['tipo_usuario'] = $u['tipo_usuario'];
            $_SESSION['foto_perfil']  = $u['foto_perfil'];
            $volta = $_GET['volta'] ?? 'index.php';
            // segurança: só redireciona para URLs relativas
            if (preg_match('/^https?:\/\//', $volta)) $volta = 'index.php';
            header("Location: $volta");
            exit;
        } else {
            $erro = 'E-mail ou senha inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Login — Conecta Pet Web</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐾</text></svg>">
</head>
<body>
<div class="auth-layout">
    <div class="auth-left">
        <span class="al-pata">🐾</span>
        <h2>Conecta Pet Web</h2>
        <p>A plataforma que conecta pessoas que amam animais. Doe, receba e faça a diferença.</p>
        <div class="al-features">
            <div class="al-feat"><span class="al-feat-icon">🎁</span> Doe acessórios que não usa mais</div>
            <div class="al-feat"><span class="al-feat-icon">📍</span> Encontre doações na sua cidade pelo CEP</div>
            <div class="al-feat"><span class="al-feat-icon">❤️</span> Ajude pets que precisam de cuidado</div>
            <div class="al-feat"><span class="al-feat-icon">🆓</span> 100% gratuito, sempre</div>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-form-wrap">
            <a href="index.php" class="auth-logo"><span>🐾</span><span>Conecta Pet Web</span></a>
            <h1 class="auth-titulo">Bem-vindo de volta!</h1>
            <p class="auth-sub">Entre com sua conta para continuar</p>

            <?php if ($erro): ?>
                <div class="alerta alerta-erro"><span class="alerta-icon">⚠️</span><?= e($erro) ?></div>
            <?php endif; ?>
            <?php if (!empty($_GET['msg'])): ?>
                <div class="alerta alerta-sucesso"><span class="alerta-icon">✅</span><?= e($_GET['msg']) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-grupo">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" placeholder="seu@email.com"
                           required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-grupo">
                    <label for="senha">Senha</label>
                    <div style="position:relative">
                        <input type="password" id="senha" name="senha" placeholder="••••••••"
                               required autocomplete="current-password" style="padding-right:44px">
                        <button type="button" onclick="toggleSenha('senha',this)"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:18px;color:var(--cinza-sub)">👁</button>
                    </div>
                </div>

                <div style="text-align:right;margin-bottom:16px;">
                    <a href="esqueci_senha.php" style="font-size:13px;color:var(--laranja);text-decoration:none;font-weight:700;">Esqueci minha senha</a>
                </div>

                <button type="submit" class="btn-auth">Entrar na minha conta →</button>
            </form>

            <div class="auth-divider">ou</div>
            <p class="auth-link">Não tem conta? <a href="cadastro.php">Cadastre-se grátis</a></p>
        </div>
    </div>
</div>
<script>
function toggleSenha(id, btn) {
    const input = document.getElementById(id);
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? '🙈' : '👁';
}
</script>
</body></html>
