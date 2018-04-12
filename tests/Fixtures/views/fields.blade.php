@form(['form'=>$form,'attributes'=>'class="form"'])


    @field('name')
    @field('email')

    @foreach($formlet->child as $formlet)
        @field('name')
    @endforeach

    @foreach($formlet->multi as $formlet)
        @field('foo')
    @endforeach


@endform