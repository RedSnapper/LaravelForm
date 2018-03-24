<div class="form-group{{count($errors) > 0 ? " has-error" : ""}}">
    @include('form::helpers.label')
    <select class="form_control" @include('form::helpers.attributes')>
        @include('form::helpers.options')
    </select>
    @include('form::helpers.errors')
</div>
