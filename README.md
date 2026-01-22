## Motor de Avaliação de Fornecedores (SPE)

Aplicação local para consolidar dados de múltiplos arquivos RIR e gerar o arquivo "AVALIAÇÃO DE FORNECEDORES.xlsx" com histórico anual e dashboard visual.

### Stack
- Laravel 11 (API e serviços)
- SQLite (persistência local)
- Vue 3 + PrimeVue + Tailwind (dashboard em Material Design 3)
- Docker com Laravel Sail

### Configuração local
1. Copie o arquivo .env.example para .env.
2. Defina APP_KEY e mantenha DB_CONNECTION=sqlite e DB_DATABASE=database/database.sqlite.
3. Crie o arquivo database/database.sqlite.
4. Rode as migrations.
5. Instale as dependências do frontend e execute o Vite.

### Comandos úteis
- php artisan migrate
- php artisan sistema:limpar
- npm run dev

### Endpoints
- POST /api/importar-rir (upload múltiplo)
- GET /api/dashboard-mensal?mes=YYYY-MM
- GET /api/heatmap-anual?ano=YYYY
- GET /api/exportar-avaliacao?ano=YYYY
