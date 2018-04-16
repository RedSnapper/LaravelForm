<div class="form-group">
    @include('form::helpers.label')
    <textarea class="form-control{{count($errors) > 0 ? " is-invalid" : ""}}" @include('form::helpers.attributes')>{{$value}}</textarea>
    @include('form::helpers.errors')
</div>
