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
                <li>
                    <button type="button" class="sf-breadcrumb-link-btn" data-sf-account-drawer-open title="Kateqoriyaları göstər">
                        {{ $parent }} <i class="fa-regular fa-chevron-down ms-1 sf-breadcrumb-icon"></i>
                    </button>
                </li>
            @endif
            @if ($title)
                <li><i class="fa-solid fa-chevron-right sf-breadcrumb__sep"></i></li>
                <li class="is-active">
                    <span class="sf-breadcrumb-title-text">{{ $title }}</span>
                </li>
            @endif
            <li class="sf-breadcrumb-cat-action d-lg-none ms-auto">
                <button type="button" class="sf-breadcrumb-browse-btn" data-sf-account-drawer-open title="Bütün kateqoriyalar">
                    <i class="fa-regular fa-grid-2 me-1"></i>
                    <span>Kateqoriyalar</span>
                </button>
            </li>
        </ul>
    </div>
</div>
