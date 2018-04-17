@form(['form'=>$form,'attributes'=>'class="form"'])

	@foreach($formlet->fields() as $field)
		@field()
	@endforeach

	@foreach($formlet->child as $formlet)
		@field('name')
	@endforeach

	@foreach($formlet->multi as $formlet)
		@field('foo')
	@endforeach

@endform