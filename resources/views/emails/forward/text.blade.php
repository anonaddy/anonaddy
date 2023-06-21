@if($locationText === 'top')
    @include('emails.forward.text_banner')


@endif
{!! $text !!}
@if($locationText === 'bottom')


    @include('emails.forward.text_banner')
@endif