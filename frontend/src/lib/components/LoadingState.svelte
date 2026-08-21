<script lang="ts">interface Props { label?: string; } let { label = "Loading" }: Props = $props();</script>
<div class="inline-flex items-center gap-[0.65rem] text-sm font-bold text-wg-text-muted" role="status"><span class="size-4 animate-spin rounded-full border-2 border-wg-accent border-r-transparent" aria-hidden="true"></span><span>{label}</span></div>
