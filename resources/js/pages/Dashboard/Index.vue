<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage} from '@inertiajs/vue3';

const { props } = usePage();
const role = props.currentUserRole ?? props.auth?.user?.role;
const docentesCount = props.docentesCount;
const cursosCount = props.cursosCount;
const cursosPorPeriodo = props.cursosPorPeriodo ?? [];
const cursosDocente = props.cursosDocente ?? [];
</script>
<template>
  <AppLayout>
    <div class="p-8 min-h-screen bg-background text-foreground">
      <h1 class="text-3xl font-bold mb-8 text-center">Dashboard</h1>
      <div v-if="role === 'docente'">
        <h2 class="text-xl font-semibold mb-4">Mis cursos</h2>
        <div v-if="!cursosDocente.length" class="text-sm text-muted-foreground">No tienes cursos asignados.</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="c in cursosDocente" :key="c.id" class="bg-card p-4 rounded-xl border">
            <div class="font-semibold mb-1">{{ c.codigo }} - {{ c.nombre }}</div>
            <div class="text-xs text-muted-foreground mb-2">Área: {{ c.area ?? '—' }} · Modalidad: {{ c.modalidad }}</div>
            <div class="text-xs mb-1">Avance: {{ c.avance }}%</div>
            <div class="h-2 bg-muted rounded mb-3"><div class="h-2 bg-green-600 rounded" :style="{ width: `${c.avance}%` }"></div></div>
            <div class="text-xs mb-2">
              <span class="mr-2">Actas: {{ c.faltantes?.acta ?? 0 }}</span>
              <span class="mr-2">Guías: {{ c.faltantes?.guia ?? 0 }}</span>
              <span class="mr-2">Present.: {{ c.faltantes?.presentacion ?? 0 }}</span>
              <span class="mr-2">Trabajos: {{ c.faltantes?.trabajo ?? 0 }}</span>
              <span>Excel: {{ c.faltantes?.excel ?? 0 }}</span>
            </div>
            <div class="flex gap-2 justify-end">
              <button class="p-1" title="Abrir" @click="$inertia.get(`/cursos/${c.id}`)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-primary"><path d="M12 5C5 5 2 12 2 12s3 7 10 7 10-7 10-7-3-7-10-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Módulo: Número de docentes -->
        <div class="bg-card p-6 rounded-xl shadow-sm border border-border transition">
          <h2 class="text-xl font-semibold mb-2">Docentes Registrados</h2>
          <p class="text-3xl font-bold text-green-600">{{ docentesCount }}</p>
        </div>

        <!-- Módulo: Número de cursos -->
        <div class="bg-card p-6 rounded-xl shadow-sm border border-border transition">
          <h2 class="text-xl font-semibold mb-2">Cursos Registrados</h2>
          <p class="text-3xl font-bold text-blue-600">{{ cursosCount }}</p>
        </div>

        <!-- Módulo: Cursos por período -->
        <div class="bg-card p-6 rounded-xl shadow-sm border border-border transition">
          <h2 class="text-xl font-semibold mb-2">Cursos por Período</h2>
          <ul>
            <li v-for="periodo in cursosPorPeriodo" :key="periodo.periodo">
              <span class="text-muted-foreground">{{ periodo.periodo }}:</span>
              <span class="text-green-600 font-bold">{{ periodo.total }}</span>
            </li>
          </ul>
        </div>

        <!-- Módulo adicional: Acceso rápido -->
        <div class="bg-card p-6 rounded-xl shadow-sm border border-border transition">
          <h2 class="text-xl font-semibold mb-2">Acceso Rápido</h2>
          <div class="flex flex-col gap-4">
            <button
              @click="router.get('/docents')"
              class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition"
            >
              Ver Docentes
            </button>
            <button
              @click="router.get('/cursos')"
              class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition"
            >
              Ver Cursos
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
