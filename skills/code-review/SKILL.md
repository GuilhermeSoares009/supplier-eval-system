---
name: code-review
description: Analisar código para boas práticas, segurança, performance e manutenibilidade. Especializada em aplicações Laravel e Vue.js.
license: Apache-2.0
---

Esta skill guia a revisão de código para garantir que atenda altos padrões de qualidade, segurança e manutenibilidade, especificamente para projetos **Laravel** e **Vue.js**.

## Checklist para Revisão

### 1. Laravel (Backend)
- **Arquitetura**:
  - **Controllers**: Devem ser magros. Mova lógica de negócio para Services ou Actions.
  - **Models**: Devem lidar com relacionamentos e scopes, mas não lógica de negócio complexa.
  - **D.R.Y.**: Garanta que o código não seja repetido desnecessariamente.
- **Performance**:
  - **Problemas N+1**: Verifique eager loading (`with()`) em queries Eloquent.
  - **Database**: Queries eficientes e sugestões de indexação.
- **Segurança**:
  - **Validação**: Garanta que todas as entradas sejam validadas (uso de `FormRequest` preferido).
  - **Autorização**: Verifique uso de Policies ou Gates.
  - **Sanitização**: Garanta que não há vulnerabilidades de SQL injection.

### 2. Vue.js (Frontend)
- **Composition API**: Garanta uso consistente de `<script setup>` e Composition API.
- **Reatividade**: Verifique uso correto de `ref` vs `reactive` e perda de reatividade (ex: desestruturação de props).
- **Design de Componentes**:
  - Componentes devem ser pequenos, focados e reutilizáveis.
  - Props devem ser tipadas.
  - Events devem ser definidos.
- **Performance**:
  - Lazy loading de rotas/componentes quando apropriado.
  - Computed properties eficientes.

### 3. Código Limpo Geral
- **Nomenclatura**: Variáveis e funções devem ter nomes descritivos e significativos (snake_case para PHP/DB, camelCase para JS).
- **Comentários**: Código deve ser auto-documentado; comentários devem explicar *por quê*, não *o quê*.
- **Formatação**: Aderência aos padrões PSR-12 (PHP) e Prettier/Eslint (JS).

## Formato de Saída

Ao realizar uma revisão, estruture o feedback da seguinte forma:

1.  **Resumo**: Visão geral breve da qualidade do código.
2.  **Problemas Críticos** (se houver): Bugs, falhas de segurança ou bloqueadores de performance.
3.  **Melhorias**: Sugestões para refatoração, limpeza ou otimização.
4.  **Elogios**: O que foi feito bem.

## Exemplo de Uso

"Revise este método do controller para problemas de performance."
"Verifique este componente Vue para uso correto de reatividade."

