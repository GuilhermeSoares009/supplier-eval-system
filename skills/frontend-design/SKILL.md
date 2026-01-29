---
name: frontend-design
description: Criar interfaces frontend de alta qualidade usando Vue.js, shadcn-vue ou PrimeVue, e TailwindCSS. Use esta skill quando o usuário pedir para construir componentes web, páginas, dashboards ou aplicações.
license: Apache-2.0
---

Esta skill guia a criação de interfaces frontend profissionais usando **Vue.js 3 (Composition API)**, **shadcn-vue** ou **PrimeVue**, e **TailwindCSS**.

> **Nota**: shadcn-vue é a versão Vue do shadcn/ui. Componentes são copiados diretamente para o projeto e totalmente customizáveis via Tailwind.


## Pensamento de Design

Antes de codificar, entenda o contexto e defina uma direção estética CLARA:
- **Propósito**: Que problema esta interface resolve? Quem vai usar?
- **Tom**: Escolha uma direção (ex: Limpo/Corporativo, Moderno/Glassmorphism, Dashboard/Denso de Dados).
- **Restrições**: Deve usar Vue.js 3, componentes PrimeVue e utilitários TailwindCSS.

## Diretrizes de Estética Frontend

Foque em:
- **Tipografia**: Use fontes modernas sans-serif (Inter, Roboto, etc.) via classes Tailwind.
- **Cores & Tema**: Use paletas de cores semânticas. Utilize as capacidades de tema do PrimeVue combinadas com utilitários de cor do Tailwind.
- **Movimento**: Adicione transições sutis usando componentes `Transition` do Vue e classes `transition-*` do Tailwind.
- **Componentes**: 
    - Use componentes **PrimeVue** para interações complexas (Tabelas, Modais, Dropdowns).
    - Use **TailwindCSS** para layout, espaçamento e customizações de estilo.
    - Evite lutar contra os estilos do PrimeVue; estenda-os ou envolva-os graciosamente.

## Regras de Implementação

1. **Especificidades Vue.js**:
   - Use sintaxe `<script setup>`.
   - Use Composition API.
   - Garanta que a reatividade seja tratada corretamente (`ref`, `reactive`, `computed`).

2. **Uso do TailwindCSS**:
   - Use classes utilitárias para quase tudo (layout, espaçamento, cores, tipografia).
   - Use layouts `flex` e `grid` liberalmente.
   - Use `@apply` em CSS padrão apenas se absolutamente necessário para reusabilidade, caso contrário prefira classes inline.

3. **Integração PrimeVue**:
   - Use o modo unstyled ou passe props `class` para componentes PrimeVue para estilizá-los com Tailwind.
   - Garanta que as funcionalidades de acessibilidade do PrimeVue sejam preservadas.

4. **Responsividade**:
   - Sempre implemente mobile-first ou garanta que designs desktop-first degradem graciosamente.
   - Use breakpoints do Tailwind (`md:`, `lg:`) efetivamente.

## Usando shadcn-vue

### O que é shadcn-vue?
- Biblioteca de componentes **copy-paste** para Vue.js
- Componentes são adicionados ao seu projeto (não instalados via npm)
- Totalmente customizáveis via TailwindCSS
- Baseado em Radix Vue (primitivos acessíveis)

### Instalação
```bash
npx shadcn-vue@latest init
```

### Configuração de Cores Personalizadas
Para manter a paleta de cores do projeto (verde/amarelo/vermelho), configure em `tailwind.config.js`:

```js
module.exports = {
  theme: {
    extend: {
      colors: {
        // Cores do sistema de avaliação
        success: {
          DEFAULT: 'hsl(142, 76%, 36%)', // green-600
          foreground: 'hsl(0, 0%, 100%)',
        },
        warning: {
          DEFAULT: 'hsl(45, 93%, 47%)', // yellow-500
          foreground: 'hsl(0, 0%, 0%)',
        },
        destructive: {
          DEFAULT: 'hsl(0, 84%, 60%)', // red-500
          foreground: 'hsl(0, 0%, 100%)',
        },
        // Cores shadcn padrão
        border: 'hsl(214, 32%, 91%)',
        input: 'hsl(214, 32%, 91%)',
        ring: 'hsl(222, 47%, 11%)',
        background: 'hsl(0, 0%, 100%)',
        foreground: 'hsl(222, 47%, 11%)',
        primary: {
          DEFAULT: 'hsl(222, 47%, 11%)',
          foreground: 'hsl(210, 40%, 98%)',
        },
        secondary: {
          DEFAULT: 'hsl(210, 40%, 96%)',
          foreground: 'hsl(222, 47%, 11%)',
        },
        muted: {
          DEFAULT: 'hsl(210, 40%, 96%)',
          foreground: 'hsl(215, 16%, 47%)',
        },
        accent: {
          DEFAULT: 'hsl(210, 40%, 96%)',
          foreground: 'hsl(222, 47%, 11%)',
        },
      },
    },
  },
}
```

