<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const docentes = page.props.docentes ?? []; // Debes pasar docentes desde el backend
const cursos = usePage().props.cursos ?? [];

const mostrarFormulario = ref(false);
const nuevoCurso = ref({
  nombre: '',
  codigo: '',
  descripcion: '',
  creditos: '',
  nivel: 'pregrado',
  modalidad: 'presencial',
  image_url: '',
  docente_id: '',
});

function agregarCurso() {
  router.post('/cursos', nuevoCurso.value, {
    onSuccess: () => {
      mostrarFormulario.value = false;
      nuevoCurso.value = {
        nombre: '',
        codigo: '',
        descripcion: '',
        creditos: '',
        nivel: 'pregrado',
        modalidad: 'presencial',
        image_url: '',
        docente_id: '',
      };
    }
  });
}

function editarCurso(id) {
  router.get(`/cursos/${id}/edit`);
}

function eliminarCurso(id) {
  if (confirm('¿Estás seguro de que deseas eliminar este curso?')) {
    router.delete(`/cursos/${id}`, {
      onSuccess: () => {
        // Aquí puedes agregar lógica adicional después de eliminar el curso, si es necesario
      }
    });
  }
}
</script>

<template>
  <AppLayout>
    <div class="p-8 bg-gray-100 min-h-screen">
      <h1 class="text-3xl font-bold mb-8 text-center">Asignaturas <button color="red"> <strong>"2025-10"</strong></button></h1>
      <!-- Botón para mostrar el formulario -->
      <div class="flex justify-end mb-4">
        <button
          @click="mostrarFormulario = !mostrarFormulario"
          class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition"
        >
          + Agregar curso
        </button>
      </div>
      <!-- Formulario para agregar curso -->
      <form
        v-if="mostrarFormulario"
        @submit.prevent="agregarCurso"
        class="bg-white p-4 rounded shadow mb-6 flex flex-col gap-2 max-w-md mx-auto"
      >
        <input v-model="nuevoCurso.nombre" placeholder="Nombre del curso" class="border p-2 rounded" required />
        <input v-model="nuevoCurso.codigo" placeholder="Código" class="border p-2 rounded" required />
        <input v-model="nuevoCurso.descripcion" placeholder="Descripción" class="border p-2 rounded" />
        <input v-model="nuevoCurso.creditos" type="number" placeholder="Créditos" class="border p-2 rounded" required />
        <select v-model="nuevoCurso.nivel" class="border p-2 rounded" required>
          <option value="pregrado">Pregrado</option>
          <option value="postgrado">Postgrado</option>
        </select>
        <input v-model="nuevoCurso.modalidad" placeholder="Modalidad" class="border p-2 rounded" required />
        <input v-model="nuevoCurso.image_url" placeholder="URL de imagen (opcional)" class="border p-2 rounded" />
        <!-- Selección de docente -->
        <select v-model="nuevoCurso.docente_id" class="border p-2 rounded" required>
          <option value="">Seleccione un docente</option>
          <option v-for="docente in docentes" :key="docente.id" :value="docente.id">
            {{ docente.nombre }} {{ docente.apellido }}
          </option>
        </select>
      
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Guardar</button>
      </form>
      <!-- Grid de tarjetas -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
        <div
          v-for="curso in cursos"
          :key="curso.id"
          class="rounded-xl shadow-lg bg-white border relative overflow-hidden group hover:shadow-2xl transition-shadow min-h-[270px] flex flex-col"
        >
          <!-- Banner superior -->
          <div class="h-20 bg-gradient-to-r from-green-600 to-blue-600 flex items-end px-4 pb-2 relative">
            <h2 class="text-white text-lg font-bold truncate w-3/4">{{ curso.nombre }}</h2>
            <span class="absolute right-4 top-4 bg-white rounded-full p-1 shadow">
              <img src="/resources/js/pages/Cursos/snoppy.png" alt="logo" class="w-8 h-8 object-contain" />
            </span>
          </div>
          <div class="p-4 flex-1">
            <div class="text-xs text-gray-500 mb-1">{{ curso.docente?.nombre ?? '' }}</div>
            <div class="text-sm text-gray-700 mb-2 truncate">{{ curso.descripcion ?? 'Ver tus tareas' }}</div>
          </div>
          <!-- Puedes agregar aquí íconos decorativos si quieres, pero sin acciones -->
          <div class="flex justify-end gap-4 px-4 pb-4 border-t pt-2">
            <!-- Íconos decorativos opcionales -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v4a1 1 0 001 1h3m10 0h3a1 1 0 001-1V7m-1 4V7a2 2 0 00-2-2H6a2 2 0 00-2 2v4m16 0v10a2 2 0 01-2 2H6a2 2 0 01-2-2V11" /></svg>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>