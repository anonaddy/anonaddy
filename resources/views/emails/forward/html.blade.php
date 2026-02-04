@if($locationHtml === 'off' && ! $showSpamBanner)
    {!! $html !!}
@else
    <table style="width:100% !important;">
        <tbody>
            @if($showSpamBanner)
                @include('emails.forward.html_spam_warning')
            @endif
            @if($locationHtml === 'top')
                @include('emails.forward.html_banner')
            @endif
            <tr>
                <td style="padding:10px 0 !important;width:100% !important;">
                    {!! $html !!}
                </td>
            </tr>
            @if($locationHtml === 'bottom')
                @include('emails.forward.html_banner')
            @endif
        </tbody>
    </table>
@endif