@echo off
title Enviar site para GitHub
color 0A

echo ==========================================
echo       ENVIANDO SITE PARA O GITHUB
echo ==========================================
echo.

cd /d "%~dp0"

echo [1/5] Inicializando Git...
git init

echo.
echo [2/5] Adicionando todos os arquivos...
git add .

echo.
echo [3/5] Criando commit...
git commit -m "Atualizacao completa do site"

echo.
echo [4/5] Configurando branch main...
git branch -M main

echo.
echo ==========================================
echo [5/5] CONECTANDO AO GITHUB
echo ==========================================
echo.
echo Digite a URL do seu repositorio GitHub:
set /p REPO=

git remote remove origin 2>nul
git remote add origin %REPO%

echo.
echo Enviando arquivos...
git push -u origin main

echo.
echo ==========================================
echo              CONCLUIDO
echo ==========================================
pause