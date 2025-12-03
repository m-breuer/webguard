<div>
    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <template x-for="(monthData, month) in data" :key="month">
            <div x-data="{
                monthName: new Date(month + '-02').toLocaleString('{{ app()->getLocale() }}', { month: 'long', year: 'numeric' }),
                firstDayOfMonth: new Date(monthData.days[0].date).getDay() === 0 ? 7 : new Date(monthData.days[0].date).getDay()
            }">
                <x-container>
                    <x-heading type="h3" space=true>
                        <span x-text="monthName"></span>
                        <template x-if="monthData.monthly_average_uptime !== null">
                            <span class="text-sm text-gray-500 dark:text-gray-400"
                                x-text="' (' + monthData.monthly_average_uptime.toFixed(2) + '%)'"></span>
                        </template>
                    </x-heading>
                    <div class="grid grid-cols-7 gap-1.5">
                        <div class="text-center text-xs text-gray-500 dark:text-gray-400">
                            {{ __('calendar.days.short.mon') }}
                        </div>
                        <div class="text-center text-xs text-gray-500 dark:text-gray-400">
                            {{ __('calendar.days.short.tue') }}
                        </div>
                        <div class="text-center text-xs text-gray-500 dark:text-gray-400">
                            {{ __('calendar.days.short.wed') }}
                        </div>
                        <div class="text-center text-xs text-gray-500 dark:text-gray-400">
                            {{ __('calendar.days.short.thu') }}
                        </div>
                        <div class="text-center text-xs text-gray-500 dark:text-gray-400">
                            {{ __('calendar.days.short.fri') }}
                        </div>
                        <div class="text-center text-xs text-gray-500 dark:text-gray-400">
                            {{ __('calendar.days.short.sat') }}
                        </div>
                        <div class="text-center text-xs text-gray-500 dark:text-gray-400">
                            {{ __('calendar.days.short.sun') }}
                        </div>

                        <template x-for="i in (firstDayOfMonth - 1)" :key="i">
                            <div></div>
                        </template>

                        <template x-for="day in monthData.days" :key="day.date">
                            <div x-data="{ tooltip: false }" @mouseenter="tooltip = true" @mouseleave="tooltip = false"
                                class="relative">
                                <div class="h-8 w-full rounded-sm"
                                    :class="{
                                        'bg-gray-200 dark:bg-gray-700': day.uptime_percentage === null,
                                        'bg-green-500': day.uptime_percentage >= 97.5,
                                        'bg-yellow-400': day.uptime_percentage >= 90 && day.uptime_percentage < 97.5,
                                        'bg-red-500': day.uptime_percentage !== null && day.uptime_percentage < 90
                                    }">
                                    <span class="sr-only" x-text="day.date"></span>
                                </div>
                                <div x-show="tooltip" x-transitions
                                    class="absolute z-10 rounded-lg bg-gray-900 p-2 text-xs font-medium text-white shadow-sm dark:bg-gray-700"
                                    style="bottom: 100%; left: 50%; transform: translateX(-50%); margin-bottom: 5px; white-space: nowrap;">
                                    <span
                                        x-text="day.date + ': ' + (day.uptime_percentage !== null ? day.uptime_percentage.toFixed(3) + '%' : '{{ __('calendar.legend.no_data') }}')"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </x-container>
            </div>
        </template>
    </div>

    <x-container>
        <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
            <div class="flex items-center gap-1">
                <div class="h-4 w-4 rounded-sm bg-green-500"></div>
                <span>{{ __('calendar.legend.excellent') }}</span>
            </div>
            <div class="flex items-center gap-1">
                <div class="h-4 w-4 rounded-sm bg-yellow-400"></div>
                <span>{{ __('calendar.legend.good') }}</span>
            </div>
            <div class="flex items-center gap-1">
                <div class="h-4 w-4 rounded-sm bg-red-500"></div>
                <span>{{ __('calendar.legend.poor') }}</span>
            </div>
            <div class="flex items-center gap-1">
                <div class="h-4 w-4 rounded-sm bg-gray-200 dark:bg-gray-700"></div>
                <span>{{ __('calendar.legend.no_data') }}</span>
            </div>
        </div>
    </x-container>

</div>
