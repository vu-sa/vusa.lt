$(document).ready(function () {
    $('#slider').bxSlider({
        ticker: true,
        tickerSpeed: 5000,
        tickerHover: true,
        slideWidth: 30,
        randomStart: true,
        responsive: true,
        pagerType: 'full',
        slideSelector: 'div.slider'
    });

    $("#clients-slider").carousel({
        interval: 4000 //TIME IN MILLI SECONDS
    });

    $('#Tekstas').append(window.location.href).text();

    if ($('#infoPageText').clientHeight) {
        var infoPageText = document.getElementById('infoPageText').clientHeight;
        document.getElementById('infoPageSquare').style.height = infoPageText + 'px';
    }

    $(".hoverPalinys").hover(function () {
        $(this).find('.padalinysNameShort').css("color", "#bd2835")
    }, function () {
        $(this).find('.padalinysNameShort').css("color", "");
    });

    var itemPerPage;
    if ($(window).width() <= 480) {
        itemPerPage = 1;
    }
    if ($(window).width() <= 768 && $(window).width() > 480) {
        itemPerPage = 3;
    }

    if ($(window).width() > 768) {
        itemPerPage = 5;
    }

    $('#content-slider').lightSlider({
        item: itemPerPage,
        auto: true,
        loop: true,
        pauseOnHover: true,
        controls: false
    });
});

$(function () {
    // Remove Search if user Resets Form or hits Escape!
    $('body, .navbar-collapse form[role="search"] button[type="reset"]').on('click keyup', function (event) {
        if (event.which == 27 && $('.navbar-collapse form[role="search"]').hasClass('active') ||
            $(event.currentTarget).attr('type') == 'reset') {
            closeSearch();
        }
    });

    function closeSearch() {
        var $form = $('.navbar-collapse form[role="search"].active')
        $form.find('input').val('');
        $form.removeClass('active');
    }

    // Show Search if form is not active // event.preventDefault() is important, this prevents the form from submitting
    $(document).on('click', '.navbar-collapse form[role="search"]:not(.active) button[type="submit"]', function (event) {
        event.preventDefault();
        var $form = $(this).closest('form'),
            $input = $form.find('input');
        $form.addClass('active');
        $input.focus();
    });

    // ONLY FOR DEMO // Please use $('form').submit(function(event)) to track from submission
    // if your form is ajax remember to call `closeSearch()` to close the search container
    //$(document).on('click', '.navbar-collapse form[role="search"].active button[type="submit"]', function (event) {
    //if ($input.value())
    $('.navbar-collapse form[role="search"].active button[type="submit"]').submit(function (event) {
        event.preventDefault();
        var $form = $(this).closest('form'),
            $input = $form.find('input');
        closeSearch();
    });
});