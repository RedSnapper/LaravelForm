@form(['form'=>$form,'attributes'=>'class="form"'])

    @foreach($formlets as $formlet)
        @formlet()
    @endforeach

@endform