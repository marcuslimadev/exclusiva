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

// ===========================
// Webhooks (sem autenticação)
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
// Rotas protegidas (com auth)
// ===========================
$router->group(['prefix' => 'api', 'middleware' => 'auth'], function () use ($router) {
    
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
    
    // Conversas
    $router->get('/conversas', 'ConversasController@index');
    $router->get('/conversas/tempo-real', 'ConversasController@tempoReal');
    $router->get('/conversas/{id}', 'ConversasController@show');
    $router->post('/conversas/{id}/mensagens', 'ConversasController@sendMessage');
});
