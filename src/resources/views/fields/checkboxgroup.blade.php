<div class="form-group">

	@if(isset($label))
		<label class="control-label">{{$label}}</label>
	@endif

	@foreach($options as $option)
		<div class="form-check">
			<label class="form-check-label">
				<input class="form-check-input{{count($errors) > 0 ? " is-invalid" : ""}}"
					   @include('form::helpers.attributes',['attributes'=>$option->attributes]) value="{{$option->value}}"/>
				{{$option->label}}
			</label>
		</div>
	@endforeach

	@include('form::helpers.errors')

</div>