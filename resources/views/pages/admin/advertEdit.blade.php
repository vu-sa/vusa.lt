@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Atnaujinti reklaminį banerį
                <br/>
                <small style="color: red">* - reikalingi laukai</small>
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == '/admin/reklama/{permalink}/redaguoti' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li class="active">Redaguoti reklaminį banerį</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            @if (count($errors) > 0)
                <div class="alert alert-danger" role="alert">
                    <ul>
                        <?php $errors = array_unique($errors->all());?>
                        @foreach ($errors as $error)
                            <li>{{$error}}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="row">
                <div class="col-md-12">
                    {!! Form::model($banners, ['method' => 'PATCH' ]) !!}
                    <div class="form-group" id="name_input">
                        {{ Form::label('title', 'Pavadinimas *') }}
                        {{ Form::text('title', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group photo">
                        {{ Form::label('value', 'Paveikslėlis *') }}
                        <span class="input-group-btn">
                            <a id="lfm" data-input="value" class="btn btn-primary">
                            <i class="far fa-image"></i> Pasirinkti nuotrauką
                            </a>
                          </span>
                        {{ Form::text('value', null, array('class'=>'form-control', 'readonly'=>'readonly')) }}
                    </div>

                    <div class="form-group" id="name_input">
                        {{ Form::label('url', 'Nuoroda *') }}
                        {{ Form::text('url', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('hide', 'Ar nematomas?') }}
                        {{ Form::checkbox('hide', 1) }}
                    </div>

                    {{Form::submit('Atnaujinti',['class'=>'btn btn-primary', 'id'=>'submit'])}}

                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>

    <script>
        $("#title").focusout(function () {
            var InputTitle = $('#title').val().toLowerCase();
            InputTitle = InputTitle.replace(/[ą]/g, 'a');
            InputTitle = InputTitle.replace(/[č]/g, 'c');
            InputTitle = InputTitle.replace(/[ę]/g, 'e');
            InputTitle = InputTitle.replace(/[ė]/g, 'e');
            InputTitle = InputTitle.replace(/[į]/g, 'i');
            InputTitle = InputTitle.replace(/[š]/g, 's');
            InputTitle = InputTitle.replace(/[ų]/g, 'u');
            InputTitle = InputTitle.replace(/[ū]/g, 'u');
            InputTitle = InputTitle.replace(/[ž]/g, 'z');
            InputTitle = InputTitle.replace(/ /g, '-');
            InputTitle = InputTitle.replace(/[,:"„”]/g, '');
            $("#permalink").val(InputTitle);
        });
    </script>

<script>
        
    (function( $ ){

$.fn.filemanager = function(type, options) {
  type = type || 'file';

  this.on('click', function(e) {
    var route_prefix = (options && options.prefix) ? options.prefix : '/filemanager';
    var target_input = $('#' + $(this).data('input'));
    var target_preview = $('#' + $(this).data('preview'));
    window.open(route_prefix + '?type=' + type, 'FileManager', 'width=900,height=600');
    window.SetUrl = function (items) {
      var file_path = items.map(function (item) {
        return item.url;
      }).join(',');

      // set the value of the desired input to image url
      target_input.val('').val(file_path).trigger('change');

      // clear previous preview
      target_preview.html('');

      // set or change the preview image src
      items.forEach(function (item) {
        target_preview.append(
          $('<img>').css('height', '5rem').attr('src', item.thumb_url)
        );
      });

      // trigger change event
      target_preview.trigger('change');
    };
    return false;
  });
}

})(jQuery);


$('#lfm').filemanager('image');

    </script>
@endsection