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
      router.reload(); // Recarga la pagina para reflejar los cambios
    }
  });
}
</script>

<template>
  <AppLayout>
    <div class="p-8 bg-background text-foreground min-h-screen">
      <h1 class="text-3xl font-bold mb-8 text-center">Editar Curso</h1>
      <form
        @submit.prevent="actualizarCurso"
        class="bg-card text-foreground p-6 rounded-xl shadow-sm border border-border mb-6 flex flex-col gap-2 max-w-md mx-auto"
      >
        <input v-model="curso.nombre" placeholder="Nombre del curso" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required />
        <input v-model="curso.codigo" placeholder="CÃ³digo" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required />
        <input v-model="curso.descripcion" placeholder="DescripciÃ³n" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" />
        <input v-model="curso.creditos" type="number" placeholder="CrÃ©ditos" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required />
        <select v-model="curso.nivel" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required>
          <option value="pregrado">Pregrado</option>
          <option value="postgrado">Postgrado</option>
        </select>
        <input v-model="curso.modalidad" placeholder="Modalidad" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required />
        <!-- SelecciÃ³n de docente -->
        <select v-model="curso.docente_id" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required>
          <option value="">Seleccione un docente</option>
          <option v-for="docente in docentes" :key="docente.id" :value="docente.id">
            {{ docente.nombre }} {{ docente.apellido }}
          </option>
        </select>
        <select v-model="curso.responsable_id" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring">
          <option value="">Seleccione responsable</option>
          <option v-for="responsable in responsables" :key="responsable.id" :value="responsable.id">
            {{ responsable.name }}
          </option>
        </select>
        <input v-model="curso.drive_url" placeholder="URL de Google Drive" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" />
      
        <button type="submit" class="bg-primary text-primary-foreground px-4 py-2 rounded hover:opacity-90 transition">Actualizar</button>
      </form>
    </div>
  </AppLayout>
</template>






