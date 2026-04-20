import { ref, onMounted, onUnmounted, watch } from 'vue';

/**
 * Composable para manejar el tema de la aplicación (claro/oscuro)
 * Sincroniza con localStorage y preferencias del sistema
 */
export function useTheme() {
    const isDark = ref(false);
    const isSystemPreference = ref(false);

    /**
     * Aplica el tema al documento
     */
    const applyTheme = (dark) => {
        isDark.value = dark;
        if (dark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    };

    /**
     * Guarda la preferencia en localStorage
     */
    const saveThemePreference = (preference) => {
        localStorage.setItem('theme', preference);
    };

    /**
     * Obtiene la preferencia guardada
     */
    const getSavedTheme = () => {
        return localStorage.getItem('theme');
    };

    /**
     * Detecta la preferencia del sistema
     */
    const getSystemPreference = () => {
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    };

    /**
     * Establece tema oscuro
     */
    const setDarkMode = () => {
        applyTheme(true);
        saveThemePreference('dark');
        isSystemPreference.value = false;
    };

    /**
     * Establece tema claro
     */
    const setLightMode = () => {
        applyTheme(false);
        saveThemePreference('light');
        isSystemPreference.value = false;
    };

    /**
     * Usa preferencia del sistema
     */
    const setSystemPreference = () => {
        const systemPrefersDark = getSystemPreference();
        applyTheme(systemPrefersDark);
        saveThemePreference('system');
        isSystemPreference.value = true;
    };

    /**
     * Toggle entre temas
     */
    const toggleTheme = () => {
        if (isDark.value) {
            setLightMode();
        } else {
            setDarkMode();
        }
    };

    /**
     * Inicializa el tema al montar
     */
    const initTheme = () => {
        const savedTheme = getSavedTheme();

        if (savedTheme === 'dark') {
            applyTheme(true);
            isSystemPreference.value = false;
        } else if (savedTheme === 'light') {
            applyTheme(false);
            isSystemPreference.value = false;
        } else {
            // System preference or no preference saved
            const systemPrefersDark = getSystemPreference();
            applyTheme(systemPrefersDark);
            isSystemPreference.value = true;
        }
    };

    /**
     * Escucha cambios en la preferencia del sistema
     */
    let mediaQueryListener = null;

    const setupSystemPreferenceListener = () => {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        mediaQueryListener = (e) => {
            if (isSystemPreference.value || !getSavedTheme()) {
                applyTheme(e.matches);
            }
        };

        mediaQuery.addEventListener('change', mediaQueryListener);
    };

    const cleanupSystemPreferenceListener = () => {
        if (mediaQueryListener) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.removeEventListener('change', mediaQueryListener);
        }
    };

    // Lifecycle hooks para uso fuera de componentes Vue
    onMounted(() => {
        initTheme();
        setupSystemPreferenceListener();
    });

    onUnmounted(() => {
        cleanupSystemPreferenceListener();
    });

    return {
        isDark,
        isSystemPreference,
        setDarkMode,
        setLightMode,
        setSystemPreference,
        toggleTheme,
        initTheme,
    };
}

/**
 * Inicializa el tema inmediatamente (para usar en app.js antes de montar la app)
 * Esto previene el flash de tema incorrecto
 */
export function initializeThemeOnLoad() {
    const savedTheme = localStorage.getItem('theme');

    if (savedTheme === 'dark') {
        document.documentElement.classList.add('dark');
    } else if (savedTheme === 'light') {
        document.documentElement.classList.remove('dark');
    } else {
        // System preference
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (systemPrefersDark) {
            document.documentElement.classList.add('dark');
        }
    }
}

export default useTheme;
