<div class="col-md-4">
    <div class="linksBlock">
        <span class="linksTitle">Kviečia dalyvauti</span>

        <?php
        $windowsCount = floor(sizeof($links) / 4);
        if (sizeof($links) % 4 != 0)
            $windowsCount += 1;
        ?>
        <div id="linksSlides" class="carousel slide" data-ride="carousel">
            @if($windowsCount > 0)
                <ol class="carousel-indicators">
                    @for($z = 0; $z < $windowsCount; $z++)
                        @if ($z == 0)
                            <li data-target="#linksSlides" data-slide-to="0" class="active"></li>
                        @else
                            <li data-target="#linksSlides" data-slide-to="{{$z}}"></li>
                        @endif
                    @endfor
                </ol>
            @endif
            <div class="carousel-inner" role="listbox">
                <?php $index = -1; $y = 0;?>
                @for($i = 0;$i < $windowsCount; $i++)
                    <?php
                    if ($index == -1) {
                        $class = 'item active';
                        $index = 0;
                    } else {
                        $class = 'item';
                    }
                    ?>
                    <div class="{{$class}}">
                        <?php ?>
                        @for($index2 = 0; $index2 < 4 && $i * 4 +$index2< sizeof($links); $index2++)
                            <?php $y = $i * 4 + $index2; ?>
                            <div class="linkslink">
                                {!! $links[$y] !!}<br/>
                            </div>
                        @endfor
                    </div>
                @endfor
            </div>
        </div>
    </div>
</div>