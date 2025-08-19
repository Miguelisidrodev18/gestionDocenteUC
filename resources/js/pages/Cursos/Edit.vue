<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const curso = ref(page.props.curso); // Datos del curso que se van a editar
const docentes = page.props.docentes ?? []; // Lista de docentes

function actualizarCurso() {
  router.put(`/cursos/${curso.value.id}`, curso.value, {
    onSuccess: () => {
      alert('Curso actualizado correctamente.');
      router.reload(); // Recarga la página para reflejar los cambios
    }
  });
}

function handleImageUpload(event) {
  const file = event.target.files[0];
  curso.value.image = file; // Agrega el archivo al objeto curso
}
</script>

<template>
  <AppLayout>
    <div class="p-8 bg-gray-100 min-h-screen">
      <h1 class="text-3xl font-bold mb-8 text-center">Editar Curso</h1>
      <form
        @submit.prevent="actualizarCurso"
        class="bg-white p-4 rounded shadow mb-6 flex flex-col gap-2 max-w-md mx-auto"
      >
        <input v-model="curso.nombre" placeholder="Nombre del curso" class="border p-2 rounded" required />
        <input v-model="curso.codigo" placeholder="Código" class="border p-2 rounded" required />
        <input v-model="curso.descripcion" placeholder="Descripción" class="border p-2 rounded" />
        <input v-model="curso.creditos" type="number" placeholder="Créditos" class="border p-2 rounded" required />
        <select v-model="curso.nivel" class="border p-2 rounded" required>
          <option value="pregrado">Pregrado</option>
          <option value="postgrado">Postgrado</option>
        </select>
        <input v-model="curso.modalidad" placeholder="Modalidad" class="border p-2 rounded" required />
        <input v-model="curso.image_url" placeholder="URL de imagen (opcional)" class="border p-2 rounded" />
        <!-- Selección de docente -->
        <select v-model="curso.docente_id" class="border p-2 rounded" required>
          <option value="">Seleccione un docente</option>
          <option v-for="docente in docentes" :key="docente.id" :value="docente.id">
            {{ docente.nombre }} {{ docente.apellido }}
          </option>
        </select>
        <input v-model="curso.drive_url" placeholder="URL de Google Drive" class="border p-2 rounded" />
      
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Actualizar</button>
      </form>
    </div>
  </AppLayout>
</template>