<div class="sidebar">
    <div class="sidebar-wrapper">
        <ul class="nav">
            <li @if ($pageSlug == 'search') class="active " @endif>
                <a href="/search">
                    <i class="tim-icons icon-zoom-split"></i>
                    <p>{{ __('Поиск') }}</p>
                </a>
            </li>
            <li @if ($pageSlug == 'scan') class="active " @endif>
                <a href="/scan">
                    <i class="tim-icons icon-components"></i>
                    <p>{{ __('Сканер QR') }}</p>
                </a>
            </li>
            @if(Auth::user()->is_admin)
                <li @if ($pageSlug == 'change') class="active " @endif>
                    <a href="/report">
                        <i class="tim-icons icon-badge"></i>
                        <p>{{ __('Смены') }}</p>
                    </a>
                </li>
                <li @if ($pageSlug == 'sync') class="active " @endif>
                    <a href="{{ route('sync.index') }}">
                        <i class="tim-icons icon-cloud-download-93"></i>
                        <p>{{ __('Синхронизация') }}</p>
                    </a>
                </li>
                <li @if ($pageSlug == 'permission') class="active " @endif>
                    <a href="{{ route('permission.index') }}">
                        <i class="tim-icons icon-lock-circle"></i>
                        <p>{{ __('Права доступа') }}</p>
                    </a>
                </li>
            @endif
        </ul>
    </div>
</div>
