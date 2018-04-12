@form(['form'=>$form,'attributes'=>'class="form"'])

    @formlet()
    @formlet('child')

    @foreach($formlet->formlet('child')->multi as $formlet)
        @formlet()
    @endforeach

@endform