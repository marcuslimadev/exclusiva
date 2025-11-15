<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

// Aumenta o tempo limite de execução e memória
set_time_limit(0);
ini_set('memory_limit', '256M');

$lockFile = STORAGE_DIR . '/sync.lock';
$lock = fopen($lockFile, 'c+');
if (!$lock) {
    echo "❌ Não consegui abrir o lockfile.\n";
    exit;
}
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    echo "⚠ Já existe um processo de sincronização rodando.\n";
    exit;
}

$now = date('Y-m-d H:i:s');
pdo()->prepare('UPDATE import_status SET is_running=1 WHERE id=1')->execute();

echo "🚀 Iniciando sincronização em {$now}\n";

// Cache para evitar geocodificações repetidas
$geocode_cache = [];

// ===== SISTEMA DE GEOCODIFICAÇÃO OTIMIZADO =====

/**
 * Sistema de geocodificação com cache e timeouts otimizados
 */
function geocodeWithFallbacks($logradouro, $bairro, $cidade, $estado, $cep, $codigo = null)
{
    global $geocode_cache;
    
    // Cria chave de cache baseada nos dados do endereço
    $cache_key = md5($logradouro . $bairro . $cidade . $estado . $cep);
    
    // Verifica cache primeiro
    if (isset($geocode_cache[$cache_key])) {
        echo "💾 Usando coordenadas do cache para imóvel {$codigo}\n";
        return $geocode_cache[$cache_key];
    }
    
    echo "🌍 Iniciando geocodificação para imóvel " . ($codigo ?? 'N/A') . "\n";
    
    $lat = null;
    $lng = null;
    $method = 'none';
    
    // FALLBACK 1: Tentativa via CEP usando ViaCEP (mais rápido que BrasilAPI)
    if (!empty($cep) && is_null($lat)) {
        echo "📍 Tentativa 1: Geocodificação por CEP via ViaCEP\n";
        $result = geocodeViaCep($cep);
        if (is_array($result) && count($result) >= 2 && $result[0] && $result[1]) {
            $lat = $result[0];
            $lng = $result[1];
            $method = 'viacep';
            echo "✅ Coordenadas encontradas via ViaCEP: {$lat}, {$lng}\n";
        } else {
            echo "⚠ ViaCEP não retornou coordenadas válidas para CEP: {$cep}\n";
        }
    }
    
    // FALLBACK 2: Endereço completo via Nominatim (apenas se CEP falhou)
    if (is_null($lat) && !empty($logradouro) && !empty($cidade)) {
        echo "📍 Tentativa 2: Endereço completo via Nominatim\n";
        $result = geocodeAddressNominatim($logradouro, $bairro, $cidade, $estado, $cep);
        if (is_array($result) && count($result) >= 2 && $result[0] && $result[1]) {
            $lat = $result[0];
            $lng = $result[1];
            $method = 'nominatim_full';
            echo "✅ Coordenadas encontradas via Nominatim (endereço completo): {$lat}, {$lng}\n";
        }
    }
    
    // FALLBACK 3: Só cidade + bairro via Nominatim
    if (is_null($lat) && !empty($cidade) && !empty($bairro)) {
        echo "📍 Tentativa 3: Cidade + Bairro via Nominatim\n";
        $result = geocodeCityNeighborhood($cidade, $bairro, $estado);
        if (is_array($result) && count($result) >= 2 && $result[0] && $result[1]) {
            $lat = $result[0];
            $lng = $result[1];
            $method = 'nominatim_city_neighborhood';
            echo "✅ Coordenadas encontradas via Nominatim (cidade + bairro): {$lat}, {$lng}\n";
        }
    }
    
    // FALLBACK 4: Base de dados local de cidades
    if (is_null($lat) && !empty($cidade) && !empty($estado)) {
        echo "📍 Tentativa 4: Base de dados local de cidades\n";
        $result = getCityCoordinatesFromLocalDB($cidade, $estado);
        if (is_array($result) && count($result) >= 2 && $result[0] && $result[1]) {
            $lat = $result[0];
            $lng = $result[1];
            $method = 'local_db';
            echo "✅ Coordenadas encontradas na base local: {$lat}, {$lng}\n";
        }
    }
    
    // FALLBACK 5: Coordenadas aproximadas do estado
    if (is_null($lat) && !empty($estado)) {
        echo "📍 Tentativa 5: Coordenadas aproximadas do estado\n";
        $result = getStateCoordinates($estado);
        if (is_array($result) && count($result) >= 2 && $result[0] && $result[1]) {
            $lat = $result[0];
            $lng = $result[1];
            $method = 'state_approx';
            echo "⚠ Usando coordenadas aproximadas do estado {$estado}: {$lat}, {$lng}\n";
        }
    }
    
    $final_result = [$lat, $lng, $method];
    
    if ($lat && $lng) {
        // Valida se coordenadas estão dentro do Brasil
        if ($lat >= -33.75 && $lat <= 5.27 && $lng >= -73.99 && $lng <= -28.84) {
            echo "🎯 Geocodificação concluída com sucesso usando método: {$method}\n";
            logGeocodingMethod($codigo, $method);
            
            // Armazena no cache
            $geocode_cache[$cache_key] = $final_result;
            
            return $final_result;
        } else {
            echo "⚠ Coordenadas fora do território brasileiro: {$lat}, {$lng}. Descartando.\n";
        }
    }
    
    echo "❌ Falha em todos os métodos de geocodificação\n";
    $final_result = [null, null, 'failed'];
    
    // Armazena falha no cache para evitar tentar novamente
    $geocode_cache[$cache_key] = $final_result;
    
    return $final_result;
}

