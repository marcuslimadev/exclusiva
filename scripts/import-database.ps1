#!/usr/bin/env pwsh
# Script para importar schema PostgreSQL no Render.com

Write-Host "=== Importando Schema PostgreSQL no Render ===" -ForegroundColor Cyan
Write-Host ""

$DB_URL = "postgresql://crm_exclusiva_user:M99kr2jyGMCANcR5k088oHwEdyfKZenw@dpg-d4at68er433s738idmq0-a.render.com/crm_exclusiva"

# Verificar se psql está instalado
if (-not (Get-Command psql -ErrorAction SilentlyContinue)) {
    Write-Host "❌ psql não encontrado!" -ForegroundColor Red
    Write-Host "Instale o PostgreSQL client:" -ForegroundColor Yellow
    Write-Host "  - Windows: https://www.postgresql.org/download/windows/" -ForegroundColor Yellow
    Write-Host "  - Ou use o Render Dashboard para executar SQL" -ForegroundColor Yellow
    exit 1
}

Write-Host "📦 Conectando ao banco PostgreSQL..." -ForegroundColor Yellow

# Importar schema
$schema_file = Join-Path $PSScriptRoot "..\database\schema_postgres.sql"

if (-not (Test-Path $schema_file)) {
    Write-Host "❌ Arquivo schema_postgres.sql não encontrado!" -ForegroundColor Red
    exit 1
}

Write-Host "📥 Importando schema..." -ForegroundColor Yellow
psql $DB_URL -f $schema_file

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "✅ Schema importado com sucesso!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Verificando tabelas criadas..." -ForegroundColor Yellow
    psql $DB_URL -c "\dt"
} else {
    Write-Host ""
    Write-Host "❌ Erro ao importar schema!" -ForegroundColor Red
    Write-Host "Você pode importar manualmente através do Render Dashboard:" -ForegroundColor Yellow
    Write-Host "  1. Acesse: https://dashboard.render.com/" -ForegroundColor Yellow
    Write-Host "  2. Vá em exclusiva-mysql" -ForegroundColor Yellow
    Write-Host "  3. Clique em 'Connect' > 'PSQL Command'" -ForegroundColor Yellow
    Write-Host "  4. Cole o conteúdo de database/schema_postgres.sql" -ForegroundColor Yellow
}
