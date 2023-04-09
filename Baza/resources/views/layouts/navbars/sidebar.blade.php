<div class="sidebar">
    <div class="sidebar-wrapper">
        <ul class="nav">
            <li @if ($pageSlug == 'search') class="active " @endif>
                <a href="{{ route('tickets.search') }}">
                    <i class="tim-icons icon-zoom-split"></i>
                    <p>{{ __('Поиск') }}</p>
                </a>
            </li>
            <li @if ($pageSlug == 'scan') class="active " @endif>
                <a href="{{ route('tickets.scan') }}">
                    <i class="tim-icons icon-components"></i>
                    <p>{{ __('Сканер QR') }}</p>
                </a>
            </li>
            <li @if ($pageSlug == 'icons') class="active " @endif>
                <a href="{{ route('pages.icons') }}">
                    <i class="tim-icons icon-atom"></i>
                    <p>{{ __('Icons') }}</p>
                </a>
            </li>
        </ul>
    </div>
</div>
