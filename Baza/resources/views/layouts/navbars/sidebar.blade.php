<div class="sidebar">
    <div class="sidebar-wrapper">
        <ul class="nav">
            <li @if ($pageSlug == 'search') class="active " @endif>
                <a href="https://vhod.spaceofjoy.ru/search">
                    <i class="tim-icons icon-zoom-split"></i>
                    <p>{{ __('Поиск') }}</p>
                </a>
            </li>
            <li @if ($pageSlug == 'scan') class="active " @endif>
                <a href="https://vhod.spaceofjoy.ru/scan">
                    <i class="tim-icons icon-components"></i>
                    <p>{{ __('Сканер QR') }}</p>
                </a>
            </li>
            @if(Auth::user()->is_admin)
                <li @if ($pageSlug == 'change') class="active " @endif>
                    <a href="https://vhod.spaceofjoy.ru/report">
                        <i class="tim-icons icon-badge"></i>
                        <p>{{ __('Смены') }}</p>
                    </a>
                </li>
            @endif
        </ul>
    </div>
</div>
