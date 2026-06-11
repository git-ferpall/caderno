<?php
declare(strict_types=1);

/** Tipos de manejo que o assistente consegue criar via diálogo. */
function iaTiposComDialogo(): array
{
    return [
        'irrigacao', 'colheita', 'semeadura', 'plantio',
        'herbicida', 'fungicida', 'inseticida', 'fertilizante',
        'personalizado',
    ];
}

/** Manejos que usam insumo (herbicida etc.) em vez de produto/cultura. */
function iaTiposInsumo(): array
{
    return ['herbicida', 'fungicida', 'inseticida', 'fertilizante'];
}

function iaTipoUsaInsumo(string $tipo): bool
{
    return in_array($tipo, iaTiposInsumo(), true);
}

function iaChaveCatalogoInsumo(string $tipo): string
{
    return match ($tipo) {
        'herbicida' => 'herbicidas',
        'fungicida' => 'fungicidas',
        'inseticida' => 'inseticidas',
        'fertilizante' => 'fertilizantes',
        default => 'herbicidas',
    };
}

function iaCampoDetalheInsumo(string $tipo): string
{
    return $tipo ?: 'insumo';
}

function iaListaInsumosContexto(array $contexto, string $tipo): array
{
    $chave = iaChaveCatalogoInsumo($tipo);
    return $contexto[$chave] ?? [];
}

function iaFormatarListaNomes(array $items, string $campo = 'nome', int $max = 12): string
{
    if (!$items) {
        return '';
    }
    $nomes = array_filter(array_map(
        static fn ($item) => trim((string) ($item[$campo] ?? '')),
        array_slice($items, 0, $max)
    ));
    if (!$nomes) {
        return '';
    }
    $lista = implode(', ', $nomes);
    if (count($items) > $max) {
        $lista .= ' e outras';
    }
    return $lista;
}

function iaHintDialogoLista(): string
{
    return ' Toque na lista, ou digite/fale o número ou o nome.';
}

/** Opções clicáveis enviadas à UI durante o diálogo. */
function iaDialogoOpcoes(string $campo, array $intent, array $contexto, int $max = 12): array
{
    $opcoes = [];

    switch ($campo) {
        case 'pendente_escolha':
            foreach (array_slice($intent['_pendentes_opcao'] ?? [], 0, $max) as $i => $p) {
                $tipo = iaTiposManejo()[$p['tipo'] ?? ''] ?? ($p['tipo'] ?? 'manejo');
                $partes = [$tipo];
                if (!empty($p['produto'])) {
                    $partes[] = (string) $p['produto'];
                }
                if (!empty($p['areas'])) {
                    $partes[] = (string) $p['areas'];
                }
                if (!empty($p['data'])) {
                    $partes[] = iaFormatarDataConsulta((string) $p['data']);
                }
                $opcoes[] = [
                    'valor' => (string) ($i + 1),
                    'label' => implode(' · ', $partes),
                ];
            }
            break;

        case 'area':
            foreach (array_slice($contexto['areas'] ?? [], 0, $max) as $a) {
                $nome = trim((string) ($a['nome'] ?? ''));
                if ($nome !== '') {
                    $opcoes[] = ['valor' => $nome, 'label' => $nome];
                }
            }
            break;

        case 'produto':
            foreach (array_slice($contexto['produtos'] ?? [], 0, $max) as $p) {
                $nome = trim((string) ($p['nome'] ?? ''));
                if ($nome !== '') {
                    $opcoes[] = ['valor' => $nome, 'label' => $nome];
                }
            }
            break;

        case 'insumo':
            $tipoInsumo = (string) ($intent['tipo'] ?? '');
            foreach (array_slice(iaListaInsumosContexto($contexto, $tipoInsumo), 0, $max) as $item) {
                $nome = trim((string) ($item['nome'] ?? ''));
                if ($nome !== '') {
                    $opcoes[] = ['valor' => $nome, 'label' => $nome];
                }
            }
            break;

        case 'tipo':
            foreach (iaTiposComDialogo() as $id) {
                $label = iaTiposManejo()[$id] ?? $id;
                $opcoes[] = ['valor' => $label, 'label' => $label];
            }
            break;

        case 'tipo_semeadura':
            foreach (['Direta', 'Bandeja', 'Canteiro', 'Replantio'] as $ts) {
                $opcoes[] = ['valor' => $ts, 'label' => $ts];
            }
            break;

        case 'cancel_confirmacao':
            $opcoes[] = ['valor' => 'sim', 'label' => 'Sim, confirmar'];
            $opcoes[] = ['valor' => 'não', 'label' => 'Não, cancelar'];
            break;
    }

    return $opcoes;
}

