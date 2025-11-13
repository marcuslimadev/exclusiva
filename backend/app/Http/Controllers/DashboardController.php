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
        try {
            $stats = [
                'leads' => [
                    'total' => DB::table('leads')->count(),
                    'novos' => DB::table('leads')->where('status', 'novo')->count(),
                    'em_atendimento' => DB::table('leads')->where('status', 'em_atendimento')->count(),
                    'qualificados' => DB::table('leads')->where('status', 'qualificado')->count(),
                    'fechados_mes' => DB::table('leads')
                        ->where('status', 'fechado')
                        ->whereRaw('EXTRACT(MONTH FROM updated_at) = ?', [now()->month])
                        ->count()
                ],
                'conversas' => [
                    'ativas' => DB::table('conversas')->where('status', 'ativa')->count(),
                    'hoje' => DB::table('conversas')->whereDate('iniciada_em', today())->count(),
                    'aguardando' => DB::table('conversas')->where('status', 'aguardando_corretor')->count()
                ],
                'corretores' => [
                    'total' => DB::table('users')->where('tipo', 'corretor')->where('ativo', true)->count(),
                    'online' => 0
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
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
        try {
            $conversas = DB::table('conversas')
                ->leftJoin('leads', 'conversas.lead_id', '=', 'leads.id')
                ->select(
                    'conversas.id as conversa_id',
                    'conversas.lead_id',
                    'conversas.telefone',
                    'conversas.iniciada_em',
                    'leads.nome as lead_nome'
                )
                ->orderBy('conversas.iniciada_em', 'desc')
                ->limit(10)
                ->get()
                ->map(function($conv) {
                    return [
                        'tipo' => 'nova_conversa',
                        'descricao' => 'Nova conversa iniciada com ' . ($conv->lead_nome ?? $conv->telefone),
                        'timestamp' => $conv->iniciada_em,
                        'data' => [
                            'conversa_id' => $conv->conversa_id,
                            'lead_id' => $conv->lead_id
                        ]
                    ];
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
}
