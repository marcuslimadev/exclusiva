<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de sincronização de imóveis
 * Busca dados da API da Exclusiva Lar e atualiza o banco de dados
 */
class PropertySyncService
{
    private $apiToken;
    private $baseUrl = 'https://www.exclusivalarimoveis.com.br/api/v1/app/imovel';
    
    public function __construct()
    {
        $this->apiToken = env('EXCLUSIVA_API_TOKEN');
        
        if (!$this->apiToken) {
            throw new \Exception('EXCLUSIVA_API_TOKEN não configurado no .env');
        }
    }
    
    /**
     * Sincronizar todos os imóveis
     */
    public function syncAll()
    {
        $startTime = microtime(true);
        
        Log::info('🏠 Iniciando sincronização de imóveis...');
        
        try {
            $stats = [
                'found' => 0,
                'new' => 0,
                'updated' => 0,
                'errors' => 0
            ];
            
            $page = 1;
            $totalPages = 1;
            
            // Loop por todas as páginas
            do {
                Log::info("📄 Buscando página {$page}...");
                
                // Buscar lista de imóveis (com paginação)
                $lista = $this->callApi("/lista?page={$page}");
                
                if (!isset($lista['resultSet']['data'])) {
                    throw new \Exception('Resposta da API inválida: estrutura esperada não encontrada');
                }
                
                $resultSet = $lista['resultSet'];
                $imoveis = $resultSet['data'];
                $totalPages = $resultSet['total_pages'] ?? 1;
                $totalItems = $resultSet['total_items'] ?? 0;
                
                Log::info("📊 Página {$page}/{$totalPages} - {count($imoveis)} imóveis", [
                    'total_items' => $totalItems,
                    'per_page' => $resultSet['per_page'] ?? 20
                ]);
                
                $stats['found'] += count($imoveis);
                
                foreach ($imoveis as $item) {
                    $codigo = $item['codigoImovel'] ?? null;
                    
                    if (!$codigo) {
                        $stats['errors']++;
                        continue;
                    }
                    
                    try {
                        // Buscar dados completos do imóvel
                        $response = $this->callApi("/dados/{$codigo}");
                        
                        if (!isset($response['resultSet'])) {
                            throw new \Exception("Dados não encontrados para imóvel {$codigo}");
                        }
                        
                        $imovel = $response['resultSet'];
                        
                        // Verificar se já existe
                        $existing = Property::where('codigo_imovel', $codigo)->first();
                        
                        $data = $this->mapPropertyData($imovel);
                        
                        if ($existing) {
                            $existing->update($data);
                            $stats['updated']++;
                            Log::debug("✏️ Imóvel {$codigo} atualizado");
                        } else {
                            Property::create($data);
                            $stats['new']++;
                            Log::debug("➕ Imóvel {$codigo} criado");
                        }
                        
                    } catch (\Exception $e) {
                        $stats['errors']++;
                        Log::error("❌ Erro ao processar imóvel {$codigo}", [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                $page++;
                
            } while ($page <= $totalPages);
            
            $elapsed = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('✅ Sincronização concluída', [
                'stats' => $stats,
                'time_ms' => $elapsed
            ]);
            
            return [
                'success' => true,
                'stats' => $stats,
                'time_ms' => $elapsed
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Erro na sincronização de imóveis', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Mapear dados do imóvel da API para o formato do banco
     */
    private function mapPropertyData($imovel)
    {
        // Converter áreas
        $areaPrivativa = $this->parseArea($imovel['area']['privativa']['valor'] ?? null);
        $areaTotal = $this->parseArea($imovel['area']['total']['valor'] ?? null);
        $areaTerreno = $this->parseArea($imovel['area']['terreno']['valor'] ?? null);
        
        // Características
        $caracteristicas = [];
        if (!empty($imovel['caracteristicas'])) {
            foreach ($imovel['caracteristicas'] as $carac) {
                if (isset($carac['nomeCaracteristica'])) {
                    $caracteristicas[] = $carac['nomeCaracteristica'];
                }
            }
        }
        
        // Imagem destaque
        $imagemDestaque = $this->getImagemDestaque($imovel['imagens'] ?? []);
        
        // Preparar dados de imagens
        $imagensData = [];
        if (!empty($imovel['imagens']) && is_array($imovel['imagens'])) {
            foreach ($imovel['imagens'] as $img) {
                if (isset($img['url'])) {
                    $imagensData[] = [
                        'url' => $img['url'],
                        'destaque' => $img['destaque'] ?? false
                    ];
                }
            }
        }
        
        return [
            'codigo_imovel' => $imovel['codigoImovel'],
            'referencia_imovel' => $imovel['referenciaImovel'] ?? null,
            'finalidade_imovel' => $imovel['finalidadeImovel'] ?? null,
            'tipo_imovel' => $imovel['descricaoTipoImovel'] ?? null,
            'descricao' => $imovel['descricaoImovel'] ?? null,
            'dormitorios' => $imovel['dormitorios'] ?? 0,
            'suites' => $imovel['suites'] ?? 0,
            'banheiros' => $imovel['banheiros'] ?? 0,
            'garagem' => $imovel['garagem'] ?? 0,
            'valor_venda' => $imovel['valorEsperado'] ?? null,
            'valor_iptu' => $imovel['valorIPTU'] ?? null,
            'valor_condominio' => $imovel['valorCondominio'] ?? null,
            'cidade' => $imovel['endereco']['cidade'] ?? null,
            'estado' => $imovel['endereco']['estado'] ?? null,
            'bairro' => $imovel['endereco']['bairro'] ?? null,
            'logradouro' => $imovel['endereco']['logradouro'] ?? null,
            'numero' => $imovel['endereco']['numero'] ?? null,
            'complemento' => $imovel['endereco']['complemento'] ?? null,
            'cep' => $imovel['endereco']['cep'] ?? null,
            'area_privativa' => $areaPrivativa,
            'area_total' => $areaTotal,
            'area_terreno' => $areaTerreno,
            'imagem_destaque' => $imagemDestaque,
            'imagens' => json_encode($imagensData), // Garantir que seja JSON
            'caracteristicas' => json_encode($caracteristicas),
            'em_condominio' => $imovel['emCondominio'] ? 1 : 0,
            'exclusividade' => $imovel['exclusividade'] ? 1 : 0,
            'exibir_imovel' => $imovel['exibirImovel'] ? 1 : 0,
            'active' => $imovel['exibirImovel'] ? 1 : 0,
            'api_data' => json_encode($imovel)
        ];
    }
    
    /**
     * Converter área de string para float
     */
    private function parseArea($valor)
    {
        if (!$valor) return null;
        return (float) str_replace(',', '.', $valor);
    }
    
    /**
     * Obter imagem destaque
     */
    private function getImagemDestaque($imagens)
    {
        if (empty($imagens)) return null;
        
        // Buscar imagem marcada como destaque
        foreach ($imagens as $img) {
            if (isset($img['destaque']) && $img['destaque']) {
                return $img['url'];
            }
        }
        
        // Se não tiver destaque, pega a primeira
        return $imagens[0]['url'] ?? null;
    }
    
    /**
     * Fazer chamada à API da Exclusiva Lar
     */
    private function callApi($endpoint)
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "token: {$this->apiToken}"
            ],
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception("API retornou HTTP {$httpCode}: {$response}");
        }
        
        if ($error) {
            throw new \Exception("Erro cURL: {$error}");
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Resposta JSON inválida: ' . json_last_error_msg());
        }
        
        return $data;
    }
}
