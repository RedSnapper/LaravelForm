<div class="form-group{{count($errors) > 0 ? " has-error" : ""}}">
    @include('form::helpers.label')
    <textarea class="form_control" @include('form::helpers.attributes')>{{$value}}</textarea>
    @include('form::helpers.errors')
</div>
