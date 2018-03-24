<div class="form-group{{count($errors) > 0 ? " has-error" : ""}}">
    <div class="checkbox">
        <label>
            <input class="form_control" @include('form::helpers.attributes') value="{{$value}}"/> {{$label}}
        </label>
    </div>
    @include('form::helpers.errors')
</div>


