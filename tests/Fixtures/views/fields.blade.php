@form(['form'=>$form,'attributes'=>'class="form"'])

    @foreach($formlets['main'] as $formlet)
        @field('name')
        @field('email')
    @endforeach

    @foreach($formlets['child'] as $formlet)
        @field('name')
    @endforeach



@endform