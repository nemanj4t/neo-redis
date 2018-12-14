@extends('layouts.app')

@section('content')

    {{--Unos liste timova za koje igrac igrac igra ili je igrao--}}
    <div id="input-container" class="list-group">
        <input type="text" name="team" id="team_0" onkeyup="addNewInput(this)" class="list-group-item"/>
    </div>

@endsection

@section('scripts')
    <script>
        function addNewInput(element) {
            if (!element.value) {
                element.parentNode.removeChild(element.nextElementSibling);
                return;
            } else if (element.nextElementSibling)
                return;
            let newInput = element.cloneNode();
            newInput.id = newInput.name + '_' + (parseInt(element.id.substring(element.id.indexOf('_') + 1)) + 1);
            newInput.value = '';
            element.parentNode.appendChild(newInput);
        }
    </script>
@endsection