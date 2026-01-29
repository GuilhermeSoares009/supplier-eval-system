<script setup>
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
</script>

<template>
  <div class="p-8 space-y-8 bg-background min-h-screen text-foreground">
    <div class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight">Demonstração Shadcn-Vue + Cores do Projeto</h1>
        <p class="text-muted-foreground">Exemplo de componentes shadcn com paleta personalizada (Success, Warning, Destructive).</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Card Success -->
      <Card class="border-l-4 border-l-success shadow-md hover:shadow-lg transition-all duration-200">
        <CardHeader>
          <CardTitle class="flex items-center justify-between">
            <span class="text-xl">Fornecedor A</span>
            <Badge variant="success">Aprovado</Badge>
          </CardTitle>
          <CardDescription>Score: 98/100</CardDescription>
        </CardHeader>
        <CardContent>
          <p class="text-sm">Desempenho excelente no último trimestre. Entregas no prazo e qualidade superior.</p>
        </CardContent>
        <CardFooter>
          <Button variant="success" class="w-full">Ver Detalhes</Button>
        </CardFooter>
      </Card>

      <!-- Card Warning -->
      <Card class="border-l-4 border-l-warning shadow-md hover:shadow-lg transition-all duration-200">
        <CardHeader>
          <CardTitle class="flex items-center justify-between">
            <span class="text-xl">Fornecedor B</span>
            <Badge variant="warning">Atenção</Badge>
          </CardTitle>
          <CardDescription>Score: 75/100</CardDescription>
        </CardHeader>
        <CardContent>
          <p class="text-sm">Atrasos leves na entrega identificados. Monitoramento necessário nos próximos envios.</p>
        </CardContent>
        <CardFooter>
          <Button variant="warning" class="w-full text-black">Ver Detalhes</Button>
        </CardFooter>
      </Card>

      <!-- Card Destructive -->
      <Card class="border-l-4 border-l-destructive shadow-md hover:shadow-lg transition-all duration-200">
        <CardHeader>
          <CardTitle class="flex items-center justify-between">
            <span class="text-xl">Fornecedor C</span>
            <Badge variant="destructive">Reprovado</Badge>
          </CardTitle>
          <CardDescription>Score: 45/100</CardDescription>
        </CardHeader>
        <CardContent>
          <p class="text-sm">Múltiplas falhas de qualidade no recebimento. Ação corretiva imediata necessária.</p>
        </CardContent>
        <CardFooter>
          <Button variant="destructive" class="w-full">Ver Detalhes</Button>
        </CardFooter>
      </Card>
    </div>

    <div class="flex gap-4 p-6 border rounded-lg bg-card text-card-foreground">
        <div class="flex flex-col gap-2">
            <h3 class="font-semibold">Botoes Variados</h3>
            <div class="flex gap-2 flex-wrap">
                <Button>Default</Button>
                <Button variant="secondary">Secondary</Button>
                <Button variant="outline">Outline</Button>
                <Button variant="ghost">Ghost</Button>
                <Button variant="link">Link</Button>
            </div>
        </div>
    </div>
  </div>
</template>
