@form(['form'=>$form,'attributes'=>'class="form"'])

    @formlet('main')
    @formlet('main.child')

    @foreach($formlets->first('main')->multi as $formlet)
        @formlet()
    @endforeach

@endform