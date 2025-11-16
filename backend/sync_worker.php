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
define('FORCE_FULL_UPDATE', false); // Forçar atualização completa (ignora cache de 4 horas)

/**
 * Formata descrição de texto plano para HTML
 */
function format_description_html($text) {
    if (empty($text)) {
        return null;
    }
    
    // Remove tags <p> e </p> se existirem
    $text = preg_replace('/<\/?p>/', '', $text);
    
    // Decodifica HTML entities (&amp; -> &, &lt; -> <, &gt; -> >, &ndash; -> –)
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Remove espaços extras
    $text = trim($text);
    
    // Converte quebras de linha em <br> temporariamente
    $text = str_replace(["\r\n", "\r", "\n"], "|||BR|||", $text);
    
    // Processa emojis e marcadores especiais
    $html = '';
    $lines = explode("|||BR|||", $text);
    $inList = false;
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        if (empty($line)) {
            if ($inList) {
                $html .= '</ul>';
                $inList = false;
            }
            $html .= '<br>';
            continue;
        }
        
        // Detecta títulos com emojis (🏡, ✨, 🌟, 💰, etc)
        if (preg_match('/^[🏡✨🌟💰🚗📄🎯🔑⭐🎉]/', $line)) {
            if ($inList) {
                $html .= '</ul>';
                $inList = false;
            }
            $html .= '<h3>' . htmlspecialchars($line) . '</h3>';
        }
        // Detecta itens de lista (começa com -, *, •, ou emoji seguido de **)
        elseif (preg_match('/^[\-\*•]/', $line) || preg_match('/^\*\*/', $line)) {
            if (!$inList) {
                $html .= '<ul>';
                $inList = true;
            }
            // Remove o marcador inicial
            $item = preg_replace('/^[\-\*•]\s*/', '', $line);
            // Converte **texto:** para <strong>texto:</strong> ANTES de escapar
            $item = preg_replace('/^\*\*([^:]+):\*\*/', '<strong>$1:</strong>', $item);
            // Escapa o resto mas preserva as tags <strong>
            $item = str_replace(['<strong>', '</strong>'], ['|||STRONG|||', '|||/STRONG|||'], $item);
            $item = htmlspecialchars($item, ENT_NOQUOTES);
            $item = str_replace(['|||STRONG|||', '|||/STRONG|||'], ['<strong>', '</strong>'], $item);
            $html .= '<li>' . $item . '</li>';
        }
        // Linha normal
        else {
            if ($inList) {
                $html .= '</ul>';
                $inList = false;
            }
            // Detecta negrito **texto**
            $line = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $line);
            $line = str_replace(['<strong>', '</strong>'], ['|||STRONG|||', '|||/STRONG|||'], $line);
            $line = htmlspecialchars($line, ENT_NOQUOTES);
            $line = str_replace(['|||STRONG|||', '|||/STRONG|||'], ['<strong>', '</strong>'], $line);
            $html .= '<p>' . $line . '</p>';
        }
    }
    
    if ($inList) {
        $html .= '</ul>';
    }
    
    return $html;
}

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

