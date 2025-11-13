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
        $query = Lead::with(['corretor', 'conversas']);
        
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
        $query->orderBy('ultima_interacao', 'desc');
        
        // Paginação
        $leads = $query->paginate($request->per_page ?? 20);
        
        return response()->json([
            'success' => true,
            'data' => $leads
        ]);
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
        $stats = [
            'total' => Lead::count(),
            'novos' => Lead::where('status', 'novo')->count(),
            'em_atendimento' => Lead::where('status', 'em_atendimento')->count(),
            'qualificados' => Lead::where('status', 'qualificado')->count(),
            'fechados' => Lead::where('status', 'fechado')->count(),
            'hoje' => Lead::whereDate('created_at', today())->count()
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
