@form(['form'=>$form,'attributes'=>'class="form"'])

    @foreach($formlets->main as $formlet)
        @field('name')
        @field('email')
    @endforeach

    @foreach($formlets->first('main')->child as $formlet)
        @field('name')
    @endforeach

    @foreach($formlets->first('main.child')->multi as $formlet)
        @field('foo')
    @endforeach


@endform