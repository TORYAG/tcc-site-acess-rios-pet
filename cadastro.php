<?php
session_start();
if (isset($_SESSION['id_usuario'])) { header("Location: index.php"); exit; }
require_once __DIR__ . "/config/conexao.php";
require_once __DIR__ . "/config/helpers.php";

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome         = trim($_POST['nome']         ?? '');
    $email        = trim($_POST['email']        ?? '');
    $senha        = $_POST['senha']             ?? '';
    $telefone     = trim($_POST['telefone']     ?? '');
    $tipo_usuario = $_POST['tipo_usuario']      ?? '';
    $bio          = trim($_POST['bio']          ?? '');
    $cep          = preg_replace('/\D/', '', $_POST['cep'] ?? '');
    $rua          = trim($_POST['rua']          ?? '');
    $numero       = trim($_POST['numero']       ?? '');
    $complemento  = trim($_POST['complemento']  ?? '');
    $bairro       = trim($_POST['bairro']       ?? '');
    $cidade       = trim($_POST['cidade']       ?? '');
    $estado_uf    = trim($_POST['estado']       ?? '');

    $tipos_ok = ['doador','receptor','ong'];

    if (!$nome || !$email || !$senha || !$tipo_usuario) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif (!in_array($tipo_usuario, $tipos_ok)) {
        $erro = 'Tipo de usuário inválido.';
    } else {
        $verif = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email=?");
        $verif->execute([$email]);
        if ($verif->rowCount()) {
            $erro = 'Este e-mail já está cadastrado.';
        } else {
            $pdo->beginTransaction();
            try {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $s = $pdo->prepare("INSERT INTO usuario (nome,email,senha,telefone,bio,tipo_usuario) VALUES (?,?,?,?,?,?)");
                $s->execute([$nome, $email, $hash, $telefone, $bio, $tipo_usuario]);
                $novo_id = (int)$pdo->lastInsertId();

                if (!empty($cep) && strlen($cep) === 8) {
                    $cep_fmt = substr($cep,0,5).'-'.substr($cep,5,3);
                    $se = $pdo->prepare("INSERT INTO endereco_usuario (cep,rua,numero,complemento,bairro,cidade,estado,id_usuario) VALUES (?,?,?,?,?,?,?,?)");
                    $se->execute([$cep_fmt, $rua, $numero, $complemento, $bairro, $cidade, $estado_uf, $novo_id]);
                }

                $pdo->commit();
                header("Location: login.php?msg=" . urlencode('Cadastro realizado! Faça login para começar.'));
                exit;
            } catch (Exception $ex) {
                $pdo->rollBack();
                $erro = 'Erro ao cadastrar. Tente novamente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Cadastro — Conecta Pet Web</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐾</text></svg>">
</head>
<body>
<div class="auth-layout">
    <div class="auth-left">
        <span class="al-pata">🐶</span>
        <h2>Junte-se a nós!</h2>
        <p>Cadastre-se e faça parte de uma comunidade que cuida dos animais com amor e carinho.</p>
        <div class="al-features">
            <div class="al-feat"><span class="al-feat-icon">🏠</span> CEP preenche seu endereço automaticamente</div>
            <div class="al-feat"><span class="al-feat-icon">⭐</span> Receba avaliações de outros usuários</div>
            <div class="al-feat"><span class="al-feat-icon">🔔</span> Notificações em tempo real</div>
            <div class="al-feat"><span class="al-feat-icon">❤️</span> Salve favoritos e acompanhe tudo</div>
        </div>
    </div>

    <div class="auth-right" style="padding:40px 48px;overflow-y:auto;">
        <div class="auth-form-wrap" style="max-width:500px;">
            <a href="index.php" class="auth-logo"><span>🐾</span><span>Conecta Pet Web</span></a>
            <h1 class="auth-titulo">Criar conta</h1>
            <p class="auth-sub">Preencha os dados — é rápido e gratuito!</p>

            <?php if ($erro): ?>
                <div class="alerta alerta-erro"><span class="alerta-icon">⚠️</span><?= e($erro) ?></div>
            <?php endif; ?>

            <form method="POST">
                <!-- Dados pessoais -->
                <p class="form-secao">👤 Dados Pessoais</p>

                <div class="form-grupo">
                    <label>Nome completo *</label>
                    <input type="text" name="nome" placeholder="Seu nome completo" required value="<?= e($_POST['nome'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <div class="form-grupo">
                        <label>E-mail *</label>
                        <input type="email" name="email" placeholder="seu@email.com" required value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="form-grupo">
                        <label>Senha *</label>
                        <input type="password" name="senha" placeholder="Mín. 6 caracteres" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-grupo">
                        <label>Telefone / WhatsApp</label>
                        <input type="tel" name="telefone" placeholder="(11) 99999-9999" value="<?= e($_POST['telefone'] ?? '') ?>">
                    </div>
                    <div class="form-grupo">
                        <label>Tipo de usuário *</label>
                        <select name="tipo_usuario" required>
                            <option value="">Selecione...</option>
                            <option value="doador"   <?= ($_POST['tipo_usuario']??'')==='doador'  ?'selected':'' ?>>🎁 Doador</option>
                            <option value="receptor" <?= ($_POST['tipo_usuario']??'')==='receptor'?'selected':'' ?>>🐾 Receptor</option>
                            <option value="ong"      <?= ($_POST['tipo_usuario']??'')==='ong'     ?'selected':'' ?>>🏥 ONG</option>
                        </select>
                    </div>
                </div>
                <div class="form-grupo">
                    <label>Sobre você (bio)</label>
                    <textarea name="bio" placeholder="Conte sobre você e seus pets..." rows="2"><?= e($_POST['bio'] ?? '') ?></textarea>
                </div>

                <hr class="form-divider">

                <!-- Endereço via CEP -->
                <p class="form-secao">📍 Endereço <span style="font-weight:500;text-transform:none;letter-spacing:0;font-size:12px;color:var(--cinza-sub)">(opcional — facilita doações locais)</span></p>

                <?php
                $cep_prefix = 'usuario';
                $cep_dados  = [];
                include __DIR__ . '/config/cep_fields.php';
                ?>

                <button type="submit" class="btn-auth" style="margin-top:16px;">Criar minha conta 🚀</button>
            </form>

            <p class="auth-link">Já tem conta? <a href="login.php">Faça login</a></p>
        </div>
    </div>
</div>
<script src="config/cep.js"></script>
</body></html>
