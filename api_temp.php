<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Conectar ao banco local
$host = 'localhost';
$dbname = 'imobi_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão: ' . $e->getMessage()]);
    exit;
}

// Rota simples: GET /api_temp.php?route=properties
$route = $_GET['route'] ?? '';

if ($route === 'properties') {
    try {
        // Buscar propriedades com imagens
        $stmt = $pdo->query("SELECT * FROM imo_properties WHERE active = 1 AND exibir_imovel = 1 ORDER BY created_at DESC");
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Processar cada propriedade para garantir que tenha imagens em formato array
        foreach ($properties as &$property) {
            // Processar imagens
            if (!empty($property['imagens'])) {
                $imagens = json_decode($property['imagens'], true);
                if (!$imagens) {
                    $imagens = [];
                }
            } else {
                $imagens = [];
            }
            
            // Se não tem imagens, criar uma fake para testar slideshow
            if (empty($imagens)) {
                $imagens = [
                    ['url' => 'https://via.placeholder.com/800x600?text=Imóvel+1'],
                    ['url' => 'https://via.placeholder.com/800x600?text=Imóvel+2'],
                    ['url' => 'https://via.placeholder.com/800x600?text=Imóvel+3']
                ];
            }
            
            $property['imagens'] = $imagens;
            
            // Garantir que tenha imagem_destaque
            if (empty($property['imagem_destaque']) && !empty($imagens)) {
                $property['imagem_destaque'] = $imagens[0]['url'];
            }
            
            // Formatar descrição se existir
            if (!empty($property['descricao'])) {
                $property['descricao_formatada'] = formatarTextoComIA($property['descricao']);
            }
            
            // Converter valores numéricos
            $property['valor_venda'] = floatval($property['valor_venda']);
            $property['valor_condominio'] = floatval($property['valor_condominio']);
            $property['valor_iptu'] = floatval($property['valor_iptu']);
            $property['dormitorios'] = intval($property['dormitorios']);
            $property['suites'] = intval($property['suites']);
            $property['garagem'] = intval($property['garagem']);
            $property['area_total'] = floatval($property['area_total']);
        }
        
        echo json_encode([
            'success' => true,
            'total' => count($properties),
            'data' => $properties
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Route not found']);
}

// Função para formatar texto com OpenAI (placeholder por enquanto)
function formatarTextoComIA($texto) {
    // Por enquanto, apenas limpar e formatar o texto básico
    $texto = trim($texto);
    $texto = ucfirst(strtolower($texto));
    $texto = str_replace(['  ', '   '], ' ', $texto);
    
    // TODO: Integrar com OpenAI
    return $texto;
}
?>