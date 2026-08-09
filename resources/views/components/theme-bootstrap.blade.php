<meta name="color-scheme" content="light dark">
<script>
    (() => {
        const html = document.documentElement;
        const theme = html.dataset.theme || 'system';
        const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        html.classList.toggle('dark', isDark);
        html.style.colorScheme = isDark ? 'dark' : 'light';
    })();
</script>
<style>
    html {
        background-color: #f8fafc;
        color-scheme: light;
    }

    html.dark {
        background-color: #020617;
        color-scheme: dark;
    }
</style>
