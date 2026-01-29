---
name: requirements-validator
description: Validar se a implementação atende os requisitos e especificações do usuário.
license: Apache-2.0
---

Esta skill garante que a solução desenvolvida esteja perfeitamente alinhada com as solicitações do usuário e as especificações do projeto.

## Processo de Validação

### 1. Extração de Requisitos
- **Analisar Solicitação**: Quebre o prompt do usuário em requisitos atômicos.
- **Identificar Restrições**: Note quaisquer restrições técnicas ou de design (ex: "deve usar Tailwind", "deve ser mobile-friendly").
- **Identificar Casos Extremos**: O que acontece se a entrada estiver vazia? E se a API falhar?

### 2. Verificação de Implementação
Para cada requisito atômico, verifique:
- **Existência**: A funcionalidade está presente?
- **Correção**: Funciona como descrito?
- **Completude**: Todos os aspectos estão cobertos (UI, Lógica, Banco de Dados)?

### 3. Análise de Lacunas
- Identifique funcionalidades faltantes.
- Identifique desvios das instruções.
- Identifique casos extremos não tratados.

## Checklist de Validação

- [ ] **Requisitos Funcionais**: O código faz o que deveria fazer?
- [ ] **Requisitos Não-Funcionais**: É rápido o suficiente? Seguro? Acessível?
- [ ] **Restrições do Usuário**: Respeitamos a regra "sem bibliotecas"? Usamos o framework correto?
- [ ] **Tratamento de Erros**: Erros são exibidos graciosamente ao usuário?
- [ ] **Limpeza**: A solução implementada é limpa ou um "hack"?

## Formato de Saída

Forneça um relatório de validação:

1.  **Status**: ✅ APROVADO / ⚠️ ATENÇÃO / ❌ REPROVADO
2.  **Cobertura**: % de requisitos atendidos.
3.  **Itens Faltantes**: Lista com marcadores do que foi perdido.
4.  **Desvios**: Onde o código difere da especificação.
5.  **Sugestões**: Como fechar as lacunas.

