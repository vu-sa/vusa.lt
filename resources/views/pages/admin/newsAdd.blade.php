@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Sukurti naują naujieną
                <br/>
                <small style="color: red">* - reikalingi laukai</small>
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/naujienos/prideti' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li>Naujienos</li><li class="active">Pridėti naujieną</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            @if (count($errors) > 0)
                <div class="alert alert-danger" role="alert">
                    <ul>
                        <?php $errors = array_unique($errors->all());?>
                        @foreach ($errors as $error)
                            @if($error == 'validation.required')
                                <li>Naujienos pavadinimo, datos, kategorijos, iliustracijos, įvadinio ir pilno teksto bei svarbiausių punktų laukai turi būti
                                    užpildyti.
                                </li>
                            @elseif($error == 'validation.unique')
                                <li>Naujienos pavadinimas su tokiu pavadinimu jau egzistuoja.</li>
                            @else
                                <li>{{$error}}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="row">
                <div class="col-md-12">
                    {!! Form::open() !!}
                    <div class="form-group">
                        {{ Form::label('title', 'Naujienos pavadinimas *') }}
                        {{ Form::text('title', '', array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('publish_time', 'Publikavimo data *') }}
                        <br/>
                        {{ Form::selectYear('year', '2015', date('Y')+1, date('Y')) }}:
                        {{ Form::selectMonth('month', substr(date('m'), 0, 1) == "0" ? substr(date('m'), 1, 1) : date('m')) }}:
                        {{ Form::selectRange('day', 1, 31, substr(date('d'), 0, 1) == "0" ? substr(date('d'), 1, 1) : date('d')) }} -
                        {{ Form::selectRange('hour', 0, 24, substr(date('H'), 0, 1) == "0" ? substr(date('H'), 1, 1) : date('H')) }}:
                        {{ Form::selectRange('minute', 0, 59, substr(date('m'), 0, 1) == "0" ? substr(date('m'), 1, 1) + 2 : date('m') + 2) }}
                        {{ Form::hidden('publish_time', '', array('class'=>'form-control', 'disabled'=>'disabled')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('permalink', 'Nuoroda į naujieną') }}
                        {{ Form::text('permalink', '', array('class'=>'form-control', 'readonly'=>'readonly')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('lang', 'Kalba *') }}
                        {{ Form::select('lang', array('lt' => 'LT', 'en' => 'EN'), '', array('class'=>'form-control') )}}
                    </div>

                    <div class="form-group" style="display: none" id="title_lt_input">
                        {{ Form::label('title_lt', 'Naujiena LT kalba *') }}
                        {{ Form::text('title_lt', '', array('class'=>'form-control') )}}
                    </div>

                    <div class="form-group" style="display: none" id="permalink_lt_input">
                        {{ Form::label('permalink_lt', 'Nuoroda į LT naujieną') }}
                        {{ Form::text('permalink_lt', '', array('class'=>'form-control', 'readonly'=>'readonly')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('cat', 'Kategorija *') }}
                        {{ Form::select('cat', $newsCatsShort, '', array('class'=>'form-control') )}}
                    </div>

                    <div class="form-group">
                        {{ Form::label('source', 'Šaltinis (numatytasis yra VU SA)') }}
                        {{ Form::text('source', '', array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group photo">
                        {{ Form::label('image', 'Iliustracija naujienos antraštei *') }}
                        <span class="input-group-btn">
                            <a id="lfm" data-input="image" class="btn btn-primary">
                            <i class="far fa-image"></i> Pasirinkti nuotrauką
                            </a>
                          </span>
                        {{ Form::text('image', '', array('class'=>'form-control', 'readonly'=>'readonly')) }}
                    </div>

                    {{ Form::hidden('frame_width', '', array('class'=>'form-control')) }}
                    {{ Form::hidden('frame_height', '', array('class'=>'form-control')) }}

                    {{ Form::hidden('width', '', array('class'=>'form-control')) }}
                    {{ Form::hidden('height', '', array('class'=>'form-control')) }}

                    {{ Form::hidden('x', '', array('class'=>'form-control')) }}
                    {{ Form::hidden('y', '', array('class'=>'form-control')) }}

                    <div class="form-group">
                        {{ Form::label('imageAuthor', 'Nuotraukos autorius') }}
                        {{ Form::text('imageAuthor', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('important', 'Svarbi naujiena') }}
                        {{ Form::checkbox('important', '1', false) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('draft', 'Juodraštis') }}
                        {{ Form::checkbox('draft', '1', false) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('short', 'Įvadinis tekstas *') }}
                        {{ Form::textarea('short','', array('class'=>'form-control edit')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('text', 'Pilnas tekstas *') }}
                        {{ Form::textarea('text', '', array('class'=>'form-control edit')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('mainPoints', 'Svarbiausi punktai (kurti sąrašu) *') }}
                        {{ Form::textarea('mainPoints', '', array('class'=>'form-control edit')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('tags', 'Tagai (atskirti ; ir # nedėti)') }}
                        {{ Form::text('tags', '', array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('readMore', 'Skaityti daugiau nuoroda') }}
                        {{ Form::text('readMore', '', array('class'=>'form-control')) }}
                    </div>

                    {{Form::submit('Sukurti',['class'=>'btn btn-primary'])}}

                    {{ Form::close() }}
                </div>
            </div>
        </section>
    </div>
    <script>
        $("#cropBtn").on('click', function () {
            $("#imageSRC").attr("src", document.getElementById("image").value);
        });

        var eyeCandy = $('#cropContainerEyecandy');
        var croppedOptions = {
            uploadUrl: 'upload',
            cropUrl: 'crop',
            cropData: {
                'width': eyeCandy.width(),
                'height': eyeCandy.height()
            }
        };
        var cropperBox = new Croppic('cropContainerEyecandy', croppedOptions);

        $("#title").focusout(function () {
            var InputTitle = $('#title').val().toLowerCase();
            InputTitle = InputTitle.replace(/[ą]/g, 'a');
            InputTitle = InputTitle.replace(/[č]/g, 'c');
            InputTitle = InputTitle.replace(/[ęė]/g, 'e');
            InputTitle = InputTitle.replace(/[į]/g, 'i');
            InputTitle = InputTitle.replace(/[š]/g, 's');
            InputTitle = InputTitle.replace(/[ūų]/g, 'u');
            InputTitle = InputTitle.replace(/[ž]/g, 'z');
            InputTitle = InputTitle.replace(/ /g, '-');
            InputTitle = InputTitle.replace(/[,:"„”.?!]/g, '');
            $("#permalink").val(InputTitle);
        });

        $("#title_lt_name").focusout(function () {
            var InputTitle = $('#title_lt').val().toLowerCase();
            InputTitle = InputTitle.replace(/[ą]/g, 'a');
            InputTitle = InputTitle.replace(/[č]/g, 'c');
            InputTitle = InputTitle.replace(/[ęė]/g, 'e');
            InputTitle = InputTitle.replace(/[į]/g, 'i');
            InputTitle = InputTitle.replace(/[š]/g, 's');
            InputTitle = InputTitle.replace(/[ūų]/g, 'u');
            InputTitle = InputTitle.replace(/[ž]/g, 'z');
            InputTitle = InputTitle.replace(/ /g, '-');
            InputTitle = InputTitle.replace(/[,:"„”.?]/g, '');
            $("#permalink_lt").val(InputTitle);
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
