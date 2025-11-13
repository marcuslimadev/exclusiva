<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lead;

/**
 * Controller de Leads
 */
class LeadsController extends Controller
{
    /**
     * Listar leads
     * GET /api/leads
     */
    public function index(Request $request)
    {
        try {
            $db = app('db');
            $query = $db->table('leads');
            
            // Filtros
            if ($request->status) {
                $query->where('status', $request->status);
            }
            
            if ($request->corretor_id) {
                $query->where('corretor_id', $request->corretor_id);
            }
            
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('nome', 'like', '%' . $request->search . '%')
                      ->orWhere('telefone', 'like', '%' . $request->search . '%');
                });
            }
            
            // Ordenação
            $query->orderBy('updated_at', 'desc');
            
            // Get all leads (simplificado - sem paginação por enquanto)
            $leads = $query->get();
            
            return response()->json([
                'success' => true,
                'data' => $leads
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Detalhes do lead
     * GET /api/leads/{id}
     */
    public function show($id)
    {
        $lead = Lead::with(['corretor', 'conversas.mensagens', 'propertyMatches.property'])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $lead
        ]);
    }
    
    /**
     * Atualizar lead
     * PUT /api/leads/{id}
     */
    public function update(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);
        
        $lead->update($request->only([
            'nome',
            'email',
            'status',
            'corretor_id',
            'budget_min',
            'budget_max',
            'localizacao',
            'quartos',
            'suites',
            'garagem'
        ]));
        
        return response()->json([
            'success' => true,
            'message' => 'Lead atualizado com sucesso',
            'data' => $lead
        ]);
    }
    
    /**
     * Estatísticas de leads
     * GET /api/leads/stats
     */
    public function stats()
    {
        try {
            $db = app('db');
            $stats = [
                'total' => $db->table('leads')->count(),
                'novos' => $db->table('leads')->where('status', 'novo')->count(),
                'em_atendimento' => $db->table('leads')->where('status', 'em_atendimento')->count(),
                'qualificados' => $db->table('leads')->where('status', 'qualificado')->count(),
                'fechados' => $db->table('leads')->where('status', 'fechado')->count(),
                'hoje' => $db->table('leads')->whereDate('created_at', date('Y-m-d'))->count()
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
}
