---
name: frontend-testing
description: Validar funcionalidades do frontend, UX (User Experience) e CX (Customer Experience). Verificar se nada está quebrado, se as ações funcionam corretamente e se a experiência do usuário está adequada.
license: Apache-2.0
---

Esta skill guia a validação completa do frontend, focando em funcionalidade, usabilidade e experiência do cliente.

## Processo de Teste

### 1. Testes Funcionais
Verificar se todas as funcionalidades estão operando corretamente:

#### Interações Básicas
- [ ] **Cliques**: Todos os botões respondem ao clique?
- [ ] **Formulários**: Inputs aceitam dados? Validações funcionam?
- [ ] **Navegação**: Links e rotas funcionam? Não há erros 404?
- [ ] **Modais/Dialogs**: Abrem e fecham corretamente?
- [ ] **Dropdowns/Selects**: Exibem opções? Seleção funciona?

#### Fluxos Completos
- [ ] **Upload de Arquivos**: Aceita formatos corretos? Mostra progresso?
- [ ] **Submissão de Dados**: Dados são enviados? Feedback é exibido?
- [ ] **Filtros/Busca**: Resultados são filtrados corretamente?
- [ ] **Paginação**: Navegação entre páginas funciona?
- [ ] **Ordenação**: Colunas são ordenadas corretamente?

#### Estados da Aplicação
- [ ] **Loading**: Spinners/skeletons aparecem durante carregamento?
- [ ] **Empty State**: Mensagem clara quando não há dados?
- [ ] **Error State**: Erros são exibidos de forma compreensível?
- [ ] **Success State**: Confirmações de sucesso são visíveis?

### 2. Testes de UX (User Experience)

#### Usabilidade
- [ ] **Clareza**: Ações são óbvias? Usuário sabe o que fazer?
- [ ] **Feedback Visual**: Hover states, active states, disabled states estão claros?
- [ ] **Consistência**: Padrões visuais são consistentes em toda a aplicação?
- [ ] **Acessibilidade**: 
  - Contraste de cores adequado (WCAG AA)?
  - Navegação por teclado funciona?
  - Labels descritivos em inputs?

#### Performance Percebida
- [ ] **Responsividade**: Interface responde rapidamente?
- [ ] **Transições**: Animações são suaves (não travadas)?
- [ ] **Carregamento**: Dados carregam em tempo aceitável (\<3s)?

#### Layout e Design
- [ ] **Responsividade**: Funciona em mobile, tablet e desktop?
- [ ] **Alinhamento**: Elementos estão alinhados corretamente?
- [ ] **Espaçamento**: Padding/margin adequados?
- [ ] **Tipografia**: Textos são legíveis? Hierarquia clara?

### 3. Testes de CX (Customer Experience)

#### Jornada do Usuário
- [ ] **Onboarding**: Primeira experiência é clara?
- [ ] **Fluxo Principal**: Tarefa principal é fácil de completar?
- [ ] **Erros**: Mensagens de erro são úteis (não técnicas)?
- [ ] **Ajuda**: Tooltips/hints estão disponíveis onde necessário?

#### Satisfação
- [ ] **Frustração**: Há pontos de fricção desnecessários?
- [ ] **Eficiência**: Usuário consegue completar tarefas rapidamente?
- [ ] **Confiança**: Interface transmite profissionalismo?

## Checklist de Validação Rápida

### ✅ Funcionalidade
- Todas as ações executam sem erros no console
- Dados são salvos/carregados corretamente
- Validações impedem dados inválidos

### ✅ UX
- Interface é intuitiva
- Feedback visual está presente
- Performance é aceitável

### ✅ CX
- Jornada do usuário é fluida
- Mensagens são amigáveis
- Não há pontos de frustração óbvios

## Formato de Saída

Ao realizar testes, forneça um relatório estruturado:

### 1. Resumo Executivo
- **Status Geral**: ✅ Aprovado / ⚠️ Atenção / ❌ Reprovado
- **Principais Problemas**: Lista dos 3 problemas mais críticos (se houver)

### 2. Detalhamento por Categoria

#### Funcionalidade
- **Problemas Encontrados**: Lista de bugs/erros
- **Funcionalidades OK**: O que está funcionando

#### UX
- **Problemas de Usabilidade**: Pontos confusos ou difíceis
- **Pontos Positivos**: O que está bem feito

#### CX
- **Fricções**: Onde o usuário pode se frustrar
- **Melhorias Sugeridas**: Como melhorar a experiência

### 3. Priorização
- **Crítico** 🔴: Impede uso da aplicação
- **Alto** 🟡: Prejudica significativamente a experiência
- **Médio** 🔵: Melhorias desejáveis
- **Baixo** ⚪: Polimento/refinamento

## Ferramentas de Teste

### Console do Navegador
- Verificar erros JavaScript
- Monitorar requisições de rede
- Inspecionar elementos

### Teste Manual
- Navegar pela aplicação como usuário
- Testar em diferentes tamanhos de tela
- Testar com diferentes dados (vazio, muito, pouco)

### Casos Extremos
- Dados vazios
- Dados muito longos
- Caracteres especiais
- Conexão lenta
- Erros de API
