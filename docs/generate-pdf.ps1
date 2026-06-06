# Génère DOCUMENT_TECHNIQUE.pdf depuis le Markdown (Edge ou Chrome headless)
$ErrorActionPreference = 'Stop'
$docsDir = $PSScriptRoot
$mdPath = Join-Path $docsDir 'DOCUMENT_TECHNIQUE.md'
$htmlPath = Join-Path $docsDir 'DOCUMENT_TECHNIQUE.html'
$pdfPath = Join-Path $docsDir 'DOCUMENT_TECHNIQUE.pdf'

if (-not (Test-Path $mdPath)) {
    Write-Error "Fichier introuvable: $mdPath"
}

$md = Get-Content -Path $mdPath -Raw -Encoding UTF8
$b64 = [Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($md))

$html = @"
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>MAMI.GA — Document technique</title>
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; max-width: 900px; margin: 2rem auto; padding: 0 1.5rem; color: #1e293b; line-height: 1.5; font-size: 11pt; }
    h1 { font-size: 22pt; border-bottom: 2px solid #f8b803; padding-bottom: 0.3rem; }
    h2 { font-size: 16pt; margin-top: 1.5rem; color: #0f172a; }
    h3 { font-size: 13pt; }
    pre, code { font-family: Consolas, monospace; background: #f1f5f9; font-size: 9pt; }
    pre { padding: 0.75rem; overflow-x: auto; border-radius: 6px; white-space: pre-wrap; word-wrap: break-word; }
    table { border-collapse: collapse; width: 100%; margin: 1rem 0; font-size: 10pt; }
    th, td { border: 1px solid #cbd5e1; padding: 0.4rem 0.6rem; text-align: left; }
    th { background: #f8fafc; }
    hr { border: none; border-top: 1px solid #e2e8f0; margin: 2rem 0; }
    @media print { body { margin: 1cm; } }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>
<body>
  <div id="root"></div>
  <script>
    const md = atob('$b64');
    document.getElementById('root').innerHTML = marked.parse(md);
  </script>
</body>
</html>
"@

[System.IO.File]::WriteAllText($htmlPath, $html, [System.Text.UTF8Encoding]::new($false))

$browsers = @(
    "${env:ProgramFiles}\Microsoft\Edge\Application\msedge.exe",
    "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe",
    "${env:ProgramFiles}\Google\Chrome\Application\chrome.exe"
)
$browser = $browsers | Where-Object { Test-Path $_ } | Select-Object -First 1

if (-not $browser) {
    Write-Error "Installez Microsoft Edge ou Google Chrome pour générer le PDF."
}

if (Test-Path $pdfPath) { Remove-Item $pdfPath -Force }

$uri = ([System.Uri]::new($htmlPath)).AbsoluteUri
& $browser --headless=new --disable-gpu --no-pdf-header-footer --virtual-time-budget=15000 --print-to-pdf="$pdfPath" $uri 2>$null

Start-Sleep -Seconds 5

if (-not (Test-Path $pdfPath)) {
    Write-Error "Échec génération PDF. Ouvrez $htmlPath → Ctrl+P → Enregistrer en PDF."
}

Write-Host "PDF créé: $pdfPath"
