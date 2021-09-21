@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Pridėti reklaminį banerį
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/reklama/prideti' ? '<li><a><i class="fas fa-tachometer-alt"></i>Home</a></li> <li>Baneriai</li> <li class="active">Pridėti banerį</li>': '' !!}
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
                    {!! Form::open(['files'=>true ]) !!}

                    <div class="form-group" id="name_input">
                        {{ Form::label('title', 'Pavadinimas *') }}
                        {{ Form::text('title', '', array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group photo">
                        {{ Form::label('value', 'Paveikslėlis *') }}
                        <span class="input-group-btn">
                            <a id="lfm" data-input="value" class="btn btn-primary">
                            <i class="far fa-image"></i> Pasirinkti nuotrauką
                            </a>
                          </span>
                        {{ Form::text('value', '', array('class'=>'form-control', 'readonly'=>'readonly')) }}
                    </div>

                    <div class="form-group" id="name_input">
                        {{ Form::label('url', 'Nuoroda *') }}
                        {{ Form::text('url', '', array('class'=>'form-control',)) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('hide', 'Ar nematomas?') }}
                        {{ Form::checkbox('hide', '1', false) }}
                    </div>

                    {{Form::submit('Sukurti',['class'=>'btn btn-primary', 'id'=>'submit'])}}

                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>

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