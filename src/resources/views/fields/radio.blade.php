<div @if(count($errors))class="has-error"@endif>

    @if(isset($label))
        <strong class="control-label">{{$label}}</strong>
    @endif

    <div class="radio">
        @foreach($options as $option)
            <label>
                <input @include('form::helpers.attributes',['attributes'=>$option->attributes]) value="{{$option->value}}"/>
                {{$option->label}}
            </label>
        @endforeach
    </div>
    @include('form::helpers.errors')
</div>