<x-filament::page>
    <div class="grid gap-6">
        @if($this->getWidgets())
            <x-filament-widgets::widgets
                :widgets="$this->getWidgets()"
                :columns="$this->getColumns()"
            />
        @else
            <div class="text-center py-12">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Dashboard in costruzione
                </h3>
                <p class="text-gray-500 dark:text-gray-400">
                    I widget saranno aggiunti a breve...
                </p>
            </div>
        @endif
    </div>
</x-filament::page>