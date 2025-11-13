# Script de Inicialização - CRM Exclusiva Lar
# Execute: .\start.ps1

Write-Host "🚀 Iniciando CRM Exclusiva Lar..." -ForegroundColor Cyan
Write-Host ""

# Verifica se está na pasta correta
if (!(Test-Path "frontend\package.json")) {
    Write-Host "❌ Erro: Execute este script na pasta C:\xampp\htdocs\imobi" -ForegroundColor Red
    exit 1
}

# Verifica se XAMPP está rodando
Write-Host "🔍 Verificando XAMPP..." -ForegroundColor Yellow
$apacheRunning = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
$mysqlRunning = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue

if (!$apacheRunning) {
    Write-Host "⚠️  Apache não está rodando. Inicie o XAMPP Control Panel!" -ForegroundColor Red
    Start-Process "C:\xampp\xampp-control.exe"
    Write-Host "Aguarde o Apache e MySQL iniciarem..." -ForegroundColor Yellow
    pause
}

if (!$mysqlRunning) {
    Write-Host "⚠️  MySQL não está rodando. Inicie o XAMPP Control Panel!" -ForegroundColor Red
    pause
}

Write-Host "✅ Backend (Lumen) rodando em: http://localhost/imobi/backend/public" -ForegroundColor Green
Write-Host ""

# Inicia Frontend
Write-Host "🎨 Iniciando Frontend Vue.js..." -ForegroundColor Yellow
Set-Location frontend

# Verifica se node_modules existe
if (!(Test-Path "node_modules")) {
    Write-Host "📦 Instalando dependências npm..." -ForegroundColor Yellow
    npm install
}

Write-Host ""
Write-Host "✅ Frontend iniciando em: http://localhost:5173" -ForegroundColor Green
Write-Host ""
Write-Host "📱 Credenciais de Login:" -ForegroundColor Cyan
Write-Host "   Email: admin@exclusiva.com" -ForegroundColor White
Write-Host "   Senha: password" -ForegroundColor White
Write-Host ""
Write-Host "🔥 Sistema pronto! Abrindo navegador..." -ForegroundColor Green
Write-Host ""

# Aguarda 2 segundos e abre o navegador
Start-Sleep -Seconds 2
Start-Process "http://localhost:5173"

# Inicia o servidor dev
npm run dev
