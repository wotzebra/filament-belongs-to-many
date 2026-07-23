<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
        state: $wire.entangle('{{ $getStatePath() }}'),
        search: '',
        searchSelected: '',
        page: 1,
        perPage: {{ $getPagination() }},
        items: [],
        selected: [],
        loading: true,
        init () {
            this.state ??= [] // Insure that it uses an array

            $wire.callSchemaComponentMethod(@js($getKey()), 'fetchItems')
            $wire.$on('belongs-to-many::itemsFetchedFor-{{ $getStatePath() }}', (items) => {
                this.items = [...items[0]]

                this.selected = (Alpine.raw(this.state) || [])
                    .map((id) => this.items.find((item) => item.id === id))

                this.loading = false
            })

            $wire.$on('belongs-to-many::resetSelected-{{ $getStatePath() }}', () => {
                $wire.callSchemaComponentMethod(@js($getKey()), 'fetchItems')
            })

            $watch('search', () => this.page = 1)
        },
        updateState () {
            this.state = [...this.selected.map((item) => item.id)]
        },
        reorder (event) {
            // Identity-based so it stays correct even when the selected list is filtered.
            const list = Alpine.raw(this.selected) || []
            const view = this.selectedFiltered()
            const moved = view[event.oldIndex]
            const remaining = view.filter((item) => item !== moved)
            const before = remaining[event.newIndex] ?? null

            list.splice(list.indexOf(moved), 1)
            list.splice(before ? list.indexOf(before) : list.length, 0, moved)
            this.selected = list

            this.updateState()

            // HACK update prevKeys to new sort order
            // https://github.com/alpinejs/alpine/discussions/1635
            $refs.selected_template._x_prevKeys = this.state
        },
        currentPage () {
            return this.unselected()
                .slice((this.page - 1) * this.perPage, this.page * this.perPage)
        },
        unselected () {
            return this.items
                .filter((item) => item.html.toLowerCase().includes(this.search.toLowerCase()))
                .filter((item) => ! this.selected.includes(item))
        },
        selectedFiltered () {
            return this.selected
                .filter((item) => item.html.toLowerCase().includes(this.searchSelected.toLowerCase()))
        },
        maxPage () {
            return Math.ceil(this.unselected().length / this.perPage)
        },
        toggle (item) {
            if (this.selected.includes(item)) {
                this.selected = this.selected.filter((selection) => selection.id !== item.id)
            } else {
                this.selected.push(item)
            }

            if (this.page > this.maxPage()) {
                this.page = this.maxPage()
            } else if (this.page < 1) {
                this.page = 1
            }

            this.updateState()
        },
    }">
        @if (! $isDisabled())
            <div class="grid grid-cols-2 gap-3.5" x-show="! loading" x-cloak>
                {{-- Available --}}
                <div class="min-h-[20rem] max-h-[32rem] bg-white dark:bg-white/5 border border-gray-950/10 dark:border-white/10 rounded-lg overflow-hidden flex flex-col shadow-sm">
                    <div class="flex items-center justify-between px-3 py-2">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Available</span>
                        <span
                            class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400 tabular-nums"
                            x-text="unselected().length"
                        ></span>
                    </div>

                    <div class="px-2 pb-2">
                        <div class="relative">
                            <x-heroicon-m-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500 pointer-events-none" />
                            <input
                                type="text"
                                x-model="search"
                                placeholder="Search..."
                                class="
                                    w-full border-none pl-9 pr-9 py-1.5 text-base text-gray-950 outline-none transition
                                    duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500
                                    disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:text-white
                                    dark:placeholder:text-gray-500 dark:disabled:text-gray-400
                                    dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] sm:text-sm sm:leading-6
                                    bg-white dark:bg-white/5 dark:focus:ring-primary-500 dark:ring-white/20 duration-75
                                    fi-input-wrp flex focus:ring-2 focus:ring-primary-600 ring-1 ring-gray-950/10
                                    rounded-lg shadow-sm
                                "
                            >
                            <button
                                type="button"
                                x-show="search.length"
                                x-cloak
                                @click="search = ''"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-0.5 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-500 dark:hover:text-gray-300 dark:hover:bg-white/10 transition"
                            >
                                <x-heroicon-m-x-mark class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div class="overflow-y-auto flex-auto">
                        <template x-for="(item, key) in currentPage()" :key="key">
                            <div
                                @click="toggle(item)"
                                class="group flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5"
                            >
                                <div x-html="item.html" class="flex-1 min-w-0 text-gray-700 group-hover:text-gray-950 dark:text-gray-300 dark:group-hover:text-white [&_div]:truncate"></div>
                                <x-heroicon-m-plus class="w-4 h-4 mr-3 shrink-0 text-gray-300 group-hover:text-primary-500 dark:text-gray-600 dark:group-hover:text-primary-400" />
                            </div>
                        </template>
                    </div>

                    <div
                        class="flex justify-center items-center gap-1 border-t border-gray-950/5 dark:border-white/5 py-1.5 text-gray-500"
                        x-show="unselected().length > perPage"
                        x-cloak
                    >
                        <button type="button" class="p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-white/5 disabled:opacity-30 disabled:hover:bg-transparent" :disabled="page === 1" @click="page = 1">
                            <x-heroicon-s-chevron-double-left class="w-4 h-4" />
                        </button>
                        <button type="button" class="p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-white/5 disabled:opacity-30 disabled:hover:bg-transparent" :disabled="page === 1" @click="page -= 1">
                            <x-heroicon-s-chevron-left class="w-4 h-4" />
                        </button>
                        <div class="px-2 text-xs font-medium tabular-nums">
                            <span x-text="page"></span> / <span x-text="maxPage()"></span>
                        </div>
                        <button type="button" class="p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-white/5 disabled:opacity-30 disabled:hover:bg-transparent" :disabled="page === maxPage()" @click="page += 1">
                            <x-heroicon-s-chevron-right class="w-4 h-4" />
                        </button>
                        <button type="button" class="p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-white/5 disabled:opacity-30 disabled:hover:bg-transparent" :disabled="page === maxPage()" @click="page = maxPage()">
                            <x-heroicon-s-chevron-double-right class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {{-- Selected --}}
                <div class="min-h-[20rem] max-h-[32rem] bg-white dark:bg-white/5 border border-gray-950/10 dark:border-white/10 rounded-lg overflow-hidden flex flex-col shadow-sm">
                    <div class="flex items-center justify-between px-3 py-2">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Selected</span>
                        <span
                            class="text-xs font-semibold px-2 py-0.5 rounded-full bg-primary-50 text-primary-700 dark:bg-primary-400/10 dark:text-primary-400 tabular-nums"
                            x-text="selected.length"
                        ></span>
                    </div>

                    <div class="px-2 pb-2">
                        <div class="relative">
                            <x-heroicon-m-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500 pointer-events-none" />
                            <input
                                type="text"
                                x-model="searchSelected"
                                placeholder="Filter selected..."
                                class="
                                    w-full border-none pl-9 pr-9 py-1.5 text-base text-gray-950 outline-none transition
                                    duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500
                                    disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:text-white
                                    dark:placeholder:text-gray-500 dark:disabled:text-gray-400
                                    dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] sm:text-sm sm:leading-6
                                    bg-white dark:bg-white/5 dark:focus:ring-primary-500 dark:ring-white/20 duration-75
                                    fi-input-wrp flex focus:ring-2 focus:ring-primary-600 ring-1 ring-gray-950/10
                                    rounded-lg shadow-sm
                                "
                            >
                            <button
                                type="button"
                                x-show="searchSelected.length"
                                x-cloak
                                @click="searchSelected = ''"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-0.5 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-500 dark:hover:text-gray-300 dark:hover:bg-white/10 transition"
                            >
                                <x-heroicon-m-x-mark class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div
                        class="overflow-y-auto flex-auto"
                        @if ($getSortable())
                            x-sortable="selected"
                        x-on:end="reorder($event)"
                        @endif
                    >
                        <template x-for="(item, key) in selectedFiltered()" :key="key" x-ref="selected_template">
                            <div
                                x-sortable-item="item.id"
                                @click="toggle(item)"
                                class="group flex items-center cursor-pointer bg-primary-50/50 hover:bg-primary-100/70 dark:bg-primary-400/5 dark:hover:bg-primary-400/10"
                            >
                                @if ($getSortable())
                                    <button
                                        type="button"
                                        x-sortable-handle
                                        @click.stop
                                        class="shrink-0 self-stretch flex items-center px-2 cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400"
                                    >
                                        <svg width="10" height="16" viewBox="0 0 10 16" fill="currentColor" aria-hidden="true">
                                            <circle cx="2" cy="3" r="1.3" /><circle cx="8" cy="3" r="1.3" />
                                            <circle cx="2" cy="8" r="1.3" /><circle cx="8" cy="8" r="1.3" />
                                            <circle cx="2" cy="13" r="1.3" /><circle cx="8" cy="13" r="1.3" />
                                        </svg>
                                    </button>
                                @endif
                                <div x-html="item.html" class="flex-1 min-w-0 text-primary-950 dark:text-primary-100 [&_div]:truncate @if ($getSortable()) [&>div]:pl-1 @endif"></div>
                                <x-heroicon-m-x-mark class="w-4 h-4 mr-3 shrink-0 text-primary-400 opacity-0 group-hover:opacity-100 transition" />
                            </div>
                        </template>
                    </div>

                    <div class="px-3 py-2 border-t border-gray-950/5 dark:border-white/5 text-xs text-gray-400 dark:text-gray-500">
                        @if ($getSortable())
                            Click to remove · drag the handle to reorder
                        @else
                            Click to remove
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div x-show="! loading" x-cloak>
                <div class="max-h-[32rem] bg-white dark:bg-white/5 border border-gray-950/10 dark:border-white/10 rounded-lg overflow-hidden flex flex-col shadow-sm">
                    <div class="flex items-center justify-between px-3 py-2 border-b border-gray-950/10 dark:border-white/10">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Selected</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400 tabular-nums" x-text="selected.length"></span>
                    </div>
                    <div class="overflow-y-auto flex-auto">
                        <template x-for="(item, key) in selected" :key="key">
                            <div
                                x-html="item.html"
                                class="border-b border-gray-950/10 dark:border-white/10 last:border-b-0 text-gray-700 dark:text-gray-300"
                            ></div>
                        </template>
                    </div>
                </div>
            </div>
        @endif

        <template x-if="loading">
            <div class="min-h-[20rem] border border-gray-950/10 dark:border-white/10 rounded-lg overflow-hidden flex justify-center items-center">
                <x-filament::loading-indicator class="w-10 h-10" />
            </div>
        </template>
    </div>
</x-dynamic-component>
