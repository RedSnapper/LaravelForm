<div class="form-group">
    @include('form::helpers.label')
    <select class="form-control{{count($errors) > 0 ? " is-invalid" : ""}}" @include('form::helpers.attributes')>
        @include('form::helpers.options')
    </select>
    @include('form::helpers.errors')
</div>
