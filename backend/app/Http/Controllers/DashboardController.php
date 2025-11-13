<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Conversa;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Controller do Dashboard
 */
class DashboardController extends Controller
{
    /**
     * Estatísticas gerais
     * GET /api/dashboard/stats
     */
    public function stats()
    {
        $stats = [
            'leads' => [
                'total' => Lead::count(),
                'novos' => Lead::where('status', 'novo')->count(),
                'em_atendimento' => Lead::where('status', 'em_atendimento')->count(),
                'qualificados' => Lead::where('status', 'qualificado')->count(),
                'fechados_mes' => Lead::where('status', 'fechado')
                    ->whereMonth('updated_at', now()->month)
                    ->count()
            ],
            'conversas' => [
                'ativas' => Conversa::where('status', 'ativa')->count(),
                'hoje' => Conversa::whereDate('iniciada_em', today())->count(),
                'aguardando' => Conversa::where('status', 'aguardando_corretor')->count()
            ],
            'corretores' => [
                'total' => User::where('tipo', 'corretor')->where('ativo', 1)->count(),
                'online' => 0 // Implementar depois com WebSocket
            ]
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
    
    /**
     * Gráfico de atendimentos (últimos 7 dias)
     * GET /api/dashboard/chart/atendimentos
     */
    public function chartAtendimentos()
    {
        $dados = DB::table('conversas')
            ->select(DB::raw('DATE(iniciada_em) as data'), DB::raw('COUNT(*) as total'))
            ->where('iniciada_em', '>=', now()->subDays(7))
            ->groupBy('data')
            ->orderBy('data')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $dados
        ]);
    }
    
    /**
     * Atividades recentes
     * GET /api/dashboard/atividades
     */
    public function atividades()
    {
        // Buscar últimas conversas iniciadas
        $conversas = Conversa::with(['lead'])
            ->orderBy('iniciada_em', 'desc')
            ->limit(10)
            ->get()
            ->map(function($conv) {
                return [
                    'tipo' => 'nova_conversa',
                    'descricao' => 'Nova conversa iniciada com ' . ($conv->lead->nome ?? $conv->telefone),
                    'timestamp' => $conv->iniciada_em,
                    'data' => [
                        'conversa_id' => $conv->id,
                        'lead_id' => $conv->lead_id
                    ]
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $conversas
        ]);
    }
}
