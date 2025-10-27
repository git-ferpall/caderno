<?php
/**
 * 🛠️ Corrige registros de caminhos incorretos no silo_arquivos
 * - Ajusta prefixos errados
 * - Remove registros órfãos (sem arquivo/pasta física)
 * - Gera backup antes das mudanças
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🛠️ Correção Automática de Caminhos - Silo</h2>";
$base = realpath(__DIR__ . '/../../uploads');
if (!$base) die('<p style="color:red;">❌ Base de uploads não encontrada.</p>');

// Backup prévio
$backupFile = __DIR__ . '/backup_silo_' . date('Ymd_His') . '.json';
$dados = $mysqli->query("SELECT * FROM silo_arquivos")->fetch_all(MYSQLI_ASSOC);
file_put_contents($backupFile, json_encode($dados, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo "<p>📦 Backup salvo em <code>$backupFile</code></p>";

$res = $mysqli->query("SELECT id, caminho_arquivo FROM silo_arquivos");
$fix = $del = 0;

while ($r = $res->fetch_assoc()) {
    $id = $r['id'];
    $caminho = trim($r['caminho_arquivo'], '/');
    $orig = $caminho;

    // Normaliza prefixos
    if (preg_match('#^uploads/(\d+/.*)$#', $caminho, $m)) {
        $caminho = 'silo/' . $m[1];
    }
    if (preg_match('#^(\d+/.*)$#', $caminho, $m)) {
        $caminho = 'silo/' . $m[1];
    }

    // Caminho absoluto
    $abs = $base . '/' . $caminho;

    if (!file_exists($abs)) {
        echo "<p style='color:red;'>❌ Removendo órfão ID {$id}: {$orig}</p>";
        $mysqli->query("DELETE FROM silo_arquivos WHERE id = $id");
        $del++;
    } elseif ($orig !== $caminho) {
        echo "<p style='color:orange;'>🟡 Corrigindo prefixo ID {$id}: <code>{$orig}</code> → <code>{$caminho}</code></p>";
        $stmt = $mysqli->prepare("UPDATE silo_arquivos SET caminho_arquivo = ? WHERE id = ?");
        $stmt->bind_param('si', $caminho, $id);
        $stmt->execute();
        $stmt->close();
        $fix++;
    }
}

echo "<hr><p>✅ Correções aplicadas: <b>{$fix}</b> &nbsp; 🗑️ Removidos: <b>{$del}</b></p>";
echo "<p>🔙 Backup disponível em: <code>{$backupFile}</code></p>";
?>
