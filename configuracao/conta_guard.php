<?php
declare(strict_types=1);

/**
 * Regras de acesso do papel 'apontador' (funcionário de conta que só
 * registra apontamentos).
 *
 * A lista é checada de forma centralizada:
 *   - sso/verify_jwt.php     → endpoints JSON (403 em CONTA_APONTADOR_ENDPOINTS_BLOQUEADOS)
 *   - configuracao/protect.php → páginas HTML (403 em CONTA_APONTADOR_PAGINAS_BLOQUEADAS)
 *
 * Sufixos terminados em '/' bloqueiam o diretório inteiro.
 */

// Endpoints de GESTÃO (cadastros, exclusões, arquivos, relatórios, admin)
const CONTA_APONTADOR_ENDPOINTS_BLOQUEADOS = [
    '/funcoes/salvar_propriedade.php',
    '/funcoes/update_propriedade.php',
    '/funcoes/excluir_propriedade.php',
    '/funcoes/salvar_area.php',
    '/funcoes/excluir_area.php',
    '/funcoes/salvar_produto.php',
    '/funcoes/editar_produto.php',
    '/funcoes/excluir_produto.php',
    '/funcoes/remover_produto.php',
    '/funcoes/salvar_maquina.php',
    '/funcoes/excluir_maquina.php',
    '/funcoes/salvar_contato.php',
    '/funcoes/salvar_estufa.php',
    '/funcoes/remover_estufa.php',
    '/funcoes/salvar_bancada.php',
    '/funcoes/remover_bancada.php',
    '/funcoes/atualizar_bancada_produtos.php',
    '/funcoes/silo/',
    '/funcoes/relatorios/',
    '/funcoes/frutibank/',
    '/funcoes/admin/',
    '/funcoes/conta/',
];

// Páginas de GESTÃO (o menu também esconde esses links para o apontador)
const CONTA_APONTADOR_PAGINAS_BLOQUEADAS = [
    '/home/propriedade.php',
    '/home/editar_propriedade.php',
    '/home/minhas_propriedades.php',
    '/home/propriedades.php',
    '/home/produtos.php',
    '/home/areas.php',
    '/home/maquinas.php',
    '/home/silo.php',
    '/home/relatorios.php',
    '/home/relatorios_manejos.php',
    '/home/relatorio_fitossanitario.php',
    '/home/relatorio_irrigacao.php',
    '/home/relatorio_produtividade.php',
    '/home/frutibank.php',
    '/home/frutibank_cobranca.php',
    '/home/usuarios_conta.php',
    '/home/meus_clientes.php',
    '/home/admin_usuarios.php',
    '/home/admin_offline.php',
    '/home/clientes.php',
];

/** O script atual (ou informado) está em uma das listas de bloqueio? */
function conta_script_bloqueado(array $lista, ?string $script = null): bool
{
    $script = str_replace('\\', '/', $script ?? ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script === '') return false;

    foreach ($lista as $sufixo) {
        if (substr($sufixo, -1) === '/') {
            if (strpos($script, $sufixo) !== false) return true;
        } elseif (substr($script, -strlen($sufixo)) === $sufixo) {
            return true;
        }
    }
    return false;
}
