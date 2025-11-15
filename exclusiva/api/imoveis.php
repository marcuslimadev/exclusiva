<?php
$origins = [
  'http://localhost',
  'http://localhost:3000',
  'http://127.0.0.1:5173',
  'https://fe0f201e658d.ngrok-free.app'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$reqMethod = $_SERVER['REQUEST_METHOD'] ?? '';
$reqHeaders = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ?? '';

if (in_array($origin, $origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With' . ($reqHeaders ? ", $reqHeaders" : ''));
    header('Access-Control-Max-Age: 86400');
}

if ($reqMethod === 'OPTIONS') {
    http_response_code(204);
    exit;
}


if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}
require_once __DIR__ . '/../lib/sync.php';
header('Content-Type: application/json; charset=utf-8');


if (should_sync() && !is_running()) {
    start_background_sync();
}

$pdo = pdo();
$params = [];
$where = [];

if (!empty($_GET['bairro'])) { $where[] = 'i.bairro LIKE :bairro'; $params[':bairro'] = '%' . $_GET['bairro'] . '%'; }
if (!empty($_GET['cidade'])) { $where[] = 'i.cidade LIKE :cidade'; $params[':cidade'] = '%' . $_GET['cidade'] . '%'; }
if (!empty($_GET['min'])) { $where[] = 'i.valor >= :min'; $params[':min'] = floatval($_GET['min']); }
if (!empty($_GET['max'])) { $where[] = 'i.valor <= :max'; $params[':max'] = floatval($_GET['max']); }
if (!empty($_GET['tipo'])) { $where[] = 'i.tipo LIKE :tipo'; $params[':tipo'] = '%' . $_GET['tipo'] . '%'; }
if (!empty($_GET['ativos'])) { $where[] = 'i.status_ativo = :ativos'; $params[':ativos'] = intval($_GET['ativos']) ? 1 : 0; }

$sql = '
SELECT
  i.codigo, i.referencia, i.finalidade, i.tipo, i.dormitorios, i.suites, i.banheiros, i.salas, i.garagem, i.acomodacoes,
  i.ano_construcao, i.valor, i.cidade, i.estado, i.bairro, i.logradouro, i.numero, i.cep,
  i.area_privativa, i.area_total, i.terreno, i.descricao, i.status_ativo, i.atualizado_em, i.cadastrado_em,
  i.lat, i.lng,
  GROUP_CONCAT(im.url ORDER BY im.destaque DESC, im.id ASC) AS imagens
FROM imoveis i
LEFT JOIN imoveis_imagens im ON im.codigo = i.codigo
';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' GROUP BY i.codigo
ORDER BY i.atualizado_em DESC, i.valor DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Enriquecimento com imagens ---
foreach ($rows as &$row) {
    $row['imagens'] = $row['imagens'] ? explode(',', $row['imagens']) : [];
    $row['thumb_url'] = $row['imagens'][0] ?? null;
}

echo json_encode([
    'status' => true,
    'last_import_at' => last_import_at(),
    'is_running' => is_running(),
    'total' => count($rows),
    'data' => $rows
], JSON_UNESCAPED_UNICODE);
