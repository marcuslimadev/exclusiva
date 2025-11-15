<?php

namespace App\Http\Controllers;

use App\Services\PropertySyncService;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    private $syncService;
    
    public function __construct(PropertySyncService $syncService)
    {
        $this->syncService = $syncService;
    }
    
    /**
     * Sincronizar imóveis manualmente
     * 
     * GET /api/properties/sync
     */
    public function sync()
    {
        $result = $this->syncService->syncAll();
        
        if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sincronização concluída com sucesso',
                    'data' => $result['stats'],
                    'time_ms' => $result['time_ms'],
                    'errors_detail' => $result['errors_detail']
                ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => $result['error']
            ], 500);
        }
    }
}