/** Resolve resposta por número da lista ou por nome aproximado. */
function iaResolverEscolhaCatalogo(string $texto, array $catalogo, int $max = 12): string
{
    $limpo = trim($texto);
    if (preg_match('/^\d+$/u', $limpo)) {
        $idx = (int) $limpo - 1;
        $slice = array_values(array_slice($catalogo, 0, $max));
        if (isset($slice[$idx])) {
            return trim((string) ($slice[$idx]['nome'] ?? $slice[$idx]['label'] ?? ''));
        }
    }

    $match = iaMelhorMatch($texto, $catalogo, 0.45);
    return $match['label'] ?: $texto;
}

function iaUsuarioPulouCampo(string $texto): bool
{
    $t = iaNormalizarTexto($texto);
    return (bool) preg_match(
        '/\b(?:pular|pul[ae]r|n[aã]o|sem|nenhum|nenhuma|nao|dispens|ignor|pr[oó]ximo|deixa|skip)\b/u',
        $t
    );
}

function iaNormalizarDataResposta(string $texto): ?string
{
    $t = iaNormalizarTexto($texto);

    if (preg_match('/\bhoje\b/u', $t)) {
        return date('Y-m-d');
    }
    if (preg_match('/\bontem\b/u', $t)) {
        return date('Y-m-d', strtotime('-1 day'));
    }
    if (preg_match('/\b(\d{4})-(\d{2})-(\d{2})\b/u', $t, $m)) {
        return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
    }
    if (preg_match('/\b(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?\b/u', $t, $m)) {
        $dia = (int) $m[1];
        $mes = (int) $m[2];
        $ano = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : (int) date('Y');
        if ($ano < 100) {
            $ano += 2000;
        }
        if (checkdate($mes, $dia, $ano)) {
            return sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
        }
    }

    $meses = [
        'janeiro' => 1, 'fevereiro' => 2, 'marco' => 3, 'março' => 3, 'abril' => 4,
        'maio' => 5, 'junho' => 6, 'julho' => 7, 'agosto' => 8,
        'setembro' => 9, 'outubro' => 10, 'novembro' => 11, 'dezembro' => 12,
    ];
    if (preg_match('/\b(\d{1,2})\s+de\s+(\w+)(?:\s+de\s+(\d{4}))?\b/u', $t, $m)) {
        $dia = (int) $m[1];
        $mesNome = iaNormalizarTexto($m[2]);
        $ano = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : (int) date('Y');
        if (isset($meses[$mesNome]) && checkdate($meses[$mesNome], $dia, $ano)) {
            return sprintf('%04d-%02d-%02d', $ano, $meses[$mesNome], $dia);
        }
    }

    return null;
}

/**
 * Converte "informações insuficientes" / desconhecido em criar_apontamento para o diálogo.
 */
function iaRepararIntentParaDialogo(array $intent, string $texto): array
{
    if (in_array($intent['acao'] ?? '', ['consultar', 'cancelar_apontamento'], true)) {
        return $intent;
    }

    $intent = iaNormalizarIntent($intent);
    $t = iaNormalizarTexto($texto);

    $querCriar = (bool) preg_match(
        '/\b(?:adicionar|registrar|lan[cç]ar|criar|novo|incluir|fazer|apontamento|manejo)\b/u',
        $t
    );

    if (empty($intent['tipo'])) {
        $tipoDetectado = iaNormalizarTipoManejo($texto);
        if ($tipoDetectado) {
            $intent['tipo'] = $tipoDetectado;
        }
    }

    $intent = iaTentarSugestaoIntent($intent, $texto);

    if (!empty($intent['tipo']) && iaEhFalaSoTipo($texto, (string) $intent['tipo'])) {
        $intent['_veio_sugestao'] = true;
    }

    if (($intent['acao'] ?? '') === 'desconhecido') {
        if ($querCriar || !empty($intent['tipo'])) {
            $intent['acao'] = 'criar_apontamento';
            $intent['confianca'] = max((float) ($intent['confianca'] ?? 0.3), 0.75);
            if (str_contains(mb_strtolower((string) ($intent['mensagem'] ?? '')), 'insuficient')) {
                $intent['mensagem'] = iaFraseAberturaDialogo((string) ($intent['tipo'] ?? ''));
            }
        }
    }

    return $intent;
}

/** Usuário disse só o nome do manejo ("herbicida", "colheita"). */
function iaEhFalaSoTipo(string $texto, string $tipo): bool
{
    $t = iaNormalizarTexto(trim($texto));
    if (strlen($t) > 60) {
        return false;
    }
    $detectado = iaNormalizarTipoManejo($texto);
    return $detectado === $tipo;
}

