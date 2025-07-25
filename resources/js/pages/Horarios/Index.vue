<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref } from 'vue';

const dias = [
  { nombre: 'lun', fecha: 21 },
  { nombre: 'mar', fecha: 22 },
  { nombre: 'mié', fecha: 23 },
  { nombre: 'jue', fecha: 24 },
  { nombre: 'vie', fecha: 25 },
  { nombre: 'sáb', fecha: 26 },
  { nombre: 'dom', fecha: 27 },
];

const diaActual = 22; // Puedes calcularlo dinámicamente
</script>

<template>
  <AppLayout>
    <div class="p-8 bg-gray-100 min-h-screen">
      <h1 class="text-3xl font-bold mb-8">Calendario</h1>
      <div class="bg-white rounded-xl shadow p-4">
        <div class="flex justify-between items-center mb-4">
          <button class="text-2xl px-2">&lt;</button>
          <span class="font-semibold text-lg">jul 21 - jul 27, 2025</span>
          <button class="text-2xl px-2">&gt;</button>
        </div>
        <div class="grid grid-cols-7 border rounded overflow-hidden">
          <div
            v-for="dia in dias"
            :key="dia.fecha"
            class="flex flex-col items-center py-6 border-r last:border-r-0"
          >
            <span class="text-gray-500 font-medium mb-2">{{ dia.nombre }}</span>
            <span
              :class="[
                'text-2xl font-bold',
                dia.fecha === diaActual
                  ? 'bg-blue-500 text-white rounded-full px-4 py-2'
                  : 'text-gray-700'
              ]"
            >
              {{ dia.fecha }}
            </span>
            <!-- Aquí puedes mostrar eventos/horarios del día -->
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>