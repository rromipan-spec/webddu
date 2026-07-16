@echo off
setlocal

if "%~1"=="" (
    echo Cara pakai: deploy.cmd "Jelaskan perubahan"
    exit /b 1
)

powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%~dp0deploy.ps1" -Message "%~1"
exit /b %ERRORLEVEL%
