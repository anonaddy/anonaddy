@if($location === 'off')
    {!! $html !!}
@else
    <table style="width:100%;">
        <tbody>
            @if($location === 'top')
            <tr>
                <td style="padding:10px 20px;background-color:#fff;text-align:center;line-height:1.5;border-bottom:1px solid #cbd2d9;font-size:12px;width:100%;">
                This email was sent to {{ $aliasEmail }} from {{ $fromEmail }}{{ $replacedSubject }} and has been forwarded by <a href="https://anonaddy.com" style="color:#2d3a8c;text-decoration:underline;" target="_blank" rel="noreferrer noopener nofollow">AnonAddy</a><br>Click <a href="{{ $deactivateUrl }}" style="color:#2d3a8c;text-decoration:underline;" target="_blank" rel="noreferrer noopener nofollow">here</a> to deactivate this alias
                </td>
            </tr>
            @endif
            <tr>
                <td style="padding:10px 0;width:100%;">
                    {!! $html !!}
                </td>
            </tr>
            @if($location === 'bottom')
            <tr>
                <td style="padding:10px 20px;background-color:#fff;text-align:center;line-height:1.5;border-top:1px solid #cbd2d9;font-size:12px;width:100%;">
                This email was sent to {{ $aliasEmail }} from {{ $fromEmail }}{{ $replacedSubject }} and has been forwarded by <a href="https://anonaddy.com" style="color:#2d3a8c;text-decoration:underline;" target="_blank" rel="noreferrer noopener nofollow">AnonAddy</a><br>Click <a href="{{ $deactivateUrl }}" style="color:#2d3a8c;text-decoration:underline;" target="_blank" rel="noreferrer noopener nofollow">here</a> to deactivate this alias
                </td>
            </tr>
            @endif
        </tbody>
    </table>
@endif