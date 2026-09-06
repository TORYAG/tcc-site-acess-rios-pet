<?php
// config/helpers.php — Funções utilitárias v3

/** Escapa string para exibição segura */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/** Trunca texto com reticências */
function truncar(string $str, int $max = 100): string {
    if (mb_strlen($str) <= $max) return $str;
    return mb_substr($str, 0, $max) . '…';
}

/** Tempo relativo em português */
function tempo_relativo(string $data): string {
    $diff = time() - strtotime($data);
    if ($diff < 60)      return 'agora mesmo';
    if ($diff < 3600)    return 'há ' . floor($diff/60) . ' min';
    if ($diff < 86400)   return 'há ' . floor($diff/3600) . 'h';
    if ($diff < 604800)  return 'há ' . floor($diff/86400) . ' dias';
    if ($diff < 2592000) return 'há ' . floor($diff/604800) . ' sem.';
    return date('d/m/Y', strtotime($data));
}

/** Formata data pt-BR */
function formatar_data(string $data): string {
    return date('d/m/Y \à\s H:i', strtotime($data));
}

/** Formata CEP */
function formatar_cep(string $cep): string {
    $c = preg_replace('/\D/', '', $cep);
    return strlen($c) === 8 ? substr($c,0,5).'-'.substr($c,5,3) : $cep;
}

/** Ícone do tipo de usuário */
function icone_tipo(string $tipo): string {
    return match($tipo) {
        'doador'   => '🎁',
        'receptor' => '🐾',
        'ong'      => '🏥',
        'admin'    => '⚙️',
        default    => '👤',
    };
}

/** Estrelas HTML */
function estrelas_html(float $nota, bool $interativo = false): string {
    $html = '<div class="estrelas">';
    for ($i = 1; $i <= 5; $i++) {
        $class = $i <= round($nota) ? 'estrela cheia' : 'estrela vazia';
        $html .= "<span class=\"$class\">★</span>";
    }
    $html .= '</div>';
    return $html;
}

/** Cria notificação */
function criar_notificacao(PDO $pdo, int $id_usuario, string $mensagem, string $link = '', string $tipo = 'sistema'): void {
    $stmt = $pdo->prepare("INSERT INTO notificacao (id_usuario, mensagem, link, tipo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id_usuario, $mensagem, $link, $tipo]);
}

/** Valida imagem (MIME real) */
function validar_imagem(array $arquivo): array {
    $tipos_ok = ['image/jpeg','image/png','image/gif','image/webp'];
    $max_bytes = 5 * 1024 * 1024;

    if ($arquivo['error'] !== UPLOAD_ERR_OK)   return ['ok'=>false,'msg'=>'Erro no upload.'];
    if ($arquivo['size'] > $max_bytes)          return ['ok'=>false,'msg'=>'Imagem muito grande. Máximo 5 MB.'];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $tipos_ok)) return ['ok'=>false,'msg'=>'Formato inválido. Use JPG, PNG, GIF ou WebP.'];
    return ['ok'=>true,'mime'=>$mime];
}

/** Salva imagem e retorna o nome */
function salvar_imagem(array $arquivo, string $prefixo = 'img'): ?string {
    $validacao = validar_imagem($arquivo);
    if (!$validacao['ok']) return null;

    $ext  = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
    $nome = $prefixo . '_' . uniqid('', true) . '.' . strtolower($ext);
    $dir  = __DIR__ . '/../uploads/';

    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return move_uploaded_file($arquivo['tmp_name'], $dir . $nome) ? $nome : null;
}

/** Verifica se usuário é admin */
function e_admin(): bool {
    return isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin';
}

/** Redireciona com mensagem flash */
function redirecionar(string $url, string $msg = '', string $tipo = 'sucesso'): void {
    if ($msg) {
        $_SESSION['flash_msg']  = $msg;
        $_SESSION['flash_tipo'] = $tipo;
    }
    header("Location: $url");
    exit;
}

/** Exibe e limpa mensagem flash */
function flash(): string {
    if (empty($_SESSION['flash_msg'])) return '';
    $msg  = $_SESSION['flash_msg'];
    $tipo = $_SESSION['flash_tipo'] ?? 'sucesso';
    unset($_SESSION['flash_msg'], $_SESSION['flash_tipo']);
    $icon = $tipo === 'erro' ? '⚠️' : ($tipo === 'aviso' ? '⚡' : '✅');
    return "<div class=\"alerta alerta-$tipo\"><span class=\"alerta-icon\">$icon</span>" . e($msg) . "</div>";
}
