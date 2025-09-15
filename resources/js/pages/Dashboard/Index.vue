<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage} from '@inertiajs/vue3';

const { props } = usePage();
const docentesCount = props.docentesCount;
const cursosCount = props.cursosCount;
const cursosPorPeriodo = props.cursosPorPeriodo ?? [];
</script>
<template>
  <AppLayout>
    <div class="p-8 bg-gray-100 min-h-screen">
      <h1 class="text-3xl font-bold mb-8 text-center">Dashboard</h1>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Módulo: Número de docentes -->
        <div class="bg-white p-6 rounded shadow hover:shadow-lg transition">
          <h2 class="text-xl font-semibold mb-2">Docentes Registrados</h2>
          <p class="text-3xl font-bold text-green-600">{{ docentesCount }}</p>
        </div>

        <!-- Módulo: Número de cursos -->
        <div class="bg-white p-6 rounded shadow hover:shadow-lg transition">
          <h2 class="text-xl font-semibold mb-2">Cursos Registrados</h2>
          <p class="text-3xl font-bold text-blue-600">{{ cursosCount }}</p>
        </div>

        <!-- Módulo: Cursos por período -->
        <div class="bg-white p-6 rounded shadow hover:shadow-lg transition">
          <h2 class="text-xl font-semibold mb-2">Cursos por Período</h2>
          <ul>
            <li v-for="periodo in cursosPorPeriodo" :key="periodo.periodo">
              <span class="text-gray-700">{{ periodo.periodo }}:</span>
              <span class="text-green-600 font-bold">{{ periodo.total }}</span>
            </li>
          </ul>
        </div>

        <!-- Módulo adicional: Acceso rápido -->
        <div class="bg-white p-6 rounded shadow hover:shadow-lg transition">
          <h2 class="text-xl font-semibold mb-2">Acceso Rápido</h2>
          <div class="flex flex-col gap-4">
            <button
              @click="router.get('/docentes')"
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