/**
 * FALLBACK 1: ViaCEP + Nominatim (otimizado)
 */
function geocodeViaCep($cep)
{
    $cep = preg_replace('/\D/', '', $cep);
    if (strlen($cep) !== 8) return [null, null];

    $url = "https://viacep.com.br/ws/{$cep}/json/";
    $context = stream_context_create([
        'http' => [
            'timeout' => 5, // Reduzido para 5 segundos
            'method' => 'GET',
            'header' => 'User-Agent: PropertySync/1.0'
        ]
    ]);
    
    $resp = @file_get_contents($url, false, $context);
    
    if ($resp === false) return [null, null];

    $data = json_decode($resp, true);
    if (!empty($data['localidade']) && !empty($data['uf']) && empty($data['erro'])) {
        $result = geocodeAddressNominatim($data['logradouro'], $data['bairro'], $data['localidade'], $data['uf'], $cep);
        return is_array($result) && count($result) >= 2 ? $result : [null, null];
    }
    return [null, null];
}

/**
 * FALLBACK 2: Nominatim com endereço completo (otimizado)
 */
function geocodeAddressNominatim($logradouro, $bairro, $cidade, $estado, $cep)
{
    $query_parts = array_filter([$logradouro, $bairro, $cidade . ' - ' . $estado, $cep]);
    if (empty($query_parts)) return [null, null];
    
    $query = urlencode(implode(', ', $query_parts));
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$query}&countrycodes=br&limit=1";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 8, // Reduzido para 8 segundos
            'user_agent' => 'PropertySync/1.0'
        ]
    ]);
    
    // Rate limiting reduzido
    usleep(500000); // 0.5 segundos
    
    $resp = @file_get_contents($url, false, $context);
    if ($resp === false) return [null, null];

    $data = json_decode($resp, true);
    if (!empty($data[0]['lat']) && !empty($data[0]['lon'])) {
        return [(float)$data[0]['lat'], (float)$data[0]['lon']];
    }

    return [null, null];
}

/**
 * FALLBACK 3: Cidade + Bairro (otimizado)
 */
function geocodeCityNeighborhood($cidade, $bairro, $estado)
{
    $query = urlencode("{$bairro}, {$cidade} - {$estado}, Brasil");
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$query}&countrycodes=br&limit=1";

    $context = stream_context_create([
        'http' => [
            'timeout' => 8,
            'user_agent' => 'PropertySync/1.0'
        ]
    ]);
    
    usleep(500000); // 0.5 segundos
    
    $resp = @file_get_contents($url, false, $context);
    if ($resp === false) return [null, null];

    $data = json_decode($resp, true);
    if (!empty($data[0]['lat']) && !empty($data[0]['lon'])) {
        return [(float)$data[0]['lat'], (float)$data[0]['lon']];
    }

    return [null, null];
}

/**
 * FALLBACK 4: Base local de cidades (expandida)
 */
