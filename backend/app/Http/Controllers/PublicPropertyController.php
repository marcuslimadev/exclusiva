<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;

class PublicPropertyController extends Controller
{
    /**
     * Listar imóveis disponíveis (público)
     * 
     * GET /api/properties
     */
    public function index(Request $request)
    {
        $query = Property::where('active', 1)
            ->where('exibir_imovel', 1)
            ->orderBy('created_at', 'desc');
        
        // Filtros opcionais
        if ($request->has('tipo')) {
            $query->where('tipo_imovel', 'ILIKE', '%' . $request->tipo . '%');
        }
        
        if ($request->has('cidade')) {
            $query->where('cidade', 'ILIKE', '%' . $request->cidade . '%');
        }
        
        if ($request->has('bairro')) {
            $query->where('bairro', 'ILIKE', '%' . $request->bairro . '%');
        }
        
        if ($request->has('quartos_min')) {
            $query->where('dormitorios', '>=', $request->quartos_min);
        }
        
        if ($request->has('preco_min')) {
            $query->where('valor_venda', '>=', $request->preco_min);
        }
        
        if ($request->has('preco_max')) {
            $query->where('valor_venda', '<=', $request->preco_max);
        }
        
        $properties = $query->get();
        
        // Fix: Converter campos JSON que vieram como string
        $properties = $properties->map(function($prop) {
            if (is_string($prop->imagens)) {
                $prop->imagens = json_decode($prop->imagens, true) ?: [];
            }
            if (is_string($prop->caracteristicas)) {
                $prop->caracteristicas = json_decode($prop->caracteristicas, true) ?: [];
            }
            return $prop;
        });
        
        return response()->json([
            'success' => true,
            'total' => $properties->count(),
            'data' => $properties
        ]);
    }
    
    /**
     * Detalhes de um imóvel específico
     * 
     * GET /api/properties/{codigo}
     */
    public function show($codigo)
    {
        $property = Property::where('codigo_imovel', $codigo)
            ->where('active', 1)
            ->where('exibir_imovel', 1)
            ->first();
        
        if (!$property) {
            return response()->json([
                'success' => false,
                'error' => 'Imóvel não encontrado'
            ], 404);
        }
        
        // Fix: Converter campos JSON que vieram como string
        if (is_string($property->imagens)) {
            $property->imagens = json_decode($property->imagens, true) ?: [];
        }
        if (is_string($property->caracteristicas)) {
            $property->caracteristicas = json_decode($property->caracteristicas, true) ?: [];
        }
        
        return response()->json([
            'success' => true,
            'data' => $property
        ]);
    }
}
