<?php
// config/conexao.php — Conexão PDO
// Ajuste as credenciais conforme seu servidor

$host   = 'localhost';
$dbname = 'conecta_pet_web';
$user   = 'root';
$pass   = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$charset",
        $user, $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
        <h2>❌ Erro de conexão com o banco de dados</h2>
        <p>Verifique as credenciais em <code>config/conexao.php</code></p>
        <details><summary>Detalhes técnicos</summary><pre>' . htmlspecialchars($e->getMessage()) . '</pre></details>
    </div>');
}