function getCityCoordinatesFromLocalDB($cidade, $estado)
{
    // Base expandida de cidades brasileiras
    $cities = [
        // Capitais
        'São Paulo-SP' => [-23.5505, -46.6333], 'Rio de Janeiro-RJ' => [-22.9068, -43.1729],
        'Brasília-DF' => [-15.7942, -47.8822], 'Salvador-BA' => [-12.9714, -38.5014],
        'Fortaleza-CE' => [-3.7172, -38.5433], 'Belo Horizonte-MG' => [-19.9191, -43.9386],
        'Manaus-AM' => [-3.1190, -60.0217], 'Curitiba-PR' => [-25.4244, -49.2654],
        'Recife-PE' => [-8.0476, -34.8770], 'Goiânia-GO' => [-16.6869, -49.2648],
        'Belém-PA' => [-1.4558, -48.4902], 'Porto Alegre-RS' => [-30.0346, -51.2177],
        'São Luís-MA' => [-2.5387, -44.2828], 'Maceió-AL' => [-9.6658, -35.7350],
        'Teresina-PI' => [-5.0892, -42.8019], 'Natal-RN' => [-5.7945, -35.2110],
        'João Pessoa-PB' => [-7.1195, -34.8450], 'Aracaju-SE' => [-10.9472, -37.0731],
        'Florianópolis-SC' => [-27.5954, -48.5480], 'Vitória-ES' => [-20.3155, -40.3128],
        'Campo Grande-MS' => [-20.4697, -54.6201], 'Cuiabá-MT' => [-15.6014, -56.0979],
        'Macapá-AP' => [0.0389, -51.0664], 'Rio Branco-AC' => [-9.9754, -67.8249],
        'Boa Vista-RR' => [2.8235, -60.6758], 'Porto Velho-RO' => [-8.7612, -63.9023],
        'Palmas-TO' => [-10.1753, -48.2982],
        
        // Região Metropolitana de BH
        'Contagem-MG' => [-19.9317, -44.0536], 'Betim-MG' => [-19.9678, -44.1983],
        'Nova Lima-MG' => [-19.9857, -43.8469], 'Ribeirão das Neves-MG' => [-19.7668, -44.0868],
        'Santa Luzia-MG' => [-19.7697, -43.8514], 'Vespasiano-MG' => [-19.6919, -43.9231],
        'Sabará-MG' => [-19.8842, -43.8058], 'Pedro Leopoldo-MG' => [-19.6175, -44.0430],
        'Lagoa Santa-MG' => [-19.6308, -43.8944], 'Esmeraldas-MG' => [-19.7625, -44.3144],
        'Ibirité-MG' => [-20.0219, -44.0581], 'Sete Lagoas-MG' => [-19.4658, -44.2467],
        
        // Outras cidades importantes de MG
        'Uberlândia-MG' => [-18.9113, -48.2622], 'Juiz de Fora-MG' => [-21.7587, -43.3496],
        'Montes Claros-MG' => [-16.7289, -43.8617], 'Ipatinga-MG' => [-19.4683, -42.5486],
        'Divinópolis-MG' => [-20.1439, -44.8839], 'Governador Valadares-MG' => [-18.8512, -41.9492],
        'Poços de Caldas-MG' => [-21.7879, -46.5619], 'Patos de Minas-MG' => [-18.5789, -46.5180],
        'Barbacena-MG' => [-21.2258, -43.7736], 'Varginha-MG' => [-21.5519, -45.4328],
        'Uberaba-MG' => [-19.7483, -47.9319], 'Araguari-MG' => [-18.6478, -48.1867],
        'Passos-MG' => [-20.7188, -46.6097], 'Lavras-MG' => [-21.2453, -45.0009],
        'Itajubá-MG' => [-22.4205, -45.4527], 'Pouso Alegre-MG' => [-22.2300, -45.9364],
        
        // Cidades da Grande São Paulo
        'Guarulhos-SP' => [-23.4538, -46.5333], 'Campinas-SP' => [-22.9099, -47.0626],
        'São Bernardo do Campo-SP' => [-23.6914, -46.5645], 'Santo André-SP' => [-23.6542, -46.5347],
        'Osasco-SP' => [-23.5329, -46.7913], 'São José dos Campos-SP' => [-23.2237, -45.9009],
        'Ribeirão Preto-SP' => [-21.1767, -47.8208], 'Sorocaba-SP' => [-23.5015, -47.4526],
    ];
    
    $key = $cidade . '-' . $estado;
    if (isset($cities[$key])) {
        return $cities[$key];
    }
    
    // Tenta busca case-insensitive
    foreach ($cities as $city_key => $coords) {
        if (stripos($city_key, $cidade) !== false && strpos($city_key, $estado) !== false) {
            return $coords;
        }
    }
    
    // Tenta buscar no banco se existir
    try {
        $pdo = pdo();
        $stmt = $pdo->prepare('SELECT lat, lng FROM cidades WHERE nome = ? AND estado = ? LIMIT 1');
        $stmt->execute([$cidade, $estado]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && $row['lat'] && $row['lng']) {
            return [(float)$row['lat'], (float)$row['lng']];
        }
    } catch (Exception $e) {
        // Ignora se tabela não existe
    }
    
    return [null, null];
}

