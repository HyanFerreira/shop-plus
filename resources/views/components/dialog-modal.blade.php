@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="px-6 py-4">
        <div class="text-lg font-semibold text-slate-900">
            {{ $title }}
        </div>

        <div class="mt-4 text-sm text-slate-600">
            {{ $content }}
        </div>
    </div>

    <div class="flex flex-row justify-end border-t border-slate-200 bg-slate-50 px-6 py-4 text-end">
        {{ $footer }}
    </div>
</x-modal>