function iaTentarSugestaoIntent(array $intent, string $texto): array
{
    if (($intent['acao'] ?? '') !== 'desconhecido' || !empty($intent['tipo'])) {
        return $intent;
    }

    $t = iaNormalizarTexto($texto);
    $fragments = [
        'herbi' => 'herbicida',
        'fungi' => 'fungicida',
        'inset' => 'inseticida',
        'ferti' => 'fertilizante',
        'adub' => 'fertilizante',
        'defens' => 'herbicida',
        'irrig' => 'irrigacao',
        'colh' => 'colheita',
        'sem' => 'semeadura',
        'plant' => 'plantio',
    ];

    foreach ($fragments as $frag => $tipo) {
        if (str_contains($t, $frag)) {
            $intent['tipo'] = $tipo;
            $intent['acao'] = 'criar_apontamento';
            $intent['confianca'] = 0.78;
            $intent['_veio_sugestao'] = true;
            $intent['mensagem'] = iaFraseSugestao($tipo);
            return $intent;
        }
    }

    return $intent;
}

function iaFraseSugestao(string $tipo): string
{
    $nome = iaNomeTipoManejo($tipo);
    return "Você quer registrar {$nome}? Se sim, me fala quando foi.";
}

function iaFraseAberturaDialogo(string $tipo): string
{
    if ($tipo === '') {
        return 'Me conta o que você quer registrar.';
    }
    $nome = iaNomeTipoManejo($tipo);
    return "Beleza, vamos lançar {$nome}.";
}

/**
 * Próxima pergunta para completar o intent (null = pronto para confirmar/executar).
 *
 * @return array{campo: string, pergunta: string}|null
 */
function iaProximaPergunta(array $intent, array $resolucao, array $contexto): ?array
{
    return match ($intent['acao'] ?? '') {
        'criar_apontamento' => iaProximaPerguntaCriar($intent, $resolucao, $contexto),
        'concluir_apontamento' => iaProximaPerguntaConcluir($intent, $resolucao, $contexto),
        'cancelar_apontamento' => iaProximaPerguntaCancelar($intent),
        'editar_apontamento' => iaProximaPerguntaEditar($intent),
        default => null,
    };
}

function iaPrepararIntentConcluir(array $intent, array $contexto): array
{
    if (!empty($intent['apontamento_id']) && empty($intent['_concluir_tipo'])) {
        foreach ($contexto['pendentes'] ?? [] as $p) {
            if ((int) ($p['id'] ?? 0) === (int) $intent['apontamento_id']) {
                $intent['_concluir_tipo'] = (string) ($p['tipo'] ?? '');
                break;
            }
        }
    }

    if (!empty($intent['apontamento_id'])) {
        return $intent;
    }

    $pendentes = $contexto['pendentes'] ?? [];
    $tipo = (string) ($intent['tipo'] ?? '');
    if ($tipo !== '') {
        $pendentes = array_values(array_filter($pendentes, static fn ($p) => ($p['tipo'] ?? '') === $tipo));
    }

    if (count($pendentes) === 1) {
        $intent['apontamento_id'] = (int) $pendentes[0]['id'];
        $intent['_concluir_tipo'] = (string) $pendentes[0]['tipo'];
    } elseif (count($pendentes) > 1) {
        $intent['_pendentes_opcao'] = array_slice($pendentes, 0, 8);
    }

    return $intent;
}

function iaProximaPerguntaConcluir(array $intent, array $resolucao, array $contexto): ?array
{
    $intent = iaPrepararIntentConcluir($intent, $contexto);

    if (empty($intent['apontamento_id']) && !empty($intent['_pendentes_opcao'])) {
        return [
            'campo' => 'pendente_escolha',
            'pergunta' => 'Qual pendente concluir?' . iaHintDialogoLista(),
        ];
    }

    if (empty($intent['apontamento_id'])) {
        return null;
    }

    $tipo = (string) ($intent['_concluir_tipo'] ?? $intent['tipo'] ?? '');
    if ($tipo === '' && !empty($intent['apontamento_id'])) {
        foreach ($contexto['pendentes'] ?? [] as $p) {
            if ((int) ($p['id'] ?? 0) === (int) $intent['apontamento_id']) {
                $tipo = (string) ($p['tipo'] ?? '');
                break;
            }
        }
    }

    if ($tipo === 'colheita') {
        $qtd = $intent['quantidade'] ?? null;
        if ($qtd === null || !is_numeric($qtd) || (float) $qtd <= 0) {
            return [
                'campo' => 'quantidade',
                'pergunta' => 'Quanto colheu? Informe a quantidade para concluir a colheita.',
            ];
        }
    }

    return null;
}

