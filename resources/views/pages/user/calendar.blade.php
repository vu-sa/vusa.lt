<script type="application/javascript">
    $(document).ready(function () {
        $("#my-calendar").zabuto_calendar({
            ajax: {
                url: "/{{Lang::locale()}}/renginiai",
                modal: true
            },
            language: "{{Lang::locale()}}",
            today: true,
            badge: true,
            show_days: false,
//            show_previous: 2,
//            show_next: 1,
            url: "/{{Lang::locale()}}/renginiai"
        })
    });
</script>

<div class="col-lg-4 col-sm-12">
    <div id="my-calendar" class="calendarBackground" style="height: 320px"></div>
</div>