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
            
            // Formatar datas para ISO8601 (compatível com JavaScript)
            $leads = collect($leads)->map(function($lead) {
                if (isset($lead->created_at)) {
                    $lead->created_at = date('c', strtotime($lead->created_at));
                }
                if (isset($lead->updated_at)) {
                    $lead->updated_at = date('c', strtotime($lead->updated_at));
                }
                if (isset($lead->primeira_interacao)) {
                    $lead->primeira_interacao = date('c', strtotime($lead->primeira_interacao));
                }
                if (isset($lead->ultima_interacao)) {
                    $lead->ultima_interacao = date('c', strtotime($lead->ultima_interacao));
                }
                return $lead;
            });
            
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

    /**
     * Atualizar estado do lead (para Kanban drag-and-drop)
     * PATCH /api/leads/{id}/state
     */
    public function updateState(Request $request, $id)
    {
        try {
            $lead = Lead::findOrFail($id);
            
            $this->validate($request, [
                'state' => 'required|string|max:2'
            ]);
            
            $lead->state = $request->state;
            $lead->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Estado atualizado com sucesso',
                'data' => $lead
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar status do funil do lead (para Kanban drag-and-drop)
     * PATCH /api/leads/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $lead = Lead::findOrFail($id);
            
            $this->validate($request, [
                'status' => 'required|in:novo,em_atendimento,qualificado,proposta,fechado,perdido'
            ]);
            
            $lead->status = $request->status;
            $lead->updated_at = now();
            $lead->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Status do funil atualizado com sucesso',
                'data' => $lead
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
