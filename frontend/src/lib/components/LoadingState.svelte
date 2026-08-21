<script lang="ts">interface Props { label?: string; } let { label = "Loading" }: Props = $props();</script>
<div class="loading" role="status"><span aria-hidden="true"></span><span>{label}</span></div>
<style>.loading { display: inline-flex; align-items: center; gap: 0.65rem; color: var(--wg-text-muted); font-size: 0.875rem; font-weight: 700; } .loading > span:first-child { width: 1rem; height: 1rem; border: 2px solid var(--wg-accent); border-right-color: transparent; border-radius: 50%; animation: spin 700ms linear infinite; } @keyframes spin { to { transform: rotate(360deg); } }</style>
