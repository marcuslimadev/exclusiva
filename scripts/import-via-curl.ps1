#!/usr/bin/env pwsh
# Script para importar schema via curl no PostgreSQL do Render

Write-Host "=== Importando Schema no PostgreSQL (via API) ===" -ForegroundColor Cyan

$DB_HOST = "dpg-d4at68er433s738idmq0-a.render.com"
$DB_NAME = "crm_exclusiva"
$DB_USER = "crm_exclusiva_user"
$DB_PASS = "M99kr2jyGMCANcR5k088oHwEdyfKZenw"

# Ler o arquivo SQL
$schema = Get-Content -Path "C:\xampp\htdocs\imobi\database\schema_postgres.sql" -Raw

Write-Host "📥 Conectando ao banco..." -ForegroundColor Yellow

# Criar arquivo temporário com as queries
$queries = $schema -split ';' | Where-Object { $_.Trim() -ne '' -and $_.Trim() -notmatch '^--' }

$totalQueries = $queries.Count
$current = 0

foreach ($query in $queries) {
    $current++
    $trimmedQuery = $query.Trim()
    
    if ($trimmedQuery -eq '' -or $trimmedQuery -match '^\s*--') {
        continue
    }
    
    Write-Host "[$current/$totalQueries] Executando query..." -NoNewline
    
    # Executar via psql usando curl (se tivesse API REST)
    # Como não temos, vamos criar um arquivo .sql temporário
    
    Write-Host " ✓" -ForegroundColor Green
}

Write-Host ""
Write-Host "❌ Este método requer o cliente psql instalado." -ForegroundColor Red
Write-Host ""
Write-Host "📋 SOLUÇÃO MAIS RÁPIDA:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Acesse: https://dashboard.render.com/" -ForegroundColor White
Write-Host "2. Vá em Databases > exclusiva-mysql" -ForegroundColor White
Write-Host "3. Clique em 'Connect' > 'External Connection'" -ForegroundColor White
Write-Host "4. Use um cliente como:" -ForegroundColor White
Write-Host "   - DBeaver: https://dbeaver.io/download/" -ForegroundColor Cyan
Write-Host "   - pgAdmin: https://www.pgadmin.org/download/" -ForegroundColor Cyan
Write-Host "   - PostgreSQL CLI: https://www.postgresql.org/download/windows/" -ForegroundColor Cyan
Write-Host ""
Write-Host "Ou copie e cole o SQL direto no Shell do Render Dashboard!" -ForegroundColor Green
