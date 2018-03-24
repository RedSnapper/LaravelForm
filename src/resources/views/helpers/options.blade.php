@foreach($options as $option)
    @if(isset($option->options))
        <optgroup label="{{$option->label}}">
            @foreach($option->options as $option)
                @include('form::helpers.option')
            @endforeach
        </optgroup>
    @else
        @include('form::helpers.option')
    @endif
@endforeach