/**
 * FALLBACK 5: Coordenadas aproximadas do estado
 */
function getStateCoordinates($estado) 
{
    $states = [
        'AC' => [-9.0238, -70.8120], 'AL' => [-9.5713, -36.7820], 'AP' => [1.4554, -51.9082],
        'AM' => [-4.1406, -65.1220], 'BA' => [-13.2940, -41.4583], 'CE' => [-5.4984, -39.3206],
        'DF' => [-15.7942, -47.8822], 'ES' => [-19.1834, -40.3089], 'GO' => [-15.8270, -49.8362],
        'MA' => [-4.9609, -45.2744], 'MT' => [-12.6819, -56.9211], 'MS' => [-20.7722, -54.7852],
        'MG' => [-18.5122, -44.5550], 'PA' => [-3.9020, -52.4796], 'PB' => [-7.2400, -36.7820],
        'PR' => [-24.8220, -51.1696], 'PE' => [-8.8137, -36.9541], 'PI' => [-6.6684, -42.7762],
        'RJ' => [-22.9099, -43.2095], 'RN' => [-5.4026, -36.9541], 'RS' => [-30.0346, -51.2177],
        'RO' => [-10.9472, -62.8237], 'RR' => [1.99, -61.33], 'SC' => [-27.2423, -50.2189],
        'SP' => [-23.5505, -46.6333], 'SE' => [-10.5741, -37.3857], 'TO' => [-10.1753, -48.2982]
    ];
    
    return isset($states[$estado]) ? $states[$estado] : [null, null];
}

/**
 * Log do método de geocodificação
 */
function logGeocodingMethod($codigo, $method)
{
    try {
        $pdo = pdo();
        $stmt = $pdo->prepare('INSERT INTO geocoding_stats (codigo, method, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE method = VALUES(method), created_at = VALUES(created_at)');
        $stmt->execute([$codigo, $method]);
    } catch (Exception $e) {
        // Ignora se tabela não existe
    }
}

// ===== FUNÇÕES AUXILIARES ORIGINAIS (otimizadas) =====

function call_api_request($url, $method = 'GET', $payload = [])
{
    $finalUrl = $url;
    $method = strtoupper($method);

    if ($method === 'GET' && !empty($payload)) {
        $query = http_build_query($payload);
        $finalUrl .= (strpos($finalUrl, '?') === false ? '?' : '&') . $query;
    }

    echo "🔗 Requisitando lista: {$finalUrl} ({$method})\n";

    $ch = curl_init();
    $options = [
        CURLOPT_URL => $finalUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'token: ' . API_TOKEN,
            'Content-Type: application/json'
        ]
    ];

    if ($method !== 'GET') {
        $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    curl_setopt_array($ch, $options);
    $res = curl_exec($ch);
    if ($res === false) {
        echo "❌ Erro CURL: " . curl_error($ch) . "\n";
    }
    curl_close($ch);

    return $res ? json_decode($res, true) : null;
}

function http_get($url)
{
    echo "📥 Requisitando detalhes: {$url}\n";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30, // Reduzido de 60 para 30
        CURLOPT_CONNECTTIMEOUT => 10, // Timeout de conexão
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'token: ' . API_TOKEN
        ]
    ]);
    $res = curl_exec($ch);
    if ($res === false) {
        echo "❌ Erro CURL: " . curl_error($ch) . "\n";
    }
    curl_close($ch);
    return $res ? json_decode($res, true) : null;
}

