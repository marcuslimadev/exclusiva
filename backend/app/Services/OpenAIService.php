<?php

namespace App\Services;

/**
 * Serviço de integração com OpenAI
 * APROVEITADO de: application/services/OpenAIService.php
 * 
 * Funcionalidades:
 * - Transcrição de áudio (Whisper API)
 * - Processamento de texto (GPT)
 * - Extração de dados estruturados
 */
class OpenAIService
{
    private $apiKey;
    private $model;
    
    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
        $this->model = env('OPENAI_MODEL', 'gpt-4o-mini');
    }
    
    /**
     * Transcrever áudio do WhatsApp usando Whisper API
     * 
     * @param string $audioPath Caminho do arquivo de áudio
     * @return array Resultado da transcrição
     */
    public function transcribeAudio($audioPath)
    {
        $url = 'https://api.openai.com/v1/audio/transcriptions';
        
        $file = new \CURLFile($audioPath, 'audio/ogg', 'audio.ogg');
        
        $postFields = [
            'file' => $file,
            'model' => 'whisper-1',
            'language' => 'pt'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return [
                'success' => true,
                'text' => $data['text'] ?? ''
            ];
        }
        
        \Log::error('OpenAI Transcription Error', [
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error
        ]);
        
        return [
            'success' => false,
            'error' => 'Transcription failed',
            'details' => $response
        ];
    }
    
    /**
     * Extrair dados estruturados do lead usando GPT
     * 
     * @param string $conversationHistory Histórico da conversa
     * @return array Dados extraídos
     */
    public function extractLeadData($conversationHistory)
    {
        $systemPrompt = "Você é um assistente especializado em extrair informações estruturadas de conversas imobiliárias.
        
Analise a conversa e extraia os seguintes dados:
- budget_min: orçamento mínimo (número)
- budget_max: orçamento máximo (número)
- localizacao: bairro/cidade desejada
- quartos: número de quartos
- suites: número de suítes
- garagem: número de vagas
- caracteristicas_desejadas: lista de características mencionadas

Retorne APENAS um JSON válido sem explicações adicionais.";

        $userPrompt = "Conversa:\n\n" . $conversationHistory . "\n\nExtrai os dados no formato JSON.";
        
        $result = $this->chatCompletion($systemPrompt, $userPrompt);
        
        if ($result['success']) {
            try {
                $extracted = json_decode($result['content'], true);
                return [
                    'success' => true,
                    'data' => $extracted
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => 'Failed to parse JSON response'
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Processar mensagem e gerar resposta contextual
     * 
     * @param string $message Mensagem do usuário
     * @param string $context Contexto da conversa
     * @return array Resposta gerada
     */
    public function processMessage($message, $context = '')
    {
        $systemPrompt = "Você é um atendente virtual da Exclusiva Lar Imóveis, uma imobiliária especializada.
        
Seu objetivo é:
- Ser cordial, profissional e prestativo
- Fazer perguntas para entender as necessidades do cliente
- Coletar informações sobre: orçamento, localização preferida, quantidade de quartos, características desejadas
- Manter o tom conversacional e amigável

IMPORTANTE: Suas respostas devem ser curtas e diretas (máximo 3 linhas).";

        $userPrompt = ($context ? "Contexto anterior:\n$context\n\n" : "") . "Cliente: $message\n\nResponda:";
        
        return $this->chatCompletion($systemPrompt, $userPrompt);
    }
    
    /**
     * Fazer chamada à API de Chat Completion
     */
    private function chatCompletion($systemPrompt, $userPrompt)
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 500
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            $content = $data['choices'][0]['message']['content'] ?? '';
            
            return [
                'success' => true,
                'content' => trim($content)
            ];
        }
        
        \Log::error('OpenAI Chat Completion Error', [
            'http_code' => $httpCode,
            'response' => $response
        ]);
        
        return [
            'success' => false,
            'error' => 'Chat completion failed'
        ];
    }
}
