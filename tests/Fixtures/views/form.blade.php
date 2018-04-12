@form(['form'=>$form,'attributes'=>'class="form"'])

    @field('email')
    @field('name')
    @field('child','name')

    @foreach($formlet->formlet('child')->multi as $formlet)
        @field('foo')
    @endforeach



@endform

