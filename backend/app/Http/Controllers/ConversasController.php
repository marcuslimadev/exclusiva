<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversa;
use App\Models\Mensagem;
use App\Services\TwilioService;

/**
 * Controller de Conversas
 */
class ConversasController extends Controller
{
    private $twilio;
    
    public function __construct(TwilioService $twilio)
    {
        $this->twilio = $twilio;
    }
    
    /**
     * Listar conversas
     * GET /api/conversas
     */
    public function index(Request $request)
    {
        $query = Conversa::with(['lead', 'corretor']);
        
        // Filtrar por status
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        // Apenas conversas ativas por padrão
        if (!$request->has('all')) {
            $query->where('status', '!=', 'finalizada');
        }
        
        $conversas = $query->orderBy('ultima_atividade', 'desc')
            ->paginate($request->per_page ?? 20);
        
        return response()->json([
            'success' => true,
            'data' => $conversas
        ]);
    }
    
    /**
     * Detalhes da conversa com mensagens
     * GET /api/conversas/{id}
     */
    public function show($id)
    {
        $conversa = Conversa::with(['lead', 'corretor', 'mensagens'])
            ->findOrFail($id);
        
        // Marcar mensagens como lidas
        Mensagem::where('conversa_id', $id)
            ->where('direction', 'incoming')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        
        return response()->json([
            'success' => true,
            'data' => $conversa
        ]);
    }
    
    /**
     * Enviar mensagem manual
     * POST /api/conversas/{id}/mensagens
     */
    public function sendMessage(Request $request, $id)
    {
        $this->validate($request, [
            'content' => 'required|string'
        ]);
        
        $conversa = Conversa::findOrFail($id);
        
        // Enviar via Twilio
        $result = $this->twilio->sendMessage($conversa->telefone, $request->input('content'));
        
        if ($result['success']) {
            // Registrar mensagem
            $mensagem = Mensagem::create([
                'conversa_id' => $conversa->id,
                'message_sid' => $result['message_sid'],
                'direction' => 'outgoing',
                'message_type' => 'text',
                'content' => $request->input('content'),
                'status' => 'sent',
                'sent_at' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Mensagem enviada',
                'data' => $mensagem
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Falha ao enviar mensagem'
        ], 500);
    }
    
    /**
     * Conversas ativas em tempo real
     * GET /api/conversas/tempo-real
     */
    public function tempoReal()
    {
        $conversas = Conversa::with(['lead', 'mensagens' => function($q) {
                $q->orderBy('sent_at', 'desc')->limit(1);
            }])
            ->where('status', 'ativa')
            ->orderBy('ultima_atividade', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $conversas
        ]);
    }
}
