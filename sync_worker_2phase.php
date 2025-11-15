<?php
/**
 * Worker de sincronização de imóveis - Duas fases
 * Fase 1: Percorre TODAS as páginas e salva dados básicos
 * Fase 2: Busca detalhes apenas dos imóveis que precisam atualização
 */

require_once __DIR__ . '/exclusiva/lib/db.php';

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
    $pdo = pdo();
    
    $sql = "INSERT INTO imoveis 
        (codigo, referencia, atualizado_em, cadastrado_em, status_ativo)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        referencia = VALUES(referencia),
        atualizado_em = VALUES(atualizado_em),
        cadastrado_em = VALUES(cadastrado_em),
        status_ativo = VALUES(status_ativo)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $row['codigoImovel'],
        $row['referenciaImovel'] ?? null,
        $row['ultimaAtualizacaoImovel'] ?? null,
        $row['dataInsercaoImovel'] ?? null,
        ($row['statusImovel'] ?? false) ? 1 : 0
    ]);
}

echo "\n📋 FASE 1: Salvando lista completa de imóveis...\n";
echo "════════════════════════════════════════════════════════════════\n";

$page = 1;
$totalSaved = 0;
$maxPages = 999;

do {
    // Tentar GET com page/per_page (padrão que funciona)
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
    
    // Proteção contra loop infinito
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

// Coletar IDs que precisam de atualização (sem detalhes ou desatualizados)
$pdo = pdo();
$stmt = $pdo->query("
    SELECT codigo 
    FROM imoveis 
    WHERE descricao IS NULL 
       OR cidade IS NULL 
       OR atualizado_em < DATE_SUB(NOW(), INTERVAL 4 HOUR)
    ORDER BY atualizado_em ASC NULLS FIRST
");

$ids = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ids[] = $row['codigo'];
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
        
        // Atualizar com detalhes completos
        upsert_detalhes($imovel);
        
        echo "✓ Imóvel {$codigo} atualizado\n";
        $updated++;
        
        // Rate limiting
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
    $pdo = pdo();
    
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
    
    // SQL de atualização
    $sql = "UPDATE imoveis SET
        finalidade = ?,
        tipo = ?,
        dormitorios = ?,
        suites = ?,
        banheiros = ?,
        salas = ?,
        garagem = ?,
        acomodacoes = ?,
        ano_construcao = ?,
        valor = ?,
        cidade = ?,
        estado = ?,
        bairro = ?,
        logradouro = ?,
        numero = ?,
        cep = ?,
        area_privativa = ?,
        area_total = ?,
        terreno = ?,
        descricao = ?,
        atualizado_em = ?
        WHERE codigo = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $d['finalidadeImovel'] ?? null,
        $d['descricaoTipoImovel'] ?? null,
        $d['dormitorios'] ?? 0,
        $d['suites'] ?? 0,
        $d['banheiros'] ?? 0,
        $d['salas'] ?? 0,
        $d['garagem'] ?? 0,
        $d['acomodacoes'] ?? 0,
        $d['anoConstrucao'] ?? null,
        $d['valorEsperado'] ?? null,
        $d['endereco']['cidade'] ?? null,
        $d['endereco']['estado'] ?? null,
        $d['endereco']['bairro'] ?? null,
        $d['endereco']['logradouro'] ?? null,
        $d['endereco']['numero'] ?? null,
        $d['endereco']['cep'] ?? null,
        $area_privativa,
        $area_total,
        $area_terreno,
        $d['descricaoImovel'] ?? null,
        $d['atualizadoEm'] ?? date('Y-m-d H:i:s'),
        $d['codigoImovel']
    ]);
    
    $codigo = $d['codigoImovel'];
    
    // Atualizar imagens
    $pdo->prepare('DELETE FROM imoveis_imagens WHERE codigo=?')->execute([$codigo]);
    if (!empty($d['imagens'])) {
        $stmt = $pdo->prepare('INSERT INTO imoveis_imagens (codigo, url, destaque) VALUES (?,?,?)');
        foreach ($d['imagens'] as $img) {
            $url = $img['url'] ?? null;
            $dest = ($img['destaque'] ?? false) ? 1 : 0;
            if ($url) $stmt->execute([$codigo, $url, $dest]);
        }
    }
    
    // Atualizar características
    $pdo->prepare('DELETE FROM imoveis_caracteristicas WHERE codigo=?')->execute([$codigo]);
    if (!empty($d['caracteristicas'])) {
        $stmt = $pdo->prepare('INSERT INTO imoveis_caracteristicas (codigo, grupo, nome) VALUES (?,?,?)');
        foreach ($d['caracteristicas'] as $c) {
            $g = $c['nomeGrupo'] ?? null;
            $n = $c['nomeCaracteristica'] ?? null;
            if ($n) $stmt->execute([$codigo, $g, $n]);
        }
    }
}

echo "\n🎉 SINCRONIZAÇÃO COMPLETA!\n";
echo "Total salvo na fase 1: {$totalSaved}\n";
echo "Total atualizado na fase 2: {$updated}\n";
echo "Erros: {$errors}\n\n";

flock($lock, LOCK_UN);
fclose($lock);
