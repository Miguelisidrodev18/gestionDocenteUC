<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const docentes = page.props.docentes ?? [];
const responsables = page.props.responsables ?? [];

const curso = ref({
  id: page.props.curso.id,
  nombre: page.props.curso.nombre ?? '',
  codigo: page.props.curso.codigo ?? '',
  descripcion: page.props.curso.descripcion ?? '',
  creditos: page.props.curso.creditos ?? '',
  nivel: page.props.curso.nivel ?? 'pregrado',
  modalidad: page.props.curso.modalidad ?? '',
  docente_id: page.props.curso.docente_id ?? '',
  drive_url: page.props.curso.drive_url ?? '',
  responsable_id: page.props.curso.user_id ?? '',
});

function actualizarCurso() {
  const { id, ...rest } = curso.value;
  const payload = { ...rest };

  if (!payload.responsable_id) {
    delete payload.responsable_id;
  }

  router.put(`/cursos/${id}`, payload, {
    onSuccess: () => {
      alert('Curso actualizado correctamente.');
      router.reload(); // Recarga la página para reflejar los cambios
    }
  });
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
        <!-- Selección de docente -->
        <select v-model="curso.docente_id" class="border p-2 rounded" required>
          <option value="">Seleccione un docente</option>
          <option v-for="docente in docentes" :key="docente.id" :value="docente.id">
            {{ docente.nombre }} {{ docente.apellido }}
          </option>
        </select>
        <select v-model="curso.responsable_id" class="border p-2 rounded">
          <option value="">Seleccione responsable</option>
          <option v-for="responsable in responsables" :key="responsable.id" :value="responsable.id">
            {{ responsable.name }}
          </option>
        </select>
        <input v-model="curso.drive_url" placeholder="URL de Google Drive" class="border p-2 rounded" />
      
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Actualizar</button>
      </form>
    </div>
  </AppLayout>
</template>
