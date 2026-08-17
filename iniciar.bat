@echo off
echo ========================================
echo  Logi-Prime - Gestao de Estoque
echo ========================================
echo.

:: Mudar para a pasta do script
cd /d "%~dp0"

:: Verificar Python
python --version >nul 2>&1
if errorlevel 1 (
    echo ERRO: Python nao encontrado!
    echo Instale em: https://www.python.org/downloads/
    pause
    exit
)

:: Criar pasta instance se nao existir
if not exist "instance" (
    echo Criando pasta do banco de dados...
    mkdir instance
)

:: Instalar dependencias
echo Instalando dependencias...
python -m pip install flask flask-sqlalchemy openpyxl werkzeug -q

echo.
echo Iniciando servidor...
echo.
echo  Acesse no navegador: http://localhost:5000
echo  Login: admin / admin123
echo.
echo  Pressione CTRL+C para parar o servidor
echo.

:: Definir variavel para banco local
set DATABASE_URL=
python app.py
pause