function geocode_address($endereco, $codigo = null)
{
    static $geocode_cache = [];
    
    $logradouro = trim($endereco['logradouro'] ?? '');
    $numero = trim($endereco['numero'] ?? '');
    $bairro = trim($endereco['bairro'] ?? '');
    $cidade = trim($endereco['cidade'] ?? '');
    $estado = strtoupper(trim($endereco['estado'] ?? ''));
    $cep = preg_replace('/\\D/', '', $endereco['cep'] ?? '');
    
    if (empty($cidade)) {
        $cidade = 'Belo Horizonte';
        $estado = $estado ?: 'MG';
    }
    
    $cacheKey = md5(json_encode([$logradouro, $numero, $bairro, $cidade, $estado, $cep]));
    if (isset($geocode_cache[$cacheKey])) {
        return $geocode_cache[$cacheKey];
    }
    
    if (empty($bairro) && empty($logradouro) && empty($cidade)) {
        $geocode_cache[$cacheKey] = ['lat' => null, 'lng' => null];
        return $geocode_cache[$cacheKey];
    }
    
    echo "   [INFO] Geocodificando imovel " . ($codigo ?? 'N/A') . "...
";
    
    if (!empty($cep)) {
        $coords = geocode_via_cep($cep);
        if ($coords['lat'] && $coords['lng']) {
            echo "   [OK] Coordenadas via ViaCEP: {$coords['lat']}, {$coords['lng']}
";
            $geocode_cache[$cacheKey] = $coords;
            return $coords;
        }
    }
    
    $queries = [];
    if ($logradouro && $numero) {
        $queries[] = "{$logradouro}, {$numero}, {$bairro}, {$cidade}, {$estado}, Brasil";
    }
    if ($logradouro) {
        $queries[] = "{$logradouro}, {$bairro}, {$cidade}, {$estado}, Brasil";
    }
    if ($bairro) {
        $queries[] = "{$bairro}, {$cidade}, {$estado}, Brasil";
    }
    $queries[] = "{$cidade}, {$estado}, Brasil";
    
    foreach ($queries as $query) {
        $coords = nominatim_search($query);
        if ($coords['lat'] && $coords['lng']) {
            echo "   [OK] Geocodificado: {$query} -> {$coords['lat']}, {$coords['lng']}
";
            $geocode_cache[$cacheKey] = $coords;
            return $coords;
        }
    }
    
    if ($estado) {
        $coords = get_state_coordinates($estado);
        if ($coords['lat'] && $coords['lng']) {
            echo "   [WARN] Coordenadas aproximadas do estado {$estado}: {$coords['lat']}, {$coords['lng']}
";
            $geocode_cache[$cacheKey] = $coords;
            return $coords;
        }
    }
    
    echo "   [WARN] Nao foi possivel geocodificar: {$bairro}, {$cidade}, {$estado}
";
    $geocode_cache[$cacheKey] = ['lat' => null, 'lng' => null];
    return $geocode_cache[$cacheKey];
}

function geocode_via_cep($cep)
{
    $cep = preg_replace('/\\D/', '', $cep);
    if (strlen($cep) !== 8) {
        return ['lat' => null, 'lng' => null];
    }
    
    $url = "https://viacep.com.br/ws/{$cep}/json/";
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'method' => 'GET',
            'header' => "User-Agent: Exclusiva-Lar-Sync/3.0
"
        ]
    ]);
    
    $resp = @file_get_contents($url, false, $context);
    if ($resp === false) {
        return ['lat' => null, 'lng' => null];
    }
    
    $data = json_decode($resp, true);
    if (empty($data) || !empty($data['erro'])) {
        return ['lat' => null, 'lng' => null];
    }
    
    $parts = array_filter([
        $data['logradouro'] ?? null,
        $data['bairro'] ?? null,
        ($data['localidade'] ?? '') . ' - ' . ($data['uf'] ?? ''),
        'Brasil'
    ]);
    
    if (empty($parts)) {
        return ['lat' => null, 'lng' => null];
    }
    
    $query = implode(', ', $parts);
    return nominatim_search($query);
}

function nominatim_search($query)
{
    static $lastCall = 0;
    
    $now = microtime(true);
    if ($lastCall > 0) {
        $elapsed = $now - $lastCall;
        if ($elapsed < 1.1) {
            usleep((int)((1.1 - $elapsed) * 1000000));
        }
    }
    
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
    $lastCall = microtime(true);
    
    if ($httpCode !== 200) {
        return ['lat' => null, 'lng' => null];
    }
    
    $data = json_decode($resp, true);
    
    if (is_array($data) && count($data) > 0) {
        return [
            'lat' => isset($data[0]['lat']) ? (float)$data[0]['lat'] : null,
            'lng' => isset($data[0]['lon']) ? (float)$data[0]['lon'] : null
        ];
    }
    
    return ['lat' => null, 'lng' => null];
}

