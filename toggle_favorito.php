<?php
session_start();
require_once __DIR__ . "/config/conexao.php";
if (!isset($_SESSION['id_usuario'])) { header("Location: login.php"); exit; }

$id_ac  = (int)($_GET['id']   ?? 0);
$volta  = $_GET['volta'] ?? 'index.php';
// Segurança: apenas URLs relativas
if (preg_match('/^https?:\/\//', $volta)) $volta = 'index.php';

$id_u = $_SESSION['id_usuario'];
if ($id_ac > 0) {
    $existe = $pdo->prepare("SELECT 1 FROM favorito WHERE id_usuario=? AND id_acessorio=?");
    $existe->execute([$id_u, $id_ac]);
    if ($existe->fetchColumn()) {
        $pdo->prepare("DELETE FROM favorito WHERE id_usuario=? AND id_acessorio=?")->execute([$id_u, $id_ac]);
    } else {
        $pdo->prepare("INSERT IGNORE INTO favorito (id_usuario,id_acessorio) VALUES (?,?)")->execute([$id_u, $id_ac]);
    }
}
header("Location: $volta");
exit;