function fetch_property_list($page, $limit)
{
    $baseUrl = API_BASE . '/lista';
    $attempts = [
        ['method' => 'GET', 'params' => ['status' => 'ativos', 'pagina' => $page, 'limite' => $limit]],
        ['method' => 'GET', 'params' => ['status' => 'ativo', 'page' => $page, 'per_page' => $limit]],
        ['method' => 'POST', 'params' => ['status' => 'ativos', 'pagina' => $page, 'limite' => $limit]]
    ];

    foreach ($attempts as $index => $attempt) {
        $response = call_api_request($baseUrl, $attempt['method'], $attempt['params']);

        if ($response && ($response['status'] ?? false)) {
            return $response;
        }

        $label = ($index + 1);
        echo "⚠ Tentativa {$label} ({$attempt['method']}) da lista falhou ou retornou estrutura inválida.\n";
    }

    return null;
}

function dec_or_null($v)
{
    if ($v === null || $v === '') return null;
    if (is_array($v) && isset($v['valor'])) $v = $v['valor'];
    $v = str_replace(['.', ' '], '', $v);
    $v = str_replace(',', '.', $v);
    return is_numeric($v) ? (float)$v : null;
}

function int_or_null($v)
{
    if ($v === null || $v === '') return null;
    return is_numeric($v) ? intval($v) : null;
}

function str_or_null($v)
{
    return ($v === null || $v === '') ? null : $v;
}

function upsert_imovel($d)
{
    echo "💾 Salvando imóvel {$d['codigo']} - {$d['tipo']} ({$d['cidade']}/{$d['bairro']})\n";
    $pdo = pdo();
    $sql = 'INSERT INTO imoveis
    (codigo, referencia, finalidade, tipo, dormitorios, suites, banheiros, salas, garagem, acomodacoes, ano_construcao, valor,
     cidade, estado, bairro, logradouro, numero, cep, area_privativa, area_total, terreno, descricao,
     status_ativo, atualizado_em, cadastrado_em, lat, lng)
    VALUES
    (:codigo,:referencia,:finalidade,:tipo,:dormitorios,:suites,:banheiros,:salas,:garagem,:acomodacoes,:ano_construcao,:valor,
     :cidade,:estado,:bairro,:logradouro,:numero,:cep,:area_privativa,:area_total,:terreno,:descricao,
     :status_ativo,:atualizado_em,:cadastrado_em,:lat,:lng)
    ON DUPLICATE KEY UPDATE
    referencia=VALUES(referencia),
    finalidade=VALUES(finalidade),
    tipo=VALUES(tipo),
    dormitorios=VALUES(dormitorios),
    suites=VALUES(suites),
    banheiros=VALUES(banheiros),
    salas=VALUES(salas),
    garagem=VALUES(garagem),
    acomodacoes=VALUES(acomodacoes),
    ano_construcao=VALUES(ano_construcao),
    valor=VALUES(valor),
    cidade=VALUES(cidade),
    estado=VALUES(estado),
    bairro=VALUES(bairro),
    logradouro=VALUES(logradouro),
    numero=VALUES(numero),
    cep=VALUES(cep),
    area_privativa=VALUES(area_privativa),
    area_total=VALUES(area_total),
    terreno=VALUES(terreno),
    descricao=VALUES(descricao),
    status_ativo=VALUES(status_ativo),
    atualizado_em=VALUES(atualizado_em),
    cadastrado_em=VALUES(cadastrado_em),
    lat=VALUES(lat),
    lng=VALUES(lng)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':codigo' => $d['codigo'],
        ':referencia' => $d['referencia'],
        ':finalidade' => $d['finalidade'],
        ':tipo' => $d['tipo'],
        ':dormitorios' => $d['dormitorios'],
        ':suites' => $d['suites'],
        ':banheiros' => $d['banheiros'],
        ':salas' => $d['salas'],
        ':garagem' => $d['garagem'],
        ':acomodacoes' => $d['acomodacoes'],
        ':ano_construcao' => $d['ano_construcao'],
        ':valor' => $d['valor'],
        ':cidade' => $d['cidade'],
        ':estado' => $d['estado'],
        ':bairro' => $d['bairro'],
        ':logradouro' => $d['logradouro'],
        ':numero' => $d['numero'],
        ':cep' => $d['cep'],
        ':area_privativa' => $d['area_privativa'],
        ':area_total' => $d['area_total'],
        ':terreno' => $d['terreno'],
        ':descricao' => $d['descricao'],
        ':status_ativo' => $d['status_ativo'],
        ':atualizado_em' => $d['atualizado_em'],
        ':cadastrado_em' => $d['cadastrado_em'],
        ':lat' => $d['lat'],
        ':lng' => $d['lng']
    ]);
}

