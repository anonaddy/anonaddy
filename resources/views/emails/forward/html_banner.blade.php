<tr>
    <td>
        <div style="margin:0px auto;max-width:896px;padding:10px 20px;background-color:#f5f7fa;text-align:center;line-height:1.5;font-size:12px;width:100%;border-left: 3px solid #19216c;font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';color:#323f4b;overflow-wrap:break-word;">
            This email was sent to <span style="font-weight:500;color:#19216c;">{{ $aliasEmail }}</span>{{ $aliasDescription ? ' (' . $aliasDescription . ')' : '' }} from <span style="font-weight:500;color:#19216c;">{{ $fromEmail }}</span>{{ $replacedSubject }}<br>Click <a href="{{ $deactivateUrl }}" style="color:#2d3a8c;text-decoration:underline;" target="_blank" rel="noreferrer noopener nofollow">here</a> to deactivate this alias
        </div>
    </td>
</tr>