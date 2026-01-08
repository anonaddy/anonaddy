<tr>
    <td>
        <div style="margin:0px auto !important;width:100% !important;padding:10px 20px !important;background-color:#CF1124 !important;text-align:center !important;line-height:1.5 !important;font-size:14px !important;font-weight:500 !important;font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji' !important;color:#ffffff !important;overflow-wrap:break-word !important;">
            @if ($failedDmarc)
            Warning: This email has failed its domain's authentication requirements. It may be spoofed or improperly forwarded.
            @else
            Warning: This email has a high spam score. It may be unsolicited, promotional, or contain malicious content.
            @endif
        </div>
    </td>
</tr>