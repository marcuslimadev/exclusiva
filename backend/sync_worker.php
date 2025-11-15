<?php
/**
 * Worker de sincronização de imóveis - Duas fases
 * Fase 1: Percorre TODAS as páginas e salva dados básicos
 * Fase 2: Busca detalhes apenas dos imóveis que precisam atualização
 * 
 * VERSÃO CORRIGIDA - Schema PostgreSQL
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
echo "📌 Versão: 3.0 - Backend Lumen + PostgreSQL\n";

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
            'User-Agent: Sync-Worker-Backend/3.0'
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

// ============== GEOCODIFICAÇÃO NOMINATIM ==============

function geocode_address($endereco)
{
    // Montar query de busca
    $logradouro = trim($endereco['logradouro'] ?? '');
    $numero = trim($endereco['numero'] ?? '');
    $bairro = trim($endereco['bairro'] ?? '');
    $cidade = trim($endereco['cidade'] ?? '');
    $estado = trim($endereco['estado'] ?? '');
    
    // Se cidade está vazia, assumir Belo Horizonte/MG (base da Exclusiva Lar)
    if (empty($cidade)) {
        $cidade = 'Belo Horizonte';
        $estado = 'MG';
    }
    
    // Se não tem endereço mínimo, retorna null
    if (empty($bairro) && empty($logradouro)) {
        return ['lat' => null, 'lng' => null];
    }
    
    // Construir query de busca (do mais específico para o mais geral)
    $queries = [];
    
    // Tentar com endereço completo
    if ($logradouro && $numero) {
        $queries[] = "{$logradouro}, {$numero}, {$bairro}, {$cidade}, {$estado}, Brasil";
    }
    
    // Tentar sem número
    if ($logradouro && $bairro) {
        $queries[] = "{$logradouro}, {$bairro}, {$cidade}, {$estado}, Brasil";
    }
    
    // Tentar apenas bairro + cidade
    if ($bairro) {
        $queries[] = "{$bairro}, {$cidade}, {$estado}, Brasil";
    }
    
    // Tentar apenas cidade
    $queries[] = "{$cidade}, {$estado}, Brasil";
    
    foreach ($queries as $query) {
        $coords = nominatim_search($query);
        if ($coords['lat'] && $coords['lng']) {
            echo "   🗺️  Geocodificado: {$query} → {$coords['lat']}, {$coords['lng']}\n";
            return $coords;
        }
        
        // Rate limiting: Nominatim exige 1 segundo entre requests
        usleep(1100000); // 1.1 segundos
    }
    
    echo "   ⚠️  Não foi possível geocodificar: {$bairro}, {$cidade}, {$estado}\n";
    return ['lat' => null, 'lng' => null];
}

function nominatim_search($query)
{
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'q' => $query,
        'format' => 'json',
        'limit' => 1,
        'addressdetails' => 1,
        'countrycodes' => 'br'
    ]);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Exclusiva-Lar-Sync/3.0 (contato@exclusivalarimoveis.com.br)'
        ]
    ]);
    
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['lat' => null, 'lng' => null];
    }
    
    $data = json_decode($resp, true);
    
    if (is_array($data) && count($data) > 0) {
        return [
            'lat' => $data[0]['lat'] ?? null,
            'lng' => $data[0]['lon'] ?? null
        ];
    }
    
    return ['lat' => null, 'lng' => null];
}

// ============== FASE 1: SALVAR LISTA COMPLETA ==============

function upsert_basico($row)
{
    // Mapear finalidade para valores aceitos pelo schema
    $finalidade_raw = $row['finalidadeImovel'] ?? 'Venda';
    $finalidade_map = [
        'Locação' => 'Aluguel',
        'Venda' => 'Venda',
        'Aluguel' => 'Aluguel',
        'Venda/Aluguel' => 'Venda/Aluguel',
        'Venda / Aluguel' => 'Venda/Aluguel',
    ];
    $finalidade = $finalidade_map[$finalidade_raw] ?? 'Venda';
    
    $data = [
        'codigo_imovel' => $row['codigoImovel'],
        'referencia_imovel' => $row['referenciaImovel'] ?? null,
        'finalidade_imovel' => $finalidade,
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
    
    // Mapear finalidade para valores aceitos pelo schema
    $finalidade_raw = $d['finalidadeImovel'] ?? 'Venda';
    $finalidade_map = [
        'Locação' => 'Aluguel',
        'Venda' => 'Venda',
        'Aluguel' => 'Aluguel',
        'Venda/Aluguel' => 'Venda/Aluguel',
        'Venda / Aluguel' => 'Venda/Aluguel',
    ];
    $finalidade = $finalidade_map[$finalidade_raw] ?? 'Venda';
    
    // Determinar valores (venda/aluguel baseado na finalidade)
    $valor_venda = null;
    $valor_aluguel = null;
    $finalidade_lower = strtolower($finalidade);
    
    if (strpos($finalidade_lower, 'venda') !== false) {
        $valor_venda = $d['valorEsperado'] ?? null;
    }
    if (strpos($finalidade_lower, 'aluguel') !== false) {
        $valor_aluguel = $d['valorEsperado'] ?? null;
    }
    
    // Coordenadas - tentar da API primeiro, depois geocodificar
    $latitude = $d['endereco']['latitude'] ?? null;
    $longitude = $d['endereco']['longitude'] ?? null;
    
    // Se não tem coordenadas, tentar geocodificar pelo endereço
    if (empty($latitude) || empty($longitude)) {
        $coords = geocode_address($d['endereco'] ?? []);
        $latitude = $coords['lat'];
        $longitude = $coords['lng'];
    }
    
    // Coletar imagens em array JSON
    $imagens = [];
    $imagem_destaque = null;
    if (!empty($d['imagens'])) {
        foreach ($d['imagens'] as $img) {
            $imagens[] = [
                'url' => $img['url'],
                'destaque' => (bool)($img['destaque'] ?? false)
            ];
            if (($img['destaque'] ?? false) && !$imagem_destaque) {
                $imagem_destaque = $img['url'];
            }
        }
    }
    
    // Se não tem imagem destaque, pega a primeira
    if (!$imagem_destaque && !empty($imagens)) {
        $imagem_destaque = $imagens[0]['url'];
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
            'finalidade_imovel' => $finalidade,
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
            'latitude' => $latitude,
            'longitude' => $longitude,
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