function iaProximaPerguntaCancelar(array $intent): ?array
{
    if (empty($intent['apontamento_id']) && empty($intent['_cancel_confirmado'])) {
        return [
            'campo' => 'cancel_confirmacao',
            'pergunta' => 'Confirma cancelar o último apontamento?' . iaHintDialogoLista(),
        ];
    }
    return null;
}

function iaProximaPerguntaEditar(array $intent): ?array
{
    if (trim((string) ($intent['observacoes'] ?? '')) === '') {
        return [
            'campo' => 'observacoes',
            'pergunta' => 'Qual observação você quer salvar nesse apontamento?',
        ];
    }
    return null;
}

function iaMarcarFlagsDialogoPreenchidos(array $intent): array
{
    if (!empty($intent['data']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $intent['data'])) {
        $intent['_data_respondida'] = true;
    }
    if (!empty($intent['area_nomes'])) {
        /* resolvido depois */
    }
    if (array_key_exists('previsao_dias', $intent) && $intent['previsao_dias'] !== null) {
        $intent['_previsao_respondida'] = true;
    }
    if (!empty($intent['observacoes']) || ($intent['_obs_respondida'] ?? false)) {
        $intent['_obs_respondida'] = true;
    }
    return $intent;
}

function iaProximaPerguntaCriar(array $intent, array $resolucao, array $contexto): ?array
{
    if (($intent['acao'] ?? '') !== 'criar_apontamento') {
        return null;
    }

    $intent = iaMarcarFlagsDialogoPreenchidos($intent);
    $tipo = (string) ($intent['tipo'] ?? '');

    if ($tipo === '') {
        return [
            'campo' => 'tipo',
            'pergunta' => 'O que foi hoje?' . iaHintDialogoLista(),
        ];
    }

    if (!in_array($tipo, iaTiposComDialogo(), true)) {
        return null;
    }

    if (empty($intent['_data_respondida'])) {
        return [
            'campo' => 'data',
            'pergunta' => 'Quando foi? Pode ser hoje, ontem, ou a data — tipo 15 de maio.',
        ];
    }

    if (empty($resolucao['area_ids'])) {
        if (empty($intent['area_nomes'])) {
            return [
                'campo' => 'area',
                'pergunta' => 'Em qual área?' . iaHintDialogoLista(),
            ];
        }

        return [
            'campo' => 'area',
            'pergunta' => 'Não achei essa área. Qual delas?' . iaHintDialogoLista(),
        ];
    }

    if (iaTipoUsaInsumo($tipo) && trim((string) ($intent['insumo_nome'] ?? '')) === '') {
        $rotulo = iaNomeInsumo($tipo);
        return [
            'campo' => 'insumo',
            'pergunta' => "Qual {$rotulo} você usou?" . iaHintDialogoLista(),
        ];
    }

    if ($tipo !== 'personalizado' && !iaTipoUsaInsumo($tipo) && empty($resolucao['produto_ids'])) {
        if (empty($intent['produto_nomes'])) {
            return [
                'campo' => 'produto',
                'pergunta' => 'Qual cultura ou produto?' . iaHintDialogoLista(),
            ];
        }

        return [
            'campo' => 'produto',
            'pergunta' => 'Não encontrei esse produto. Qual é o correto?' . iaHintDialogoLista(),
        ];
    }

    if ($tipo === 'personalizado' && trim((string) ($intent['titulo'] ?? '')) === '') {
        return [
            'campo' => 'titulo',
            'pergunta' => 'Qual o título deste manejo personalizado?',
        ];
    }

    $perguntaQtd = iaPerguntaQuantidade($tipo);
    if ($perguntaQtd !== null) {
        $qtd = $intent['quantidade'] ?? null;
        if ($qtd === null || !is_numeric($qtd) || (float) $qtd <= 0) {
            return ['campo' => 'quantidade', 'pergunta' => $perguntaQtd];
        }
    }

    if ($tipo === 'semeadura' && empty($intent['tipo_semeadura'])) {
        return [
            'campo' => 'tipo_semeadura',
            'pergunta' => 'Qual o tipo de semeadura?' . iaHintDialogoLista(),
        ];
    }

    if ($tipo === 'irrigacao' && empty($intent['_tempo_respondido'])) {
        return [
            'campo' => 'tempo_irrigacao',
            'pergunta' => 'Quanto tempo durou a irrigação? Em horas ou minutos — ou diga pular.',
        ];
    }

    if ($tipo === 'plantio' && empty($intent['_previsao_respondida'])) {
        return [
            'campo' => 'previsao',
            'pergunta' => 'Quantos dias para a previsão de colheita? Se não quiser marcar, diga pular.',
        ];
    }

    if (empty($intent['_obs_respondida'])) {
        return [
            'campo' => 'observacoes',
            'pergunta' => 'Quer acrescentar alguma observação? Se não, diga pular.',
        ];
    }

    return null;
}

