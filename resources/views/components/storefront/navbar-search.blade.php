<div class="navbar-search-wrapper" data-sf-search>
    <button type="button" class="search-trigger-btn" data-sf-search-toggle
            aria-label="Məhsul axtar" title="Axtar" aria-expanded="false">
        <i class="fal fa-search"></i>
    </button>

    <div class="search-bar-inline">
        <form class="search-form" action="/shop" method="GET" role="search" data-sf-search-form>
            <i class="fal fa-search search-form-icon"></i>
            <input type="text"
                   name="search"
                   class="search-input"
                   placeholder="Məhsul və ya kateqoriya axtar…"
                   aria-label="Məhsul axtar"
                   autocomplete="off"
                   data-sf-search-input>

            <span class="search-spinner" aria-label="Yüklənir…" hidden data-sf-search-spinner>
                <i class="fa-solid fa-spinner fa-spin"></i>
            </span>

            <button type="button" class="search-clear-btn" aria-label="Təmizlə" title="Təmizlə"
                    data-sf-search-clear hidden>
                <i class="fa-solid fa-xmark"></i>
            </button>

            <button type="button" class="search-close-btn" aria-label="Bağla" title="Bağla"
                    data-sf-search-close>
                <i class="fa-regular fa-times"></i>
            </button>

            <button type="submit" class="search-submit-btn">Axtar</button>
        </form>

        <div class="search-dropdown-menu" data-sf-search-results hidden></div>
    </div>
</div>
