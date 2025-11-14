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
        try {
            $db = app('db');
            $query = $db->table('conversas')
                ->leftJoin('leads', 'conversas.lead_id', '=', 'leads.id')
                ->select('conversas.*', 'leads.nome as lead_nome', 'leads.email as lead_email');
            
            // Filtrar por status
            if ($request->status) {
                $query->where('conversas.status', $request->status);
            }
            
            // Apenas conversas ativas por padrão
            if (!$request->has('all')) {
                $query->where('conversas.status', '!=', 'finalizada');
            }
            
            $conversas = $query->orderBy('conversas.ultima_atividade', 'desc')->get();
            
            // Formatar datas para ISO8601 (compatível com JavaScript)
            $conversas = $conversas->map(function($conversa) {
                if ($conversa->iniciada_em) {
                    $conversa->iniciada_em = date('c', strtotime($conversa->iniciada_em));
                }
                if ($conversa->ultima_atividade) {
                    $conversa->ultima_atividade = date('c', strtotime($conversa->ultima_atividade));
                }
                if ($conversa->finalizada_em) {
                    $conversa->finalizada_em = date('c', strtotime($conversa->finalizada_em));
                }
                return $conversa;
            });
            
            return response()->json([
                'success' => true,
                'data' => $conversas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Detalhes da conversa com mensagens
     * GET /api/conversas/{id}
     */
    public function show($id)
    {
        try {
            $db = app('db');
            
            \Log::info("Buscando conversa ID: {$id}");
            
            // Buscar conversa com dados do lead
            $conversa = $db->table('conversas')
                ->leftJoin('leads', 'conversas.lead_id', '=', 'leads.id')
                ->leftJoin('users', 'conversas.corretor_id', '=', 'users.id')
                ->select(
                    'conversas.*',
                    'leads.nome as lead_nome',
                    'leads.email as lead_email',
                    'leads.whatsapp_name as lead_whatsapp_name',
                    'users.nome as corretor_nome'
                )
                ->where('conversas.id', $id)
                ->first();
            
            if (!$conversa) {
                \Log::warning("Conversa {$id} não encontrada");
                return response()->json([
                    'success' => false,
                    'error' => 'Conversa não encontrada'
                ], 404);
            }
            
            \Log::info("Conversa encontrada, buscando mensagens...");
            
            // Buscar mensagens
            $mensagens = $db->table('mensagens')
                ->where('conversa_id', $id)
                ->orderBy('sent_at', 'asc')
                ->get();
            
            \Log::info("Encontradas " . count($mensagens) . " mensagens");
            
            // Formatar mensagens para garantir campos corretos
            $mensagens = $mensagens->map(function($msg) {
                return [
                    'id' => $msg->id,
                    'conversa_id' => $msg->conversa_id,
                    'message_sid' => $msg->message_sid ?? null,
                    'direction' => $msg->direction,
                    'message_type' => $msg->message_type,
                    'content' => $msg->content ?? '',
                    'media_url' => $msg->media_url ?? null,
                    'transcription' => $msg->transcription ?? null,
                    'status' => $msg->status ?? 'sent',
                    'sent_at' => $msg->sent_at,
                    'read_at' => $msg->read_at ?? null,
                    'created_at' => $msg->created_at ?? null
                ];
            });
            
            // Marcar mensagens como lidas
            $db->table('mensagens')
                ->where('conversa_id', $id)
                ->where('direction', 'incoming')
                ->whereNull('read_at')
                ->update(['read_at' => date('Y-m-d H:i:s')]);
            
            \Log::info("Mensagens marcadas como lidas");
            
            // Converter conversa para array e adicionar mensagens
            $conversaArray = (array) $conversa;
            $conversaArray['mensagens'] = $mensagens->toArray();
            
            return response()->json([
                'success' => true,
                'data' => $conversaArray
            ]);
        } catch (\Exception $e) {
            \Log::error("Erro ao buscar conversa {$id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
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
