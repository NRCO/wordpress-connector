<header class="ampize-header fixed flex justify-between">
    <nav class="ampize-nav-left flex">
        <div role="button" on="tap:ampize-menu-sidebar.toggle" tabindex="0" class="ampize-sidebar-trigger md-hide lg-hide pl1">☰</div>
        <a href="[[ $component['pageTree']['url'] ]]" title="[[ $component['pageTree']['name'] ]]" class="ampize-menu-logo" rel="home">
            <span class="ampize-hidden">[[ $component['pageTree']['name'] ]]</span>
        </a>
        <div class="ampize-menu-tabs flex justify-center xs-hide sm-hide">
            @if (isset($component["itemConfig"]["settings"]['displayRoot'])&&$component["itemConfig"]["settings"]['displayRoot'])
                <div class="flex ampize-menu-tab @if ($component['context']['page']['id'] == $component['pageTree']['id']) active @endif">
                    <a href="[[ $component['pageTree']['url'] ]]"><strong>[[ $component['pageTree']['name'] ]]</strong></a>
                </div>
            @endif
            @foreach ($component['pageTree']['children'] as $page)
                <div class="flex ampize-menu-tab @if (!empty($page["active"])) active @endif">
                    <a href="[[ $page['url'] ]]">[[ $page['name'] ]]</a>
                </div>
            @endforeach
        </div>
        @if (!empty($component['itemConfig']['settings']['targetURL']))
            <form method="get" class="ampize-search xs-hide sm-hide" action="[[$component['itemConfig']['settings']['targetURL'] ]]" target="_top">
                <input type="search" class="ampize-search-field"
                       @if (empty($component['itemConfig']['settings']['placeholder'])) placeholder="Search..." @else placeholder="[[$component['itemConfig']['settings']['placeholder'] ]]" @endif
                       @if (!empty($component['itemConfig']['settings']['defaultValue'])) value="[[$component['itemConfig']['settings']['defaultValue'] ]]" @endif
                       name="[[$component['itemConfig']['settings']['key'] ]]">
                @if ($component["context"]['previewMode'])
                    <input type="hidden" name="siteId" value="[[$component['context']['site']['id'] ]]">
                    <input type="hidden" name="pageId" value="[[$component['itemConfig']['settings']['pageId'] ]]">
                @endif
            </form>
        @endif
    </nav>
</header>
<amp-sidebar id="ampize-menu-sidebar" side="left" layout="nodisplay">
    <div class="ampize-menu-sidebar-layer">
        <div role="button" on="tap:ampize-menu-sidebar.toggle" tabindex="0" class="ampize-menu-sidebar-close">✕</div>
        @if (!empty($component['itemConfig']['settings']['targetURL']))
        <form method="get" class="ampize-search-mobile" action="[[$component['itemConfig']['settings']['targetURL'] ]]" target="_top">
            <input type="search" class="ampize-search-mobile-field"
                   @if (empty($component['itemConfig']['settings']['placeholder'])) placeholder="Search..." @else placeholder="[[$component['itemConfig']['settings']['placeholder'] ]]" @endif
                   @if (!empty($component['itemConfig']['settings']['defaultValue'])) value="[[$component['itemConfig']['settings']['defaultValue'] ]]" @endif
                   name="[[$component['itemConfig']['settings']['key'] ]]">
            @if ($component["context"]['previewMode'])
                <input type="hidden" name="siteId" value="[[$component['context']['site']['id'] ]]">
                <input type="hidden" name="pageId" value="[[$component['itemConfig']['settings']['pageId'] ]]">
            @endif
        </form>
        @endif
        <form action="/" target="_top" novalidate >

            <div @if (!empty($component['itemConfig']['settings']['targetURL'])) class="ampize-menu-sidebar-items-search" @else class="ampize-menu-sidebar-items" @endif >
                @if (isset($component["itemConfig"]["settings"]['displayRoot'])&&$component["itemConfig"]["settings"]['displayRoot'])
                    <a href="[[ $component['pageTree']['url'] ]]" class="ampize-menu-sidebar-item @if ($component['context']['page']['id'] == $component['pageTree']['id']) active @endif">
                        [[ $component['pageTree']['name'] ]]
                    </a>
                @endif
                    @foreach ($component['pageTree']['children'] as $page)
                        @if(!empty($page['children']))
                        <label class="ampize-menu-sidebar-item @if (!empty($page["active"])) active @endif has-children">
                            <input type="checkbox">[[ $page['name'] ]]
                            <span class="ampize-menu-sidebar-item-expand"> > </span>
                            <div class="ampize-menu-sidebar-submenu">
                                <div class="ampize-menu-sidebar-submenu-return"> < Back</div>
                                <div class="ampize-menu-sidebar-submenu-items">
                                    <a class="ampize-menu-sidebar-item ampize-menu-sidebar-submenu-item" href="[[ $page['url'] ]]"><strong>[[ $page['name'] ]]</strong></a>
                                    @foreach ($page['children'] as $subPage)
                                        <a class="ampize-menu-sidebar-item ampize-menu-sidebar-submenu-item @if (!empty($subPage["active"])) active @endif" href="[[ $subPage['url'] ]]">[[ $subPage['name'] ]]</a>
                                    @endforeach
                                </div>
                            </div>
                        </label>
                        @else <a class="ampize-menu-sidebar-item @if (!empty($page["active"])) active @endif" href="[[ $page['url'] ]]">[[ $page['name'] ]]</a>
                        @endif
                    @endforeach
            </div>
        </form>
    </div>
</amp-sidebar>
