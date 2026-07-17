import { inject, provide } from "vue";

export const SIDEBAR_CONTEXT_KEY = Symbol("sidebar");

export function provideSidebarContext(context) {
  provide(SIDEBAR_CONTEXT_KEY, context);
}

export function useSidebarContext() {
  return inject(SIDEBAR_CONTEXT_KEY, null);
}
