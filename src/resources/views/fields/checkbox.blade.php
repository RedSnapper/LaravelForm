<div class="form-group">
	<div class="form-check">
		<label class="form-check-label">
			<input class="form-check-input{{count($errors) > 0 ? " is-invalid" : ""}}"
				   @include('form::helpers.attributes') value="{{$value}}"/>
			{{$label}}
		</label>
	</div>
	@include('form::helpers.errors')
</div>


