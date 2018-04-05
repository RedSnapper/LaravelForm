@form(['form'=>$form,'attributes'=>'class="form"'])

    @foreach($formlets as $forms)
        @foreach($forms as $formlet)
            @formlet()
        @endforeach
    @endforeach

@endform