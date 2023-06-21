@if($locationHtml === 'off')
    {!! $html !!}
@else
    <table style="width:100%;">
        <tbody>
            @if($locationHtml === 'top')
                @include('emails.forward.html_banner')
            @endif
            <tr>
                <td style="padding:10px 0;width:100%;">
                    {!! $html !!}
                </td>
            </tr>
            @if($locationHtml === 'bottom')
                @include('emails.forward.html_banner')
            @endif
        </tbody>
    </table>
@endif