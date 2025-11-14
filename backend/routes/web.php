<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

// Health check
$router->get('/', function () use ($router) {
    return response()->json([
        'app' => 'Exclusiva Lar CRM',
        'version' => $router->app->version(),
        'status' => 'online'
    ]);
});

// Landing Page Pública
$router->get('/imoveis', function () {
    $path = base_path('public/imoveis.html');
    if (file_exists($path)) {
        return response(file_get_contents($path))
            ->header('Content-Type', 'text/html');
    }
    return response('Landing page não encontrada. Path: ' . $path, 404);
});

// Database test
$router->get('/db-test', function () {
    try {
        // Debug: mostrar configuração
        $config = config('database.connections.pgsql');
        
        $pdo = app('db')->connection()->getPdo();
        $result = app('db')->select('SELECT COUNT(*) as count FROM users');
        return response()->json([
            'database' => 'connected',
            'users_count' => $result[0]->count,
            'debug_host' => $config['host'] ?? 'N/A',
            'debug_database' => $config['database'] ?? 'N/A',
            'has_database_url' => !empty(env('DATABASE_URL'))
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'database' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Teste de leads (sem autenticação)
$router->get('/test-leads', function () {
    try {
        $leads = app('db')->select('SELECT id, nome, telefone, email, origem, created_at FROM leads ORDER BY created_at DESC LIMIT 5');
        return response()->json([
            'total' => count($leads),
            'leads' => $leads
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});

// Teste de stats (sem autenticação)
$router->get('/test-stats', function () {
    try {
        $stats = [
            'leads_total' => app('db')->table('leads')->count(),
            'leads_novos' => app('db')->table('leads')->where('status', 'novo')->count(),
            'conversas_ativas' => app('db')->table('conversas')->where('status', 'ativa')->count(),
            'corretores_ativos' => app('db')->table('users')->where('tipo', 'corretor')->where('ativo', true)->count()
        ];
        return response()->json($stats);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});

// Stats completo para o dashboard (sem auth temporariamente)
$router->get('/api-public/dashboard/stats', function () {
    try {
        $db = app('db');
        $stats = [
            'leads' => [
                'total' => $db->table('leads')->count(),
                'novos' => $db->table('leads')->where('status', 'novo')->count(),
                'em_atendimento' => $db->table('leads')->where('status', 'em_atendimento')->count(),
                'qualificados' => $db->table('leads')->where('status', 'qualificado')->count(),
                'fechados_mes' => $db->table('leads')->where('status', 'fechado')->whereRaw('EXTRACT(MONTH FROM updated_at) = ?', [date('m')])->count()
            ],
            'conversas' => [
                'ativas' => $db->table('conversas')->where('status', 'ativa')->count(),
                'hoje' => $db->table('conversas')->whereDate('iniciada_em', date('Y-m-d'))->count(),
                'aguardando' => $db->table('conversas')->where('status', 'aguardando_corretor')->count()
            ],
            'corretores' => [
                'total' => $db->table('users')->where('tipo', 'corretor')->where('ativo', true)->count(),
                'online' => 0
            ]
        ];
        return response()->json(['success' => true, 'data' => $stats]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

// Atividades recentes (sem auth temporariamente)
$router->get('/api-public/dashboard/atividades', function () {
    try {
        $atividades = app('db')->table('conversas')
            ->leftJoin('leads', 'conversas.lead_id', '=', 'leads.id')
            ->select('conversas.id as conversa_id', 'conversas.lead_id', 'conversas.telefone', 'conversas.iniciada_em', 'leads.nome as lead_nome')
            ->orderBy('conversas.iniciada_em', 'desc')
            ->limit(10)
            ->get()
            ->map(function($conv) {
                return [
                    'tipo' => 'nova_conversa',
                    'descricao' => 'Nova conversa iniciada com ' . ($conv->lead_nome ?? $conv->telefone),
                    'timestamp' => $conv->iniciada_em,
                    'data' => ['conversa_id' => $conv->conversa_id, 'lead_id' => $conv->lead_id]
                ];
            });
        return response()->json(['success' => true, 'data' => $atividades]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

// Leads públicos temporários
$router->get('/api-public/leads', function () {
    try {
        $leads = app('db')->table('leads')
            ->select('id', 'nome', 'telefone', 'email', 'origem', 'status', 'created_at', 'updated_at')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['success' => true, 'data' => $leads]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

// Conversas públicas temporárias
$router->get('/api-public/conversas', function () {
    try {
        $conversas = app('db')->table('conversas')
            ->leftJoin('leads', 'conversas.lead_id', '=', 'leads.id')
            ->select('conversas.*', 'leads.nome as lead_nome', 'leads.email as lead_email')
            ->orderBy('conversas.iniciada_em', 'desc')
            ->get();
        return response()->json(['success' => true, 'data' => $conversas]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

// Usuários de teste (TEMPORÁRIO - REMOVER DEPOIS)
$router->get('/api-public/test-users', function () {
    try {
        $users = app('db')->table('users')
            ->select('id', 'nome', 'email', 'tipo', 'ativo', 'created_at')
            ->get();
        return response()->json(['success' => true, 'data' => $users]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

// Debug conversa específica (TEMPORÁRIO)
$router->get('/debug/conversa/{id}', function ($id) {
    try {
        $db = app('db');
        
        $conversa = $db->table('conversas')->where('id', $id)->first();
        if (!$conversa) {
            return response()->json(['error' => 'Conversa não encontrada', 'id' => $id], 404);
        }
        
        $mensagens = $db->table('mensagens')->where('conversa_id', $id)->get();
        
        return response()->json([
            'conversa' => $conversa,
            'total_mensagens' => count($mensagens),
            'mensagens_sample' => $mensagens->take(3)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Debug sync único imóvel (TEMPORÁRIO)
$router->get('/debug/property-sync/{codigo}', function ($codigo) {
    try {
        $syncService = app(App\Services\PropertySyncService::class);
        
        // Testar busca de dados
        $reflection = new ReflectionClass($syncService);
        $method = $reflection->getMethod('callApi');
        $method->setAccessible(true);
        
        $response = $method->invoke($syncService, "/dados/{$codigo}");
        $imovel = $response['resultSet'] ?? null;
        
        if (!$imovel) {
            return response()->json(['error' => 'Imóvel não encontrado na API'], 404);
        }
        
        // Testar mapeamento
        $mapMethod = $reflection->getMethod('mapPropertyData');
        $mapMethod->setAccessible(true);
        
        $mapped = $mapMethod->invoke($syncService, $imovel);
        
        return response()->json([
            'codigo' => $codigo,
            'api_data' => $imovel,
            'mapped_data' => $mapped
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_slice($e->getTrace(), 0, 5)
        ], 500);
    }
});

// Debug lista da API (TEMPORÁRIO)
$router->get('/debug/property-list/{page?}', function ($page = 1) {
    try {
        $syncService = app(App\Services\PropertySyncService::class);
        
        $reflection = new ReflectionClass($syncService);
        $method = $reflection->getMethod('callApi');
        $method->setAccessible(true);
        
        $response = $method->invoke($syncService, "/lista?page={$page}");
        $resultSet = $response['resultSet'] ?? [];
        
        return response()->json([
            'page' => $page,
            'total_pages' => $resultSet['total_pages'] ?? 0,
            'total_items' => $resultSet['total_items'] ?? 0,
            'per_page' => $resultSet['per_page'] ?? 0,
            'sample_codes' => collect($resultSet['data'] ?? [])->pluck('codigoImovel')->take(10)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});

// Debug configurações (TEMPORÁRIO)
$router->get('/debug/config', function () {
    return response()->json([
        'app_env' => env('APP_ENV'),
        'app_debug' => env('APP_DEBUG'),
        'has_exclusiva_token' => !empty(env('EXCLUSIVA_API_TOKEN')),
        'token_preview' => env('EXCLUSIVA_API_TOKEN') ? substr(env('EXCLUSIVA_API_TOKEN'), 0, 10) . '...' : 'NOT SET',
        'has_openai_key' => !empty(env('OPENAI_API_KEY')),
        'has_twilio_sid' => !empty(env('TWILIO_ACCOUNT_SID')),
        'has_database_url' => !empty(env('DATABASE_URL'))
    ]);
});

// ===========================
// ROTAS PÚBLICAS (SEM AUTENTICAÇÃO)
// ===========================
$router->group(['prefix' => 'api/properties'], function () use ($router) {
    // Rotas específicas ANTES das dinâmicas
    $router->get('/sync', 'PropertyController@sync');
    
    // Rotas dinâmicas
    $router->get('/', 'PublicPropertyController@index');
    $router->get('/{codigo}', 'PublicPropertyController@show');
});

// ===========================
// WEBHOOK (SEM AUTENTICAÇÃO)
// ===========================
$router->group(['prefix' => 'webhook'], function () use ($router) {
    $router->post('/whatsapp', 'WebhookController@receive');
    $router->post('/whatsapp/status', 'WebhookController@status');
});

// ===========================
// Autenticação (sem middleware)
// ===========================
$router->group(['prefix' => 'api/auth'], function () use ($router) {
    $router->post('/login', 'AuthController@login');
});

// ===========================
// Rotas protegidas (TEMPORARIAMENTE SEM AUTH - PARA DEBUG)
// ===========================
$router->group(['prefix' => 'api'], function () use ($router) {
    
    // Auth
    $router->get('/auth/me', 'AuthController@me');
    $router->post('/auth/logout', 'AuthController@logout');
    
    // Dashboard
    $router->get('/dashboard/stats', 'DashboardController@stats');
    $router->get('/dashboard/chart/atendimentos', 'DashboardController@chartAtendimentos');
    $router->get('/dashboard/atividades', 'DashboardController@atividades');
    
    // Leads
    $router->get('/leads', 'LeadsController@index');
    $router->get('/leads/stats', 'LeadsController@stats');
    $router->get('/leads/{id}', 'LeadsController@show');
    $router->put('/leads/{id}', 'LeadsController@update');
    $router->patch('/leads/{id}/state', 'LeadsController@updateState');
    $router->patch('/leads/{id}/status', 'LeadsController@updateStatus');
    
    // Conversas
    $router->get('/conversas', 'ConversasController@index');
    $router->get('/conversas/tempo-real', 'ConversasController@tempoReal');
    $router->get('/conversas/{id}', 'ConversasController@show');
    $router->post('/conversas/{id}/mensagens', 'ConversasController@sendMessage');
});
