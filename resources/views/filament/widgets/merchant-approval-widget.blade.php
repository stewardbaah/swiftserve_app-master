<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-x-3 rounded-lg bg-danger-500/10 p-4 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400">
            <svg class="h-6 w-6 text-danger-600 dark:text-danger-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold">Pending Admin Approval</h3>
                <p class="mt-1 text-xs text-danger-600/90 dark:text-danger-400/90">
                    Your restaurant is currently pending approval. It will not be visible to customers or accept orders until approved by an administrator.
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
