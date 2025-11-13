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
        'cidade',
        'estado',
        'bairro',
        'endereco',
        'area_privativa',
        'area_total',
        'imagem_destaque',
        'caracteristicas',
        'imagens',
        'exibir_imovel',
        'active'
    ];
    
    protected $casts = [
        'dormitorios' => 'integer',
        'suites' => 'integer',
        'banheiros' => 'integer',
        'garagem' => 'integer',
        'valor_venda' => 'decimal:2',
        'valor_aluguel' => 'decimal:2',
        'area_privativa' => 'decimal:2',
        'area_total' => 'decimal:2',
        'exibir_imovel' => 'boolean',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // Relacionamento
    public function leadMatches()
    {
        return $this->hasMany(LeadPropertyMatch::class, 'property_id');
    }
}
