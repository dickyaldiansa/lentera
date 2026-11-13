$ErrorActionPreference = 'SilentlyContinue'
$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path

$pidFile = Join-Path $projectRoot 'storage\framework\lentera-tunnel.pid'
if (Test-Path $pidFile) {
    $savedPid = Get-Content $pidFile
    $savedProcess = Get-Process -Id $savedPid -ErrorAction SilentlyContinue
    if ($savedProcess -and $savedProcess.ProcessName -eq 'cloudflared') {
        Stop-Process -Id $savedPid -ErrorAction SilentlyContinue
    }
    Remove-Item -LiteralPath $pidFile -Force
}

Write-Host 'Cloudflare Tunnel LENTERA telah dihentikan. Apache/Laragon tetap berjalan.' -ForegroundColor Yellow