function iaPerguntaQuantidade(string $tipo): ?string
{
    return match ($tipo) {
        'irrigacao' => 'Qual volume foi irrigado? Litros ou metros cúbicos.',
        'colheita' => 'Quanto colheu? Pode falar em quilos, caixas ou sacas.',
        'semeadura' => 'Qual a quantidade semeada? Sementes, bandejas, mudas ou quilos.',
        'plantio' => 'Quanto plantou? Mudas, sacas, bandejas ou quilos.',
        'herbicida' => 'Quanto aplicou? Litros, mililitros ou gramas.',
        'fungicida' => 'Qual a dose? Litros, mililitros ou gramas.',
        'inseticida' => 'Qual a quantidade? Litros ou mililitros.',
        'fertilizante' => 'Quanto aplicou? Quilos, litros ou gramas.',
        default => null,
    };
}

function iaNomeInsumo(string $tipo): string
{
    return match ($tipo) {
        'herbicida' => 'herbicida',
        'fungicida' => 'fungicida',
        'inseticida' => 'inseticida',
        'fertilizante' => 'fertilizante',
        default => 'insumo',
    };
}

/** Ordem dos passos do diálogo (para barra de progresso). */
function iaCamposDialogoOrdem(array $intent): array
{
    $tipo = (string) ($intent['tipo'] ?? '');
    $campos = [];
    if ($tipo === '') {
        $campos[] = 'tipo';
    }
    $campos[] = 'data';
    $campos[] = 'area';
    if ($tipo === 'personalizado') {
        $campos[] = 'titulo';
    } elseif (iaTipoUsaInsumo($tipo)) {
        $campos[] = 'insumo';
    } elseif ($tipo !== '') {
        $campos[] = 'produto';
    }
    if (in_array($tipo, ['irrigacao', 'colheita', 'semeadura', 'plantio'], true) || iaTipoUsaInsumo($tipo)) {
        $campos[] = 'quantidade';
    }
    if ($tipo === 'semeadura') {
        $campos[] = 'tipo_semeadura';
    }
    if ($tipo === 'irrigacao') {
        $campos[] = 'tempo_irrigacao';
    }
    if ($tipo === 'plantio') {
        $campos[] = 'previsao';
    }
    $campos[] = 'observacoes';
    return $campos;
}

function iaProgressoDialogo(array $intent, string $campoAtual): array
{
    if (($intent['acao'] ?? '') === 'concluir_apontamento') {
        $campos = [];
        if (!empty($intent['_pendentes_opcao'])) {
            $campos[] = 'pendente_escolha';
        }
        $tipoColheita = (string) ($intent['_concluir_tipo'] ?? $intent['tipo'] ?? '');
        if ($tipoColheita === 'colheita') {
            $campos[] = 'quantidade';
        }
        if ($campos) {
            $idx = array_search($campoAtual, $campos, true);
            return [
                'passo' => $idx === false ? 1 : $idx + 1,
                'total' => count($campos),
            ];
        }
    }

    $ordem = iaCamposDialogoOrdem($intent);
    $idx = array_search($campoAtual, $ordem, true);
    $passo = $idx === false ? 1 : $idx + 1;
    return ['passo' => $passo, 'total' => max(1, count($ordem))];
}

function iaNomeTipoManejo(?string $tipo): string
{
    return match ($tipo) {
        'plantio' => 'um plantio',
        'semeadura' => 'uma semeadura',
        'colheita' => 'uma colheita',
        'irrigacao' => 'uma irrigação',
        'herbicida' => 'uma aplicação de herbicida',
        'fungicida' => 'uma aplicação de fungicida',
        'inseticida' => 'uma aplicação de inseticida',
        'fertilizante' => 'uma adubação ou fertilização',
        'personalizado' => 'um manejo personalizado',
        default => 'um apontamento',
    };
}

function iaFormatarDataFala(string $data): string
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        return $data;
    }
    if ($data === date('Y-m-d')) {
        return 'hoje';
    }
    if ($data === date('Y-m-d', strtotime('-1 day'))) {
        return 'ontem';
    }
    $ts = strtotime($data);
    return $ts ? date('d/m/Y', $ts) : $data;
}

