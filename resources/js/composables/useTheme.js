import { onMounted, onUnmounted, ref } from 'vue';
const THEME_KEY = 'fanana-theme';
const stored = typeof window !== 'undefined' ? localStorage.getItem(THEME_KEY) : null;
const theme = ref(stored || 'system');
const isDark = ref(false);
let mediaQuery;
const resolveDark = (value) => value === 'dark' || (value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
const applyTheme = (value) => {
    theme.value = ['light', 'dark', 'system'].includes(value) ? value : 'system';
    if (typeof window === 'undefined') return;
    localStorage.setItem(THEME_KEY, theme.value);
    isDark.value = resolveDark(theme.value);
    document.documentElement.classList.toggle('dark', isDark.value);
    document.documentElement.style.colorScheme = isDark.value ? 'dark' : 'light';
};
const handleSystemChange = () => { if (theme.value === 'system') applyTheme('system'); };
export function useTheme() {
    const toggleTheme = () => applyTheme(theme.value === 'light' ? 'dark' : theme.value === 'dark' ? 'system' : 'light');
    onMounted(() => {
        applyTheme(localStorage.getItem(THEME_KEY) || 'system');
        mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener?.('change', handleSystemChange);
    });
    onUnmounted(() => mediaQuery?.removeEventListener?.('change', handleSystemChange));
    return { theme, isDark, applyTheme, toggleTheme };
}
