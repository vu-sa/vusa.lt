@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Atnaujinti naują naujieną
                <br/>
                <small style="color: red">* - reikalingi laukai</small>
            </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/naujienos/{permalink}/redaguoti' ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li> <li>Naujienos</li> <li class="active">Redaguoti naujieną</li>': '' !!}
            </ol>
        </section>

        <section class="content">
            @if (count($errors) > 0)
                <div class="alert alert-danger" role="alert">
                    <ul>
                        <?php $errors = array_unique($errors->all());?>
                        @foreach ($errors as $error)
                            @if($error == 'validation.required')
                                <li>Naujienos pavadinimo, datos, kategorijos, iliustracijos, įvadinio ir pilno teksto
                                    bei svarbiausių punktų laukai turi būti
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
                    {!! Form::model($newInfo, ['method' => 'PATCH' ]) !!}
                    <div class="form-group">
                        {{ Form::label('title', 'Naujienos pavadinimas *') }}
                        {{ Form::text('title', null, array('class'=>'form-control')) }}
                    </div>

                    <?php
                    $date = explode("-", explode(" ", $newInfo['publish_time'])[0]);
                    $time = explode(":", explode(" ", $newInfo['publish_time'])[1]);
                    ?>
                    <div class="form-group">
                        {{ Form::label('publish_time', 'Publikavimo data *') }}
                        <br/>
                        {{ Form::selectYear('year', $date[0], date('Y')+1) }}
                        {{ Form::selectMonth('month', substr($date[1], 0, 1) == "0" ? substr($date[1], 1, 1) : $date[1]) }}
                        {{ Form::selectRange('day', 1, 31, substr($date[2], 0, 1) == "0" ? substr($date[2], 1, 1) : $date[2]) }}
                        {{ Form::selectRange('hour', 0, 24, substr($time[0], 0, 1) == "0" ? substr($time[0], 1, 1) : $time[0]) }} :
                        {{ Form::selectRange('minute', 0, 59, substr($time[1], 0, 1) == "0" ? substr($time[1], 1, 1) : $time[1]) }}
                        {{ Form::hidden('publish_time', null, array('class'=>'form-control', 'disabled'=>'disabled')) }}
                    </div>

                    <script>
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
                            InputTitle = InputTitle.replace(/[,:"„”.?]/g, '');
                            $("#permalink").val(InputTitle);
                        });
                    </script>

                    <div class="form-group">
                        {{ Form::label('permalink', 'Nuoroda į naujieną') }}
                        {{ Form::text('permalink', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('lang', 'Kalba *') }}
                        {{ Form::select('lang', array('lt' => 'LT', 'en' => 'EN'), null, array('class'=>'form-control') )}}
                    </div>

                    <div class="form-group" style="display: none" id="title_lt_input">
                        {{ Form::label('title_lt', 'Naujiena LT kalba *') }}
                        {{ Form::text('title_lt', null, array('class'=>'form-control') )}}
                    </div>

                    <div class="form-group" style="display: none" id="permalink_lt_input">
                        {{ Form::label('permalink_lt', 'Nuoroda į LT naujieną') }}
                        {{ Form::text('permalink_lt', null, array('class'=>'form-control', 'readonly'=>'readonly')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('cat', 'Kategorija *') }}
                        {{ Form::select('cat', $newsCatsShort, null, array('class'=>'form-control') )}}
                    </div>

                    <div class="form-group">
                        {{ Form::label('source', 'Šaltinis') }}
                        {{ Form::text('source', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group photo">
                        {{ Form::label('image', 'Iliustracija naujienos antraštei *') }}
                        <span class="input-group-btn">
                            <a id="lfm" data-input="image" class="btn btn-primary">
                              <i class="far fa-image"></i> Pasirinkti nuotrauką
                            </a>
                          </span>
                        {{ Form::text('image', null, array('class'=>'form-control', 'readonly'=>'readonly')) }}
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

                    @can('handleLikeCB', App\Models\Page::class)
                    <div class="form-group">
                        {{ Form::label('important', 'Svarbi naujiena') }}
                        {{ Form::checkbox('important', null) }}
                    </div>
                    @endcan

                    <div class="form-group">
                        {{ Form::label('draft', 'Juodraštis') }}
                        {{ Form::checkbox('draft', null) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('short', 'Įvadinis tekstas *') }}
                        {{ Form::textarea('short', null, array('class'=>'form-control edit')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('text', 'Pilnas tekstas *') }}
                        {{ Form::textarea('text', null, array('class'=>'form-control edit')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('mainPoints', 'Svarbiausi punktai (kurti sąrašu) *') }}
                        {{ Form::textarea('mainPoints', null, array('class'=>'form-control edit')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('tags', 'Tagai (atskirti ; ir # nedėti)') }}
                        {{ Form::text('tags', null, array('class'=>'form-control')) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('readMore', 'Skaityti daugiau linkas') }}
                        {{ Form::text('readMore', null, array('class'=>'form-control')) }}
                    </div>

                    {{Form::submit('Atnaujinti',['class'=>'btn btn-primary'])}}

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

        $(document).ready(function () {
            $('select[name=lang]').change(function (e) {
                if ($('select[name=lang]').val() == 'lt') {
                    $('#title_lt_input').hide();
                    $('#permalink_lt_input').hide();
                }
                if ($('select[name=lang]').val() == 'en') {
                    $('#title_lt_input').show();
                    $('#permalink_lt_input').show();
                }
            });

            if ($('select[name=lang]').val() == 'en') {
                $('#title_lt_input').show();
                $('#permalink_lt_input').show();
            }
            if ($('select[name=lang]').val() == 'lt') {
                $('#title_lt_input').hide();
                $('#permalink_lt_input').hide();
            }
        });

        $(document).ready(function () {
            $("#title_lt").autocomplete({
                source: "/admin/naujienos/newsName",
                minLength: 3,
                select: function (event, ui) {
                    $('#title').val(ui.item.value);
                }
            });
        });

        $("#title_lt").focusout(function () {
            var InputTitle = $('#title_lt').val().toLowerCase();
            InputTitle = InputTitle.replace(/[ą]/g, 'a');
            InputTitle = InputTitle.replace(/[č]/g, 'c');
            InputTitle = InputTitle.replace(/[ęė]/g, 'e');
            InputTitle = InputTitle.replace(/[į]/g, 'i');
            InputTitle = InputTitle.replace(/[š]/g, 's');
            InputTitle = InputTitle.replace(/[ūų]/g, 'u');
            InputTitle = InputTitle.replace(/[ž]/g, 'z');
            InputTitle = InputTitle.replace(/ /g, '-');
            InputTitle = InputTitle.replace(/[,:"„”.?!]/g, '');
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