function replace_imagens($codigo, $imagens)
{
    echo "🖼 Atualizando imagens do imóvel {$codigo}\n";
    $pdo = pdo();
    $pdo->prepare('DELETE FROM imoveis_imagens WHERE codigo=?')->execute([$codigo]);
    if (!$imagens) return;
    $stmt = $pdo->prepare('INSERT INTO imoveis_imagens (codigo, url, destaque) VALUES (?,?,?)');
    foreach ($imagens as $img) {
        $url = $img['url'] ?? null;
        $dest = isset($img['destaque']) && $img['destaque'] ? 1 : 0;
        if ($url) $stmt->execute([$codigo, $url, $dest]);
    }
}

function replace_caracteristicas($codigo, $caracts)
{
    echo "⚙️ Atualizando características do imóvel {$codigo}\n";
    $pdo = pdo();
    $pdo->prepare('DELETE FROM imoveis_caracteristicas WHERE codigo=?')->execute([$codigo]);
    if (!$caracts) return;
    $stmt = $pdo->prepare('INSERT INTO imoveis_caracteristicas (codigo, grupo, nome) VALUES (?,?,?)');
    foreach ($caracts as $c) {
        $g = $c['nomeGrupo'] ?? null;
        $n = $c['nomeCaracteristica'] ?? null;
        if ($n) $stmt->execute([$codigo, $g, $n]);
    }
}

function needs_detail_update($codigo, $apiUpdatedAt)
{
    $pdo = pdo();
    $stmt = $pdo->prepare('SELECT atualizado_em FROM imoveis WHERE codigo=?');
    $stmt->execute([$codigo]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo "📌 Imóvel {$codigo} não existe no banco. Será importado.\n";
        return true;
    }
    if (!$apiUpdatedAt) return false;
    $db = strtotime($row['atualizado_em']);
    $api = strtotime($apiUpdatedAt);
    return $api > $db;
}

// ===== LOOP DE IMPORTAÇÃO OTIMIZADO =====

$page = 1;
$total_pages = 1;
$geocoding_stats = [
    'total' => 0,
    'success' => 0,
    'failed' => 0,
    'cached' => 0
];

$start_time = time();
$max_execution_time = 300; // 5 minutos

