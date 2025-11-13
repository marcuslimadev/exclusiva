<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

/**
 * Controller para receber webhooks do Twilio
 */
class WebhookController extends Controller
{
    private $whatsappService;
    
    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }
    
    /**
     * Receber mensagens do Twilio
     * POST /webhook/whatsapp
     */
    public function receive(Request $request)
    {
        $webhookData = $request->all();
        
        Log::info('╔════════════════════════════════════════════════════════════════╗');
        Log::info('║           🔔 WEBHOOK RECEBIDO DO TWILIO                       ║');
        Log::info('╚════════════════════════════════════════════════════════════════╝');
        Log::info('📱 De: ' . ($webhookData['From'] ?? 'N/A'));
        Log::info('👤 Nome: ' . ($webhookData['ProfileName'] ?? 'N/A'));
        Log::info('💬 Mensagem: ' . ($webhookData['Body'] ?? '[mídia]'));
        Log::info('🆔 MessageSid: ' . ($webhookData['MessageSid'] ?? 'N/A'));
        Log::info('📍 Cidade: ' . ($webhookData['FromCity'] ?? 'N/A'));
        Log::info('🗺️  Estado: ' . ($webhookData['FromState'] ?? 'N/A'));
        Log::info('🌍 País: ' . ($webhookData['FromCountry'] ?? 'N/A'));
        if (isset($webhookData['Latitude']) && isset($webhookData['Longitude'])) {
            Log::info('📌 Coordenadas: ' . $webhookData['Latitude'] . ', ' . $webhookData['Longitude']);
        }
        Log::info('─────────────────────────────────────────────────────────────────');
        
        try {
            $result = $this->whatsappService->processIncomingMessage($webhookData);
            
            Log::info('╔════════════════════════════════════════════════════════════════╗');
            Log::info('║           ✅ WEBHOOK PROCESSADO COM SUCESSO                   ║');
            Log::info('╚════════════════════════════════════════════════════════════════╝');
            Log::info('📊 Resultado:', $result);
            Log::info('═════════════════════════════════════════════════════════════════');
            
            // Twilio espera resposta 200 OK (pode ser vazio ou TwiML)
            return response()->json([
                'success' => true,
                'message' => 'Processado',
                'result' => $result
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('ERRO NO WEBHOOK', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Status callback do Twilio
     * POST /webhook/whatsapp/status
     */
    public function status(Request $request)
    {
        $statusData = $request->all();
        
        Log::info('Status callback recebido', $statusData);
        
        // Atualizar status da mensagem no banco se necessário
        
        return response('OK', 200);
    }
}
