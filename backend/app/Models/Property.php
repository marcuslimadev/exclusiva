<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $table = 'imo_properties';
    
    protected $fillable = [
        'codigo_imovel',
        'referencia_imovel',
        'finalidade_imovel',
        'tipo_imovel',
        'descricao',
        'dormitorios',
        'suites',
        'banheiros',
        'garagem',
        'valor_venda',
        'valor_aluguel',
        'valor_iptu',
        'valor_condominio',
        'cidade',
        'estado',
        'bairro',
        'logradouro',
        'numero',
        'complemento',
        'cep',
        'area_privativa',
        'area_total',
        'area_terreno',
        'imagem_destaque',
        'imagens',
        'caracteristicas',
        'em_condominio',
        'exclusividade',
        'exibir_imovel',
        'active',
        'api_data'
    ];
    
    protected $casts = [
        'dormitorios' => 'integer',
        'suites' => 'integer',
        'banheiros' => 'integer',
        'garagem' => 'integer',
        'valor_venda' => 'decimal:2',
        'valor_aluguel' => 'decimal:2',
        'valor_iptu' => 'decimal:2',
        'valor_condominio' => 'decimal:2',
        'area_privativa' => 'decimal:2',
        'area_total' => 'decimal:2',
        'area_terreno' => 'decimal:2',
        'imagens' => 'json',
        'caracteristicas' => 'json',
        'api_data' => 'json',
        'em_condominio' => 'boolean',
        'exclusividade' => 'boolean',
        'exibir_imovel' => 'boolean',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // Accessor para garantir que imagens sempre retorne array
    public function getImagensAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }
        return $value ?: [];
    }
    
    // Accessor para garantir que caracteristicas sempre retorne array
    public function getCaracteristicasAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }
        return $value ?: [];
    }
    
    // Relacionamento
    public function leadMatches()
    {
        return $this->hasMany(LeadPropertyMatch::class, 'property_id');
    }
}
