@form(['form'=>$form,'attributes'=>'class="form"'])

    @field('main','email')
    @field('main','name')
    @field('main.child','name')

    @foreach($formlets->first('main.child')->multi as $formlet)
        @field('foo')
    @endforeach



@endform