function iaConfirmarResposta(string $campo, string $texto, array $intent): string
{
    return match ($campo) {
        'tipo' => 'Combinado, ' . iaNomeTipoManejo((string) ($intent['tipo'] ?? '')) . '.',
        'data' => 'Anotei: ' . iaFormatarDataFala((string) ($intent['data'] ?? '')) . '.',
        'area' => 'Show, ' . ($intent['area_nomes'][0] ?? $texto) . '.',
        'produto' => 'Certo, ' . ($intent['produto_nomes'][0] ?? $texto) . '.',
        'insumo' => 'Beleza, ' . ($intent['insumo_nome'] ?? $texto) . '.',
        'quantidade' => 'Ok, '
            . ($intent['quantidade'] ?? '')
            . (($intent['unidade'] ?? '') ? ' ' . $intent['unidade'] : '') . '.',
        'previsao' => empty($intent['previsao_dias'])
            ? 'Sem previsão de colheita, tudo bem.'
            : 'Previsão de ' . (int) $intent['previsao_dias'] . ' dias, anotado.',
        'observacoes' => trim((string) ($intent['observacoes'] ?? '')) !== ''
            ? 'Observação incluída.'
            : 'Sem observações então.',
        'titulo' => 'Título anotado.',
        default => 'Entendi.',
    };
}

function iaMontarFalaAssistente(array $intent, string $pergunta, string $campoAtual, ?array $contexto = null): string
{
    $partes = [];

    if (!empty($intent['_ultimo_campo']) && !empty($intent['_ultimo_texto'])) {
        $partes[] = iaConfirmarResposta(
            (string) $intent['_ultimo_campo'],
            (string) $intent['_ultimo_texto'],
            $intent
        );
    } elseif ($campoAtual === 'data' && !empty($intent['tipo']) && empty($intent['_data_respondida'])) {
        $nome = iaNomeTipoManejo((string) $intent['tipo']);
        if (!empty($intent['_veio_sugestao'])) {
            $partes[] = "Entendi — você quer registrar {$nome}.";
        } else {
            $partes[] = "Beleza, vamos registrar {$nome}.";
        }
    } elseif ($campoAtual === 'tipo') {
        $partes[] = 'Me fala qual manejo você quer registrar.';
    } elseif ($campoAtual === 'pendente_escolha' && !empty($intent['_pendentes_opcao'])) {
        $lista = [];
        foreach ($intent['_pendentes_opcao'] as $i => $p) {
            $n = $i + 1;
            $tipo = iaTiposManejo()[$p['tipo'] ?? ''] ?? ($p['tipo'] ?? 'manejo');
            $lista[] = "{$n}, {$tipo}"
                . (!empty($p['areas']) ? ' em ' . $p['areas'] : '')
                . (!empty($p['data']) ? ', ' . iaFormatarDataFala((string) $p['data']) : '');
        }
        $partes[] = 'Tenho ' . count($lista) . ' opções: ' . implode('. ', $lista) . '.';
    } elseif ($contexto !== null && iaDialogoOpcoes($campoAtual, $intent, $contexto)) {
        $ops = iaDialogoOpcoes($campoAtual, $intent, $contexto);
        $labels = array_map(static fn ($o) => (string) ($o['label'] ?? ''), $ops);
        $amostra = array_slice(array_filter($labels), 0, 6);
        if ($amostra) {
            $partes[] = 'Na tela: ' . implode(', ', $amostra)
                . (count($labels) > count($amostra) ? ', e outras' : '') . '.';
        }
    }

    $partes[] = $pergunta;

    return iaUnirFrasesFala($partes);
}

/** Junta frases com pausas naturais para TTS. */
function iaUnirFrasesFala(array $partes): string
{
    $texto = trim(implode(' ', array_filter(array_map('trim', $partes))));
    return preg_replace('/\s+/', ' ', $texto) ?? $texto;
}

function iaLimparIntentCliente(array $intent): array
{
    unset(
        $intent['_ultimo_campo'],
        $intent['_ultimo_texto'],
        $intent['_dialogo_ack'],
        $intent['_veio_sugestao']
    );
    return $intent;
}

function iaDeveDialogar(array $intent, array $resolucao, array $contexto): bool
{
    return iaProximaPergunta($intent, $resolucao, $contexto) !== null;
}

function iaInferirUnidade(string $texto): ?string
{
    $t = mb_strtolower($texto);
    if (preg_match('/\bm[³3]|metro c/u', $t)) {
        return 'm3';
    }
    if (preg_match('/litro/u', $t)) {
        return 'litros';
    }
    if (preg_match('/\bkg|quilo/u', $t)) {
        return 'kg';
    }
    if (preg_match('/caixa/u', $t)) {
        return 'caixas';
    }
    if (preg_match('/saca/u', $t)) {
        return 'sacas';
    }
    if (preg_match('/semente/u', $t)) {
        return 'sementes';
    }
    if (preg_match('/bandeja/u', $t)) {
        return 'bandejas';
    }
    if (preg_match('/muda/u', $t)) {
        return 'mudas';
    }
    if (preg_match('/\bml\b|mililitro/u', $t)) {
        return 'ml';
    }
    if (preg_match('/\bg\b|grama/u', $t)) {
        return 'g';
    }
    return null;
}

