@echo off
REM run-tests.bat - Script para ejecutar la suite completa de tests en Windows
REM Uso: run-tests.bat [opcion]
REM   sin parametros = ejecutar ambos backends
REM   backend = solo PHPUnit
REM   frontend = solo Jest
REM   coverage = con reportes de cobertura

setlocal enabledelayedexpansion

set OPTION=%1
if "%OPTION%"=="" set OPTION=all

echo.
echo ════════════════════════════════════════
echo   SCAD - Suite de Tests
echo ════════════════════════════════════════
echo.

if "%OPTION%"=="backend" (
    call :run_backend
    exit /b !ERRORLEVEL!
) else if "%OPTION%"=="frontend" (
    call :run_frontend
    exit /b !ERRORLEVEL!
) else if "%OPTION%"=="coverage" (
    call :run_coverage
    exit /b !ERRORLEVEL!
) else (
    call :run_all
    exit /b !ERRORLEVEL!
)

:run_backend
echo.
echo [1/1] Ejecutando Tests Backend ^(PHPUnit^)...
echo.
call composer test
if !ERRORLEVEL! neq 0 (
    echo.
    echo ❌ Tests Backend FALLARON
    exit /b 1
)
echo.
echo ✅ Tests Backend APROBADOS
exit /b 0

:run_frontend
echo.
echo [1/1] Ejecutando Tests Frontend ^(Jest^)...
echo.
call npm test
if !ERRORLEVEL! neq 0 (
    echo.
    echo ❌ Tests Frontend FALLARON
    exit /b 1
)
echo.
echo ✅ Tests Frontend APROBADOS
exit /b 0

:run_coverage
echo.
echo [1/2] Ejecutando Tests Backend con cobertura...
echo.
call .\vendor\bin\phpunit.bat --coverage-text
echo.
echo [2/2] Ejecutando Tests Frontend con cobertura...
echo.
call npm run test:coverage
echo.
echo ✅ Reportes de cobertura generados
echo   Backend: tests/coverage/html/index.html
echo   Frontend: tests/coverage/index.html
echo.
exit /b 0

:run_all
echo.
echo [1/2] Ejecutando Tests Backend ^(PHPUnit^)...
echo.
call composer test
if !ERRORLEVEL! neq 0 (
    echo.
    echo ❌ Tests Backend FALLARON
    exit /b 1
)
echo.
echo ✅ Tests Backend APROBADOS
echo.

echo.
echo [2/2] Ejecutando Tests Frontend ^(Jest^)...
echo.
call npm test
if !ERRORLEVEL! neq 0 (
    echo.
    echo ❌ Tests Frontend FALLARON
    exit /b 1
)
echo.
echo ✅ Tests Frontend APROBADOS
echo.

echo.
echo ════════════════════════════════════════
echo   ✅ TODOS LOS TESTS APROBADOS
echo ════════════════════════════════════════
echo.
echo Listo para hacer commit/push 🚀
echo.
exit /b 0
