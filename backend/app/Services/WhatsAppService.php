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
     * Processar mensagem recebida do webhook Twilio
     */
    public function processIncomingMessage($webhookData)
    {
        try {
            Log::info('=== WEBHOOK RECEBIDO ===', $webhookData);
            
            $from = $webhookData['From'] ?? null;
            $body = $webhookData['Body'] ?? '';
            $messageSid = $webhookData['MessageSid'] ?? null;
            $mediaUrl = $webhookData['MediaUrl0'] ?? null;
            $mediaType = $webhookData['MediaContentType0'] ?? null;
            $profileName = $webhookData['ProfileName'] ?? null;
            
            if (!$from) {
                return ['success' => false, 'error' => 'Número de origem não identificado'];
            }
            
            // Limpar telefone
            $telefone = $this->cleanPhoneNumber($from);
            
            // 1. Obter ou criar conversa
            $conversa = $this->getOrCreateConversa($telefone, $profileName);
            
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
            
            // 4. Verificar se é primeira mensagem (boas-vindas)
            if ($conversa->mensagens()->count() === 1) {
                return $this->handleFirstMessage($conversa, $telefone, $profileName);
            }
            
            // 5. Processar com IA e responder
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
     * Obter ou criar conversa
     */
    private function getOrCreateConversa($telefone, $profileName)
    {
        $conversa = Conversa::where('telefone', $telefone)
            ->where('status', '!=', 'finalizada')
            ->first();
        
        if (!$conversa) {
            $conversa = Conversa::create([
                'telefone' => $telefone,
                'whatsapp_name' => $profileName,
                'status' => 'ativa',
                'stage' => 'inicial',
                'iniciada_em' => Carbon::now()
            ]);
            
            Log::info('Nova conversa criada', ['id' => $conversa->id, 'telefone' => $telefone]);
        }
        
        return $conversa;
    }
    
    /**
     * Primeira mensagem - Enviar boas-vindas
     */
    private function handleFirstMessage($conversa, $telefone, $profileName)
    {
        // Criar lead
        $lead = $this->createLead($telefone, $profileName, $conversa->id);
        
        $conversa->update(['lead_id' => $lead->id]);
        
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
     * Processar mensagem regular
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
            
            // Verificar se já tem dados suficientes para matching
            if ($conversa->lead && $this->hasEnoughDataForMatching($conversa->lead)) {
                $this->performPropertyMatching($conversa->lead, $conversa);
            }
        }
        
        return [
            'success' => true,
            'message' => 'Mensagem processada',
            'ai_response' => $aiResponse['content'] ?? null
        ];
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
     * Fazer matching de imóveis
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
            
            Log::info('Matching realizado', [
                'lead_id' => $lead->id,
                'properties_found' => $properties->count()
            ]);
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
     * Criar lead
     */
    private function createLead($telefone, $profileName, $conversaId)
    {
        $lead = Lead::firstOrCreate(
            ['telefone' => $telefone],
            [
                'whatsapp_name' => $profileName,
                'status' => 'novo',
                'origem' => 'whatsapp',
                'primeira_interacao' => Carbon::now(),
                'ultima_interacao' => Carbon::now()
            ]
        );
        
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
