<script lang="ts">
    export type StatusTone = "healthy" | "degraded" | "danger" | "neutral" | "paused";

    interface Props { tone?: StatusTone; label: string; }
    let { tone = "neutral", label }: Props = $props();

    const tones: Record<StatusTone, string> = {
        healthy: "bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200",
        degraded: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200",
        danger: "bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-200",
        neutral: "bg-wg-surface-muted text-wg-text-muted",
        paused: "bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200",
    };
</script>

<span class={`inline-flex items-center gap-[0.4rem] rounded-full px-[0.6rem] py-[0.3rem] text-xs leading-none font-extrabold ${tones[tone]}`}><span class="size-[0.45rem] rounded-full bg-current" aria-hidden="true"></span>{label}</span>