function iaExtrairNumero(string $texto): ?float
{
    if (preg_match('/(\d+(?:[.,]\d+)?)/u', $texto, $m)) {
        return (float) str_replace(',', '.', $m[1]);
    }
    return null;
}

function iaNormalizarTipoManejo(string $texto): ?string
{
    $t = iaNormalizarTexto(iaCorrigirTranscricaoPt($texto, 'tipo'));
    if (str_contains($t, 'irrig')) {
        return 'irrigacao';
    }
    if (str_contains($t, 'colh')) {
        return 'colheita';
    }
    if (preg_match('/\b(?:semead|sem(?:ei|ead)?)\b/u', $t)) {
        return 'semeadura';
    }
    if (preg_match('/\b(?:plant(?:io|ei|ar|o)?|plan(?:to|tei)?)\b/u', $t) || preg_match('/\bplan\s*[12]\b/u', $t)) {
        return 'plantio';
    }
    if (str_contains($t, 'herbi')) {
        return 'herbicida';
    }
    if (str_contains($t, 'fungi')) {
        return 'fungicida';
    }
    if (str_contains($t, 'inset')) {
        return 'inseticida';
    }
    if (str_contains($t, 'ferti') || str_contains($t, 'adub')) {
        return 'fertilizante';
    }
    if (str_contains($t, 'personal')) {
        return 'personalizado';
    }
    return null;
}

function iaNormalizarTipoSemeadura(string $texto): ?string
{
    $t = iaNormalizarTexto($texto);
    foreach (['Direta', 'Bandeja', 'Canteiro', 'Replantio'] as $tipo) {
        if (str_contains($t, iaNormalizarTexto($tipo))) {
            return $tipo;
        }
    }
    if (str_contains($t, 'bandeja')) {
        return 'Bandeja';
    }
    if (str_contains($t, 'canteiro')) {
        return 'Canteiro';
    }
    if (str_contains($t, 'replanti')) {
        return 'Replantio';
    }
    return 'Direta';
}

function iaUnidadePadraoPorTipo(string $tipo): ?string
{
    return match ($tipo) {
        'colheita' => 'kg',
        'irrigacao' => 'litros',
        'semeadura' => 'sementes',
        'plantio' => 'mudas',
        'herbicida', 'fungicida', 'inseticida' => 'litros',
        'fertilizante' => 'kg',
        default => null,
    };
}

function iaResolverEscolhaPendente(string $texto, array $opcoes): ?array
{
    if (!$opcoes) {
        return null;
    }
    $t = iaNormalizarTexto($texto);
    $limpo = trim($texto);

    if (preg_match('/^\d+$/u', $limpo)) {
        $num = (int) $limpo;
        if ($num >= 1 && $num <= count($opcoes)) {
            return $opcoes[$num - 1] ?? null;
        }
        foreach ($opcoes as $op) {
            if ((int) ($op['id'] ?? 0) === $num) {
                return $op;
            }
        }
    }

    if (preg_match('/\b(\d+)\b/u', $t, $m)) {
        $num = (int) $m[1];
        if ($num >= 1 && $num <= count($opcoes)) {
            return $opcoes[$num - 1] ?? null;
        }
        foreach ($opcoes as $op) {
            if ((int) ($op['id'] ?? 0) === $num) {
                return $op;
            }
        }
    }

    $ordinais = [
        '/\b(?:primeir[oa]|1)\b/u' => 0,
        '/\b(?:segund[oa]|2)\b/u' => 1,
        '/\b(?:terceir[oa]|3)\b/u' => 2,
        '/\b(?:quart[oa]|4)\b/u' => 3,
        '/\b(?:quint[oa]|5)\b/u' => 4,
        '/\b(?:sext[oa]|6)\b/u' => 5,
        '/\b(?:s[eé]tim[oa]|7)\b/u' => 6,
        '/\b(?:oitav[oa]|8)\b/u' => 7,
    ];
    foreach ($ordinais as $padrao => $idx) {
        if (preg_match($padrao, $t)) {
            return $opcoes[$idx] ?? null;
        }
    }

    foreach ($opcoes as $op) {
        $area = iaNormalizarTexto((string) ($op['areas'] ?? ''));
        if ($area !== '' && str_contains($t, $area)) {
            return $op;
        }
        $tipo = iaNormalizarTexto((string) ($op['tipo'] ?? ''));
        if ($tipo !== '' && str_contains($t, $tipo)) {
            return $op;
        }
    }

    return null;
}

