<?php

namespace App\Services;

use App\Models\Conversa;
use App\Models\Lead;
use App\Models\Mensagem;
use App\Models\Property;
use App\Models\LeadPropertyMatch;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Serviço Orquestrador de WhatsApp
 * APROVEITADO E ADAPTADO de: ConversationService.php
 * 
 * Responsabilidades:
 * - Receber e processar webhooks do Twilio
 * - Gerenciar conversas e mensagens
 * - Transcrever áudios
 * - Extrair dados de leads via IA
 * - Fazer matching de imóveis
 * - Enviar respostas automáticas
 */
class WhatsAppService
{
    private $twilio;
    private $openai;
    
    public function __construct(TwilioService $twilio, OpenAIService $openai)
    {
        $this->twilio = $twilio;
        $this->openai = $openai;
    }
    
    /**
     * Processar mensagem recebida do webhook (Twilio ou Evolution API)
     */
    public function processIncomingMessage($webhookData)
    {
        try {
            Log::info('🔄 Extraindo dados do webhook...');
            
            // Dados normalizados pelo WebhookController
            $from = $webhookData['from'] ?? null;
            $body = $webhookData['message'] ?? '';
            $messageSid = $webhookData['message_id'] ?? null;
            $mediaUrl = $webhookData['media_url'] ?? null;
            $mediaType = $webhookData['media_type'] ?? null;
            
            // Dados do perfil WhatsApp
            $profileName = $webhookData['profile_name'] ?? null;
            $source = $webhookData['source'] ?? 'unknown';
            
            // Dados de localização (se disponível)
            $location = $webhookData['location'] ?? [];
            $latitude = $location['latitude'] ?? null;
            $longitude = $location['longitude'] ?? null;
            $city = $location['city'] ?? null;
            $state = $location['state'] ?? null;
            $country = $location['country'] ?? null;
            
            Log::info('📦 Dados extraídos:', [
                'telefone' => $from,
                'nome' => $profileName,
                'origem' => $source,
                'localizacao' => $city && $state ? "$city, $state" : ($city ?? $state ?? 'N/A'),
                'tem_midia' => $mediaUrl ? 'Sim' : 'Não'
            ]);
            
            if (!$from) {
                return ['success' => false, 'error' => 'Número de origem não identificado'];
            }
            
            // Limpar telefone
            $telefone = $this->cleanPhoneNumber($from);
            
            // 1. Obter ou criar conversa
            $conversaData = [
                'profile_name' => $profileName,
                'city' => $city,
                'state' => $state,
                'country' => $country,
                'latitude' => $latitude,
                'longitude' => $longitude
            ];
            $conversa = $this->getOrCreateConversa($telefone, ['profile_name' => $profileName]);
            
            // 2. Registrar mensagem recebida
            $messageType = $this->detectMessageType($mediaUrl, $mediaType);
            $mensagem = $this->saveMensagem($conversa->id, [
                'message_sid' => $messageSid,
                'direction' => 'incoming',
                'message_type' => $messageType,
                'content' => $body,
                'media_url' => $mediaUrl,
                'status' => 'received'
            ]);
            
            // 3. Processar áudio se necessário
            if ($messageType === 'audio' && $mediaUrl) {
                $body = $this->transcribeAudio($mediaUrl, $conversa->id, $mensagem->id);
            }
            
            // 4. Garantir que lead existe (criar se não existir)
            if (!$conversa->lead_id) {
                $lead = $this->createLead($telefone, $conversaData, $conversa->id);
                $conversa->update(['lead_id' => $lead->id]);
                Log::info('Lead criado e vinculado à conversa', ['lead_id' => $lead->id, 'conversa_id' => $conversa->id]);
            }
            
            // 5. Verificar se é primeira mensagem (boas-vindas)
            if ($conversa->mensagens()->count() === 1) {
                return $this->handleFirstMessage($conversa, $telefone, $conversaData);
            }
            
            // 6. Processar com IA e responder
            return $this->handleRegularMessage($conversa, $body);
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter ou criar conversa com dados geográficos
     */
    private function getOrCreateConversa($telefone, $dados)
    {
        $conversa = Conversa::where('telefone', $telefone)
            ->where('status', '!=', 'finalizada')
            ->first();
        
        if (!$conversa) {
            $conversa = Conversa::create([
                'telefone' => $telefone,
                'whatsapp_name' => $dados['profile_name'],
                'status' => 'ativa',
                'stage' => 'boas_vindas',
                'iniciada_em' => Carbon::now()
            ]);
            
            Log::info('Nova conversa criada', [
                'id' => $conversa->id,
                'telefone' => $telefone,
                'whatsapp_name' => $dados['profile_name']
            ]);
        } else {
            // Atualizar nome se não existir
            if (!$conversa->whatsapp_name && $dados['profile_name']) {
                $conversa->update(['whatsapp_name' => $dados['profile_name']]);
                Log::info('Conversa atualizada com nome', ['whatsapp_name' => $dados['profile_name']]);
            }
        }
        
        return $conversa;
    }
    
    /**
     * Primeira mensagem - Enviar boas-vindas
     */
    private function handleFirstMessage($conversa, $telefone, $dados)
    {
        // Criar lead com todos os dados capturados
        $lead = $this->createLead($telefone, $dados, $conversa->id);
        
        $conversa->update([
            'lead_id' => $lead->id,
            'stage' => 'coleta_dados' // Avança para coleta de dados
        ]);
        
        // Mensagem de boas-vindas
        $mensagemBoasVindas = "Olá! 😊 Que alegria ter você aqui na *Exclusiva Lar Imóveis*!\n\n" .
                             "Meu nome é da equipe de atendimento e estou aqui de coração para te ajudar a encontrar o imóvel dos seus sonhos! 🏡✨\n\n" .
                             "Vamos começar? Me conta com suas palavras:\n\n" .
                             "🎤 *Pode enviar um áudio* (é mais fácil!) ou digitar, como preferir:\n\n" .
                             "💰 Quanto você pode investir?\n" .
                             "📍 Qual região você procura?\n" .
                             "🛏️ Quantos quartos você precisa?\n" .
                             "✨ Tem algum desejo especial?\n\n" .
                             "Estou aqui para te ouvir! 💙";
        
        $this->sendMessage($conversa->id, $telefone, $mensagemBoasVindas);
        
        return [
            'success' => true,
            'message' => 'Primeira mensagem processada',
            'lead_id' => $lead->id
        ];
    }
    
    /**
     * Processar mensagem regular com progressão inteligente de stages
     */
    private function handleRegularMessage($conversa, $message)
    {
        // Buscar histórico da conversa
        $historico = $this->getConversationHistory($conversa->id);
        
        // Processar com IA
        $aiResponse = $this->openai->processMessage($message, $historico);
        
        if ($aiResponse['success']) {
            // Enviar resposta
            $this->sendMessage($conversa->id, $conversa->telefone, $aiResponse['content']);
            
            // Tentar extrair dados do lead
            $this->extractAndUpdateLeadData($conversa);
            
            // Recarregar lead com dados atualizados
            $conversa->load('lead');
            
            // INTELIGÊNCIA: Decidir próximo stage baseado em dados
            $this->progressStage($conversa);
            
            // Verificar se já tem dados suficientes para matching
            if ($conversa->lead && $this->hasEnoughDataForMatching($conversa->lead)) {
                // Transição automática: coleta_dados → matching → apresentacao
                $this->performPropertyMatching($conversa->lead, $conversa);
                $conversa->update(['stage' => 'apresentacao']);
            }
        }
        
        return [
            'success' => true,
            'message' => 'Mensagem processada',
            'ai_response' => $aiResponse['content'] ?? null,
            'current_stage' => $conversa->stage
        ];
    }
    
    /**
     * Progressão inteligente de stages baseada em contexto
     */
    private function progressStage($conversa)
    {
        if (!$conversa->lead) return;
        
        $lead = $conversa->lead;
        $currentStage = $conversa->stage;
        
        // Regras de transição automática
        switch ($currentStage) {
            case 'coleta_dados':
                // Se já tem orçamento OU localização OU quartos, progride para matching
                if ($lead->budget_min || $lead->budget_max || $lead->localizacao || $lead->quartos) {
                    Log::info('🎯 PROGRESSÃO DE STAGE: coleta_dados → matching');
                    Log::info('   └─ Conversa ID: ' . $conversa->id);
                    Log::info('   └─ Lead ID: ' . $lead->id);
                    Log::info('   └─ Motivo: Dados suficientes coletados');
                    // Não muda ainda - aguarda matching retornar resultados
                } else {
                    // Ainda coletando dados
                    $conversa->update(['stage' => 'aguardando_info']);
                }
                break;
                
            case 'apresentacao':
                // Se cliente pergunta sobre imóvel específico ou demonstra interesse
                // (detectado pela IA no contexto)
                $contexto = strtolower($conversa->contexto_conversa ?? '');
                if (strpos($contexto, 'interesse') !== false || 
                    strpos($contexto, 'visita') !== false ||
                    strpos($contexto, 'ver') !== false) {
                    $conversa->update(['stage' => 'interesse']);
                    Log::info('🎯 PROGRESSÃO DE STAGE: apresentacao → interesse');
                    Log::info('   └─ Conversa ID: ' . $conversa->id);
                    Log::info('   └─ Motivo: Cliente demonstrou interesse');
                    Log::info('   └─ Contexto detectado: ' . $contexto);
                }
                break;
                
            case 'interesse':
                // Se cliente solicita agendamento explicitamente
                $ultimaMensagem = strtolower($conversa->ultima_mensagem ?? '');
                if (strpos($ultimaMensagem, 'agendar') !== false || 
                    strpos($ultimaMensagem, 'visitar') !== false ||
                    strpos($ultimaMensagem, 'ver o imovel') !== false ||
                    strpos($ultimaMensagem, 'quando posso') !== false) {
                    $conversa->update(['stage' => 'agendamento']);
                    $lead->update(['status' => 'qualificado']);
                    Log::info('🎯 PROGRESSÃO DE STAGE: interesse → agendamento');
                    Log::info('   └─ Conversa ID: ' . $conversa->id);
                    Log::info('   └─ Motivo: Cliente solicitou agendamento');
                    Log::info('   └─ Lead Status: qualificado ⭐');
                    Log::info('   └─ Última mensagem: ' . substr($ultimaMensagem, 0, 50) . '...');
                }
                break;
                
            case 'sem_match':
                // Se cliente aceita refinar critérios
                $conversa->update(['stage' => 'refinamento']);
                break;
                
            case 'refinamento':
                // Volta para coleta_dados com critérios ajustados
                $conversa->update(['stage' => 'coleta_dados']);
                break;
        }
    }
    
    /**
     * Transcrever áudio
     */
    private function transcribeAudio($mediaUrl, $conversaId, $mensagemId)
    {
        try {
            // Baixar áudio
            $audioData = $this->twilio->downloadMedia($mediaUrl);
            
            if (!$audioData['success']) {
                return '[Áudio não pôde ser processado]';
            }
            
            // Salvar temporariamente
            $audioPath = storage_path('app/temp/audio_' . time() . '.ogg');
            file_put_contents($audioPath, $audioData['data']);
            
            // Transcrever
            $transcription = $this->openai->transcribeAudio($audioPath);
            
            // Limpar arquivo
            @unlink($audioPath);
            
            if ($transcription['success']) {
                // Atualizar mensagem com transcrição
                Mensagem::where('id', $mensagemId)->update([
                    'transcription' => $transcription['text']
                ]);
                
                return $transcription['text'];
            }
            
            return '[Não foi possível transcrever o áudio]';
            
        } catch (\Exception $e) {
            Log::error('Erro ao transcrever áudio', ['error' => $e->getMessage()]);
            return '[Erro ao processar áudio]';
        }
    }
    
    /**
     * Extrair e atualizar dados do lead
     */
    private function extractAndUpdateLeadData($conversa)
    {
        if (!$conversa->lead) return;
        
        $historico = $this->getConversationHistory($conversa->id);
        $extracted = $this->openai->extractLeadData($historico);
        
        if ($extracted['success'] && !empty($extracted['data'])) {
            $conversa->lead->update($extracted['data']);
            
            Log::info('Dados do lead atualizados', [
                'lead_id' => $conversa->lead->id,
                'data' => $extracted['data']
            ]);
        }
    }
    
    /**
     * Verificar se tem dados suficientes para matching
     */
    private function hasEnoughDataForMatching($lead)
    {
        return $lead->budget_min && $lead->localizacao && $lead->quartos;
    }
    
    /**
     * Fazer matching de imóveis com tratamento inteligente
     */
    private function performPropertyMatching($lead, $conversa)
    {
        // Buscar imóveis compatíveis
        $properties = Property::where('active', 1)
            ->where('exibir_imovel', 1)
            ->where('dormitorios', '>=', $lead->quartos)
            ->where(function($q) use ($lead) {
                if ($lead->budget_min && $lead->budget_max) {
                    $q->whereBetween('valor_venda', [$lead->budget_min, $lead->budget_max]);
                }
            })
            ->limit(5)
            ->get();
        
        if ($properties->count() > 0) {
            // ENCONTROU IMÓVEIS!
            foreach ($properties as $property) {
                LeadPropertyMatch::create([
                    'lead_id' => $lead->id,
                    'property_id' => $property->id,
                    'conversa_id' => $conversa->id,
                    'match_score' => 80.0 // Simplificado por enquanto
                ]);
            }
            
            // Enviar mensagem com imóveis encontrados
            $mensagem = "🎉 Encontrei " . $properties->count() . " imóveis que combinam com o que você procura!\n\n";
            $mensagem .= "Vou te enviar os detalhes agora...";
            
            $this->sendMessage($conversa->id, $conversa->telefone, $mensagem);
            
            // Atualizar stage para apresentacao
            $conversa->update(['stage' => 'apresentacao']);
            
            Log::info('╔════════════════════════════════════════════════════════════════╗');
            Log::info('║           🎉 IMÓVEIS ENCONTRADOS!                             ║');
            Log::info('╚════════════════════════════════════════════════════════════════╝');
            Log::info('🏠 Quantidade: ' . $properties->count() . ' imóveis');
            Log::info('👤 Lead: ' . $lead->nome . ' (ID: ' . $lead->id . ')');
            Log::info('💰 Orçamento: R$ ' . number_format($lead->budget_min ?? 0, 0, ',', '.') . ' - R$ ' . number_format($lead->budget_max ?? 0, 0, ',', '.'));
            Log::info('📍 Localização: ' . ($lead->localizacao ?? 'N/A'));
            Log::info('🛏️  Quartos: ' . ($lead->quartos ?? 'N/A'));
            Log::info('🎯 Novo Stage: apresentacao');
            Log::info('─────────────────────────────────────────────────────────────────');
        } else {
            // NENHUM IMÓVEL ENCONTRADO
            $mensagem = "😔 No momento não tenho imóveis disponíveis que se encaixem exatamente no que você procura.\n\n";
            $mensagem .= "Mas não desanima! Posso fazer algumas coisas por você:\n\n";
            $mensagem .= "1️⃣ Podemos ajustar um pouco o orçamento ou a região?\n";
            $mensagem .= "2️⃣ Cadastro seu interesse e te aviso assim que chegar algo perfeito!\n";
            $mensagem .= "3️⃣ Posso te mostrar opções bem próximas do que você quer?\n\n";
            $mensagem .= "O que você prefere? 😊";
            
            $this->sendMessage($conversa->id, $conversa->telefone, $mensagem);
            
            // Atualizar stage para sem_match
            $conversa->update(['stage' => 'sem_match']);
            
            Log::info('╔════════════════════════════════════════════════════════════════╗');
            Log::info('║           😔 NENHUM IMÓVEL ENCONTRADO                         ║');
            Log::info('╚════════════════════════════════════════════════════════════════╝');
            Log::info('👤 Lead: ' . $lead->nome . ' (ID: ' . $lead->id . ')');
            Log::info('💰 Orçamento buscado: R$ ' . number_format($lead->budget_min ?? 0, 0, ',', '.') . ' - R$ ' . number_format($lead->budget_max ?? 0, 0, ',', '.'));
            Log::info('📍 Localização buscada: ' . ($lead->localizacao ?? 'N/A'));
            Log::info('🛏️  Quartos buscados: ' . ($lead->quartos ?? 'N/A'));
            Log::info('🎯 Novo Stage: sem_match');
            Log::info('💡 Ação: Oferecendo refinamento de critérios');
            Log::info('─────────────────────────────────────────────────────────────────');
        }
    }
    
    /**
     * Obter histórico da conversa
     */
    private function getConversationHistory($conversaId)
    {
        $mensagens = Mensagem::where('conversa_id', $conversaId)
            ->orderBy('sent_at', 'asc')
            ->get();
        
        $historico = '';
        foreach ($mensagens as $msg) {
            $remetente = $msg->direction === 'incoming' ? 'Cliente' : 'Atendente';
            $texto = $msg->transcription ?: $msg->content;
            $historico .= "$remetente: $texto\n";
        }
        
        return $historico;
    }
    
    /**
     * Enviar mensagem
     */
    private function sendMessage($conversaId, $telefone, $body)
    {
        $result = $this->twilio->sendMessage($telefone, $body);
        
        // Registrar mensagem enviada
        $this->saveMensagem($conversaId, [
            'message_sid' => $result['message_sid'] ?? null,
            'direction' => 'outgoing',
            'message_type' => 'text',
            'content' => $body,
            'status' => $result['success'] ? 'sent' : 'failed'
        ]);
        
        return $result;
    }
    
    /**
     * Salvar mensagem no banco
     */
    private function saveMensagem($conversaId, $data)
    {
        return Mensagem::create(array_merge([
            'conversa_id' => $conversaId,
            'sent_at' => Carbon::now()
        ], $data));
    }
    
    /**
     * Criar lead com dados completos do WhatsApp incluindo geolocalização
     */
    private function createLead($telefone, $dados, $conversaId)
    {
        // Montar localização se tiver cidade/estado
        $localizacao = null;
        $city = $dados['city'] ?? null;
        $state = $dados['state'] ?? null;
        
        if ($city && $state) {
            $localizacao = $city . ', ' . $state;
        } elseif ($city) {
            $localizacao = $city;
        } elseif ($state) {
            $localizacao = $state;
        }
        
        $leadData = [
            'nome' => $dados['profile_name'],
            'whatsapp_name' => $dados['profile_name'],
            'localizacao' => $localizacao,
            'status' => 'novo',
            'origem' => 'whatsapp',
            'primeira_interacao' => Carbon::now(),
            'ultima_interacao' => Carbon::now()
        ];
        
        $lead = Lead::firstOrCreate(
            ['telefone' => $telefone],
            $leadData
        );
        
        // Se o lead já existia, atualizar dados se não tiver
        if (!$lead->wasRecentlyCreated) {
            $updates = [];
            if (!$lead->nome && isset($dados['profile_name'])) $updates['nome'] = $dados['profile_name'];
            if (!$lead->localizacao && $localizacao) $updates['localizacao'] = $localizacao;
            
            if (!empty($updates)) {
                $lead->update($updates);
            }
        }
        
        Log::info('╔════════════════════════════════════════════════════════════════╗');
        Log::info('║           ' . ($lead->wasRecentlyCreated ? '🆕 LEAD CRIADO' : '🔄 LEAD ATUALIZADO') . '                               ║');
        Log::info('╚════════════════════════════════════════════════════════════════╝');
        Log::info('🆔 Lead ID: ' . $lead->id);
        Log::info('👤 Nome: ' . ($dados['profile_name'] ?? 'N/A'));
        Log::info('📱 Telefone: ' . $telefone);
        Log::info('📍 Localização: ' . ($localizacao ?? 'N/A'));
        Log::info('🎯 Status: ' . $lead->status);
        Log::info('─────────────────────────────────────────────────────────────────');
        
        return $lead;
    }
    
    /**
     * Limpar número de telefone
     */
    private function cleanPhoneNumber($phone)
    {
        return str_replace('whatsapp:', '', $phone);
    }
    
    /**
     * Detectar tipo de mensagem
     */
    private function detectMessageType($mediaUrl, $mediaType)
    {
        if (!$mediaUrl) return 'text';
        
        if (strpos($mediaType, 'audio') !== false) return 'audio';
        if (strpos($mediaType, 'image') !== false) return 'image';
        if (strpos($mediaType, 'video') !== false) return 'video';
        
        return 'document';
    }
}
