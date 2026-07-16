param(
    [Parameter(Mandatory = $true, Position = 0)]
    [ValidateNotNullOrEmpty()]
    [string] $Message
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location -LiteralPath $projectRoot

function Assert-CommandSucceeded {
    param([string] $Step)
    if ($LASTEXITCODE -ne 0) {
        throw "$Step gagal dengan exit code $LASTEXITCODE."
    }
}

Write-Host '[1/6] Memeriksa repository Git...' -ForegroundColor Cyan
if (-not (Test-Path -LiteralPath '.git')) {
    throw 'Folder ini belum menjadi repository Git.'
}

$ignoredEnv = git check-ignore 'backend/config/.env'
if ($LASTEXITCODE -ne 0 -or $ignoredEnv -ne 'backend/config/.env') {
    throw 'Deployment dihentikan: backend/config/.env belum dilindungi .gitignore.'
}

$trackedEnv = @(git ls-files -- 'backend/config/.env')
Assert-CommandSucceeded 'Pemeriksaan status .env di Git'
if ($trackedEnv.Count -gt 0) {
    throw 'Deployment dihentikan: backend/config/.env sudah pernah dilacak Git. Hapus dari index Git terlebih dahulu.'
}

Write-Host '[2/6] Memeriksa sintaks JavaScript...' -ForegroundColor Cyan
$javascriptFiles = Get-ChildItem -LiteralPath 'frontend' -Recurse -File -Filter '*.js'
foreach ($file in $javascriptFiles) {
    node --check $file.FullName
    Assert-CommandSucceeded "Pemeriksaan JavaScript $($file.Name)"
}

Write-Host '[3/6] Menyiapkan perubahan...' -ForegroundColor Cyan
git add --all
Assert-CommandSucceeded 'git add'

$stagedFiles = @(git diff --cached --name-only)
Assert-CommandSucceeded 'Pemeriksaan perubahan Git'
if ($stagedFiles.Count -eq 0) {
    Write-Host 'Tidak ada perubahan untuk di-deploy.' -ForegroundColor Yellow
    exit 0
}

$blockedFiles = @($stagedFiles | Where-Object {
    $_ -match '(^|/)\.env$' -or
    $_ -match '\.(pem|key|p12|pfx)$' -or
    $_ -match '(^|/)(id_rsa|id_ed25519)$'
})
if ($blockedFiles.Count -gt 0) {
    throw "Deployment dihentikan karena ada file rahasia: $($blockedFiles -join ', ')"
}

git diff --cached --check
Assert-CommandSucceeded 'Pemeriksaan whitespace Git'

Write-Host '[4/6] Membuat commit...' -ForegroundColor Cyan
git commit -m $Message
Assert-CommandSucceeded 'git commit'

Write-Host '[5/6] Mengirim ke GitHub...' -ForegroundColor Cyan
git push origin main
Assert-CommandSucceeded 'git push'

Write-Host '[6/6] Selesai.' -ForegroundColor Green
Write-Host 'GitHub Actions sedang meneruskan perubahan ke Hostinger.' -ForegroundColor Green
Write-Host 'Pantau: https://github.com/rromipan-spec/webddu/actions' -ForegroundColor Green
