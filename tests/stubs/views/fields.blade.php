@form(['form'=>$form,'attributes'=>'class="form"'])

    @foreach($formlets as $formlet)
        @field('name')
        @field('email')
    @endforeach

@endform