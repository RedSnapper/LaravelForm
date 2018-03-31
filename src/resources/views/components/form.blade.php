<form {!! @$attributes !!} @foreach ($form['attributes'] as $key=>$value){{$key}}="{{$value}}" @endforeach>
    @foreach($form['hidden'] as $field)
        {!! $field->render() !!}
    @endforeach
    {{ $slot }}
</form>