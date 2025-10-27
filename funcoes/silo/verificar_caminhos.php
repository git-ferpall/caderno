<?php
/**
 * 🧭 Verifica e valida caminhos físicos de arquivos/pastas no silo_arquivos.
 * Executar manualmente via navegador ou CLI.
 * Exemplo: http://seusite.com/funcoes/silo/verificar_caminhos.php
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../sso/verify_jwt.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🔍 Verificação de Caminhos - Silo de Dados</h2>";
echo "<style>
body { font-family: Arial, sans-serif; background:#f9f9f9; color:#333; }
.ok { color: green; font-weight:bold; }
.warn { color: orange; font-weight:bold; }
.err { color: red; font-weight:bold; }
pre { background:#fff; padding:8px; border-radius:6px; border:1px solid #ddd; overflow-x:auto; }
</style>";

$base = realpath(__DIR__ . '/../../uploads');
if (!$base) die('<p class="err">❌ Base de uploads não encontrada.</p>');

$res = $mysqli->query("SELECT id, user_id, nome_arquivo, caminho_arquivo, tipo FROM silo_arquivos ORDER BY user_id, id");

$total = $res->num_rows;
$ok = $warn = $err = 0;

echo "<p>Total de registros: <b>{$total}</b></p>";
echo "<table border='1' cellspacing='0' cellpadding='6' style='border-collapse:collapse;font-size:13px;'>
<tr style='background:#e0e0e0;'>
  <th>ID</th>
  <th>Usuário</th>
  <th>Tipo</th>
  <th>Nome</th>
  <th>Caminho Banco</th>
  <th>Status</th>
</tr>";

while ($r = $res->fetch_assoc()) {
    $id = $r['id'];
    $user = $r['user_id'];
    $nome = htmlspecialchars($r['nome_arquivo']);
    $tipo = $r['tipo'];
    $caminho_rel = trim($r['caminho_arquivo'], '/');

    $tentativas = [
        $base . '/' . $caminho_rel,
        $base . '/uploads/' . $caminho_rel,
        $base . '/' . preg_replace('#^uploads/#', '', $caminho_rel)
    ];

    $status = '❌ Não encontrado';
    $class = 'err';
    foreach ($tentativas as $t) {
        if (file_exists($t)) {
            $status = '✅ OK';
            $class = 'ok';
            break;
        }
    }

    // Detecção de caminhos duplicados
    if (strpos($caminho_rel, 'uploads/uploads/') !== false) {
        $status = '⚠️ Prefixo duplo "uploads/uploads"';
        $class = 'warn';
    }

    echo "<tr class='{$class}'>
        <td>{$id}</td>
        <td>{$user}</td>
        <td>{$tipo}</td>
        <td>{$nome}</td>
        <td><code>{$r['caminho_arquivo']}</code></td>
        <td class='{$class}'>{$status}</td>
    </tr>";

    if ($class === 'ok') $ok++;
    elseif ($class === 'warn') $warn++;
    else $err++;
}

echo "</table>";
echo "<hr><p>✅ OK: <b>{$ok}</b> &nbsp; ⚠️ Warnings: <b>{$warn}</b> &nbsp; ❌ Erros: <b>{$err}</b></p>";

echo "<p><small>Base: {$base}</small></p>";
?>
