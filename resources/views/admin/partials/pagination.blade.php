@if ($rows->hasPages())
    <nav class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 p-4 text-sm dark:border-gray-700" aria-label="Pagination">
        <span class="text-gray-500 dark:text-gray-400">{{ $rows->firstItem() }}–{{ $rows->lastItem() }} / {{ $rows->total() }}</span>
        <ul class="flex items-center -space-x-px text-sm">
            <li>
                @if ($rows->onFirstPage())
                    <span class="ms-0 flex h-9 items-center justify-center rounded-s-lg border border-gray-300 bg-white px-3 text-gray-300 dark:border-gray-700 dark:bg-gray-800">‹</span>
                @else
                    <a hx-get="{{ $rows->previousPageUrl() }}" hx-target="#resource-table" hx-swap="innerHTML" hx-push-url="true"
                       class="ms-0 flex h-9 cursor-pointer items-center justify-center rounded-s-lg border border-gray-300 bg-white px-3 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">‹</a>
                @endif
            </li>
            <li>
                <span class="flex h-9 items-center justify-center border border-gray-300 bg-white px-3 text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">{{ $rows->currentPage() }} / {{ $rows->lastPage() }}</span>
            </li>
            <li>
                @if ($rows->hasMorePages())
                    <a hx-get="{{ $rows->nextPageUrl() }}" hx-target="#resource-table" hx-swap="innerHTML" hx-push-url="true"
                       class="flex h-9 cursor-pointer items-center justify-center rounded-e-lg border border-gray-300 bg-white px-3 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">›</a>
                @else
                    <span class="flex h-9 items-center justify-center rounded-e-lg border border-gray-300 bg-white px-3 text-gray-300 dark:border-gray-700 dark:bg-gray-800">›</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
