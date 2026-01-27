# Sistema de Avaliação de Fornecedores (SPE)

Sistema automatizado para consolidação de dados de RIR (Relatório de Inspeção de Recebimento) e geração de dashboard mensal de qualidade de fornecedores.

## 📋 Visão Geral

Este projeto substitui processos manuais (ou bots antigos) por uma aplicação web moderna que permite:
1.  **Importação Inteligente**: Upload de múltiplos arquivos RIR (Excel/CSV) com detecção automática de layout e correção de dados.
2.  **Dashboard Mensal**: Visualização imediata do desempenho dos fornecedores.
3.  **Relatório Matriz Pivot**: Exportação em Excel com formatação condicional (Green/Yellow/Red) pronta para reuniões gerenciais.

## 🚀 Funcionalidades Principais

-   **Importação RIR 2.0**:
    -   Layout fixo (ignora cabeçalhos visuais).
    -   Normalização de nomes (Aliases): `SIEMENS HEALTHCARE` -> `SIEMENS`.
    -   Detecção automática de safra (mês/ano) pelo nome do arquivo se a data interna falhar.
    -   Atualização inteligente: reimportar um arquivo atualiza os dados, não duplica.

-   **Dashboard & Exportação**:
    -   Matriz Pivot: Fornecedores (linhas) x Meses (colunas).
    -   Subdivisão por categorias: Ótimo (>90%), Bom (>70%), Regular (<70%).
    -   Visual limpo: Células zeradas ficam vazias.

## 🛠️ Stack Tecnológica

-   **Backend**: Laravel 11 (PHP 8.2+)
-   **Frontend**: Vue.js 3 + PrimeVue + TailwindCSS
-   **Banco de Dados**: SQLite (padrão) ou MySQL/PostgreSQL
-   **Infraestrutura**: Docker (via Laravel Sail)

## 📦 Como Rodar Localmente

### Pré-requisitos
-   Docker Desktop instalado e rodando.
-   Ou PHP 8.2+ e Composer instalados no host.

### Passos

1.  **Clone o repositório** e entre na pasta:
    ```bash
    cd "Automação Fornecedores"
    ```

2.  **Instale as dependências** (via Docker container):
    ```bash
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/var/www/html" \
        -w /var/www/html \
        laravelsail/php82-composer:latest \
        composer install --ignore-platform-reqs
    ```

3.  **Configure o ambiente**:
    ```bash
    cp .env.example .env
    # Ajuste DB_CONNECTION=sqlite se necessário
    ```

4.  **Inicie o servidor (Sail)**:
    ```bash
    ./vendor/bin/sail up -d
    ```

5.  **Gere a chave da aplicação e migre o banco**:
    ```bash
    ./vendor/bin/sail artisan key:generate
    ./vendor/bin/sail artisan migrate
    ./vendor/bin/sail artisan db:seed --class=FornecedorAliasSeeder
    ```

6.  **Instale e compile o Frontend**:
    ```bash
    ./vendor/bin/sail npm install
    ./vendor/bin/sail npm run dev
    ```

7.  **Acesse**: [http://localhost](http://localhost)

## 🧪 Testes e Qualidade

O projeto utiliza **Pest PHP** para testes automatizados. Para rodar a suíte completa:

```bash
# Rodar todos os testes
./vendor/bin/sail pest

# Rodar apenas testes de Importação RIR
./vendor/bin/sail pest tests/Unit/RirImportServiceTest.php

# Rodar com relatório de cobertura (coverage)
./vendor/bin/sail pest --coverage
```

## 📂 Estrutura de Arquivos Importante

-   `app/Services/RirImportService.php`: Coração da lógica de importação.
-   `app/Exports/AvaliacaoConsolidadaExport.php`: Lógica de geração da planilha Excel.
-   `database/seeders/FornecedorAliasSeeder.php`: Dicionário de Correção de Nomes (De/Para).
-   `resources/js/App.vue`: Interface do usuário (Dashboard).

## ⚠️ Manutenção do Dicionário de Aliases

Para adicionar novos apelidos de fornecedores (ex: corrigir "BECTON DICKINSON" para "BD"), edite o arquivo `database/seeders/FornecedorAliasSeeder.php` e rode:
```bash
./vendor/bin/sail artisan db:seed --class=FornecedorAliasSeeder
```
