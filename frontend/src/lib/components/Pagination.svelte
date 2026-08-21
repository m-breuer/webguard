<script lang="ts">
    interface Props { page: number; pages: number; href: (page: number) => string; }
    let { page, pages, href }: Props = $props();
    const adjacent = $derived(Array.from({ length: Math.max(0, Math.min(pages, 5)) }, (_, index) => Math.min(Math.max(1, page - 2) + index, pages)));
</script>
{#if pages > 1}<nav aria-label="Pagination"><a class:disabled={page === 1} aria-disabled={page === 1} href={href(Math.max(1, page - 1))}>Previous</a>{#each adjacent as current}<a class:current={current === page} aria-current={current === page ? "page" : undefined} href={href(current)}>{current}</a>{/each}<a class:disabled={page === pages} aria-disabled={page === pages} href={href(Math.min(pages, page + 1))}>Next</a></nav>{/if}
<style>nav { display: flex; flex-wrap: wrap; gap: 0.5rem; } a { min-width: 2.5rem; border: 1px solid var(--wg-border); border-radius: 0.625rem; color: var(--wg-text); padding: 0.55rem 0.75rem; font-size: 0.875rem; font-weight: 700; text-align: center; text-decoration: none; } a.current { border-color: var(--wg-accent); background: var(--wg-accent); color: var(--wg-accent-contrast); } a.disabled { pointer-events: none; opacity: 0.45; }</style>
