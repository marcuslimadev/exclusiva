<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $table = 'leads';
    
    protected $fillable = [
        'telefone',
        'nome',
        'email',
        'whatsapp_name',
        'budget_min',
        'budget_max',
        'localizacao',
        'quartos',
        'suites',
        'garagem',
        'caracteristicas_desejadas',
        'corretor_id',
        'status',
        'origem',
        'score',
        'primeira_interacao',
        'ultima_interacao'
    ];
    
    protected $casts = [
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'quartos' => 'integer',
        'suites' => 'integer',
        'garagem' => 'integer',
        'score' => 'integer',
        'primeira_interacao' => 'datetime',
        'ultima_interacao' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // Relacionamentos
    public function corretor()
    {
        return $this->belongsTo(User::class, 'corretor_id');
    }
    
    public function conversas()
    {
        return $this->hasMany(Conversa::class, 'lead_id');
    }
    
    public function propertyMatches()
    {
        return $this->hasMany(LeadPropertyMatch::class, 'lead_id');
    }
    
    public function atividades()
    {
        return $this->hasMany(Atividade::class, 'lead_id');
    }
}