/**
 * Incorpora a resposta falada/digitada no intent parcial.
 */
function iaMesclarRespostaDialogo(array $intent, string $campo, string $texto, array $contexto): array
{
    $texto = trim($texto);
    $intent = iaNormalizarIntent($intent);

    switch ($campo) {
        case 'tipo':
            if (preg_match('/^\d+$/u', $texto)) {
                $ids = iaTiposComDialogo();
                $idx = (int) $texto - 1;
                if (isset($ids[$idx])) {
                    $intent['tipo'] = $ids[$idx];
                    break;
                }
            }
            $tipo = iaNormalizarTipoManejo($texto);
            if ($tipo) {
                $intent['tipo'] = $tipo;
            }
            break;

        case 'data':
            $data = iaNormalizarDataResposta($texto);
            if ($data) {
                $intent['data'] = $data;
                $intent['_data_respondida'] = true;
            }
            break;

        case 'area':
            $nome = iaResolverEscolhaCatalogo($texto, $contexto['areas'] ?? []);
            $intent['area_nomes'] = [$nome !== '' ? $nome : $texto];
            break;

        case 'produto':
            $nome = iaResolverEscolhaCatalogo($texto, $contexto['produtos'] ?? []);
            $intent['produto_nomes'] = [$nome !== '' ? $nome : $texto];
            break;

        case 'insumo':
            $tipo = (string) ($intent['tipo'] ?? '');
            $catalogo = iaListaInsumosContexto($contexto, $tipo);
            $nome = iaResolverEscolhaCatalogo($texto, $catalogo);
            $intent['insumo_nome'] = $nome !== '' ? $nome : $texto;
            break;

        case 'quantidade':
            $num = iaExtrairNumero($texto);
            if ($num !== null) {
                $intent['quantidade'] = $num;
            }
            $un = iaInferirUnidade($texto);
            if ($un) {
                $intent['unidade'] = $un;
            } elseif (empty($intent['unidade'])) {
                $padrao = iaUnidadePadraoPorTipo((string) ($intent['tipo'] ?? ''));
                if ($padrao) {
                    $intent['unidade'] = $padrao;
                }
            }
            break;

        case 'tipo_semeadura':
            if (preg_match('/^\d+$/u', $texto)) {
                $opcoes = ['Direta', 'Bandeja', 'Canteiro', 'Replantio'];
                $idx = (int) $texto - 1;
                if (isset($opcoes[$idx])) {
                    $intent['tipo_semeadura'] = $opcoes[$idx];
                    break;
                }
            }
            $intent['tipo_semeadura'] = iaNormalizarTipoSemeadura($texto);
            break;

        case 'previsao':
            $intent['_previsao_respondida'] = true;
            if (iaUsuarioPulouCampo($texto)) {
                $intent['previsao_dias'] = null;
            } else {
                $num = iaExtrairNumero($texto);
                if ($num !== null && $num > 0) {
                    $intent['previsao_dias'] = (int) round($num);
                }
            }
            break;

        case 'observacoes':
            $intent['_obs_respondida'] = true;
            if (!iaUsuarioPulouCampo($texto)) {
                $intent['observacoes'] = $texto;
            }
            break;

        case 'titulo':
            $intent['titulo'] = $texto;
            break;

        case 'tempo_irrigacao':
            $intent['_tempo_respondido'] = true;
            if (!iaUsuarioPulouCampo($texto)) {
                $num = iaExtrairNumero($texto);
                if ($num !== null && $num > 0) {
                    $intent['tempo_irrigacao'] = $num;
                    $intent['unidade_tempo'] = preg_match('/min/u', iaNormalizarTexto($texto)) ? 'minutos' : 'horas';
                }
            }
            break;

        case 'pendente_escolha':
            $escolha = iaResolverEscolhaPendente($texto, $intent['_pendentes_opcao'] ?? []);
            if ($escolha) {
                $intent['apontamento_id'] = (int) $escolha['id'];
                $intent['_concluir_tipo'] = (string) ($escolha['tipo'] ?? '');
                unset($intent['_pendentes_opcao']);
            }
            break;

        case 'cancel_confirmacao':
            if (preg_match('/\b(?:sim|confirmo|pode|ok)\b/u', iaNormalizarTexto($texto))) {
                $intent['_cancel_confirmado'] = true;
            } else {
                $intent['acao'] = 'desconhecido';
                $intent['mensagem'] = 'Ok, mantive o apontamento.';
            }
            break;
    }

    $intent['confianca'] = min(1.0, ((float) ($intent['confianca'] ?? 0.5)) + 0.15);

    return iaNormalizarIntent($intent);
}
