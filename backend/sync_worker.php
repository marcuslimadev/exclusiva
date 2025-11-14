<?php
/**
 * Worker de sincronização de imóveis - Duas fases
 * Fase 1: Percorre TODAS as páginas e salva dados básicos
 * Fase 2: Busca detalhes apenas dos imóveis que precisam atualização
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

set_time_limit(0);
ini_set('memory_limit', '512M');

define('API_TOKEN', '$2y$10$Lcn1ct.wEfBonZldcjuVQ.pD5p8gBRNrPlHjVwruaG5HAui2XCG9O');
define('API_BASE', 'https://www.exclusivalarimoveis.com.br/api/v1/app/imovel');

$lockFile = sys_get_temp_dir() . '/sync_2phase.lock';
$lock = fopen($lockFile, 'c+');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "⚠ Já existe um processo de sincronização rodando.\n";
    exit;
}

$now = date('Y-m-d H:i:s');
echo "🚀 Iniciando sincronização em duas fases em {$now}\n";
echo "📌 Versão: 2.0 - Schema PostgreSQL Correto\n";

// ============== HELPERS DE API ==============

function call_api_get($url)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'token: ' . API_TOKEN,
            'User-Agent: Sync-Worker-2Phase/1.0'
        ]
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "⚠ API retornou HTTP {$httpCode}\n";
        return null;
    }
    
    $data = json_decode($resp, true);
    return is_array($data) ? $data : null;
}

// ============== FASE 1: SALVAR LISTA COMPLETA ==============

function upsert_basico($row)
{
    $data = [
        'codigo_imovel' => $row['codigoImovel'],
        'referencia_imovel' => $row['referenciaImovel'] ?? null,
        'finalidade_imovel' => $row['finalidadeImovel'] ?? 'Venda',
        'tipo_imovel' => $row['descricaoTipoImovel'] ?? 'Residencial',
        'active' => ($row['statusImovel'] ?? false) ? 1 : 0,
        'updated_at' => date('Y-m-d H:i:s'),
    ];
    
    DB::table('imo_properties')->updateOrInsert(
        ['codigo_imovel' => $data['codigo_imovel']],
        $data
    );
}

echo "\n📋 FASE 1: Salvando lista completa de imóveis...\n";
echo "════════════════════════════════════════════════════════════════\n";

$page = 1;
$totalSaved = 0;
$maxPages = 999;

do {
    $url = API_BASE . "/lista?status=ativo&page={$page}&per_page=100";
    echo "📄 Página {$page}: {$url}\n";
    
    $lista = call_api_get($url);
    
    if (!$lista || !($lista['status'] ?? false)) {
        echo "⚠ Falha ao obter página {$page}. Parando.\n";
        break;
    }
    
    $rs = $lista['resultSet'] ?? [];
    $data = $rs['data'] ?? [];
    $totalPages = (int)($rs['total_pages'] ?? 1);
    
    echo "   ✓ Encontrados " . count($data) . " imóveis (total de páginas: {$totalPages})\n";
    
    foreach ($data as $row) {
        upsert_basico($row);
        $totalSaved++;
    }
    
    $page++;
    
    if ($page > $maxPages) {
        echo "⚠ Atingido limite de {$maxPages} páginas. Parando.\n";
        break;
    }
    
} while ($page <= $totalPages);

echo "\n✅ FASE 1 CONCLUÍDA: {$totalSaved} imóveis salvos/atualizados\n";
echo "════════════════════════════════════════════════════════════════\n";

// ============== FASE 2: BUSCAR DETALHES ==============

echo "\n📝 FASE 2: Buscando detalhes dos imóveis...\n";
echo "════════════════════════════════════════════════════════════════\n";

$fourHoursAgo = date('Y-m-d H:i:s', strtotime('-4 hours'));

$ids = DB::table('imo_properties')
    ->where(function($query) use ($fourHoursAgo) {
        $query->whereNull('descricao')
              ->orWhereNull('cidade')
              ->orWhere('updated_at', '<', $fourHoursAgo);
    })
    ->orderBy('updated_at', 'asc')
    ->pluck('codigo_imovel')
    ->toArray();

echo "   ℹ️  Total de imóveis para atualizar: " . count($ids) . "\n\n";

$updated = 0;
$errors = 0;

foreach ($ids as $codigo) {
    try {
        $url = API_BASE . "/dados/{$codigo}";
        $det = call_api_get($url);
        
        if (!$det || !($det['status'] ?? false)) {
            echo "⚠ Falha ao obter detalhes do imóvel {$codigo}\n";
            $errors++;
            continue;
        }
        
        $imovel = $det['resultSet'];
        
        upsert_detalhes($imovel);
        
        echo "✓ Imóvel {$codigo} atualizado\n";
        $updated++;
        
        usleep(100000); // 0.1s entre requisições
        
    } catch (Exception $e) {
        echo "❌ Erro ao processar imóvel {$codigo}: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n✅ FASE 2 CONCLUÍDA: {$updated} imóveis atualizados, {$errors} erros\n";
echo "════════════════════════════════════════════════════════════════\n";

// ============== FUNÇÃO DE ATUALIZAÇÃO DETALHADA ==============

function upsert_detalhes($d)
{
    // Áreas
    $area_privativa = null;
    if (isset($d['area']['privativa']['valor'])) {
        $area_privativa = (float)str_replace(',', '.', $d['area']['privativa']['valor']);
    }
    
    $area_total = null;
    if (isset($d['area']['total']['valor'])) {
        $area_total = (float)str_replace(',', '.', $d['area']['total']['valor']);
    }
    
    $area_terreno = null;
    if (isset($d['area']['terreno']['valor'])) {
        $area_terreno = (float)str_replace(',', '.', $d['area']['terreno']['valor']);
    }
    
    $codigo = $d['codigoImovel'];
    
    // Determinar valores (venda/aluguel baseado na finalidade)
    $valor_venda = null;
    $valor_aluguel = null;
    $finalidade = strtolower($d['finalidadeImovel'] ?? '');
    
    if (strpos($finalidade, 'venda') !== false) {
        $valor_venda = $d['valorEsperado'] ?? null;
    } elseif (strpos($finalidade, 'aluguel') !== false || strpos($finalidade, 'locação') !== false) {
        $valor_aluguel = $d['valorEsperado'] ?? null;
    }
    
    // Coletar imagens em array JSON
    $imagens = [];
    $imagem_destaque = null;
    if (!empty($d['imagens'])) {
        foreach ($d['imagens'] as $img) {
            $imagens[] = $img['url'];
            if (($img['destaque'] ?? false) && !$imagem_destaque) {
                $imagem_destaque = $img['url'];
            }
        }
    }
    
    // Coletar características em array JSON
    $caracteristicas = [];
    if (!empty($d['caracteristicas'])) {
        foreach ($d['caracteristicas'] as $c) {
            $caracteristicas[] = $c['nomeCaracteristica'];
        }
    }
    
    // Atualizar dados principais
    DB::table('imo_properties')
        ->where('codigo_imovel', $codigo)
        ->update([
            'finalidade_imovel' => $d['finalidadeImovel'] ?? 'Venda',
            'tipo_imovel' => $d['descricaoTipoImovel'] ?? 'Residencial',
            'dormitorios' => $d['dormitorios'] ?? 0,
            'suites' => $d['suites'] ?? 0,
            'banheiros' => $d['banheiros'] ?? 0,
            'garagem' => $d['garagem'] ?? 0,
            'valor_venda' => $valor_venda,
            'valor_aluguel' => $valor_aluguel,
            'iptu' => $d['valorIPTU'] ?? null,
            'condominio' => $d['valorCondominio'] ?? null,
            'cidade' => $d['endereco']['cidade'] ?? null,
            'estado' => $d['endereco']['estado'] ?? null,
            'bairro' => $d['endereco']['bairro'] ?? null,
            'endereco' => $d['endereco']['logradouro'] ?? null,
            'numero' => $d['endereco']['numero'] ?? null,
            'cep' => $d['endereco']['cep'] ?? null,
            'area_privativa' => $area_privativa,
            'area_total' => $area_total,
            'descricao' => $d['descricaoImovel'] ?? null,
            'imagem_destaque' => $imagem_destaque,
            'imagens' => json_encode($imagens),
            'caracteristicas' => json_encode($caracteristicas),
            'em_condominio' => ($d['emCondominio'] ?? false) ? 1 : 0,
            'aceita_financiamento' => ($d['aceitaFinanciamento'] ?? false) ? 1 : 0,
            'exibir_imovel' => ($d['exibirImovel'] ?? false) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
}

echo "\n🎉 SINCRONIZAÇÃO COMPLETA!\n";
echo "Total salvo na fase 1: {$totalSaved}\n";
echo "Total atualizado na fase 2: {$updated}\n";
echo "Erros: {$errors}\n\n";

flock($lock, LOCK_UN);
fclose($lock);
