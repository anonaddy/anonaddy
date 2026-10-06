<tr>
    <td>
        <div id="addy-banner" class="addy-banner" style="margin:0px auto !important;max-width:896px !important;padding:12px 20px !important;background-color:#f5f7fa !important;text-align:left !important;line-height:1.6 !important;font-size:13px !important;letter-spacing:normal !important;word-spacing:normal !important;width:100% !important;border-left: 3px solid #19216c !important;font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji' !important;color:#323f4b !important;overflow-wrap:break-word !important;box-sizing:border-box !important;">
            @php
                $nbsp = static fn (string $value): string => str_replace(' ', '&nbsp;', e($value));
                $bannerActions = [];
                if (! empty($deactivateUrl)) {
                    $bannerActions[] = ['url' => $deactivateUrl, 'label' => 'Deactivate'];
                }
                if (! empty($blockEmailUrl)) {
                    $bannerActions[] = ['url' => $blockEmailUrl, 'label' => 'Block email'];
                }
                if (! empty($blockDomainUrl)) {
                    $bannerActions[] = ['url' => $blockDomainUrl, 'label' => 'Block domain'];
                }
            @endphp
            <span class="addy-banner-label" style="font-weight:400 !important;color:#7b8794 !important;">To:</span>&nbsp;<span style="font-weight:600 !important;color:#19216c !important;">{{ $aliasEmail }}</span>@if($aliasDescription)&nbsp;({{ $aliasDescription }})@endif
            <br><span class="addy-banner-label" style="font-weight:400 !important;color:#7b8794 !important;">From:</span>&nbsp;<span style="font-weight:600 !important;color:#19216c !important;">{{ $fromEmail }}</span>
            @if($replacedSubject)
                <br><span class="addy-banner-label" style="font-weight:400 !important;color:#7b8794 !important;">Original&nbsp;subject:</span>&nbsp;<span style="font-weight:600 !important;color:#19216c !important;">{{ $replacedSubject }}</span>
            @endif
            @if($bannerActions !== [])
                <br><span class="addy-banner-label" style="font-weight:400 !important;color:#7b8794 !important;">Actions:</span>&nbsp;@foreach($bannerActions as $bannerActionIndex => $bannerAction)@if($bannerActionIndex > 0)&nbsp;|&nbsp;@endif<a href="{{ $bannerAction['url'] }}" class="addy-banner-link" style="color:#2d3a8c !important;text-decoration:underline !important;border:0 !important;background:transparent !important;" target="_blank" rel="noreferrer noopener nofollow"><span class="addy-banner-link-text" style="color:#2d3a8c !important;text-decoration:underline !important;">{!! $nbsp($bannerAction['label']) !!}</span></a>@endforeach
            @endif
        </div>
    </td>
</tr>