do {
    // Verifica se estamos próximos do limite de tempo
    if ((time() - $start_time) > $max_execution_time) {
        echo "⏰ Atingindo limite de tempo de execução. Parando na página {$page}.\n";
        break;
    }
    
    echo "📄 Processando página {$page}\n";

    $pageSize = 20; // mantém alinhado com o que a API devolve por padrão
    $lista = fetch_property_list($page, $pageSize);
    if (!$lista || !isset($lista['status']) || !$lista['status']) {
        echo "⚠ Falha ao obter lista de imóveis.\n";
        break;
    }
    $rs = $lista['resultSet'];
    $data = $rs['data'] ?? [];

    // Normaliza os metadados de paginação porque a API muda o nome dos campos
    $per_page = intval($rs['per_page'] ?? $rs['perPage'] ?? $rs['limite'] ?? count($data) ?? 1);
    if ($per_page <= 0) {
        $per_page = max(1, count($data));
    }

    $total_items = intval(
        $rs['total_items'] ??
        $rs['totalItems'] ??
        $rs['total'] ??
        $rs['total_registros'] ??
        $rs['totalRegistros'] ??
        count($data)
    );

    $total_pages = intval(
        $rs['total_pages'] ??
        $rs['totalPages'] ??
        $rs['total_paginas'] ??
        $rs['totalPaginas'] ??
        0
    );

    if ($total_pages <= 0 && $per_page > 0) {
        $total_pages = (int) ceil($total_items / $per_page);
    }

    if ($total_pages <= 0) {
        $total_pages = 1;
    }

    echo "📊 Página {$page}/{$total_pages} | itens na página: " . count($data) . " | total estimado: {$total_items}\n";

    foreach ($data as $row) {
        // Verifica timeout novamente dentro do loop
        if ((time() - $start_time) > $max_execution_time) {
            echo "⏰ Timeout atingido durante processamento. Parando.\n";
            break 2;
        }
        
        $codigo = intval($row['codigoImovel']);
        $apiUpdatedAt = $row['ultimaAtualizacaoImovel'] ?? null;
        if (!needs_detail_update($codigo, $apiUpdatedAt)) {
            echo "⏩ Imóvel {$codigo} já está atualizado. Pulando.\n";
            continue;
        }

        $det = http_get(API_BASE . '/dados/' . $codigo);
        if (!$det || !isset($det['status']) || !$det['status']) {
            echo "⚠ Falha ao obter detalhes do imóvel {$codigo}\n";
            continue;
        }
        $x = $det['resultSet'];
        $end = $x['endereco'] ?? [];
        $area = $x['area'] ?? [];

        $d = [
            'codigo' => $x['codigoImovel'],
            'referencia' => str_or_null($x['referenciaImovel'] ?? null),
            'finalidade' => str_or_null($x['finalidadeImovel'] ?? null),
            'tipo' => str_or_null($x['descricaoTipoImovel'] ?? null),
            'dormitorios' => int_or_null($x['dormitorios'] ?? null),
            'suites' => int_or_null($x['suites'] ?? null),
            'banheiros' => int_or_null($x['banheiros'] ?? null),
            'salas' => int_or_null($x['salas'] ?? null),
            'garagem' => int_or_null($x['garagem'] ?? null),
            'acomodacoes' => int_or_null($x['acomodacoes'] ?? null),
            'ano_construcao' => int_or_null($x['anoConstrucao'] ?? null),
            'valor' => dec_or_null($x['valorEsperado'] ?? null),
            'cidade' => str_or_null($end['cidade'] ?? null),
            'estado' => str_or_null($end['estado'] ?? null),
            'bairro' => str_or_null($end['bairro'] ?? null),
            'logradouro' => str_or_null($end['logradouro'] ?? null),
            'numero' => str_or_null($end['numero'] ?? null),
            'cep' => str_or_null($end['cep'] ?? null),
            'area_privativa' => dec_or_null($area['privativa']['valor'] ?? null),
            'area_total' => dec_or_null($area['total']['valor'] ?? null),
            'terreno' => dec_or_null($area['terreno']['valor'] ?? null),
            'descricao' => str_or_null($x['descricaoImovel'] ?? null),
            'status_ativo' => (!empty($x['exibirImovel']) ? 1 : 0),
            'atualizado_em' => str_or_null($x['atualizadoEm'] ?? ($apiUpdatedAt ?? null)),
            'cadastrado_em' => str_or_null($x['cadastradoEm'] ?? null)
        ];

        // ===== GEOCODIFICAÇÃO COM CACHE =====
        echo "🔍 Iniciando geocodificação para imóvel {$d['codigo']}\n";
        $geocoding_stats['total']++;
        
        $geocode_result = geocodeWithFallbacks(
            $d['logradouro'],
            $d['bairro'],
            $d['cidade'],
            $d['estado'],
            $d['cep'],
            $d['codigo']
        );

        // Tratamento seguro do resultado da geocodificação
        $lat = isset($geocode_result[0]) ? $geocode_result[0] : null;
        $lng = isset($geocode_result[1]) ? $geocode_result[1] : null;
        $method = isset($geocode_result[2]) ? $geocode_result[2] : 'failed';

        $d['lat'] = $lat;
        $d['lng'] = $lng;
        
        if ($lat && $lng) {
            $geocoding_stats['success']++;
            echo "🎯 Geocodificação concluída: {$lat}, {$lng} (método: {$method})\n";
        } else {
            $geocoding_stats['failed']++;
            echo "❌ Falha na geocodificação do imóvel {$d['codigo']}\n";
        }

        // Salva no banco com transação otimizada
        try {
            pdo()->beginTransaction();
            upsert_imovel($d);
            replace_imagens($d['codigo'], $x['imagens'] ?? []);
            replace_caracteristicas($d['codigo'], $x['caracteristicas'] ?? []);
            pdo()->commit();
            echo "✔ Importado com sucesso imóvel {$d['codigo']}\n";
        } catch (Throwable $e) {
            pdo()->rollBack();
            echo "❌ Erro ao salvar imóvel {$d['codigo']}: " . $e->getMessage() . "\n";
        }
        
        // Rate limiting reduzido
        usleep(200000); // 0.2 segundos entre imóveis
    }
    $page++;
} while ($page <= $total_pages);

// ===== FUNÇÃO DE LIMPEZA E RELATÓRIO FINAL =====

/**
 * Salva relatório da sincronização
 */
