@form(['form'=>$form,'attributes'=>'class="form"'])

    @formlet('main')
    @formlet('main.child')

    @foreach($formlets->first('main.child')->multi as $formlet)
        @formlet()
    @endforeach

@endform