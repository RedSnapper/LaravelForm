<div class="form-group{{count($errors) > 0 ? " has-error" : ""}}">
    @include('form::helpers.label')
    <input class="form_control" @include('form::helpers.attributes') @include('form::helpers.value')/>
    @include('form::helpers.errors')
</div>