### Adicionando Componentes
```bash
# Adicionar componente específico
npx shadcn-vue@latest add button
npx shadcn-vue@latest add table
npx shadcn-vue@latest add card
npx shadcn-vue@latest add dialog
```

### Usando Cores Customizadas
```vue
<template>
  <!-- Botão de sucesso (verde) -->
  <Button variant="success">Aprovar</Button>
  
  <!-- Botão de atenção (amarelo) -->
  <Button variant="warning">Revisar</Button>
  
  <!-- Botão de erro (vermelho) -->
  <Button variant="destructive">Reprovar</Button>
  
  <!-- Badge com cores customizadas -->
  <Badge class="bg-green-500 text-white">Ótimo</Badge>
  <Badge class="bg-yellow-500 text-black">Bom</Badge>
  <Badge class="bg-red-500 text-white">Regular</Badge>
</template>
```

### Vantagens do shadcn-vue
- ✅ **Controle Total**: Código no seu projeto, customize à vontade
- ✅ **Sem Dependências Pesadas**: Não adiciona bibliotecas grandes
- ✅ **Acessibilidade**: Baseado em Radix Vue (WAI-ARIA compliant)
- ✅ **Tailwind Native**: Usa classes Tailwind diretamente
- ✅ **Compatível com PrimeVue**: Pode usar ambos no mesmo projeto

### Quando Usar shadcn-vue vs PrimeVue
- **shadcn-vue**: Componentes simples, máxima customização, controle total
- **PrimeVue**: Componentes complexos (DataTable avançado, Charts), temas prontos


## Workflow de Exemplo

1. **Analisar**: Determine o layout e componentes necessários.
2. **Estruturar**: Crie a estrutura do componente Vue.
3. **Estilizar**: Aplique classes Tailwind para layout e estética básica.
4. **Integrar**: Adicione componentes PrimeVue para funcionalidade.
5. **Polir**: Adicione estados hover, transições e espaçamento refinado.

## Diretrizes Específicas para Dashboards e Visualização de Dados

### Layout de Dashboard
- **Grid System**: Use `grid` do Tailwind para layouts de cards/widgets.
- **Hierarquia Visual**: Cards principais devem ter destaque (sombras, bordas, cores).
- **Densidade de Informação**: Balance informação densa com espaço em branco adequado.

### Componentes de Dados
- **Tabelas**: Use `DataTable` do PrimeVue com:
  - Paginação clara
  - Ordenação visual
  - Filtros acessíveis
  - Células com formatação condicional (cores para status)
- **Cards de Métricas**: 
  - Números grandes e legíveis
  - Labels descritivos
  - Ícones ou indicadores visuais
  - Comparações (vs. mês anterior, etc.)
- **Gráficos**: Considere integração com Chart.js ou similar.

### Paleta de Cores para Dados
- **Status**: 
  - Verde (`green-500`): Ótimo, Aprovado, Positivo
  - Amarelo (`yellow-500`): Atenção, Pendente
  - Vermelho (`red-500`): Crítico, Reprovado, Negativo
  - Azul (`blue-500`): Neutro, Informação
- **Contraste**: Garanta contraste adequado (WCAG AA mínimo).

### Interatividade
- **Hover States**: Sempre adicione feedback visual em elementos clicáveis.
- **Loading States**: Use `ProgressSpinner` ou skeletons do PrimeVue.
- **Empty States**: Mensagens claras quando não há dados.
- **Error States**: Feedback visual e textual para erros.

### Performance
- **Lazy Loading**: Para tabelas grandes, use paginação ou virtualização.
- **Computed Properties**: Para cálculos derivados de dados.
- **Debounce**: Para filtros e buscas em tempo real.
