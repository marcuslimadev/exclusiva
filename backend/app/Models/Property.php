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
        'latitude',
        'longitude',
        'em_condominio',
        'exclusividade',
        'exibir_imovel',
        'active',
        'api_data'
    ];
    
    protected $appends = ['imagens_array', 'caracteristicas_array'];
    
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
        // 'imagens' => 'json', // Removido - usando accessor customizado
        // 'caracteristicas' => 'json', // Removido - usando accessor customizado
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
        // Se já é array, retorna direto
        if (is_array($value)) {
            return $value;
        }
        
        // Se é string JSON, decodifica
        if (is_string($value) && !empty($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        // Caso contrário, retorna array vazio
        return [];
    }
    
    // Accessor para garantir que caracteristicas sempre retorne array
    public function getCaracteristicasAttribute($value)
    {
        // Se já é array, retorna direto
        if (is_array($value)) {
            return $value;
        }
        
        // Se é string JSON, decodifica
        if (is_string($value) && !empty($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        // Caso contrário, retorna array vazio
        return [];
    }
    
    // Sobrescrever toArray para garantir conversão
    public function toArray()
    {
        $array = parent::toArray();
        
        // Garantir que imagens e caracteristicas sejam arrays
        if (isset($array['imagens']) && is_string($array['imagens'])) {
            $array['imagens'] = json_decode($array['imagens'], true) ?: [];
        }
        
        if (isset($array['caracteristicas']) && is_string($array['caracteristicas'])) {
            $array['caracteristicas'] = json_decode($array['caracteristicas'], true) ?: [];
        }
        
        return $array;
    }
    
    // Relacionamento
    public function leadMatches()
    {
        return $this->hasMany(LeadPropertyMatch::class, 'property_id');
    }
}
