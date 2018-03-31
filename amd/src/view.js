
define(['jquery'], function ($) {
    return {
        init: function () {
            $('.content-nav').find('a').on('click', function () {
                var btn_prev = $('a#prev');
                var btn_next = $('a#next');
                var current = $('#content-items').find('.active');
                if (current.length === 0) {
                    $('.content-item').first().addClass('active');
                }
                if ($(this).attr('id') === 'prev') {
                    var prev = $(current).prev();
                    prev.addClass('active');
                    $(current).removeClass('active');
                    if ($(prev).is(':first-child')) {
                        $(btn_prev).hide();
                        $(btn_next).show();
                    } else {
                        $(btn_prev).show();
                        $(btn_next).show();
                    }
                } else if ($(this).attr('id') === 'next') {
                    var next = $(current).next();
                    next.addClass('active');
                    $(current).removeClass('active');
                    if ($(next).is(':last-child')) {
                        $(btn_prev).show();
                        $(btn_next).hide();
                    } else {
                        $(btn_prev).show();
                        $(btn_next).show();
                    }
                }
                var num_current = $('.content-item.active').index() + 1;
                var num_total = $('.content-item').length;
                if (num_current === 1)
                    num_current = 0;
                var value = (100 * num_current) / num_total;
                if (value == 0) {
                    value = 1;
                }
                $('#stepbystep-progress div').animate({width: value + '%'}, 900);
                $('#stepbystep-progress em').text(parseInt(value));
            });
        }
    };
});
