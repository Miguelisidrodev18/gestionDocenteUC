<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { CourseDocument, Cursos, Docente, SharedData } from '@/types';
import { Checkbox } from '@/components/ui/checkbox';

type ChecklistCourse = Cursos & {
  docente?: Docente | null;
  documents?: CourseDocument[];
};

interface ChecklistPageProps extends SharedData {
  cursos: ChecklistCourse[];
}

const page = usePage<ChecklistPageProps>();
const cursos = computed(() => page.props.cursos ?? []);
const userRole = computed(() => page.props.auth?.user?.role ?? null);

const statusMap: Record<CourseDocument['status'], string> = {
  pendiente: 'Pendiente',
  conforme_preliminar: 'Conforme preliminar',
  validado: 'Validado',
};

const latestDocument = (documents?: CourseDocument[] | null) => {
  return documents && documents.length > 0 ? documents[0] : null;
};

const formatStatus = (documento: CourseDocument | null) => {
  return documento ? statusMap[documento.status] : 'Sin documentos';
};

const isPreliminarChecked = (documento: CourseDocument | null) => {
  if (!documento) return false;
  return documento.status === 'conforme_preliminar' || documento.status === 'validado';
};

const isFinalChecked = (documento: CourseDocument | null) => {
  if (!documento) return false;
  return documento.status === 'validado';
};

const canTogglePreliminar = computed(() => userRole.value === 'responsable' || userRole.value === 'admin');
const canToggleFinal = computed(() => userRole.value === 'admin');

const updateStatus = (documento: CourseDocument, payload: { preliminar?: boolean; final?: boolean }) => {
  router.patch(`/cursos/documents/${documento.id}`, payload, {
    preserveScroll: true,
  });
};

const onPreliminarChange = (documento: CourseDocument, checked: boolean) => {
  updateStatus(documento, { preliminar: checked });
};

const onFinalChange = (documento: CourseDocument, checked: boolean) => {
  updateStatus(documento, { preliminar: checked ? true : isPreliminarChecked(documento), final: checked });
};
</script>

<template>
  <AppLayout>
    <Head title="Checklist de Cursos" />
    <div class="p-6">
      <h1 class="mb-6 text-2xl font-semibold">Checklist de cursos</h1>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Curso</TableHead>
          <TableHead>Docente</TableHead>
          <TableHead>Responsable</TableHead>
          <TableHead>Documentos</TableHead>
          <TableHead>Último estado</TableHead>
            <TableHead>Acciones</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="cursos.length === 0">
            <TableCell colspan="5" class="text-center text-muted-foreground">
              No hay cursos asignados por el momento.
            </TableCell>
          </TableRow>
          <TableRow v-for="curso in cursos" :key="curso.id">
            <TableCell class="font-medium">{{ curso.nombre }}</TableCell>
            <TableCell>
              <span v-if="curso.docente">
                {{ curso.docente.nombre }} {{ curso.docente.apellido }}
              </span>
              <span v-else class="text-muted-foreground">Sin docente asignado</span>
            </TableCell>
            <TableCell>{{ curso.responsable?.name ?? 'Sin asignar' }}</TableCell>
            <TableCell>{{ curso.documents?.length ?? 0 }}</TableCell>
            <TableCell>{{ formatStatus(latestDocument(curso.documents)) }}</TableCell>
            <TableCell>
              <div class="flex flex-col gap-2">
                <div class="flex items-center gap-2">
                  <Checkbox
                    :checked="isPreliminarChecked(latestDocument(curso.documents))"
                    :disabled="!canTogglePreliminar || !latestDocument(curso.documents)"
                    @update:checked="(value) => {
                      const doc = latestDocument(curso.documents);
                      if (doc && typeof value === 'boolean') onPreliminarChange(doc, value);
                    }"
                  />
                  <span>Conforme responsable</span>
                </div>
                <div class="flex items-center gap-2">
                  <Checkbox
                    :checked="isFinalChecked(latestDocument(curso.documents))"
                    :disabled="!canToggleFinal || !latestDocument(curso.documents)"
                    @update:checked="(value) => {
                      const doc = latestDocument(curso.documents);
                      if (doc && typeof value === 'boolean') onFinalChange(doc, value);
                    }"
                  />
                  <span>Validación supervisor</span>
                </div>
                <Link :href="`/cursos/${curso.id}/edit`" class="text-primary hover:underline">
                  Revisar curso
                </Link>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </AppLayout>
</template>
