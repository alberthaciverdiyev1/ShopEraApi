@props([
    'title' => null,
    'parent' => null,
])

<div class="sf-breadcrumb">
    <div class="container">
        <ul class="sf-breadcrumb__list">
            <li>
                <a href="/" aria-label="Ana səhifə"><i class="fa-light fa-house"></i></a>
            </li>
            @if ($parent)
                <li><i class="fa-solid fa-chevron-right sf-breadcrumb__sep"></i></li>
                <li>{{ $parent }}</li>
            @endif
            @if ($title)
                <li><i class="fa-solid fa-chevron-right sf-breadcrumb__sep"></i></li>
                <li class="is-active">{{ $title }}</li>
            @endif
        </ul>
    </div>
</div>
