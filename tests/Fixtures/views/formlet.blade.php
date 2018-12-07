@form(['form'=>$form,'attributes'=>'class="form"'])

    @formlet()
	@foreach($formlet->child as $childFormlet)
		@formlet('childFormlet')
	@endforeach

    @foreach($formlet->formlet('child')->multi as $formlet)
        @formlet()
    @endforeach

@endform