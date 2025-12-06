<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import { Docente, type BreadcrumbItem, type SharedData } from '@/types';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import PdfFileCard from '@/components/PdfFileCard.vue';
import { CirclePlus, Pencil, Trash } from 'lucide-vue-next';
import { computed } from 'vue';

interface DocentPageProps extends SharedData {
  docents: Docente[];
}

const props = usePage<DocentPageProps>();
const docents = computed(() => props.props.docents);
const auth = computed(() => props.props.auth);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Docentes', href: '/docents' }];

const deleteDocente = (id: number) => {
  if (confirm('�Est�s seguro de que deseas eliminar este docente?')) {
    router.delete(`/docents/${id}`, {
      onSuccess: () => {
        router.visit('/docents', { replace: true });
      },
    });
  }
};
</script>

<template>
  <Head title="Docentes" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div v-if="$page.props.flash && $page.props.flash.success" class="bg-success/10 text-success px-3 py-2 rounded mb-4">
      {{ $page.props.flash.success }}
    </div>
    <div v-if="$page.props.flash && $page.props.flash.error" class="bg-danger/10 text-danger px-3 py-2 rounded mb-4">
      {{ $page.props.flash.error }}
    </div>

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 bg-bg text-fg">
      <div class="flex">
        <Button
          v-if="auth.user.role !== 'docente'"
          asChild
          class="bg-primary text-primary-fg hover:opacity-90 active:opacity-100 px-4 py-2"
        >
          <Link href="/docents/create">
            <CirclePlus /> Crear Docente
          </Link>
        </Button>
      </div>

      <div class="relative flex-1 overflow-hidden rounded-xl border border-border bg-card p-4 shadow-sm">
        <Table>
          <TableCaption class="text-muted mt-4 text-sm">Lista de Docentes</TableCaption>
          <TableHeader>
            <TableRow class="border-b border-border">
              <TableHead class="px-6 py-3 text-left text-fg">ID</TableHead>
              <TableHead class="px-6 py-3 text-left text-fg">Nombre</TableHead>
              <TableHead class="px-6 py-3 text-left text-fg">Apellido</TableHead>
              <TableHead class="px-6 py-3 text-left text-fg">DNI</TableHead>
              <TableHead class="px-6 py-3 text-left text-fg">Email</TableHead>
              <TableHead class="px-6 py-3 text-left text-fg">Teléfono</TableHead>
              <TableHead class="px-6 py-3 text-left text-fg">Especialidad</TableHead>
              <TableHead class="px-6 py-3 text-left text-fg">CV Personal</TableHead>
              <TableHead class="px-6 py-3 text-left text-fg">CV Sunedu</TableHead>
              <TableHead class="px-6 py-3 text-left text-fg">CUL</TableHead>
              <TableHead class="px-6 py-3 text-left text-fg">LinkedIn</TableHead>
              <TableHead class="px-6 py-3 text-left text-fg">Estado CIP</TableHead>
              <TableHead class="px-6 py-3 text-left text-fg">N° CIP</TableHead>
              <TableHead class="px-6 py-3 text-left text-fg">Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="(docent, index) in docents"
              :key="index"
              class="hover:bg-card/80 cursor-pointer border-b border-border/60"
            >
              <TableCell class="px-6 py-4 text-fg font-medium">{{ index + 1 }}</TableCell>
              <TableCell class="px-6 py-4 text-fg">{{ docent.nombre }}</TableCell>
              <TableCell class="px-6 py-4 text-fg">{{ docent.apellido }}</TableCell>
              <TableCell class="px-6 py-4 text-fg">{{ docent.dni }}</TableCell>
              <TableCell class="px-6 py-4 text-fg">{{ docent.email ?? 'N/A' }}</TableCell>
              <TableCell class="px-6 py-4 text-fg">{{ docent.telefono }}</TableCell>
              <TableCell class="px-6 py-4 text-fg">{{ docent.especialidad }}</TableCell>

              <TableCell class="px-6 py-4">
                <PdfFileCard
                  v-if="docent.cv_personal"
                  :url="'/storage/' + docent.cv_personal"
                  :name="docent.cv_personal.split('/').pop() ?? 'cv_personal.pdf'"
                />
                <span v-else class="text-muted text-xs">No disponible</span>
              </TableCell>

              <!-- idem para cv_sunedu y cul -->

              <TableCell class="px-6 py-4 text-muted text-xs">
                {{ docent.linkedin ?? 'N/A' }}
              </TableCell>
              <TableCell class="px-6 py-4 text-muted text-xs">
                {{ docent.estado ? (docent.estado === 'activo' ? 'Habilitado' : 'No habilitado') : 'N/A' }}
              </TableCell>
              <TableCell class="px-6 py-4 text-fg">{{ docent.cip }}</TableCell>

              <TableCell class="px-6 py-4">
                <div class="flex justify-center gap-2">
                  <Button
                    v-if="auth.user.role !== 'docente' || docent.user_id === auth.user.id"
                    asChild
                    class="bg-primary text-primary-fg hover:opacity-90 active:opacity-100"
                  >
                    <Link :href="`/docents/${docent.id}/edit`">
                      <Pencil />
                    </Link>
                  </Button>
                  <Button
                    v-if="auth.user.role !== 'docente'"
                    class="bg-danger text-primary-fg hover:opacity-90 active:opacity-100"
                    @click="deleteDocente(docent.id)"
                  >
                    <Trash />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>
