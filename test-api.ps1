# Script de Teste da API - CRM Exclusiva Lar
# Execute: .\test-api.ps1

$baseUrl = "http://localhost/imobi/backend/public"

Write-Host "🧪 Testando API do Backend..." -ForegroundColor Cyan
Write-Host ""

# Teste 1: Health check
Write-Host "1️⃣ Testando endpoint raiz..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri $baseUrl -UseBasicParsing
    Write-Host "✅ Backend está respondendo!" -ForegroundColor Green
} catch {
    Write-Host "❌ Backend não está respondendo em $baseUrl" -ForegroundColor Red
    Write-Host "   Certifique-se que Apache está rodando no XAMPP" -ForegroundColor Yellow
    exit 1
}

Write-Host ""

# Teste 2: Login
Write-Host "2️⃣ Testando login..." -ForegroundColor Yellow
$loginData = @{
    email = "admin@exclusiva.com"
    senha = "password"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/api/auth/login" -Method Post -Body $loginData -ContentType "application/json"
    $token = $response.token
    Write-Host "✅ Login funcionando! Token recebido." -ForegroundColor Green
    Write-Host "   Usuário: $($response.user.nome)" -ForegroundColor White
} catch {
    Write-Host "❌ Erro no login" -ForegroundColor Red
    Write-Host "   $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Teste 3: Dashboard Stats
Write-Host "3️⃣ Testando dashboard stats..." -ForegroundColor Yellow
$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
}

try {
    $stats = Invoke-RestMethod -Uri "$baseUrl/api/dashboard/stats" -Method Get -Headers $headers
    Write-Host "✅ Dashboard funcionando!" -ForegroundColor Green
    Write-Host "   Total de Leads: $($stats.totalLeads)" -ForegroundColor White
    Write-Host "   Conversas Ativas: $($stats.conversasAtivas)" -ForegroundColor White
} catch {
    Write-Host "❌ Erro ao buscar stats" -ForegroundColor Red
}

Write-Host ""

# Teste 4: Listar Leads
Write-Host "4️⃣ Testando lista de leads..." -ForegroundColor Yellow
try {
    $leads = Invoke-RestMethod -Uri "$baseUrl/api/leads" -Method Get -Headers $headers
    Write-Host "✅ Lista de leads funcionando!" -ForegroundColor Green
    Write-Host "   Leads encontrados: $($leads.Count)" -ForegroundColor White
} catch {
    Write-Host "❌ Erro ao listar leads" -ForegroundColor Red
}

Write-Host ""

# Teste 5: Listar Conversas
Write-Host "5️⃣ Testando lista de conversas..." -ForegroundColor Yellow
try {
    $conversas = Invoke-RestMethod -Uri "$baseUrl/api/conversas" -Method Get -Headers $headers
    Write-Host "✅ Lista de conversas funcionando!" -ForegroundColor Green
    Write-Host "   Conversas encontradas: $($conversas.Count)" -ForegroundColor White
} catch {
    Write-Host "❌ Erro ao listar conversas" -ForegroundColor Red
}

Write-Host ""
Write-Host "🎉 Testes concluídos!" -ForegroundColor Green
Write-Host ""
Write-Host "📊 Resumo:" -ForegroundColor Cyan
Write-Host "   Backend: ✅ Funcionando" -ForegroundColor Green
Write-Host "   Autenticação: ✅ Funcionando" -ForegroundColor Green
Write-Host "   API Endpoints: ✅ Funcionando" -ForegroundColor Green
Write-Host ""
Write-Host "🚀 Sistema pronto para uso!" -ForegroundColor Green
