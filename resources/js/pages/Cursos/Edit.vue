<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const page = usePage();
const docentes = page.props.docentes ?? [];
const sedesCatalog = page.props.sedesCatalog ?? [];
const areas = page.props.areas ?? [];
const modalidadesCatalog = page.props.modalidadesCatalog ?? [];
const periodosCatalog = page.props.periodosCatalog ?? [];

const curso = ref({
  id: page.props.curso.id,
  nombre: page.props.curso.nombre ?? '',
  codigo: page.props.curso.codigo ?? '',
  descripcion: page.props.curso.descripcion ?? '',
  creditos: page.props.curso.creditos ?? '',
  nivel: page.props.curso.nivel ?? 'pregrado',
  modalidad: page.props.curso.modalidad ?? '',
  modalidad_id: page.props.curso.modalidad_id ?? '',
  area_id: page.props.curso.area_id ?? '',
  docente_id: page.props.curso.docente_id ?? '',
  sede_id: page.props.curso.sede_id ?? '',
  drive_url: page.props.curso.drive_url ?? '',
  periodo_academico: page.props.curso.periodo_academico ?? '',
});

const areaSeleccionada = computed(() => {
  return (areas ?? []).find((area: any) => String(area.id) === String(curso.value.area_id)) ?? null;
});
const modalidadesPorArea = computed(() => {
  const direct = areaSeleccionada.value?.modalidades ?? [];
  if (direct.length) return direct;
  return (modalidadesCatalog ?? []).filter(
    (m: any) => String(m.area_id ?? '') === String(curso.value.area_id),
  );
});

watch(
  () => curso.value.area_id,
  () => {
    curso.value.modalidad_id = '';
    curso.value.modalidad = '';
  },
);

watch(
  () => curso.value.modalidad_id,
  (value) => {
    const allModalidades = (areas ?? []).flatMap((area: any) => area.modalidades ?? []);
    const selected = allModalidades.find((m: any) => String(m.id) === String(value));
    curso.value.modalidad = selected?.nombre ?? '';
  },
);

function actualizarCurso() {
  const { id, ...rest } = curso.value;
  const payload = { ...rest };

  router.put(`/cursos/${id}`, payload, {
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Curso actualizado',
        text: 'El curso se actualizo correctamente.',
        confirmButtonText: 'OK',
        confirmButtonColor: '#6d28d9',
      }).then(() => {
        router.reload();
      });
    },
    onError: () => {
      Swal.fire({
        icon: 'error',
        title: 'Error al actualizar curso',
        text: 'Revisa los campos e intentalo nuevamente.',
        confirmButtonColor: '#6d28d9',
      });
    },
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
        <input
          v-model="curso.nombre"
          placeholder="Nombre del curso"
          class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
          required
        />
        <input
          v-model="curso.codigo"
          placeholder="NRC"
          class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
          required
        />
        <input
          v-model="curso.descripcion"
          placeholder="Descripcion"
          class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
        />
        <input
          v-model="curso.creditos"
          type="number"
          placeholder="Creditos"
          class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
          required
        />
        <select
          v-model="curso.nivel"
          class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
          required
        >
          <option value="pregrado">Pregrado</option>
          <option value="postgrado">Postgrado</option>
        </select>
        <select
          v-model="curso.area_id"
          class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="">Area (seleccionar)</option>
          <option v-for="area in areas" :key="area.id" :value="area.id">
            {{ area.nombre }}
          </option>
        </select>
        <select
          v-model="curso.modalidad_id"
          class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
          :disabled="!curso.area_id"
        >
          <option value="">Modalidad (seleccionar)</option>
          <option v-for="m in modalidadesPorArea" :key="m.id" :value="m.id">
            {{ m.nombre }}
          </option>
        </select>
        <select
          v-model="curso.docente_id"
          class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
          required
        >
          <option value="">Docente responsable</option>
          <option v-for="docente in docentes" :key="docente.id" :value="docente.id">
            {{ docente.nombre }} {{ docente.apellido }}
          </option>
        </select>
        <select
          v-model="curso.sede_id"
          class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="">Sede</option>
          <option v-for="s in sedesCatalog" :key="s.id" :value="s.id">
            {{ s.nombre }}
          </option>
        </select>
        <input
          v-model="curso.drive_url"
          placeholder="URL de Google Drive"
          class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
        />
        <select
          v-model="curso.periodo_academico"
          class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="">Periodo academico</option>
          <option v-for="p in periodosCatalog" :key="p.id" :value="p.codigo">
            {{ p.codigo }}
          </option>
        </select>

        <button type="submit" class="bg-primary text-primary-foreground px-4 py-2 rounded hover:opacity-90 transition">
          Actualizar
        </button>
      </form>
    </div>
  </AppLayout>
</template>