function get_state_coordinates($estado)
{
    $coords = [
        'AC' => ['lat' => -9.0238, 'lng' => -70.8120],
        'AL' => ['lat' => -9.5713, 'lng' => -36.7820],
        'AP' => ['lat' => 1.4061, 'lng' => -51.6022],
        'AM' => ['lat' => -3.4168, 'lng' => -65.8561],
        'BA' => ['lat' => -12.5797, 'lng' => -41.7007],
        'CE' => ['lat' => -5.4984, 'lng' => -39.3206],
        'DF' => ['lat' => -15.7998, 'lng' => -47.8645],
        'ES' => ['lat' => -19.1834, 'lng' => -40.3089],
        'GO' => ['lat' => -15.8270, 'lng' => -49.8362],
        'MA' => ['lat' => -4.9609, 'lng' => -45.2744],
        'MT' => ['lat' => -12.6819, 'lng' => -56.9211],
        'MS' => ['lat' => -20.7722, 'lng' => -54.7852],
        'MG' => ['lat' => -19.9167, 'lng' => -43.9345],
        'PA' => ['lat' => -3.7970, 'lng' => -52.4751],
        'PB' => ['lat' => -7.2399, 'lng' => -36.7819],
        'PR' => ['lat' => -24.8940, 'lng' => -51.5555],
        'PE' => ['lat' => -8.8137, 'lng' => -36.9541],
        'PI' => ['lat' => -6.6000, 'lng' => -42.2800],
        'RJ' => ['lat' => -22.9068, 'lng' => -43.1729],
        'RN' => ['lat' => -5.4026, 'lng' => -36.9541],
        'RS' => ['lat' => -30.0346, 'lng' => -51.2177],
        'RO' => ['lat' => -10.9472, 'lng' => -62.8278],
        'RR' => ['lat' => 1.3227, 'lng' => -60.6522],
        'SC' => ['lat' => -27.2423, 'lng' => -50.2189],
        'SP' => ['lat' => -23.5505, 'lng' => -46.6333],
        'SE' => ['lat' => -10.5741, 'lng' => -37.3857],
        'TO' => ['lat' => -10.1753, 'lng' => -48.2982],
    ];
    
    $estado = strtoupper($estado);
    return $coords[$estado] ?? ['lat' => null, 'lng' => null];
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

if (defined('FORCE_FULL_UPDATE') && FORCE_FULL_UPDATE) {
    echo "⚡ MODO FORÇADO: Atualizando TODOS os imóveis (ignorando cache)\n\n";
    $ids = DB::table('imo_properties')
        ->where('active', true)
        ->orderBy('updated_at', 'asc')
        ->pluck('codigo_imovel')
        ->toArray();
} else {
    $ids = DB::table('imo_properties')
        ->where(function($query) use ($fourHoursAgo) {
            $query->whereNull('descricao')
                  ->orWhereNull('cidade')
                  ->orWhere('updated_at', '<', $fourHoursAgo);
        })
        ->orderBy('updated_at', 'asc')
        ->pluck('codigo_imovel')
        ->toArray();
}

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
        
        // Contar imagens antes de salvar (para logging)
        $numImagens = 0;
        if (isset($imovel['imagens']) && is_array($imovel['imagens'])) {
            foreach ($imovel['imagens'] as $img) {
                if (isset($img['url']) && !empty($img['url'])) {
                    $numImagens++;
                }
            }
        }
        
        upsert_detalhes($imovel);
        
        echo "✓ Imóvel {$codigo} atualizado ({$numImagens} imagens)\n";
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
        $coords = geocode_address($d['endereco'] ?? [], $codigo);
        $latitude = $coords['lat'];
        $longitude = $coords['lng'];
    }
    
    // Coletar imagens em array JSON
    $imagens = [];
    $imagem_destaque = null;
    if (!empty($d['imagens']) && is_array($d['imagens'])) {
        foreach ($d['imagens'] as $img) {
            // Garantir que a URL existe antes de adicionar
            if (isset($img['url']) && !empty($img['url'])) {
                $imagens[] = [
                    'url' => $img['url'],
                    'destaque' => (bool)($img['destaque'] ?? false)
                ];
                if (($img['destaque'] ?? false) && !$imagem_destaque) {
                    $imagem_destaque = $img['url'];
                }
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
            'descricao' => format_description_html($d['descricaoImovel'] ?? null),
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
echo "Erros: {$errors}\n";

// Verificar quantos imóveis têm imagens
$comImagens = DB::table('imo_properties')
    ->whereNotNull('imagens')
    ->where('imagens', '!=', '[]')
    ->where('imagens', '!=', '')
    ->count();

echo "Imóveis com imagens: {$comImagens}\n\n";

flock($lock, LOCK_UN);
fclose($lock);
