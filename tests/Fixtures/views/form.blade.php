@form(['form'=>$form,'attributes'=>'class="form"'])

	@field('email')
	@field('name')

	@foreach($formlet->child as $childFormlet)
		@field('childFormlet','name')
	@endforeach

	@foreach($formlet->formlet('child')->multi as $formlet)
		@field('foo')
	@endforeach



@endform

