import { onMounted, onUnmounted, ref } from 'vue';
const SIDEBAR_KEY = 'fanana-sidebar-collapsed';
const isCollapsed = ref(false);
const isMobileOpen = ref(false);
const isMobile = ref(false);
let listeners = 0;
const checkMobile = () => {
    isMobile.value = window.innerWidth < 1024;
    if (!isMobile.value) isMobileOpen.value = false;
};
export function useSidebar() {
    const toggleSidebar = () => {
        if (isMobile.value) isMobileOpen.value = !isMobileOpen.value;
        else {
            isCollapsed.value = !isCollapsed.value;
            localStorage.setItem(SIDEBAR_KEY, JSON.stringify(isCollapsed.value));
        }
    };
    const closeMobile = () => { if (isMobile.value) isMobileOpen.value = false; };
    onMounted(() => {
        const saved = localStorage.getItem(SIDEBAR_KEY);
        if (saved !== null) isCollapsed.value = saved === 'true';
        checkMobile();
        if (listeners++ === 0) window.addEventListener('resize', checkMobile, { passive: true });
    });
    onUnmounted(() => {
        listeners = Math.max(0, listeners - 1);
        if (listeners === 0) window.removeEventListener('resize', checkMobile);
    });
    return { isCollapsed, isMobileOpen, isMobile, toggleSidebar, closeMobile };
}
