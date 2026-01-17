#!/bin/bash

# run-tests.sh - Script helper para ejecutar la suite completa de tests
# Uso: ./run-tests.sh [opción]
#   sin parámetros = ejecutar ambos backends
#   backend = solo PHPUnit
#   frontend = solo Jest
#   coverage = con reportes de cobertura

set -e

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir títulos
print_title() {
    echo -e "\n${BLUE}════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}════════════════════════════════════════${NC}\n"
}

# Función para imprimir resultado
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2 APROBADO${NC}\n"
    else
        echo -e "${RED}❌ $2 FALLIDO${NC}\n"
        exit 1
    fi
}

# Opción
OPTION="${1:-all}"

case $OPTION in
    backend)
        print_title "Ejecutando Tests Backend (PHPUnit)"
        composer test
        print_result $? "Tests Backend"
        ;;

    frontend)
        print_title "Ejecutando Tests Frontend (Jest)"
        npm test
        print_result $? "Tests Frontend"
        ;;

    coverage)
        print_title "Ejecutando Tests con Cobertura"

        echo -e "${YELLOW}Backend...${NC}"
        ./vendor/bin/phpunit --coverage-text || true

        echo -e "${YELLOW}Frontend...${NC}"
        npm run test:coverage || true

        echo -e "${GREEN}✅ Reportes de cobertura generados${NC}"
        echo -e "  Backend: tests/coverage/html/index.html"
        echo -e "  Frontend: tests/coverage/index.html\n"
        ;;

    all|*)
        print_title "Ejecutando Suite Completa de Tests"

        echo -e "${YELLOW}1/2 Backend (PHPUnit)...${NC}"
        if composer test; then
            print_result 0 "Tests Backend"
        else
            print_result 1 "Tests Backend"
        fi

        echo -e "${YELLOW}2/2 Frontend (Jest)...${NC}"
        if npm test; then
            print_result 0 "Tests Frontend"
        else
            print_result 1 "Tests Frontend"
        fi

        echo -e "${GREEN}════════════════════════════════════════${NC}"
        echo -e "${GREEN}  ✅ TODOS LOS TESTS APROBADOS${NC}"
        echo -e "${GREEN}════════════════════════════════════════${NC}\n"
        ;;
esac

echo -e "${GREEN}Listo para hacer commit/push 🚀${NC}\n"
