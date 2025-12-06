<script setup lang="ts">
import { computed } from 'vue';
import { FileText } from 'lucide-vue-next';

interface Props {
  /**
   * URL del archivo para abrir en una nueva pestaña.
   */
  url: string;
  /**
   * Nombre a mostrar; si no se envía, se deriva del URL.
   */
  name?: string;
  /**
   * Indica si se debe deshabilitar el enlace (por ejemplo, archivos locales aún no guardados).
   */
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  name: undefined,
  disabled: false,
});

const displayName = computed(() => {
  if (props.name && props.name.trim() !== '') {
    return props.name;
  }

  try {
    const decoded = decodeURIComponent(props.url);
    const segments = decoded.split('/');
    return segments[segments.length - 1] ?? 'archivo.pdf';
  } catch {
    return 'archivo.pdf';
  }
});
</script>

<template>
  <component
    :is="disabled ? 'div' : 'a'"
    class="pdf-file-card inline-flex w-32 flex-col items-center gap-2 rounded-xl bg-card border border-border p-3 text-fg shadow hover:bg-bg transition"
    :href="disabled ? undefined : url"
    target="_blank"
    rel="noopener noreferrer"
  >
    <div class="flex h-20 w-20 items-center justify-center rounded-lg bg-bg">
      <FileText class="h-10 w-10 text-primary" />
    </div>
    <span class="line-clamp-2 text-center text-xs font-medium text-muted">
      {{ displayName }}
    </span>
  </component>
</template>

<style scoped>
.pdf-file-card {
  min-width: 8rem;
}
</style>

