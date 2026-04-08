@echo off
echo ========================================
echo  Controle de Estoque Obra Patamares
echo ========================================
echo.

:: Mudar para a pasta do script (onde o bat esta)
cd /d "%~dp0"

:: Verificar Python
python --version >nul 2>&1
if errorlevel 1 (
    echo ERRO: Python nao encontrado!
    echo Instale em: https://www.python.org/downloads/
    pause
    exit
)

:: Instalar dependencias
echo Instalando dependencias...
python -m pip install flask flask-sqlalchemy openpyxl -q

echo.
echo Iniciando servidor...
echo.
echo  Acesse no navegador: http://localhost:5000
echo.
echo  Pressione CTRL+C para parar o servidor
echo.
python app.py
pause
