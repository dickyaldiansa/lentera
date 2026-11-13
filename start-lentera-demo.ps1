$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$cloudflared = 'C:\Program Files (x86)\cloudflared\cloudflared.exe'
$configPath = Join-Path $projectRoot 'cloudflared.yml'
$tunnelId = 'f6171b0d-a47d-4ab0-aca7-e42f88a03f51'

if (-not (Test-Path $cloudflared)) {
    throw 'cloudflared tidak ditemukan.'
}

function Test-LenteraOrigin {
    try {
        $response = Invoke-WebRequest -Uri 'http://127.0.0.1/' -Headers @{ Host = 'lentera.test' } -UseBasicParsing -TimeoutSec 5
        return $response.StatusCode -ge 200 -and $response.StatusCode -lt 500
    } catch {
        return $false
    }
}

function Test-LenteraTunnelConnection {
    $previousPreference = $ErrorActionPreference
    try {
        $ErrorActionPreference = 'SilentlyContinue'
        $tunnelInfo = & $cloudflared tunnel --config $configPath info $tunnelId 2>&1 | Out-String
        return $tunnelInfo -match 'CONNECTOR ID' -and $tunnelInfo -notmatch 'does not have any active connection'
    } finally {
        $ErrorActionPreference = $previousPreference
    }
}

$originReady = Test-LenteraOrigin
if (-not $originReady) {
    for ($attempt = 1; $attempt -le 30; $attempt++) {
        Start-Sleep -Seconds 2
        $originReady = Test-LenteraOrigin
        if ($originReady) {
            break
        }
    }
}
if (-not $originReady) {
    throw 'Apache/Laragon belum aktif atau virtual host lentera.test tidak dapat diakses setelah 60 detik.'
}

$pidFile = Join-Path $projectRoot 'storage\framework\lentera-tunnel.pid'
$tunnelRunning = $false
if (Test-Path $pidFile) {
    $savedPid = Get-Content $pidFile -ErrorAction SilentlyContinue
    $savedProcess = Get-Process -Id $savedPid -ErrorAction SilentlyContinue
    $tunnelRunning = $savedProcess -and $savedProcess.ProcessName -eq 'cloudflared'
}

# A process can survive a network interruption without being connected to
# Cloudflare. Do not treat its PID alone as a healthy public tunnel.
if ($tunnelRunning -and -not (Test-LenteraTunnelConnection)) {
    Stop-Process -Id $savedProcess.Id -ErrorAction SilentlyContinue
    $tunnelRunning = $false
}
if (-not $tunnelRunning) {
    $tunnelProcess = Start-Process -FilePath $cloudflared -ArgumentList @('tunnel','--config',$configPath,'run',$tunnelId) -WorkingDirectory $projectRoot -WindowStyle Hidden -PassThru
    Set-Content -LiteralPath $pidFile -Value $tunnelProcess.Id
}

$ready = $false
for ($attempt = 1; $attempt -le 12; $attempt++) {
    Start-Sleep -Seconds 1
    $originReady = Test-LenteraOrigin
    $tunnelReady = Test-LenteraTunnelConnection
    if ($originReady -and $tunnelReady) {
        $ready = $true
        break
    }
}

if (-not $ready) {
    throw 'LENTERA belum siap: Apache/Laragon atau koneksi Cloudflare Tunnel gagal aktif. Jalankan kembali skrip dan periksa koneksi internet.'
}

Write-Host 'LENTERA aktif dan tunnel terhubung di https://lentera.appcatalog.id' -ForegroundColor Green