function saveReport($stats, $start_time, $end_time)
{
    try {
        $pdo = pdo();
        $duration = $end_time - $start_time;
        $success_rate = $stats['total'] > 0 ? round(($stats['success'] / $stats['total']) * 100, 1) : 0;
        
        $stmt = $pdo->prepare('INSERT INTO sync_reports 
            (start_time, end_time, duration, total_processed, geocode_success, geocode_failed, success_rate, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        
        $stmt->execute([
            date('Y-m-d H:i:s', $start_time),
            date('Y-m-d H:i:s', $end_time),
            $duration,
            $stats['total'],
            $stats['success'],
            $stats['failed'],
            $success_rate
        ]);
    } catch (Exception $e) {
        // Ignora se tabela não existe
    }
}

// Finaliza processo
$end_time = time();
$duration = $end_time - $start_time;

pdo()->prepare('UPDATE import_status SET last_import_at=?, is_running=0 WHERE id=1')->execute([date('Y-m-d H:i:s')]);

// Salva relatório
saveReport($geocoding_stats, $start_time, $end_time);

// Calcula estatísticas finais
$success_rate = $geocoding_stats['total'] > 0 ? 
    round(($geocoding_stats['success'] / $geocoding_stats['total']) * 100, 1) : 0;

$avg_time_per_property = $geocoding_stats['total'] > 0 ? 
    round($duration / $geocoding_stats['total'], 2) : 0;

echo "\n🎉 SINCRONIZAÇÃO CONCLUÍDA!\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "⏱️  TEMPO DE EXECUÇÃO\n";
echo "────────────────────────────────────────────────────────────────\n";
echo "Início: " . date('Y-m-d H:i:s', $start_time) . "\n";
echo "Fim: " . date('Y-m-d H:i:s', $end_time) . "\n";
echo "Duração: " . gmdate('H:i:s', $duration) . " ({$duration}s)\n";
echo "Tempo médio por imóvel: {$avg_time_per_property}s\n";
echo "\n📊 ESTATÍSTICAS DE GEOCODIFICAÇÃO\n";
echo "────────────────────────────────────────────────────────────────\n";
echo "Total de imóveis processados: {$geocoding_stats['total']}\n";
echo "Geocodificações bem-sucedidas: {$geocoding_stats['success']}\n";
echo "Geocodificações falharam: {$geocoding_stats['failed']}\n";
echo "Taxa de sucesso: {$success_rate}%\n";

// Mostra estatísticas por método se disponível
try {
    $pdo = pdo();
    $stmt = $pdo->prepare('
        SELECT method, COUNT(*) as count 
        FROM geocoding_stats 
        WHERE DATE(created_at) = CURDATE() 
        GROUP BY method 
        ORDER BY count DESC
    ');
    $stmt->execute();
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($methods) {
        echo "\n🎯 MÉTODOS DE GEOCODIFICAÇÃO UTILIZADOS HOJE\n";
        echo "────────────────────────────────────────────────────────────────\n";
        foreach ($methods as $method) {
            $method_name = [
                'viacep' => 'ViaCEP',
                'nominatim_full' => 'Nominatim (Endereço Completo)',
                'nominatim_city_neighborhood' => 'Nominatim (Cidade + Bairro)',
                'local_db' => 'Base Local de Cidades',
                'state_approx' => 'Aproximação por Estado',
                'failed' => 'Falharam'
            ][$method['method']] ?? ucfirst($method['method']);
            
            echo "• {$method_name}: {$method['count']} imóveis\n";
        }
    }
} catch (Exception $e) {
    // Ignora se tabela não existe
}

echo "\n🏆 PERFORMANCE\n";
echo "────────────────────────────────────────────────────────────────\n";
$memory_peak = memory_get_peak_usage(true);
$memory_mb = round($memory_peak / 1024 / 1024, 2);
echo "Pico de memória: {$memory_mb} MB\n";

if ($geocoding_stats['total'] > 0) {
    $properties_per_minute = round(($geocoding_stats['total'] / $duration) * 60, 1);
    echo "Velocidade: {$properties_per_minute} imóveis/minuto\n";
}

echo "════════════════════════════════════════════════════════════════\n";
echo "✅ Processo finalizado com sucesso!\n\n";

// Limpa cache e libera recursos
unset($geocode_cache);
gc_collect_cycles();

flock($lock, LOCK_UN);
fclose($lock);

?>